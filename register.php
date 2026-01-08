<?php
session_start();
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // If user requested register.php directly, redirect to the HTML form
    header('Location: signup.html');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (!$username || !$email || !$password || $password !== $confirm) {
    $_SESSION['flash'] = 'Please fill all fields and ensure passwords match.';
    header('Location: signup.html');
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE username = ? OR email = ? LIMIT 1');
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        $_SESSION['flash'] = 'Username or email already taken.';
        header('Location: signup.html');
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
    $stmt->execute([$username, $email, $hash]);

    $userId = $pdo->lastInsertId();
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;

    // ensure lists table exists (safe no-op if migration already ran)
    $pdo->exec("CREATE TABLE IF NOT EXISTS lists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT NULL,
        is_default BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // create default Favorites list for the new user
    $stmtList = $pdo->prepare('INSERT INTO lists (user_id, name, is_default) VALUES (?, ?, 1)');
    $stmtList->execute([$userId, 'Favorites']);

    header('Location: index.php');
    exit;
} catch (Exception $e) {
    error_log('Register error: ' . $e->getMessage());
    $_SESSION['flash'] = 'Server error, try again later.';
    header('Location: signup.html');
    exit;
}
