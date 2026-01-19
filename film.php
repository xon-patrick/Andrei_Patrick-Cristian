<?php
session_start();
require __DIR__ . '/db.php';
require __DIR__ . '/film_helpers.php';

$isLoggedIn = !empty($_SESSION['user_id']);
$userId = $isLoggedIn ? (int)$_SESSION['user_id'] : 0;
$userName = $isLoggedIn ? ($_SESSION['username'] ?? 'User') : '';

$movieId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Journel</title>
  <link rel="stylesheet" href="css/film.css" />
  <link rel="stylesheet" href="css/forms.css" />
</head>
<body>
  <nav id="nav-placeholder"></nav>
  <script src="js/navbar.js" defer></script>

  <!-- movie backdrop -->
  <header id="filmHeader" class="filmHeader"></header>

  <main class="filmMainContent">
    <div class="filmInfo">
      <div class="filmThumbnail">
        <img id="filmThumbImg" src="" alt="Poster">
      </div>
      
      <div class="filmDetails">
        <h1 id="filmTitle" class="filmTitle">Loading…</h1>
        <p id="filmMeta" class="filmMeta"></p>
        <br>
        <section class="filmSynopsis">
            <h3 id="filmTagline"></h3>
            <p id="filmOverview">Loading overview…</p>
        </section>

        <section class="filmActions">
          <button class="actionBtn watched" id="btnWatched">
            <span class="icon">👁</span>
            <span>Watched</span>
          </button>
          <button class="actionBtn loved" id="btnLoved">
            <span class="icon">❤</span>
            <span>Loved</span>
          </button>
          <button class="actionBtn watchlist" id="btnWatchlist">
            <span class="icon">📋</span>
            <span>Watchlist</span>
          </button>
        </section>

      </div>
    </div>

    <!-- tmdb ratings -->
    <section class="filmRatingsSection">
      <h2>TMDB Rating</h2>
      <div id="tmdbRatingContainer" class="ratingBox">
      </div>
    </section>

    <!-- reviewurile mere -->
    <section class="filmReviews">
      <h2>Journel Reviews</h2>
      <div id="reviewsContainer">
      </div>
    </section>
  </main>
  
  <footer>
    © 2025 Journel.
  </footer>

  <script>
    const PHP_DATA = {
      isLoggedIn: <?php echo $isLoggedIn ? 'true' : 'false'; ?>,
      userId: <?php echo $userId; ?>,
      userName: '<?php echo addslashes($userName); ?>',
      movieId: <?php echo $movieId; ?>
    };
  </script>

  <script src="js/film.js" defer></script>
</body>
</html>
