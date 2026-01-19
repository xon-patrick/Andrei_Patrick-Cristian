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

// stats
$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM watched WHERE user_id = ?');
$stmt->execute([$userId]);
$totalFilms = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM watched WHERE user_id = ? AND YEAR(watched_at) = YEAR(CURDATE())');
$stmt->execute([$userId]);
$thisYearFilms = $stmt->fetch()['total'] ?? 0;

// Following/Followers counts
$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM follows WHERE follower_id = ?');
$stmt->execute([$userId]);
$following = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM follows WHERE following_id = ?');
$stmt->execute([$userId]);
$followers = $stmt->fetch()['total'] ?? 0;

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
  <link rel="stylesheet" href="css/forms.css" />
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
          <?php if ($bio): ?>
            <p class="profileBio"><?php echo htmlspecialchars($bio); ?></p>
          <?php endif; ?>
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
              echo '<div class="col-actions">';
              echo '<button class="edit-review-btn" data-tmdb-id="' . $r['tmdb_id'] . '" data-title="' . htmlspecialchars($titleEsc, ENT_QUOTES) . '" data-poster="' . htmlspecialchars($poster, ENT_QUOTES) . '" data-rating="' . $rating . '" data-liked="' . $liked . '" data-watched-date="' . $dateObj->format('Y-m-d') . '" data-notes="' . htmlspecialchars($r['notes'] ?? '', ENT_QUOTES) . '" style="background:#456;color:#fff;border:none;padding:4px 8px;border-radius:4px;cursor:pointer;font-size:0.85rem;">Edit</button>';
              echo '</div>';
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
  
  <script>
    function showModal(html) {
      const overlay = document.createElement('div');
      overlay.className = 'modal-overlay';
      const box = document.createElement('div');
      box.className = 'modal-box';
      box.innerHTML = html;
      overlay.appendChild(box);
      document.body.appendChild(overlay);
      return { overlay, box };
    }

    async function postForm(url, data) {
      const form = new URLSearchParams();
      for (const k in data) form.append(k, data[k]);
      const res = await fetch(url, { method: 'POST', body: form, credentials: 'same-origin' });
      return res.json().catch(() => ({}));
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    document.addEventListener('click', async (e) => {
      if (e.target.classList.contains('edit-review-btn')) {
        const btn = e.target;
        const tmdbId = btn.dataset.tmdbId;
        const title = btn.dataset.title;
        const poster = btn.dataset.poster;
        const rating = btn.dataset.rating || 5;
        const liked = btn.dataset.liked === '1';
        const watchedDate = btn.dataset.watchedDate;
        const notes = btn.dataset.notes || '';
        const row = btn.closest('.calendarRow');
        
        const html = `
          <h2>Edit Your Review</h2>
          <p>Update your review and grade for <strong>${escapeHtml(title)}</strong>.</p>
          <div style="display:flex;gap:10px;margin:16px 0;align-items:center;">
            <label style="min-width:120px">Watched on</label>
            <input type="date" id="watched-date" value="${watchedDate}">
          </div>
          <div style="display:flex;gap:10px;margin:16px 0;align-items:center;">
            <label style="min-width:120px">Grade (1-10)</label>
            <select id="watched-grade">${Array.from({length:10}).map((_,i)=>`<option value="${i+1}" ${i+1 == rating ? 'selected' : ''}>${i+1}</option>`).join('')}</select>
          </div>
          <div style="margin:16px 0;">
            <label>Review</label>
            <textarea id="watched-review" rows="6" placeholder="Share your thoughts about this movie...">${escapeHtml(notes)}</textarea>
          </div>
          <div style="display:flex;gap:12px;align-items:center;margin-top:8px;">
            <label><input type="checkbox" id="watched-fav" ${liked ? 'checked' : ''}> Add to favorites</label>
          </div>
          <div class="modal-actions">
            <button id="watched-delete" class="btn-danger">Delete Review</button>
            <button id="watched-submit" class="btn btn-success">Update Review</button>
            <button id="watched-cancel" class="btn btn-secondary">Cancel</button>
          </div>
        `;
        
        const { overlay, box } = showModal(html);
        
        box.querySelector('#watched-cancel').addEventListener('click', () => overlay.remove());
        
        box.querySelector('#watched-delete').addEventListener('click', async () => {
          if (!confirm('Are you sure you want to delete this review? This will remove it from your watched list and recent activity.')) {
            return;
          }
          
          const deleteRes = await postForm('delete_review.php', { tmdb_id: tmdbId });
          
          if (deleteRes.error) {
            alert('Error deleting review: ' + (deleteRes.message || deleteRes.error));
            return;
          }
          
          overlay.remove();
          
          // animatie
          row.style.opacity = '0';
          row.style.transition = 'opacity 0.3s';
          setTimeout(() => row.remove(), 300);
          
          const s = document.createElement('div');
          s.className = 'flash warning';
          s.textContent = '🗑️ Review deleted successfully!';
          document.body.appendChild(s);
          setTimeout(() => s.style.opacity = '0', 2000);
          setTimeout(() => s.remove(), 2600);
        });
        
        box.querySelector('#watched-submit').addEventListener('click', async () => {
          const newWatchedDate = box.querySelector('#watched-date').value;
          const newGrade = box.querySelector('#watched-grade').value;
          const newReview = box.querySelector('#watched-review').value;
          const newFav = box.querySelector('#watched-fav').checked ? 1 : 0;
          
          const saveRes = await postForm('save_review.php', {
            tmdb_id: tmdbId,
            title: title,
            poster: poster,
            grade: newGrade,
            review_text: newReview,
            rating: newGrade,
            liked: newFav,
            notes: newReview,
            watched_date: newWatchedDate,
            is_rewatch: '0'
          });
          
          if (saveRes.error) {
            alert('Error saving review: ' + (saveRes.message || saveRes.error));
            return;
          }
          
          overlay.remove();
          
          const s = document.createElement('div');
          s.className = 'flash success';
          s.textContent = ' Review updated!';
          document.body.appendChild(s);
          setTimeout(() => s.style.opacity = '0', 2000);
          setTimeout(() => s.remove(), 2600);
          
          setTimeout(() => window.location.reload(), 1000);
        });
      }
    });
  </script>
</body>
</html>