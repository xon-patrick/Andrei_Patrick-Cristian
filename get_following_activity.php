<?php
session_start();
require __DIR__ . '/db.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'activity' => []]);
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];

try {
    $debugStmt = $pdo->prepare('SELECT * FROM follows WHERE follower_id = ?');
    $debugStmt->execute([$currentUserId]);
    $follows = $debugStmt->fetchAll();
    error_log('Follows for user ' . $currentUserId . ': ' . json_encode($follows));
    
    $sql = "
        SELECT 
            w.watched_at,
            w.rating,
            w.liked,
            w.notes,
            f.tmdb_id,
            f.title,
            f.poster_url,
            u.user_id,
            u.username,
            u.profile_picture
        FROM watched w
        INNER JOIN follows fo ON w.user_id = fo.following_id
        INNER JOIN films f ON w.film_id = f.film_id
        INNER JOIN users u ON w.user_id = u.user_id
        WHERE fo.follower_id = ?
        AND (
            SELECT COUNT(*)
            FROM watched w2
            WHERE w2.user_id = w.user_id
            AND w2.watched_at > w.watched_at
        ) < 2
        ORDER BY w.watched_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$currentUserId]);
    $activity = $stmt->fetchAll();
    
    error_log('Activity count: ' . count($activity)); // Debug log
    
    echo json_encode([
        'success' => true,
        'activity' => $activity,
        'debug_follows_count' => count($follows),
        'debug_user_id' => $currentUserId
    ]);
    
} catch (Exception $e) {
    error_log('Get following activity error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'activity' => []
    ]);
}
