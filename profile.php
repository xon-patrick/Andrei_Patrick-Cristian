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

// Calculate stats
// Total films watched
$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM watched WHERE user_id = ?');
$stmt->execute([$userId]);
$totalFilms = $stmt->fetch()['total'] ?? 0;

// Films watched this year
$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM watched WHERE user_id = ? AND YEAR(watched_at) = YEAR(CURDATE())');
$stmt->execute([$userId]);
$thisYearFilms = $stmt->fetch()['total'] ?? 0;

// For now, following/followers are placeholder (you can implement these later)
$following = 0;
$followers = 0;

// Get favorite films
$stmt = $pdo->prepare('
  SELECT f.tmdb_id, f.title, f.poster_url 
  FROM favorites fav 
  JOIN films f ON fav.film_id = f.film_id 
  WHERE fav.user_id = ? 
  ORDER BY fav.created_at DESC 
  LIMIT 8
');
$stmt->execute([$userId]);
$favorites = $stmt->fetchAll();
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
          <div class="statNumber"><?php echo $totalFilms; ?></div>
          <div class="statLabel">Films</div>
        </div>
        <div class="stat">
          <div class="statNumber"><?php echo $thisYearFilms; ?></div>
          <div class="statLabel">This year</div>
        </div>
        <div class="stat">
          <div class="statNumber"><?php echo $following; ?></div>
          <div class="statLabel">Following</div>
        </div>
        <div class="stat">
          <div class="statNumber"><?php echo $followers; ?></div>
          <div class="statLabel">Followers</div>
        </div>
      </div>
    </section>

    <section class="favourites">
      <h2>Favourite films</h2>
      <div class="movie-grid small-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 150px)); gap: 16px;">
        <?php if (empty($favorites)): ?>
          <p style="color:#999;">No favorite films yet. Start adding some!</p>
        <?php else: ?>
          <?php foreach ($favorites as $fav): ?>
            <a href="film.php?id=<?php echo $fav['tmdb_id']; ?>" style="text-decoration: none; color: inherit;">
              <div class="movie-card" style="cursor: pointer; width: 150px;">
                <img src="<?php echo htmlspecialchars($fav['poster_url'] ?: 'https://via.placeholder.com/300x450?text=No+Image'); ?>" 
                     alt="<?php echo htmlspecialchars($fav['title']); ?>" 
                     style="width: 150px; height: 225px; object-fit: cover; border-radius: 4px;">
                <p style="margin-top: 8px; color: #fff; font-size: 0.9rem;"><?php echo htmlspecialchars($fav['title']); ?></p>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
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
          // Group by month and year
          $groupedByMonth = [];
          foreach ($rows as $r) {
            // Use DateTime to avoid timezone issues
            $dateObj = new DateTime($r['watched_at']);
            $monthYear = $dateObj->format('Y-m'); // e.g., "2025-10"
            if (!isset($groupedByMonth[$monthYear])) {
              $groupedByMonth[$monthYear] = [];
            }
            $groupedByMonth[$monthYear][] = $r;
          }
          
          // Display each month group
          foreach ($groupedByMonth as $monthYear => $monthRows) {
            $firstDateObj = new DateTime($monthRows[0]['watched_at']);
            $monthName = strtoupper($firstDateObj->format('M')); // e.g., "OCT"
            $year = $firstDateObj->format('Y');
            
            echo '<div class="monthGroup">';
            echo '<div class="monthBadge">';
            echo '<div class="mb-month">' . $monthName . '</div>';
            echo '<div class="mb-year">' . $year . '</div>';
            echo '</div>';
            
            echo '<div class="monthEntries">';
            
            // Add header row for first month only
            if ($monthYear === array_key_first($groupedByMonth)) {
              echo '<div class="calendarHeader">';
              echo '<div class="col-day">DAY</div>';
              echo '<div class="col-day">FILM</div>';
              echo '<div class="col-title"></div>';
              echo '<div class="col-released">RELEASED</div>';
              echo '<div class="col-rating">RATING</div>';
              echo '<div class="col-like">LIKE</div>';
              echo '<div class="col-rewatch">REWATCH</div>';
              echo '<div class="col-actions">EDIT</div>';
              echo '</div>';
            }
            
            foreach ($monthRows as $r) {
              $dateObj = new DateTime($r['watched_at']);
              $day = $dateObj->format('d');
              $releaseYear = $dateObj->format('Y');
              $poster = $r['poster_url'] ?: 'https://via.placeholder.com/60x90?text=No+Image';
              $rating = $r['rating'];
              $liked = $r['liked'];
              $titleEsc = htmlspecialchars($r['title']);
              
              echo '<div class="calendarRow">';
              echo '<div class="col-day">' . $day . '</div>';
              echo '<div class="col-thumb"><a href="film.php?id=' . $r['tmdb_id'] . '"><img src="' . htmlspecialchars($poster) . '" alt="" style="width:48px;height:72px;object-fit:cover;cursor:pointer;"></a></div>';
              echo '<div class="col-title"><a href="film.php?id=' . $r['tmdb_id'] . '" style="color:#fff;text-decoration:none;">' . $titleEsc . '</a></div>';
              echo '<div class="col-released">' . $releaseYear . '</div>';
              echo '<div class="col-rating">';
              if ($rating) {
                $filled = (int)round($rating/2);
                for ($i=0;$i<5;$i++) echo '<i class="star ' . ($i<$filled ? 'filled' : '') . '"></i>';
              } else {
                echo '<i class="star"></i><i class="star"></i><i class="star"></i><i class="star"></i><i class="star"></i>';
              }
              echo '</div>';
              echo '<div class="col-like">' . ($liked ? '❤' : '♡') . '</div>';
              echo '<div class="col-rewatch"></div>';
              echo '<div class="col-actions">';
              echo '<button class="delete-review-btn" data-tmdb-id="' . $r['tmdb_id'] . '" style="background:#c33;color:#fff;border:none;padding:4px 8px;border-radius:4px;cursor:pointer;font-size:0.85rem;">Delete</button>';
              echo '</div>';
              echo '</div>';
            }
            
            echo '</div>'; // close monthEntries
            echo '</div>'; // close monthGroup
          }
        }
      ?>
    </section>
  </main>

  <footer>© 2025 Jurnel.</footer>
  
  <script>
    // Handle delete review buttons
    document.addEventListener('click', async (e) => {
      if (e.target.classList.contains('delete-review-btn')) {
        if (!confirm('Are you sure you want to delete this review? This will remove it from your watched list and recent activity.')) {
          return;
        }
        
        const tmdbId = e.target.dataset.tmdbId;
        const row = e.target.closest('.calendarRow');
        
        try {
          const form = new URLSearchParams();
          form.append('tmdb_id', tmdbId);
          
          const res = await fetch('delete_review.php', {
            method: 'POST',
            body: form,
            credentials: 'same-origin'
          });
          
          const data = await res.json();
          
          if (data.ok) {
            // Remove the row with animation
            row.style.opacity = '0';
            row.style.transition = 'opacity 0.3s';
            setTimeout(() => row.remove(), 300);
            
            // Show success message
            const s = document.createElement('div');
            s.textContent = 'Review deleted!';
            s.style = 'position:fixed;top:18px;left:50%;transform:translateX(-50%);background:#ffe6e6;color:#8b0000;padding:8px 12px;border-radius:6px;z-index:10001;';
            document.body.appendChild(s);
            setTimeout(() => s.style.opacity = '0', 1500);
            setTimeout(() => s.remove(), 2100);
          } else {
            alert('Error deleting review: ' + (data.message || data.error));
          }
        } catch (err) {
          console.error('Delete error:', err);
          alert('Error deleting review');
        }
      }
    });
  </script>
</body>
</html>