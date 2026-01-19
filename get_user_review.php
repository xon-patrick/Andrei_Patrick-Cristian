<?php
session_start();
require __DIR__ . '/db.php';
require __DIR__ . '/film_helpers.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    json_response(['error' => 'Not authenticated']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$tmdbId = isset($_GET['tmdb_id']) ? (int)$_GET['tmdb_id'] : 0;

if ($tmdbId <= 0) {
    json_response(['error' => 'Invalid tmdb_id']);
    exit;
}

$stmt = $pdo->prepare('SELECT film_id FROM films WHERE tmdb_id = ? LIMIT 1');
$stmt->execute([$tmdbId]);
$film = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$film) {
    json_response(['watched' => false]);
    exit;
}

$filmId = (int)$film['film_id'];

$stmt = $pdo->prepare('
    SELECT r.grade, r.review_text, r.created_at, w.watched_at, w.rating, w.liked
    FROM reviews r
    LEFT JOIN watched w ON r.user_id = w.user_id AND r.film_id = w.film_id
    WHERE r.user_id = ? AND r.film_id = ?
    ORDER BY r.created_at DESC
    LIMIT 1
');
$stmt->execute([$userId, $filmId]);
$review = $stmt->fetch(PDO::FETCH_ASSOC);

if ($review) {
    json_response([
        'watched' => true,
        'grade' => (int)$review['grade'],
        'review_text' => $review['review_text'],
        'watched_at' => $review['watched_at'],
        'rating' => $review['rating'] ? (int)$review['rating'] : null,
        'liked' => (bool)$review['liked']
    ]);
} else {
    json_response(['watched' => false]);
}
