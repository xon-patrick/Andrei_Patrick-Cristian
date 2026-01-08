<?php
session_start();
require __DIR__ . '/db.php';
require __DIR__ . '/film_helpers.php';

if (empty($_SESSION['user_id'])) json_response(['error'=>'not_authenticated']);
$userId = (int)$_SESSION['user_id'];
$tmdb_id = isset($_POST['tmdb_id']) ? (int)$_POST['tmdb_id'] : 0;
$title = trim($_POST['title'] ?? '');
$poster = trim($_POST['poster'] ?? '');
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : null;
$liked = isset($_POST['liked']) ? (int)$_POST['liked'] : 0;
$notes = trim($_POST['notes'] ?? '');

if ($tmdb_id <= 0) json_response(['error'=>'invalid_tmdb_id']);

try {
    $filmId = getOrCreateFilm($pdo, $tmdb_id, $title, $poster);
    $stmt = $pdo->prepare('INSERT INTO watched (user_id, film_id, rating, liked, notes) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $filmId, $rating ?: null, $liked ? 1 : 0, $notes ?: null]);
    json_response(['ok'=>true]);
} catch (Exception $e) {
    error_log('add_watched error: '.$e->getMessage());
    json_response(['error'=>'server_error']);
}

function json_response($d){header('Content-Type: application/json'); echo json_encode($d); exit;}
