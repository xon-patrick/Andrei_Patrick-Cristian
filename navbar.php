<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';

if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');

  // lightweight diagnostic JSON when called with ?diag=1 - recomandare chat
  if (isset($_GET['diag'])) {
    header('Content-Type: application/json');
    $logged = !empty($_SESSION['user_id']);
    echo json_encode([
      'logged_in' => $logged,
      'username' => $logged ? ($_SESSION['username'] ?? null) : null,
    ]);
    exit;
  }
}

$avatar = 'profile.jpeg';
$username = $_SESSION['username'] ?? null;
if (!empty($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare('SELECT profile_picture, username FROM users WHERE user_id = ? LIMIT 1');
        $stmt->execute([(int)$_SESSION['user_id']]);
        $u = $stmt->fetch();
        if ($u) {
            if (!empty($u['profile_picture'])) $avatar = $u['profile_picture'];
            $username = $u['username'] ?? $username;
        }
    } catch (Exception $e) { /* ignore */ }
}
?>
<link rel="stylesheet" href="css/navbar.css">
<style>
  .nav-links .avatar, .profile-link .avatar { width:32px !important; height:32px !important; border-radius:50% !important; object-fit:cover !important; border:1px solid rgba(255,255,255,0.06) !important; margin-right:6px !important; }
</style>
<nav>
  <a href="index.php" class="logo">Jurn<span>el</span></a>

  <div class="nav-links">
    <form id="navSearchForm" class="navSearchForm" action="discover.html" method="get">
      <input type="text" id="navSearchInput" name="query" placeholder="Discover..." />
      <button type="submit" aria-label="Search">
        <span class="search-icon">🔍</span>
      </button>
    </form>

    <a href="people.php">People</a>

    <?php if (!empty($username)): ?>
      <a href="profile.php" class="profile-link nav-btn account">
        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar" class="avatar">
        <span><?php echo htmlspecialchars($username); ?></span>
      </a>
      <a href="logout.php" class="nav-btn">Log Out</a>
    <?php else: ?>
      <a href="login.html" class="nav-btn login">Log In</a>
      <a href="signup.html" class="nav-btn signup">Create Account</a>
    <?php endif; ?>
  </div>
</nav>