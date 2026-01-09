<?php
header('Content-Type: application/json');

// Test what files exist
$files = [
    'save_review.php' => file_exists('save_review.php'),
    'get_reviews.php' => file_exists('get_reviews.php'),
    'add_watched.php' => file_exists('add_watched.php'),
    'db.php' => file_exists('db.php'),
    'film_helpers.php' => file_exists('film_helpers.php'),
];

$realPaths = [
    'save_review.php' => realpath('save_review.php'),
    'get_reviews.php' => realpath('get_reviews.php'),
];

echo json_encode([
    'current_file' => __FILE__,
    'current_dir' => __DIR__,
    'script_filename' => $_SERVER['SCRIPT_FILENAME'],
    'files_exist' => $files,
    'real_paths' => $realPaths,
]);
