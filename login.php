<?php
session_start();
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit;
}

$identifier = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (!$identifier || !$password) {
  $_SESSION['flash'] = 'Missing credentials.';
  header('Location: login.html');
  exit;
}

try {
  $stmt = $pdo->prepare('SELECT user_id, username, password_hash FROM users WHERE username = ? OR email = ? LIMIT 1');
  $stmt->execute([$identifier, $identifier]);
  $user = $stmt->fetch();

  if (!$user || !password_verify($password, $user['password_hash'])) {
    $_SESSION['flash'] = 'Invalid username/email or password.';
    header('Location: login.html');
    exit;
  }

  $_SESSION['user_id'] = $user['user_id'];
  $_SESSION['username'] = $user['username'];

  header('Location: index.php');
  exit;
} catch (Exception $e) {
  error_log('Login error: ' . $e->getMessage());
  $_SESSION['flash'] = 'Server error, try again later.';
  header('Location: login.html');
  exit;
}