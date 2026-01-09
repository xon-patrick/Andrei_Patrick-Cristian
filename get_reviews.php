<?php
// Force JSON output and prevent caching
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

session_start();
require __DIR__ . '/db.php';
require __DIR__ . '/film_helpers.php';

// Accept either film_id or tmdb_id
$filmId = isset($_GET['film_id']) ? (int)$_GET['film_id'] : 0;
$tmdbId = isset($_GET['tmdb_id']) ? (int)$_GET['tmdb_id'] : 0;

// If tmdb_id is provided, look up film_id
if ($tmdbId > 0 && $filmId === 0) {
    try {
        $stmt = $pdo->prepare('SELECT film_id FROM films WHERE tmdb_id = ? LIMIT 1');
        $stmt->execute([$tmdbId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $filmId = $row ? (int)$row['film_id'] : 0;
    } catch (Exception $e) {
        json_response(['error' => 'db_error', 'reviews' => []]);
    }
}

if ($filmId <= 0) {
    json_response(['ok' => true, 'reviews' => []]);
}

try {
    // Fetch only the latest review per user for the film
    $stmt = $pdo->prepare('
        SELECT 
            r.id,
            r.grade,
            r.review_text,
            r.created_at,
            r.user_id
        FROM reviews r
        INNER JOIN (
            SELECT user_id, MAX(created_at) as max_created
            FROM reviews
            WHERE film_id = ?
            GROUP BY user_id
        ) latest ON r.user_id = latest.user_id AND r.created_at = latest.max_created
        WHERE r.film_id = ?
        ORDER BY r.created_at DESC
    ');
    $stmt->execute([$filmId, $filmId]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For each review, try to get username from users table
    foreach ($reviews as &$review) {
        try {
            $userStmt = $pdo->prepare('SELECT username FROM users WHERE user_id = ? LIMIT 1');
            $userStmt->execute([$review['user_id']]);
            $userRow = $userStmt->fetch(PDO::FETCH_ASSOC);
            $review['username'] = $userRow ? $userRow['username'] : 'User #' . $review['user_id'];
        } catch (Exception $ue) {
            $review['username'] = 'User #' . $review['user_id'];
        }
    }
    unset($review);
    
    json_response(['ok' => true, 'reviews' => $reviews]);
} catch (Exception $e) {
    error_log('get_reviews error: ' . $e->getMessage());
    json_response(['ok' => true, 'reviews' => []]);
}

