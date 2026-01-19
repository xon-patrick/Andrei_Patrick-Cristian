<?php
session_start();
require __DIR__ . '/db.php';

header('Content-Type: application/json');

$currentUserId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$search = trim($_GET['search'] ?? '');

try {
    // creare tabel follows dacă nu există
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

    // construire query
    if ($currentUserId) {
        $sql = "
            SELECT 
                u.user_id,
                u.username,
                u.email,
                u.profile_picture,
                u.bio,
                u.created_at,
                CASE WHEN f.id IS NOT NULL THEN 1 ELSE 0 END as is_following,
                (SELECT COUNT(*) FROM follows WHERE following_id = u.user_id) as followers_count,
                (SELECT COUNT(*) FROM follows WHERE follower_id = u.user_id) as following_count
            FROM users u
            LEFT JOIN follows f ON f.follower_id = ? AND f.following_id = u.user_id
            WHERE u.user_id != ?
        ";
        $params = [$currentUserId, $currentUserId];
    } else {
        // For unlogged users, don't check follow status
        $sql = "
            SELECT 
                u.user_id,
                u.username,
                u.email,
                u.profile_picture,
                u.bio,
                u.created_at,
                0 as is_following,
                (SELECT COUNT(*) FROM follows WHERE following_id = u.user_id) as followers_count,
                (SELECT COUNT(*) FROM follows WHERE follower_id = u.user_id) as following_count
            FROM users u
        ";
        $params = [];
    }
    
    if ($search !== '') {
        $whereClause = $currentUserId ? " AND" : " WHERE";
        $sql .= $whereClause . " (u.username LIKE ? OR u.email LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    $sql .= " ORDER BY u.username ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
    
} catch (Exception $e) {
    error_log('Get users error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
