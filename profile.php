<?php
session_start();
require __DIR__ . '/db.php';

if (empty($_SESSION['user_id'])) {
  header('Location: login.html');
  exit;
}

$userId = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT username, email, profile_picture, bio, created_at FROM users WHERE user_id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

$username = $user['username'] ?? $_SESSION['username'];
$profile_picture = $user['profile_picture'] ?? 'profile.jpeg';
$bio = $user['bio'] ?? '';
$flash = $_SESSION['flash'] ?? null;
if ($flash) unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Profile — Jurnel</title>
  <link rel="stylesheet" href="css/index.css" />
  <link rel="stylesheet" href="css/profile.css" />
</head>
<body>
  <?php include 'navbar.php'; ?>

  <main class="profileMain">
    <section class="profileTop">
      <?php if ($flash): ?>
        <div id="flash-msg" class="flash" style="position:fixed;top:16px;left:50%;transform:translateX(-50%);z-index:9999;min-width:260px;max-width:90%;padding:12px 16px;background:#e6ffea;border:1px solid #b6f2c9;color:#044d18;border-radius:6px;box-shadow:0 6px 18px rgba(0,0,0,0.08);opacity:1;transition:opacity 0.5s ease, transform 0.5s ease;">
          <?php echo htmlspecialchars($flash); ?>
        </div>
        <script>
          (function(){
            const f = document.getElementById('flash-msg');
            if (!f) return;
            // Auto-hide after 2 seconds without affecting layout
            setTimeout(() => {
              f.style.opacity = '0';
              f.style.transform = 'translateX(-50%) translateY(-8px)';
              setTimeout(() => { f.remove(); }, 600);
            }, 2000);
          })();
        </script>
      <?php endif; ?>
      <div class="profileCard">
        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="avatar" class="profilePhoto">
        <div class="profileInfo">
          <h1 class="profileName"><?php echo htmlspecialchars($username); ?></h1>
          <div class="profileActions">
            <a href="edit_profile.php" class="editBtn">Edit account</a>
          </div>
        </div>
      </div>

      <div class="profileStats">
        <div class="stat">
          <div class="statNumber">—</div>
          <div class="statLabel">Films</div>
        </div>
        <div class="stat">
          <div class="statNumber">—</div>
          <div class="statLabel">This year</div>
        </div>
        <div class="stat">
          <div class="statNumber">—</div>
          <div class="statLabel">Following</div>
        </div>
        <div class="stat">
          <div class="statNumber">—</div>
          <div class="statLabel">Followers</div>
        </div>
      </div>
    </section>

    <section class="favourites">
      <h2>Favourite films</h2>
      <div class="movie-grid small-grid">
        <!-- static placeholders preserved from your template -->
      </div>
    </section>

    <section class="watchCalendar">
      <h2>Recent activity</h2>
      <?php
        $stmt = $pdo->prepare('SELECT w.watched_at, w.rating, w.liked, w.notes, f.title, f.poster_url, f.tmdb_id
                               FROM watched w JOIN films f ON w.film_id = f.film_id
                               WHERE w.user_id = ?
                               ORDER BY w.watched_at DESC
                               LIMIT 50');
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        if (!$rows) {
          echo '<p style="color:#ccc">No activity yet.</p>';
        } else {
          echo '<div class="monthEntries">';
          foreach ($rows as $r) {
            $dt = strtotime($r['watched_at']);
            $day = date('d', $dt);
            $year = date('Y', $dt);
            $poster = $r['poster_url'] ?: 'https://via.placeholder.com/60x90?text=No+Image';
            $rating = $r['rating'];
            $liked = $r['liked'];
            $notes = $r['notes'];
            $titleEsc = htmlspecialchars($r['title']);
            echo "<div class=\"calendarRow\">";
            echo "<div class=\"col-day\">{$day}</div>";
            echo "<div class=\"col-thumb\"><img src=\"{$poster}\" alt=\"\" style=\"width:48px;height:72px;object-fit:cover;\"></div>";
            echo "<div class=\"col-title\">{$titleEsc}</div>";
            echo "<div class=\"col-released\">{$year}</div>";
            echo "<div class=\"col-rating\">";
            if ($rating) {
              $stars = '';
              $filled = (int)round($rating/2);
              for ($i=0;$i<5;$i++) $stars .= '<i class=\"star ' . ($i<$filled ? 'filled' : '') . '\"></i>';
              echo $stars;
            } else {
              echo '<i class="star"></i><i class="star"></i><i class="star"></i><i class="star"></i><i class="star"></i>';
            }
            echo "</div>";
            echo "<div class=\"col-like\">" . ($liked ? '❤' : '♡') . "</div>";
            echo "<div class=\"col-rewatch\">" . ('' ) . "</div>";
            echo "<div class=\"col-actions\">" . ('' ) . "</div>";
            echo "</div>";
          }
          echo '</div>';
        }
      ?>
    </section>
  </main>

  <footer>© 2025 Jurnel.</footer>
</body>
</html>