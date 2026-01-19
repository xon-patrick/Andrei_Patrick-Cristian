<?php
session_start();
require __DIR__ . '/db.php';

if (empty($_SESSION['user_id'])) { header('Content-Type: application/json'); echo json_encode(['error'=>'not_authenticated']); exit; }
$userId = (int)$_SESSION['user_id'];
$list_id = isset($_GET['list_id']) ? (int)$_GET['list_id'] : 0;
if ($list_id <= 0) { header('Content-Type: application/json'); echo json_encode(['error'=>'list_id_required']); exit; }

$stmt = $pdo->prepare('SELECT id FROM lists WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$list_id, $userId]);
if (!$stmt->fetch()) { header('Content-Type: application/json'); echo json_encode(['error'=>'list_not_found']); exit; }

$stmt = $pdo->prepare('SELECT f.tmdb_id, f.title, f.poster_url, li.added_at FROM list_items li JOIN films f ON li.film_id = f.film_id WHERE li.list_id = ? ORDER BY li.added_at DESC');
$stmt->execute([$list_id]);
$items = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode(['items'=>$items]);
exit;
