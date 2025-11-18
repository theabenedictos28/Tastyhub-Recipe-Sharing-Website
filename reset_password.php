<?php
session_start();
require 'db.php';

$message = '';
$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($newPassword) < 8) {
        $message = "Password must be at least 8 characters.";
    } elseif ($newPassword !== $confirmPassword) {
        $message = "Passwords do not match.";
    } else {
        $stmt = $conn->prepare("
            SELECT email FROM password_resets
            WHERE token = ? AND created_at >= (NOW() - INTERVAL 1 HOUR)
            LIMIT 1
        ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $row = $result->fetch_assoc()) {
            $email = $row['email'];
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashed, $email);
            $stmt->execute();

$delete_stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
$delete_stmt->bind_param("s", $email);
$delete_stmt->execute();
$delete_stmt->close();

            $message = "Password reset successful. <a href='signin.php'>Click here to sign in</a>.";
        } else {
            $message = "Invalid or expired reset link.";
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reset Password - Tasty Hub</title>
        <link href="img/favicon.png" rel="icon">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&family=Pacifico&display=swap" rel="stylesheet">

  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

  <link href="lib/animate/animate.min.css" rel="stylesheet">
  <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
  <link href="lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">

  <style>
   body {
      background-image: url('img/bg-hero.jpg');
      background-size: cover;
      background-position: center;
      position: relative;
      overflow: hidden;
    }
    body::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background-image: url('img/bg-hero.jpg');
      background-size: cover;
      background-position: center;
      filter: blur(5px);
      z-index: 1;
    }

    .card {
      border-radius: 8px;
      position: relative;
      z-index: 2;
    }
        .navbar {
    z-index: 2;
    position: relative;
}
  </style>

</head>
<body>
      <div class="container-xxl bg-dark p-0">

   <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4 px-lg-5 py-3 py-lg-0">
      <a href="index.php" class="navbar-brand p-0">
        <h2 class="text-primary m-0"><img src="img/logo_new.png" alt="Logo" style="width:50px;height:50px;">Tasty Hub</h2>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
        <span class="fa fa-bars"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarCollapse">
        <div class="navbar-nav ms-auto py-0 pe-4">
          <a href="index.php" class="nav-item nav-link">Home</a>
          <a href="guestdashboard.php" class="nav-item nav-link">Browse</a>
          <a href="guestabout.html" class="nav-item nav-link">About</a>
        </div>
        <a href="signin.php" class="btn btn-primary py-2 px-4">SIGN IN</a>
      </div>
    </nav>

        <div class="container d-flex align-items-center justify-content-center" style="min-height: calc(100vh - 76px); position: relative; z-index: 2; padding-bottom: 80px;">
            <div class="col-lg-4 col-md-6 col-sm-8 col-10">
    <div class="card shadow-lg p-4">
      <h3 class="text-center">Reset Password</h3>

      <?php if (!empty($message)): ?>
        <div class="alert alert-info"><?= $message ?></div>
      <?php endif; ?>

      <?php if (empty($message) || strpos($message, 'successfully') === false): ?>
        <form action="reset_password.php?token=<?= urlencode($token) ?>" method="POST">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <div class="mb-3">
          <label for="new_password" class="form-label">New Password</label>
          <input type="password" class="form-control" id="new_password" name="new_password" required>
        </div>
        <div class="mb-3">
          <label for="confirm_password" class="form-label">Confirm Password</label>
          <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Reset Password</button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</div>
</div>

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
