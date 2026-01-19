<?php
session_start();
require __DIR__ . '/db.php';

$loggedIn = !empty($_SESSION['user_id']);
$username = $_SESSION['username'] ?? null;

$quotes = [
  "We accept the reality of the world with which we’re presented.", //- The Truman Show
  "Life is not like in the movies. Life… is much harder.", //- Cinema Paradiso
  "I'm gonna make him an offer he can't refuse.", //-The Godfather
  "Life is like a box of chocolates. You never know what you're gonna get." // — Forrest Gump"
];
$quote = $quotes[array_rand($quotes)];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Journel</title>
  <link rel="stylesheet" href="css/index.css" />
</head>
<body>
  <div id="nav-placeholder"></div>
  <script src="js/navbar.js" defer></script>
  <!-- home -->
  <?php if ($loggedIn && $username): ?>
    <header class="home">
      <div class="home-content">
        <h1>Welcome back, <?php echo htmlspecialchars($username); ?>!</h1>
        <p style="font-style:italic;color:#ddd;max-width:800px;margin:8px auto;">“<?php echo htmlspecialchars($quote); ?>”</p>
        <h2>Glad to see you back! Pick up where you left off.</h2><br>
        <a href="profile.php" class="button">Go to profile</a>
      </div>
    </header>
  <?php else: ?>
    <header class="home">
      <div class="home-content">
        <h1>Track your journey through cinema.</h1>
        <p>Journel helps you log, rate and discover the films that shaped your story.</p>
        <a href="signup.html" class="button">Get Started</a>
      </div>
    </header>
  <?php endif; ?>


  <section class="popular">
    <h2>Trending</h2>
    <div class="movie-grid" id="movie-grid">
    </div>
  </section>

  <?php if ($loggedIn): ?>
  <section class="recent-activity">
    <h2>Recent Activity from People You Follow</h2>
    <div class="activity-container" id="activity-container">
      <p class="loading">Loading activity...</p>
    </div>
  </section>
  <?php endif; ?>

  <footer>
    © 2025 Journel.
  </footer>

   <script src="js/index.js" defer></script>
    
</body>
</html>
