<?php
session_start();
require __DIR__ . '/db.php';

$viewUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

if (!$viewUserId) {
  header('Location: people.php');
  exit;
}

$currentUserId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$isOwnProfile = ($currentUserId && $currentUserId === $viewUserId);

if ($isOwnProfile) {
  header('Location: profile.php');
  exit;
}

$stmt = $pdo->prepare('SELECT username, email, profile_picture, bio, created_at FROM users WHERE user_id = ? LIMIT 1');
$stmt->execute([$viewUserId]);
$user = $stmt->fetch();

if (!$user) {
  header('Location: people.php');
  exit;
}

$viewedUsername = $user['username'];
$viewedProfilePicture = $user['profile_picture'] ?? 'profile.jpeg';
$viewedBio = $user['bio'] ?? '';

$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM watched WHERE user_id = ?');
$stmt->execute([$viewUserId]);
$totalFilms = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM watched WHERE user_id = ? AND YEAR(watched_at) = YEAR(CURDATE())');
$stmt->execute([$viewUserId]);
$thisYearFilms = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM follows WHERE follower_id = ?');
$stmt->execute([$viewUserId]);
$following = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM follows WHERE following_id = ?');
$stmt->execute([$viewUserId]);
$followers = $stmt->fetch()['total'] ?? 0;

$isFollowing = false;
if ($currentUserId) {
  $stmt = $pdo->prepare('SELECT id FROM follows WHERE follower_id = ? AND following_id = ? LIMIT 1');
  $stmt->execute([$currentUserId, $viewUserId]);
  $isFollowing = (bool)$stmt->fetch();
}

$stmt = $pdo->prepare('
  SELECT f.tmdb_id, f.title, f.poster_url, MAX(w.watched_at) as last_watched
  FROM favorites fav 
  JOIN films f ON fav.film_id = f.film_id 
  LEFT JOIN watched w ON w.film_id = f.film_id AND w.user_id = fav.user_id
  WHERE fav.user_id = ? 
  GROUP BY f.film_id, f.tmdb_id, f.title, f.poster_url
  ORDER BY last_watched DESC 
  LIMIT 6
');
$stmt->execute([$viewUserId]);
$favorites = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?php echo htmlspecialchars($viewedUsername); ?> — Jurnel</title>
  <link rel="stylesheet" href="css/index.css" />
  <link rel="stylesheet" href="css/profile.css" />
  <link rel="stylesheet" href="css/people.css" />
</head>
<body>
  <?php include 'navbar.php'; ?>

  <main class="profileMain">
    <section class="profileTop">
      <div class="profileCard">
        <img src="<?php echo htmlspecialchars($viewedProfilePicture); ?>" alt="avatar" class="profilePhoto">
        <div class="profileInfo">
          <h1 class="profileName"><?php echo htmlspecialchars($viewedUsername); ?></h1>
          <?php if ($viewedBio): ?>
            <p class="profileBio"><?php echo htmlspecialchars($viewedBio); ?></p>
          <?php endif; ?>
          <div class="profileActions">
            <?php if ($currentUserId): ?>
              <button id="followBtn" class="btn-follow <?php echo $isFollowing ? 'following' : ''; ?>" 
                      data-user-id="<?php echo $viewUserId; ?>"
                      data-following="<?php echo $isFollowing ? 1 : 0; ?>">
                <?php echo $isFollowing ? 'Following' : 'Follow'; ?>
              </button>
            <?php else: ?>
              <a href="login.html" class="btn-follow-login">Login to Follow</a>
            <?php endif; ?>
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
          <div class="statNumber" id="followingCount"><?php echo $following; ?></div>
          <div class="statLabel">Following</div>
        </div>
        <div class="stat">
          <div class="statNumber" id="followersCount"><?php echo $followers; ?></div>
          <div class="statLabel">Followers</div>
        </div>
      </div>
    </section>

    <section class="favourites">
      <h2>Favourite films</h2>
      <div class="movie-grid small-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 150px)); gap: 16px;">
        <?php if (empty($favorites)): ?>
          <p style="color:#999;">No favorite films yet.</p>
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
        $stmt->execute([$viewUserId]);
        $rows = $stmt->fetchAll();
        if (!$rows) {
          echo '<p style="color:#ccc">No activity yet.</p>';
        } else {
          $groupedByMonth = [];
          foreach ($rows as $r) {
            $dateObj = new DateTime($r['watched_at']);
            $monthYear = $dateObj->format('Y-m'); 
            if (!isset($groupedByMonth[$monthYear])) {
              $groupedByMonth[$monthYear] = [];
            }
            $groupedByMonth[$monthYear][] = $r;
          }
          
          foreach ($groupedByMonth as $monthYear => $monthRows) {
            $firstDateObj = new DateTime($monthRows[0]['watched_at']);
            $monthName = strtoupper($firstDateObj->format('M')); 
            $year = $firstDateObj->format('Y');
            
            echo '<div class="monthGroup">';
            echo '<div class="monthBadge">';
            echo '<div class="mb-month">' . $monthName . '</div>';
            echo '<div class="mb-year">' . $year . '</div>';
            echo '</div>';
            
            echo '<div class="monthEntries">';
            
            if ($monthYear === array_key_first($groupedByMonth)) {
              echo '<div class="calendarHeader">';
              echo '<div class="col-day">DAY</div>';
              echo '<div class="col-day">FILM</div>';
              echo '<div class="col-title"></div>';
              echo '<div class="col-released">RELEASED</div>';
              echo '<div class="col-rating">RATING</div>';
              echo '<div class="col-like">LIKE</div>';
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
              echo '</div>';
            }
            
            echo '</div>'; 
            echo '</div>'; 
          }
        }
      ?>
    </section>
  </main>

  <footer>© 2025 Jurnel.</footer>
  
  <?php if ($currentUserId): ?>
  <script>
    const followBtn = document.getElementById('followBtn');
    const followersCountEl = document.getElementById('followersCount');
    const viewUserId = <?php echo $viewUserId; ?>;
    
    if (followBtn) {
      followBtn.addEventListener('click', async function() {
        const wasFollowing = this.dataset.following === '1';
        
        this.disabled = true;
        const originalText = this.textContent;
        this.textContent = '...';
        
        try {
          const formData = new FormData();
          formData.append('user_id', viewUserId);
          
          const response = await fetch('toggle_follow.php', {
            method: 'POST',
            body: formData
          });
          
          const data = await response.json();
          
          if (!response.ok) {
            throw new Error(data.error || 'Failed to toggle follow');
          }
          
          if (data.is_following) {
            this.classList.add('following');
            this.textContent = 'Following';
            this.dataset.following = '1';
            const currentCount = parseInt(followersCountEl.textContent) || 0;
            followersCountEl.textContent = currentCount + 1;
          } else {
            this.classList.remove('following');
            this.textContent = 'Follow';
            this.dataset.following = '0';
            const currentCount = parseInt(followersCountEl.textContent) || 0;
            followersCountEl.textContent = Math.max(0, currentCount - 1);
          }
          
        } catch (error) {
          console.error('Error toggling follow:', error);
          alert('Failed to update follow status. Please try again.');
          this.textContent = originalText;
        } finally {
          this.disabled = false;
        }
      });
    }
  </script>
  <?php endif; ?>
</body>
</html>
