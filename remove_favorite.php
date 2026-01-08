<?php
session_start();
require __DIR__ . '/db.php';
require __DIR__ . '/film_helpers.php';

if (empty($_SESSION['user_id'])) json_response(['error'=>'not_authenticated']);
$userId = (int)$_SESSION['user_id'];
$tmdb_id = isset($_POST['tmdb_id']) ? (int)$_POST['tmdb_id'] : 0;
if ($tmdb_id <= 0) json_response(['error'=>'invalid_tmdb_id']);

try {
    $stmt = $pdo->prepare('SELECT film_id FROM films WHERE tmdb_id = ? LIMIT 1');
    $stmt->execute([$tmdb_id]);
    $row = $stmt->fetch();
    if (!$row) json_response(['ok'=>true]);
    $filmId = (int)$row['film_id'];
    $stmt = $pdo->prepare('DELETE FROM favorites WHERE user_id = ? AND film_id = ?');
    $stmt->execute([$userId, $filmId]);
    json_response(['ok'=>true]);
} catch (Exception $e) {
    error_log('remove_favorite error: ' . $e->getMessage());
    json_response(['error'=>'server_error']);
}
