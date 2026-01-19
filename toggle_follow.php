<?php
session_start();
require __DIR__ . '/db.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];
$targetUserId = (int)($_POST['user_id'] ?? 0);

if (!$targetUserId || $targetUserId === $currentUserId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS follows (
        id INT AUTO_INCREMENT PRIMARY KEY,
        follower_id INT NOT NULL,
        following_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY ux_follower_following (follower_id, following_id),
        FOREIGN KEY (follower_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (following_id) REFERENCES users(user_id) ON DELETE CASCADE,
        CHECK (follower_id != following_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE user_id = ? LIMIT 1');
    $stmt->execute([$targetUserId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    // verificare daca e urmarit deja
    $stmt = $pdo->prepare('SELECT id FROM follows WHERE follower_id = ? AND following_id = ? LIMIT 1');
    $stmt->execute([$currentUserId, $targetUserId]);
    $existing = $stmt->fetch();

    if ($existing) {
        // unfollow
        $stmt = $pdo->prepare('DELETE FROM follows WHERE follower_id = ? AND following_id = ?');
        $stmt->execute([$currentUserId, $targetUserId]);
        
        echo json_encode([
            'success' => true,
            'action' => 'unfollowed',
            'is_following' => false
        ]);
    } else {
        // follow
        $stmt = $pdo->prepare('INSERT INTO follows (follower_id, following_id) VALUES (?, ?)');
        $stmt->execute([$currentUserId, $targetUserId]);
        
        echo json_encode([
            'success' => true,
            'action' => 'followed',
            'is_following' => true
        ]);
    }
    
} catch (Exception $e) {
    error_log('Toggle follow error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
