<?php
session_start();
require __DIR__ . '/db.php';

if (empty($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error'=>'not_authenticated']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0; // 0 => all

$sql = 'SELECT f.tmdb_id, f.title, f.poster_url, fav.created_at FROM favorites fav JOIN films f ON fav.film_id = f.film_id WHERE fav.user_id = ? ORDER BY fav.created_at DESC';
if ($limit > 0) $sql .= ' LIMIT ' . $limit;
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$rows = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode(['favorites' => $rows]);
exit;
