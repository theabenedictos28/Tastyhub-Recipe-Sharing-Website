<?php
session_start();
require 'db.php';

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } else {
        // Check if email exists in users table
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            // Remove any old tokens
            $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $del->bind_param("s", $email);
            $del->execute();

            // Generate new token
            $token = bin2hex(random_bytes(32));

            // Save token
            $stmt2 = $conn->prepare("INSERT INTO password_resets (email, token, created_at) VALUES (?, ?, NOW())");
            $stmt2->bind_param("ss", $email, $token);
            $stmt2->execute();

            // Build reset link
            $resetLink = "https://tastyhub.site/reset_password.php?token=" . urlencode($token);

            // === Configure PHPMailer ===
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.hostinger.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'info@tastyhub.site'; // ✅ your Hostinger email
                $mail->Password = 'Tastyhub@28'; // ⚠️ replace with your real Hostinger email password
                $mail->SMTPSecure = 'ssl';
                $mail->Port = 465;

                $mail->setFrom('info@tastyhub.site', 'Tasty Hub');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Tasty Hub Password Reset';
                $mail->Body = "
                    <p>Hello,</p>
                    <p>We received a request to reset your Tasty Hub account password.</p>
                    <p><a href='$resetLink'>Click here to reset your password</a></p>
                    <p>This link will expire in 1 hour.</p>
                    <p>If you did not request this, please ignore this email.</p>
                ";

                $mail->send();
                $message = "Password reset link has been sent to your email.";
            } catch (Exception $e) {
                $message = "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            // For security, don't reveal if email doesn't exist
            $message = "If that email exists, a reset link will be sent.";
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Forgot Password - Tasty Hub</title>
        <!-- Favicon -->
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
   <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4 px-lg-5 py-3 py-lg-0">
        <a href="index.php" class="navbar-brand p-0">
            <h2 class="text-primary m-0">
                <img src="img/logo_new.png" alt="Logo" style="width:50px;height:50px;">Tasty Hub
            </h2>
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


    <!-- Centered Form -->
    <div class="container d-flex align-items-center justify-content-center" style="min-height: calc(100vh - 76px); position: relative; z-index: 2; padding-bottom: 80px;">
      <div class="col-lg-4 col-md-6 col-sm-8">
        <div class="card shadow-lg p-4">
          <h3 class="text-center">Forgot Password</h3>
          <form action="forgot_password.php" method="POST">
            <div class="mb-3">
              <label for="email" class="form-label">Enter your email address:</label>
              <input type="email" class="form-control" name="email" required>
            </div>

            <?php if (!empty($message)): ?>
              <p class="alert alert-info"><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
          </form>
        </div>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
  </div>
</body>
</html>
