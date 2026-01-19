<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

session_start();
require __DIR__ . '/db.php';
require __DIR__ . '/film_helpers.php';

if (empty($_SESSION['user_id'])) {
    json_response(['error' => 'not_authenticated']);
}

$userId = (int)$_SESSION['user_id'];
$tmdbId = isset($_POST['tmdb_id']) ? (int)$_POST['tmdb_id'] : 0;
$title = trim($_POST['title'] ?? '');
$poster = trim($_POST['poster'] ?? '');
$grade = isset($_POST['grade']) ? (int)$_POST['grade'] : 0;
$reviewText = trim($_POST['review_text'] ?? '');
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : null;
$liked = isset($_POST['liked']) ? (int)$_POST['liked'] : 0;
$notes = trim($_POST['notes'] ?? '');
$watchedDate = trim($_POST['watched_date'] ?? '');
$isRewatch = isset($_POST['is_rewatch']) && $_POST['is_rewatch'] === '1';

if ($tmdbId <= 0 || $grade < 1 || $grade > 10) {
    json_response(['error' => 'invalid_input']);
}

$watchedAtValue = null;
if ($watchedDate) {
    $dateObj = DateTime::createFromFormat('Y-m-d', $watchedDate);
    if ($dateObj && $dateObj->format('Y-m-d') === $watchedDate) {
        $watchedAtValue = $watchedDate . ' 12:00:00';
    }
}

try {
    $filmId = getOrCreateFilm($pdo, $tmdbId, $title, $poster);
    
    if ($watchedAtValue) {
        $stmt = $pdo->prepare('
            INSERT INTO watched (user_id, film_id, rating, liked, notes, watched_at) 
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                rating = VALUES(rating),
                liked = VALUES(liked),
                notes = VALUES(notes),
                watched_at = VALUES(watched_at)
        ');
        $stmt->execute([$userId, $filmId, $rating ?: null, $liked ? 1 : 0, $notes ?: null, $watchedAtValue]);
    } else {
        $stmt = $pdo->prepare('
            INSERT INTO watched (user_id, film_id, rating, liked, notes) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                rating = VALUES(rating),
                liked = VALUES(liked),
                notes = VALUES(notes),
                watched_at = CURRENT_TIMESTAMP
        ');
        $stmt->execute([$userId, $filmId, $rating ?: null, $liked ? 1 : 0, $notes ?: null]);
    }
    
    if ($isRewatch) {
        $stmt = $pdo->prepare('
            INSERT INTO reviews (user_id, film_id, grade, review_text)
            VALUES (?, ?, ?, ?)
        ');
        $stmt->execute([$userId, $filmId, $grade, $reviewText ?: null]);
        
        $stmt = $pdo->prepare('
            UPDATE watched SET rewatch_count = rewatch_count + 1 WHERE user_id = ? AND film_id = ?
        ');
        $stmt->execute([$userId, $filmId]);
    } else {
        $stmt = $pdo->prepare('
            UPDATE reviews 
            SET grade = ?, review_text = ?, updated_at = CURRENT_TIMESTAMP
            WHERE user_id = ? AND film_id = ? AND id = (
                SELECT max_id FROM (
                    SELECT MAX(id) as max_id FROM reviews WHERE user_id = ? AND film_id = ?
                ) as temp
            )
        ');
        $stmt->execute([$grade, $reviewText ?: null, $userId, $filmId, $userId, $filmId]);
        
        if ($stmt->rowCount() === 0) {
            $stmt = $pdo->prepare('
                INSERT INTO reviews (user_id, film_id, grade, review_text)
                VALUES (?, ?, ?, ?)
            ');
            $stmt->execute([$userId, $filmId, $grade, $reviewText ?: null]);
        }
    }
    
    json_response(['ok' => true, 'film_id' => $filmId, 'userId' => $userId, 'tmdbId' => $tmdbId, 'grade' => $grade]);
} catch (Exception $e) {
    error_log('save_review error: ' . $e->getMessage() . ' | Stack: ' . $e->getTraceAsString());
    json_response(['error' => 'server_error', 'message' => $e->getMessage()]);
}
