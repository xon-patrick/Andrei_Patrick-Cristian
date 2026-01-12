<?php
session_start();
require __DIR__ . '/db.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
  $profile_picture = trim($_POST['profile_picture'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$username || !$email) {
        $_SESSION['flash'] = 'Username and email are required.';
        header('Location: edit_profile.php');
        exit;
    }

    try {
      // fetch current profile picture for cleanup if replaced
      $stmtOld = $pdo->prepare('SELECT profile_picture FROM users WHERE user_id = ? LIMIT 1');
      $stmtOld->execute([$userId]);
      $oldRow = $stmtOld->fetch();
      $oldPic = $oldRow['profile_picture'] ?? null;

      // handle uploaded avatar if present
      if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['avatar'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
          $_SESSION['flash'] = 'File upload error.';
          header('Location: edit_profile.php');
          exit;
        }

        $maxBytes = 2 * 1024 * 1024; // 2 MB
        if ($file['size'] > $maxBytes) {
          $_SESSION['flash'] = 'Uploaded file is too large (max 2MB).';
          header('Location: edit_profile.php');
          exit;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        if (!isset($allowed[$mime])) {
          $_SESSION['flash'] = 'Invalid image type. Allowed: JPG, PNG, WEBP, GIF.';
          header('Location: edit_profile.php');
          exit;
        }

        $ext = $allowed[$mime];
        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        $uploadsDir = __DIR__ . '/uploads';
        if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
        $dest = $uploadsDir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
          $_SESSION['flash'] = 'Failed to save uploaded file.';
          header('Location: edit_profile.php');
          exit;
        }

        // final verification of mime
        $real = $finfo->file($dest);
        if ($real !== $mime) {
          @unlink($dest);
          $_SESSION['flash'] = 'Uploaded file failed verification.';
          header('Location: edit_profile.php');
          exit;
        }

        // set public path to store in DB
        $profile_picture = 'uploads/' . $name;
      }

        // ensure username/email uniqueness (exclude current user)
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ? LIMIT 1');
        $stmt->execute([$username, $email, $userId]);
        if ($stmt->fetch()) {
            $_SESSION['flash'] = 'Username or email already taken by another account.';
            header('Location: edit_profile.php');
            exit;
        }

        // build update query
        $fields = ['username' => $username, 'email' => $email, 'bio' => $bio, 'profile_picture' => $profile_picture];
        $sqlParts = [];
        $params = [];
        foreach ($fields as $k => $v) {
            $sqlParts[] = "$k = ?";
            $params[] = $v;
        }

        if ($new_password !== '') {
            if ($new_password !== $confirm_password) {
                $_SESSION['flash'] = 'New passwords do not match.';
                header('Location: edit_profile.php');
                exit;
            }
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $sqlParts[] = 'password_hash = ?';
            $params[] = $hash;
        }

        $params[] = $userId;
        $sql = 'UPDATE users SET ' . implode(', ', $sqlParts) . ' WHERE user_id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // remove old uploaded file if we replaced it (and it's a local uploads path)
        if (!empty($oldPic) && !empty($profile_picture) && $oldPic !== $profile_picture) {
          $oldPath = __DIR__ . '/' . ltrim($oldPic, '/');
          if (strpos($oldPic, 'uploads/') === 0 && is_file($oldPath)) {
            @unlink($oldPath);
          }
        }

        $_SESSION['username'] = $username;
        $_SESSION['flash'] = 'Profile updated successfully.';
        header('Location: profile.php');
        exit;
    } catch (Exception $e) {
        error_log('Edit profile error: ' . $e->getMessage());
        $_SESSION['flash'] = 'Server error, try again later.';
        header('Location: edit_profile.php');
        exit;
    }
}

// GET — fetch current values
$stmt = $pdo->prepare('SELECT username, email, profile_picture, bio FROM users WHERE user_id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

$username = $user['username'] ?? $_SESSION['username'];
$email = $user['email'] ?? '';
$profile_picture = $user['profile_picture'] ?? 'profile.jpeg';
$bio = $user['bio'] ?? '';
$flash = $_SESSION['flash'] ?? null;
if ($flash) unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Edit Profile — Jurnel</title>
  <link rel="stylesheet" href="css/navbar.css" />
  <link rel="stylesheet" href="css/forms.css" />
</head>
<body>
  <?php include 'navbar.php'; ?>

  <?php if ($flash): ?>
    <div id="flash-msg" class="flash success">
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
        }, 2500);
      })();
    </script>
  <?php endif; ?>

  <main class="form-container">
    <h1> Edit Your Profile</h1>
    
    <form method="post" action="edit_profile.php" enctype="multipart/form-data" class="edit-form">
      <div class="form-section">
        <h3>Account Information</h3>
        
        <div class="formRow">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($username); ?>" placeholder="Enter your username">
        </div>

        <div class="formRow">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter your email">
        </div>
      </div>

      <div class="form-section">
        <h3>Profile Picture</h3>
        
        <div class="formRow">
          <label for="avatar">Upload New Picture</label>
          <input type="file" id="avatar" name="avatar" accept="image/*">
          <p class="helper-text">Accepted formats: JPG, PNG, WEBP, GIF (max 2MB)</p>
        </div>

        <div class="formRow">
          <label for="profile_picture">Or Use Picture URL</label>
          <input type="text" id="profile_picture" name="profile_picture" value="<?php echo htmlspecialchars($profile_picture); ?>" placeholder="https://example.com/image.jpg">
          <p class="helper-text">Provide a direct link to an image</p>
        </div>
      </div>

      <div class="form-section">
        <h3>About You</h3>
        
        <div class="formRow">
          <label for="bio">Bio</label>
          <textarea id="bio" name="bio" rows="5" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($bio); ?></textarea>
          <p class="helper-text">Share your favorite movies, directors, or what you love about cinema</p>
        </div>
      </div>

      <div class="form-section">
        <h3>Change Password</h3>
        <p style="color: #888; font-size: 0.9rem; margin-bottom: 1rem;">Leave blank to keep your current password</p>
        
        <div class="formRow">
          <label for="new_password">New Password</label>
          <input type="password" id="new_password" name="new_password" placeholder="Enter new password">
        </div>
        
        <div class="formRow">
          <label for="confirm_password">Confirm New Password</label>
          <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password">
        </div>
      </div>

      <div class="form-actions">
        <button class="btn btn-primary" type="submit"> Save Changes</button>
        <a href="profile.php" class="btn btn-link">Cancel</a>
      </div>
    </form>
  </main>
</body>
</html>
