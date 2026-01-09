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
$tmdbId = isset($_POST['tmdb_id']) ? (int)$_POST['tmdb_id'] : 0;

if ($tmdbId <= 0) {
    json_response(['error' => 'Invalid tmdb_id']);
    exit;
}

try {
    // Find the film
    $stmt = $pdo->prepare('SELECT film_id FROM films WHERE tmdb_id = ? LIMIT 1');
    $stmt->execute([$tmdbId]);
    $film = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$film) {
        json_response(['error' => 'Film not found']);
        exit;
    }
    
    $filmId = (int)$film['film_id'];
    
    // Delete all reviews for this user and film
    $stmt = $pdo->prepare('DELETE FROM reviews WHERE user_id = ? AND film_id = ?');
    $stmt->execute([$userId, $filmId]);
    
    // Delete from watched table (removes from recent activity)
    $stmt = $pdo->prepare('DELETE FROM watched WHERE user_id = ? AND film_id = ?');
    $stmt->execute([$userId, $filmId]);
    
    json_response(['ok' => true]);
} catch (Exception $e) {
    error_log('delete_review error: ' . $e->getMessage());
    json_response(['error' => 'server_error', 'message' => $e->getMessage()]);
}
