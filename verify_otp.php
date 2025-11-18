<?php
session_start();
require 'db.php';

// Redirect if no pending email
if (!isset($_SESSION['pending_email'])) {
    header("Location: signin.php");
    exit;
}

$email = $_SESSION['pending_email'];
$message = '';
$message_class = '';
$verified = false;

// ✅ Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// ✅ Function to send OTP using Hostinger SMTP
function sendOtp($email, $conn) {
    // Generate a new 6-digit OTP
    $otp = rand(100000, 999999);

    // Delete any existing OTP for this email
    $stmt = $conn->prepare("DELETE FROM user_otps WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    // Save new OTP
    $stmt = $conn->prepare("INSERT INTO user_otps (email, otp, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();

    // ✅ Send email via PHPMailer
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'info@tastyhub.site'; // your Hostinger email
        $mail->Password = 'Tastyhub@28'; // your Hostinger email password
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        $mail->setFrom('info@tastyhub.site', 'Tasty Hub');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Tasty Hub Verify Your Email';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; background:#fff; padding:20px; border-radius:10px; text-align:center;'>
                <img src='https://tastyhub.site/img/logo_new.png' width='60' alt='Tasty Hub'>
                <p>Hello!</p>
                <h2 style='color:#f97316;'>Your OTP Code is</h2>
                <h1 style='letter-spacing:5px; color:#333;'>$otp</h1>
                <p>This code expires in <b>5 minutes</b>.</p>
                <p>If you did not request this, you can ignore this email.</p>
                <br>
                <small style='color:#888;'>© " . date('Y') . " Tasty Hub. All rights reserved.</small>
            </div>
        ";
        $mail->send();

        return ["✅ OTP sent successfully to your email.", "alert-success"];
    } catch (Exception $e) {
        return ["❌ Failed to send OTP. Mailer Error: " . $mail->ErrorInfo, "alert-danger"];
    }
}

// ✅ Send OTP only if not sent in last 5 mins
$stmt = $conn->prepare("SELECT * FROM user_otps WHERE email = ? AND created_at >= NOW() - INTERVAL 5 MINUTE ORDER BY id DESC LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    list($message, $message_class) = sendOtp($email, $conn);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ✅ Verify OTP
    if (isset($_POST['verify_otp'])) {
        $otp = trim($_POST['otp']);
        $stmt = $conn->prepare("SELECT * FROM user_otps WHERE email = ? AND otp = ? AND created_at >= NOW() - INTERVAL 5 MINUTE");
        $stmt->bind_param("ss", $email, $otp);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            // Mark user as verified
            $stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();

            // Delete OTPs
            $stmt = $conn->prepare("DELETE FROM user_otps WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();

            // Fetch user info
            $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            // ✅ Set session + redirect
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $email;
            unset($_SESSION['pending_email']);

            header("Location: preference.php");
            exit;
        } else {
            $message = "❌ Invalid or expired OTP.";
            $message_class = "alert-danger";
        }
    }

    // ✅ Resend OTP manually
    if (isset($_POST['resend_otp'])) {
        list($message, $message_class) = sendOtp($email, $conn);
    }
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Verify Email - Tasty Hub</title>
  <link href="img/favicon.ico" rel="icon">
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
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: url('img/bg-hero.jpg') center/cover no-repeat;
    filter: blur(5px);
    z-index: 1;
}

.navbar {
    z-index: 2;
    position: relative;
}

.container-center {
    position: relative;
    z-index: 2;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: calc(100vh - 76px);
    padding-bottom: 80px;
}


.card {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 8px;
    padding: 1.5rem;
    width: 100%;
    max-width: 360px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    text-align: center;
}

.card h3 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.card p {
    font-size: 0.9rem;
    margin-bottom: 1rem;
}
</style>

</head>
<body>
          <div class="container-xxl bg-dark p-0">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4 px-lg-5 py-3 py-lg-0">
        <a href="index.php" class="navbar-brand p-0">
            <h2 class="text-primary m-0">
                <img src="img/logo_new.png" alt="Logo" style="width:50px;height:50px;"> Tasty Hub
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

    <!-- Centered OTP Card -->
    <div class="container-center">
        <div class="card">
            <h3 class="mb-4">Verify Your Email</h3>

            <?php if (!empty($message)): ?>
                <div class="alert <?= $message_class ?>"><?= $message ?></div>
            <?php endif; ?>

            <?php if (!$verified): ?>
                <p>Enter the OTP sent to <strong><?= htmlspecialchars($email) ?></strong></p>

                <form method="POST" class="mb-3">
                    <input type="text" name="otp" class="form-control mb-3 text-center" maxlength="6" placeholder="Enter OTP" required>
                    <button type="submit" name="verify_otp" class="btn btn-primary w-100">Verify</button>
                </form>

                <form method="POST">
                    <button type="submit" name="resend_otp" class="btn btn-outline-secondary w-100">Resend OTP</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>

</html>
