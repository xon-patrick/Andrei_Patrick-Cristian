<?php
session_start();
require __DIR__ . '/db.php';
require __DIR__ . '/film_helpers.php';

if (empty($_SESSION['user_id'])) json_response(['error'=>'not_authenticated']);
$userId = (int)$_SESSION['user_id'];
$list_id = isset($_POST['list_id']) ? (int)$_POST['list_id'] : 0;
$tmdb_id = isset($_POST['tmdb_id']) ? (int)$_POST['tmdb_id'] : 0;
$title = trim($_POST['title'] ?? '');
$poster = trim($_POST['poster'] ?? '');

if ($list_id <= 0 || $tmdb_id <= 0) json_response(['error'=>'invalid_input']);

// ensure list belongs to user
$stmt = $pdo->prepare('SELECT id FROM lists WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$list_id, $userId]);
if (!$stmt->fetch()) json_response(['error'=>'list_not_found']);

try {
    $filmId = getOrCreateFilm($pdo, $tmdb_id, $title, $poster);
    $stmt = $pdo->prepare('INSERT IGNORE INTO list_items (list_id, film_id) VALUES (?, ?)');
    $stmt->execute([$list_id, $filmId]);
    json_response(['ok'=>true]);
} catch (Exception $e) {
    error_log('add_to_list error: '.$e->getMessage());
    json_response(['error'=>'server_error']);
}

function json_response($d){header('Content-Type: application/json'); echo json_encode($d); exit;}
