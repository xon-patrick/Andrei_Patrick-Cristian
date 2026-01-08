<?php
header('Content-Type: application/json');
session_start();
$flash = $_SESSION['flash'] ?? null;
if ($flash) {
    unset($_SESSION['flash']);
}
echo json_encode(['flash' => $flash]);
