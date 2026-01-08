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
  <link rel="stylesheet" href="css/index.css" />
  <link rel="stylesheet" href="css/profile.css" />
  <style>
    .formRow { margin:8px 0; }
    .formRow input, .formRow textarea { width:100%; padding:8px; box-sizing:border-box; }
    .saveBtn { padding:10px 16px; background:#2b7; border:none; color:#fff; border-radius:6px; cursor:pointer; }
    .flash { max-width:900px;margin:12px auto;padding:8px 12px;background:#e6ffea;border:1px solid #b6f2c9;color:#044d18;border-radius:6px; }
  </style>
</head>
<body>
  <?php include 'navbar.php'; ?>

  <main style="max-width:900px;margin:24px auto;padding:0 12px;">
    <h1>Edit account</h1>
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

    <form method="post" action="edit_profile.php" enctype="multipart/form-data">
      <div class="formRow">
        <label>Username</label>
        <input type="text" name="username" required value="<?php echo htmlspecialchars($username); ?>">
      </div>

      <div class="formRow">
        <label>Email</label>
        <input type="email" name="email" required value="<?php echo htmlspecialchars($email); ?>">
      </div>

      <div class="formRow">
        <label>Upload profile picture (optional)</label>
        <input type="file" name="avatar" accept="image/*">
      </div>

      <div class="formRow">
        <label>Or profile picture URL</label>
        <input type="text" name="profile_picture" value="<?php echo htmlspecialchars($profile_picture); ?>">
      </div>

      <div class="formRow">
        <label>Bio</label>
        <textarea name="bio" rows="4"><?php echo htmlspecialchars($bio); ?></textarea>
      </div>

      <hr>
      <h3>Change password (optional)</h3>
      <div class="formRow">
        <label>New password</label>
        <input type="password" name="new_password">
      </div>
      <div class="formRow">
        <label>Confirm new password</label>
        <input type="password" name="confirm_password">
      </div>

      <div style="margin-top:12px;">
        <button class="saveBtn" type="submit">Save changes</button>
        <a href="profile.php" style="margin-left:12px;">Cancel</a>
      </div>
    </form>
  </main>
</body>
</html>
