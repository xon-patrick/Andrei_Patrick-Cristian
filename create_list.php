<?php
session_start();
require __DIR__ . '/db.php';

if (empty($_SESSION['user_id'])) json_response(['error'=>'not_authenticated']);
$userId = (int)$_SESSION['user_id'];
$name = trim($_POST['name'] ?? '');
$desc = trim($_POST['description'] ?? '');
if ($name === '') json_response(['error'=>'name_required']);

try {
    $stmt = $pdo->prepare('INSERT INTO lists (user_id, name, description) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $name, $desc]);
    json_response(['ok'=>true, 'list_id' => $pdo->lastInsertId()]);
} catch (Exception $e) {
    error_log('create_list error: '.$e->getMessage());
    json_response(['error'=>'server_error']);
}

function json_response($d){header('Content-Type: application/json'); echo json_encode($d); exit;}
