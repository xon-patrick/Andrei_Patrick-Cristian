<?php
session_start();
require __DIR__ . '/db.php';
require __DIR__ . '/film_helpers.php';

if (empty($_SESSION['user_id'])) {
    json_response(['error' => 'not_authenticated']);
}

$userId = (int)$_SESSION['user_id'];
$tmdb_id = isset($_POST['tmdb_id']) ? (int)$_POST['tmdb_id'] : 0;
$title = trim($_POST['title'] ?? '');
$poster = trim($_POST['poster'] ?? '');

if ($tmdb_id <= 0) json_response(['error' => 'invalid_tmdb_id']);

try {
    $filmId = getOrCreateFilm($pdo, $tmdb_id, $title, $poster);
    $stmt = $pdo->prepare('INSERT IGNORE INTO favorites (user_id, film_id) VALUES (?, ?)');
    $stmt->execute([$userId, $filmId]);
    json_response(['ok' => true, 'film_id' => $filmId]);
} catch (Exception $e) {
    error_log('add_favorite error: ' . $e->getMessage());
    json_response(['error' => 'server_error']);
}
