<?php
session_start();
require __DIR__ . '/db.php';

// Allow both logged in and unlogged users
$isLoggedIn = !empty($_SESSION['user_id']);
$username = $_SESSION['username'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>People - Journel</title>
  <link rel="stylesheet" href="css/index.css" />
  <link rel="stylesheet" href="css/people.css" />
</head>
<body>
  <?php include 'navbar.php'; ?>

  <div class="container">
    <header class="page-header">
      <h1>Film Enthusiasts</h1>
      <p>Discover and follow other people who have journeled with us!</p>
    </header>

    <div class="search-section">
      <input type="text" id="userSearch" placeholder="Search users..." />
    </div>

    <div id="usersContainer" class="users-grid">
    </div>

    <div id="loading" class="loading" style="display: none;">
      Loading users...
    </div>

    <div id="emptyState" class="empty-state" style="display: none;">
      <p>No users found</p>
    </div>
  </div>

  <footer>
    © 2025 Journel.
  </footer>

  <script>
    const IS_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
  </script>
  <script src="js/people.js" defer></script>
</body>
</html>
