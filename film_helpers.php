<?php
// Ensure required tables exist (safe no-op if migration has already run)
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS films (
            film_id INT AUTO_INCREMENT PRIMARY KEY,
            tmdb_id INT NOT NULL UNIQUE,
            title VARCHAR(512) NOT NULL,
            poster_url VARCHAR(1024),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS favorites (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            film_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY ux_user_film (user_id, film_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS watched (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            film_id INT NOT NULL,
            watched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            rating TINYINT NULL,
            liked BOOLEAN DEFAULT FALSE,
            rewatch_count INT DEFAULT 0,
            notes TEXT,
            UNIQUE KEY ux_user_film_watched (user_id, film_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS lists (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            is_default BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS list_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            list_id INT NOT NULL,
            film_id INT NOT NULL,
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            position INT DEFAULT 0,
            UNIQUE KEY ux_list_film (list_id, film_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            film_id INT NOT NULL,
            grade TINYINT NOT NULL DEFAULT 5,
            review_text TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_film_created (user_id, film_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    } catch (Exception $e) {
        // ignore — endpoints will handle errors
    }
}

// small helper to find or insert a film record
function getOrCreateFilm(PDO $pdo, int $tmdb_id, string $title = '', ?string $poster = null) {
    // try find
    $stmt = $pdo->prepare('SELECT film_id FROM films WHERE tmdb_id = ? LIMIT 1');
    $stmt->execute([$tmdb_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) return (int)$row['film_id'];

    // insert
    $stmt = $pdo->prepare('INSERT INTO films (tmdb_id, title, poster_url) VALUES (?, ?, ?)');
    $stmt->execute([$tmdb_id, $title, $poster]);
    return (int)$pdo->lastInsertId();
}

function json_response($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}