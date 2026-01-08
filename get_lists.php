<?php
session_start();
require __DIR__ . '/db.php';

if (empty($_SESSION['user_id'])) { header('Content-Type: application/json'); echo json_encode(['error'=>'not_authenticated']); exit; }
$userId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT id, name, description, is_default, created_at FROM lists WHERE user_id = ? ORDER BY is_default DESC, created_at DESC');
$stmt->execute([$userId]);
$lists = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode(['lists'=>$lists]);
exit;
