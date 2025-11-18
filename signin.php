<?php
session_start();
include 'db.php'; // Ensure this file properly connects to your database

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php"); // Redirect logged-in users
    exit;
}

$error = ""; // store error messages

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password, role, accstatus, status_updated_at, is_verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $username, $hashed_password, $role, $accstatus, $statusUpdatedAt, $is_verified);
        $stmt->fetch();

        if ($accstatus === "Banned") {
            $_SESSION['banned_user'] = $username;
            $_SESSION['banned_user_id'] = $id;
            header("Location: appeal.php");
            exit;
        }

        if (strpos($accstatus, "Suspended") !== false) {
            $days = ($accstatus === "Suspended (7 days)") ? 7 : (($accstatus === "Suspended (30 days)") ? 30 : 0);

            $suspensionEnd = new DateTime($statusUpdatedAt);
            $suspensionEnd->modify("+$days days");
            $now = new DateTime();
            $interval = $now->diff($suspensionEnd);

            if ($interval->invert == 0 && $interval->days > 0) {
                $_SESSION['suspended_days'] = $interval->days;
                $_SESSION['suspended_user'] = $username;
                $_SESSION['suspended_user_id'] = $id;
                header("Location: appeal.php");
                exit;
            } else {
                $accstatus = "Active";
                $stmtUpdate = $conn->prepare("UPDATE users SET accstatus = ? WHERE id = ?");
                $stmtUpdate->bind_param("si", $accstatus, $id);
                $stmtUpdate->execute();
                $stmtUpdate->close();

                $message = "Your suspension period has ended. Your account is now active again.";
                $stmtNotify = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                $stmtNotify->bind_param("is", $id, $message);
                $stmtNotify->execute();
                $stmtNotify->close();
            }
        }
if ($is_verified == 0) {
    $_SESSION['pending_email'] = $email; // store email securely
    $error = "⚠️ Please verify your email with the OTP before logging in. 
              <a href='verify_otp.php'>Resend OTP</a>";
} 
elseif (password_verify($password, $hashed_password)) {
    // login successful
    $_SESSION['user_id'] = $id;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;

    if ($role === 'admin') {
        header("Location: admin.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
} else {
    $error = "Invalid email or password. Please try again.";
}

    } else {
        $error = "Invalid email or password. Please try again.";
    }

    $stmt->close();
}
$conn->close();
?>

    
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Tasty Hub</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

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
</head>
    <style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap');
* {
  box-sizing: border-box;
  font-family: "Poppins", sans-serif;
}
html, body {
    max-width: 100% !important;
    overflow-x: hidden !important;
}
body {
    background-image: url('img/bg-hero.jpg'); 
    background-size: cover; 
    background-position: center;"
    position: relative; 
    overflow: hidden; 
}

body::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: url('img/bg-hero.jpg'); /* Background image */
    background-size: cover; 
    background-position: center;
    filter: blur(3px); /
    z-index: 1; 
}
p.error {
  color: red;
  font-size: 0.65rem;
  display: block;
  margin-bottom: 0px;
}

.container {
  margin-top: 100px;
  position: relative;
  max-width: 850px;
  width: 100%;
  border-radius: 8px;
  background: white;
  padding: 20px 25px;
  box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
  perspective: 2700px;
  z-index: 2;
}
.container .cover {
  position: absolute;
  top: 0;
  left: 50%;
  height: 100%;
  width: 50%;
  z-index: 98;
  transition: all 1s ease;
  transform-origin: left;
  transform-style: preserve-3d;
  backface-visibility: hidden;
}
.container #flipp:checked ~ .cover {
  transform: rotateY(-180deg);
}
.container #flipp:checked ~ .forms .login-form {
  pointer-events: none;
}
.container .cover .front,
.container .cover .back {
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  width: 100%;
  border-radius: 8px;
}
.cover .back {
  transform: rotateY(180deg);
}
.container .cover img {
  position: absolute;
  height: 100%;
  width: 100%;
  object-fit: cover;
  z-index: 10;
}
.container .cover .text {
  position: absolute;
  z-index: 10;
  height: 100%;
  width: 100%;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}
.container .cover .text::before {
  content: '';
  position: absolute;
  height: 100%;
  width: 100%;
  opacity: 0.5;
  background:  #FEA116;
}
.cover .text .text-1,
.cover .text .text-2 {
  z-index: 20;
  font-size: 20px;
  font-weight: 600;
  color: #fff;
  text-align: center;
}
.cover .text .text-2 {
  font-size: 15px;
  font-weight: 500;
}
.container .forms {
  height: 100%;
  width: 100%;
  background: #fff;
}
.container .form-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.form-content .login-form,
.form-content .signup-form {
  width: calc(100% / 2 - 25px);
}
.forms .form-content .title {
  position: relative;
  font-size: 24px;
  font-weight: 500;
  color: #333;
}
.forms .form-content .title:before {
  content: '';
  position: absolute;
  left: 0;
  bottom: 0;
  height: 3px;
  width: 25px;
  background: #FEA116;
}
.forms .signup-form .title:before {
  width: 20px;
}
.forms .form-content .input-boxes {
  margin-top: 20px;
}
.forms .form-content .input-box {
  display: flex;
  align-items: center;
  height: 47px;
  width: 100%;
  margin: 8px 0;
  position: relative;
}
.form-content .input-box input {
  height: 100%;
  width: 100%;
  outline: none;
  border: none;
  padding: 0 30px;
  font-size: 14px;
  font-weight: 500;
  border-bottom: 2px solid rgba(0, 0, 0, 0.2);
  transition: all 0.3s ease;
}
.form-content .input-box input:focus,
.form-content .input-box input:valid {
  border-color:  #FEA116;
}
.form-content .input-box i {
  position: absolute;
  color:  #FEA116;
  font-size: 15px;
}
.forms .form-content .text {
  font-size: 14px;
  font-weight: 500;
  color: #333;
}
.forms .form-content .text a {
  text-decoration: none;
}
.forms .form-content .text a:hover {
  text-decoration: underline;
}
.forms .form-content .button {
  color: #fff;
}
.forms .form-content .button input {
  color: #fff;
  background:  #FEA116;
  border-radius: 6px;
  padding: 0;
  cursor: pointer;
  transition: all 0.4s ease;
}
.forms .form-content .button input:hover {
  background: #FEB13D;
}

/* Disabled button styling */
.forms .form-content .button input:disabled {
  background: #ccc;
  cursor: not-allowed;
  opacity: 0.6;
}
.forms .form-content .button input:disabled:hover {
  background: #ccc;
}

.forms .form-content label {
  color: #FEA116;
  cursor: pointer;
}
.forms .form-content label:hover {
  text-decoration: underline;
}
.forms .form-content .login-text,
.forms .form-content .sign-up-text {
  text-align: center;
  margin-top: 5px;
}
.container #flipp {
  display: none;
}

/* Terms and conditions checkbox styling */
.terms-checkbox {
  display: flex;
  align-items: flex-start;
  margin: 10px 0;
  gap: 10px;
}

.terms-checkbox input[type="checkbox"] {
  width: 18px;
  height: 18px;
  margin: 0;
  flex-shrink: 0;
  accent-color: #FEA116;
  cursor: pointer;
}

.terms-label {
  font-size: 13px;
  color: #555;
  line-height: 1.4;
  cursor: pointer;
  margin: 0;
}

.terms-link {
  color: #FEA116;
  text-decoration: none;
  font-weight: 500;
}

.terms-link:hover {
  text-decoration: underline;
}


        .form-content .input-box input:focus,
        .form-content .input-box input:valid {
          border-color:  #FEA116;
        }
        .form-content .input-box i {
          position: absolute;
          color:  #FEA116;
          font-size: 17px;
        }

        /* Email hint icon styling */
        .email-hint-icon {
          position: absolute !important;
          right: 8px;
          color: #999 !important;
          font-size: 16px !important;
          cursor: pointer;
          transition: all 0.3s ease;
          z-index: 10;
        }

        .email-hint-icon:hover {
          color: #FEA116 !important;
          transform: scale(1.1);
        }

          /* Floating email hint tooltip */
          .email-hint {
            position: absolute;
            top: 38%;        /* vertically centered with input */
            left: 55%;      /* place to the right of input box */
            transform: translateY(-50%);
            z-index: 30;
            background: linear-gradient(135deg, #f8f9ff 0%, #e8f4fd 100%);
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            padding: 8px 12px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s ease;
          }
          /* Show state */
          .email-hint.show {
            opacity: 1;
            pointer-events: auto;
          }

          .email-hint-content i {
            color: #28a745 !important;
            font-size: 14px !important;
            margin-right: 8px;
          }

          .email-hint-content span {
            font-size: 12px;
            color: #555;
            line-height: 1.4;
          }
@media (max-width: 730px) {
  .container .cover {
    display: none;
  }
  .container {
    margin-top: 50px;
    border-radius: 0px;
  }
  .form-content .login-form,
  .form-content .signup-form {
    width: 100%;
  }

  .forms .form-content .input-box {
  display: flex;
  align-items: center;
  height: 47px;
  width: 100%;
  margin: 20px 0;
  position: relative;
}
.form-content .input-box input {
  height: 100%;
  width: 100%;
  outline: none;
  border: none;
  padding: 0 30px;
  font-size: 14px;
  font-weight: 500;
  border-bottom: 2px solid rgba(0, 0, 0, 0.2);
  transition: all 0.3s ease;
}
  .container #flipp:checked ~ .forms .signup-form {
    display: block;
  }
  .container #flipp:checked ~ .forms .login-form {
    display: none;
  }
  #email-hint {
    position: absolute;
    transform: none;
    left: 200px;
    top: 180px; 
    width: 100%;
  }
}
/* Very small devices (phones under 480px) */
@media (max-width: 480px) {
  body {
    background-size: cover;
  }
  .container .cover {
    display: none;
  }
  .container {
        margin-top: 20px;
    border-radius: 0px;
  }

  .form-content .title {
    font-size: 18px;
  }

  .cover .text .text-1 {
    font-size: 18px;
  }

  .cover .text .text-2 {
    font-size: 12px;
  }

  .form-content .input-box input {
    font-size: 13px;
  }

  .forms .form-content .button input {
    font-size: 14px;
    padding: 8px;
  }

  .terms-label {
    font-size: 12px;
  }
  
  .terms-checkbox input[type="checkbox"] {
    width: 16px;
    height: 16px;
  }
  
  #email-hint {
    position: absolute;
    transform: none;
    left: 50px;
    top: 180px;
    width: 100%;
  }
}
    </style>
<body>
            <div class="container-xxl bg-white p-0">
            <!-- Spinner Start -->
            <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>

            <!-- Spinner End -->
    <div class="container-xxl p-0">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4 px-lg-5 py-3 py-lg-0">
            <a href="index.php" class="navbar-brand p-0">
              <h2 class="text-primary m-0"><img src="img/logo_new.png" alt="Logo" style="width: 50px; height: 50px;"></i>Tasty Hub</h2>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="fa fa-bars"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav ms-auto py-0 pe-4">
                    <a href="index.php" class="nav-item nav-link">Home</a>
                    <a href="guestdashboard.php" class="nav-item nav-link ">Browse</a>
                    <a href="guestabout.html" class="nav-item nav-link">About</a>
                </div>
            </div>
        </nav>


                
  <div class="container">
    <input type="checkbox" id="flipp">
    <div class="cover">
      <div class="front">
        <img src="img/about-2.jpg" alt="">
        <div class="text">
          <span class="text-1">Discover. Create. <br> Share.</span>
          <span class="text-2">Let's get connected</span>
        </div>
      </div>
      <div class="back">
        <img class="backImg" src="img/about-1.jpg" alt="">
        <div class="text">
          <span class="text-1">Complete miles of journey <br> with one step</span>
          <span class="text-2">Let's get started</span>
        </div>
      </div>
    </div>
    <div class="forms">
        <div class="form-content">
<div class="login-form">
  <div class="title">Sign In</div>
  <form id="loginForm" action="signin.php" method="POST" novalidate>
    <div class="input-boxes">

      <!-- Email -->
      <div class="input-box">
        <i class="fas fa-envelope"></i>
        <input type="text" class="form-control" id="login-email" name="email" placeholder="Enter your email" required>
      </div>
      <p id="login-email-error" class="error"></p>

      <!-- Password -->
      <div class="input-box">
        <i class="fas fa-lock"></i>
        <input type="password" class="form-control" id="login-password" name="password" placeholder="Enter your password" required>
      </div>
      <p id="login-password-error" class="error"></p>

      <div class="text"><a href="forgot_password.php">Forgot password?</a></div>

      <!-- Submit -->
      <div class="button input-box">
        <input type="submit" value="Submit">
      </div>

      <div class="text sign-up-text">Don't have an account? <label for="flipp">Sign Up Now</label></div>
    </div>
  </form>
</div>

<div class="signup-form">
  <div class="title">Sign Up</div>
  <form action="signup.php" method="POST">
    <div class="input-boxes">
      
      <!-- Username -->
      <div class="input-box">
        <i class="fas fa-user"></i>
        <input type="text" class="form-control" id="username" name="username" 
               placeholder="Enter your username" 
               value="<?= $_SESSION['signup_old']['username'] ?? '' ?>" required>
      </div>
      <?php if (!empty($_SESSION['signup_errors']['username'])): ?>
        <p class="error"><?= $_SESSION['signup_errors']['username'] ?></p>
      <?php endif; ?>

      <!-- Email -->
      <div class="input-box email-input-container">
        <i class="fas fa-envelope"></i>
        <input type="text" class="form-control" id="email" name="email" 
               placeholder="Enter your email"
               value="<?= $_SESSION['signup_old']['email'] ?? '' ?>" required>
                <i class="fas fa-info-circle email-hint-icon" 
                  onclick="toggleEmailInfo()"
                 title="Click for email requirements"></i>
      </div>
      <?php if (!empty($_SESSION['signup_errors']['email'])): ?>
        <p class="error"><?= $_SESSION['signup_errors']['email'] ?></p>
      <?php endif; ?>
                                  <div class="email-hint" id="email-hint">
                              <div class="email-hint-content">
                                <i class="fas fa-check-circle"></i>
                                <span>Use a valid email address for password reset.</span>
                              </div>
                            </div>
      <!-- Password -->
      <div class="input-box">
        <i class="fas fa-lock"></i>
        <input type="password" class="form-control" id="password" name="password" 
               placeholder="Enter your password" required>
      </div>
      <?php if (!empty($_SESSION['signup_errors']['password'])): ?>
        <p class="error"><?= $_SESSION['signup_errors']['password'] ?></p>
      <?php endif; ?>

      <!-- Confirm Password -->
      <div class="input-box">
        <i class="fas fa-lock"></i>
        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
               placeholder="Confirm password" required>
      </div>
      <?php if (!empty($_SESSION['signup_errors']['confirm_password'])): ?>
        <p class="error"><?= $_SESSION['signup_errors']['confirm_password'] ?></p>
      <?php endif; ?>

      <!-- Terms -->
      <div class="terms-checkbox">
        <input type="checkbox" id="termsCheck" name="terms" required>
        <p for="termsCheck" class="terms-label">
          I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#legalModal" data-tab="terms" class="terms-link">Terms and Conditions</a> and <a href="#" data-bs-toggle="modal" data-bs-target="#legalModal" data-tab="privacy" class="terms-link">Privacy Policy</a>
        </p>
      </div>

      <!-- Submit -->
      <div class="button input-box">
        <input type="submit" id="signupSubmit" value="Submit">
      </div>
      </div> <div class="text sign-up-text">Already have an account? <label for="flipp">Sign In Now</label></div> </div>
    </div>
  </form>
</div>

<?php
// clear errors after showing them
unset($_SESSION['signup_errors']);
unset($_SESSION['signup_old']);
?>

    </div>
    </div>
  </div>

<!-- Modern Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg border-0 rounded-4">
      
      <!-- Icon + Title -->
      <div class="modal-header border-0 d-flex flex-column align-items-center pt-2">
        <h5 class="modal-title mt-2 fw-bold text-danger">Sign In Error</h5>
      </div>
      
      <!-- Message -->
      <div class="modal-body text-center">
<p class="text-muted fs-6 mb-0"><?= $error ?? "" ?></p>
      </div>
      
      <!-- Button -->
      <div class="modal-footer border-0 d-flex justify-content-center pb-4">
        <button type="button" class="btn btn-danger px-4 rounded-md" data-bs-dismiss="modal">
          Try Again
        </button>
      </div>
    </div>
  </div>
</div>



<!-- Legal Modal -->
<div class="modal fade" id="legalModal" tabindex="-1" aria-labelledby="legalModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <!-- Modal Header -->
      <div class="modal-header">
        <h5 class="modal-title" id="legalModalLabel">Tasty Hub - Legal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Modal Nav Tabs -->
      <ul class="nav nav-tabs px-3 pt-2" id="legalTab" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="privacy-tab" data-bs-toggle="tab" data-bs-target="#privacy" type="button" role="tab" aria-controls="privacy" aria-selected="true">Privacy Policy</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="terms-tab" data-bs-toggle="tab" data-bs-target="#terms" type="button" role="tab" aria-controls="terms" aria-selected="false">Terms & Conditions</button>
        </li>
      </ul>

      <!-- Modal Body -->
      <div class="modal-body">
        <div class="tab-content" id="legalTabContent">
          
          <!-- Privacy Tab -->
          <div class="tab-pane fade show active" id="privacy" role="tabpanel" aria-labelledby="privacy-tab">
                <p>Welcome to Tasty Hub, a community-driven recipe-sharing website. Your privacy is important to us, and this Privacy Policy outlines how we collect, use, disclose, and protect your information when you use our services.</p>
                
                <h6>1. Information We Collect</h6>
                <p>At Tasty Hub, we practice data minimization and only collect essential information needed to provide our services:</p>
                <p><strong>Personal Information:</strong><br>
                - <strong>Username:</strong> Your unique identifier on our platform, enabling you to create and manage recipes, interact with other users, and build your culinary profile within our community.<br>
                - <strong>Email Address:</strong> Used exclusively for essential communications including account verification, password recovery, important service updates, and optional community newsletters (which you can unsubscribe from at any time).</p>
                <p><strong>Automatically Collected Information:</strong><br>
                - <strong>Usage Data:</strong> We collect information about how you interact with our platform, including pages visited, time spent, and features used to improve user experience.<br>
                - <strong>Technical Data:</strong> IP address, browser type, device information, and operating system for security purposes and platform optimization.<br>
                - <strong>Cookies:</strong> We use essential cookies for site functionality and optional analytics cookies (with your consent) to understand user preferences and improve our services.</p>

                <h6>2. User-Generated Content</h6>
                <p>Your creative contributions are the heart of Tasty Hub's community. When you submit recipes, you maintain ownership of your content while granting us specific rights for platform operation:</p>
                <p>- <strong>Content Ownership:</strong> You retain full ownership and copyright of all recipes, images, and content you create.<br>
                - <strong>Platform License:</strong> By submitting content, you grant Tasty Hub a non-exclusive, worldwide, royalty-free license to use, reproduce, modify, and display your content on our platform and in promotional materials.<br>
                - <strong>Future Publications:</strong> We may feature selected community recipes in cookbooks or other publications. If your recipe is chosen, we will notify you in advance, provide proper attribution, and may offer compensation for featured content.<br>
                - <strong>Content Responsibility:</strong> You are responsible for ensuring your submitted content is original, accurate, and does not infringe on others' intellectual property rights.</p>

                <h6>3. How We Use Your Information</h6>
                <p>We use collected information solely for legitimate business purposes to enhance your Tasty Hub experience:</p>
                <p><strong>Core Services:</strong><br>
                - Account creation, management, and authentication<br>
                - Recipe submission, editing, and deletion capabilities<br>
                - Community features including comments, ratings, and user interactions<br>
                - Content moderation through our admin approval system</p>
                <p><strong>Communications:</strong><br>
                - Essential account notifications and security alerts<br>
                - Service updates and policy changes<br>
                - Optional community newsletters and featured content highlights<br>
                - Response to user inquiries and customer support</p>
                <p><strong>Platform Improvement:</strong><br>
                - Analytics to understand user preferences and platform usage<br>
                - Feature development based on community needs<br>
                - Security monitoring and fraud prevention</p>

                <h6>4. Information Sharing and Disclosure</h6>
                <p>We respect your privacy and do not sell, rent, or trade your personal information. We may share information only in these limited circumstances:</p>
                <p><strong>With Your Explicit Consent:</strong> We will ask for your permission before sharing personal information for purposes not covered in this policy.</p>
                <p><strong>Trusted Service Providers:</strong> We work with carefully vetted third-party companies for:<br>
                - Website hosting and cloud storage services<br>
                - Email delivery and communication tools<br>
                - Analytics and performance monitoring<br>
                - Payment processing (if applicable)<br>
                All service providers are bound by strict confidentiality agreements and may only use your information to provide services on our behalf.</p>
                <p><strong>Legal Requirements:</strong> We may disclose information when required by law, court order, or to protect the rights, property, or safety of Tasty Hub, our users, or others.</p>
                <p><strong>Business Transfers:</strong> In the event of a merger, acquisition, or sale of assets, user information may be transferred as part of the business transaction, with continued protection under this privacy policy.</p>

                <h6>5. Data Security and Protection</h6>
                <p>We implement comprehensive security measures to protect your personal information:</p>
                <p><strong>Technical Safeguards:</strong><br>
                - SSL encryption for all data transmission<br>
                - Secure database storage with access controls<br>
                - Regular security audits and vulnerability assessments<br>
                - Automated backup systems with encryption</p>
                <p><strong>Administrative Safeguards:</strong><br>
                - Limited employee access to personal data on a need-to-know basis<br>
                - Regular security training for staff<br>
                - Incident response procedures for potential breaches</p>
                <p>While we employ industry-standard security measures, no internet transmission or electronic storage is 100% secure. We encourage users to use strong passwords and keep login credentials confidential.</p>

                <h6>6. Your Privacy Rights</h6>
                <p>You have comprehensive control over your personal information and can exercise the following rights at any time:</p>
                <p><strong>Access Rights:</strong> Request a copy of all personal information we hold about you, including how it's used and shared.</p>
                <p><strong>Correction Rights:</strong> Update or correct any inaccurate personal information in your profile or account settings.</p>
                <p><strong>Deletion Rights:</strong> Request complete account deletion, which will remove all personal information and user-generated content from our systems within 30 days.</p>
                <p><strong>Data Portability:</strong> Request your data in a machine-readable format for transfer to another service.</p>
                <p><strong>Communication Preferences:</strong> Opt out of non-essential communications while maintaining account functionality.</p>
                <p>To exercise these rights, contact us at <a href="mailto:tastyhub@gmail.com" class="text-primary hover:underline">tastyhub@gmail.com</a> with your request and account information.</p>

                <h6>7. Data Retention</h6>
                <p>We retain your information only as long as necessary to provide services and fulfill legal obligations:</p>
                <p>- <strong>Active Accounts:</strong> Information is retained while your account remains active<br>
                - <strong>Deleted Accounts:</strong> Personal information is deleted within 30 days of account closure<br>
                - <strong>Legal Requirements:</strong> Some information may be retained longer to comply with legal obligations or resolve disputes</p>

                <h6>8. International Data Transfers</h6>
                <p>Tasty Hub operates primarily in the Philippines. If you access our services from other countries, your information may be transferred to and processed in the Philippines, where our servers and primary operations are located. We ensure appropriate safeguards are in place for any international transfers.</p>

                <h6>9. Children's Privacy</h6>
                <p>Tasty Hub is designed for users of all ages. However, we do not knowingly collect personal information from children under 13 without parental consent. If you believe a child has provided personal information without consent, please contact us immediately.</p>

                <h6>10. Changes to This Privacy Policy</h6>
                <p>We may update this Privacy Policy periodically to reflect changes in our practices or legal requirements. Significant changes will be communicated through:</p>
                <p>- Email notification to registered users<br>
                - Prominent notice on our website<br>
                - Updated effective date at the top of this policy</p>
                <p>Your continued use of Tasty Hub after changes take effect constitutes acceptance of the updated policy.</p>

                <h6>11. Contact Information</h6>
                <p>For questions, concerns, or requests regarding this Privacy Policy or our data practices, please contact us:</p>
                <p><strong>Email:</strong> <a href="mailto:tastyhub@gmail.com" class="text-primary hover:underline">tastyhub@gmail.com</a><br>
                <strong>Subject Line:</strong> Privacy Policy Inquiry<br>
                <strong>Response Time:</strong> We aim to respond to all privacy-related inquiries within 48 hours.</p>          </div>

          <!-- Terms Tab -->
          <div class="tab-pane fade" id="terms" role="tabpanel" aria-labelledby="terms-tab">
          <h6 class="text-muted">Effective Date: April 10, 2025</h6>
                <p>Welcome to Tasty Hub, a community-driven recipe-sharing website where users can discover, create, and share their own recipes. By accessing or using Tasty Hub, you agree to comply with and be bound by these Terms and Conditions. If you do not agree with any part of these Terms, you must not use our services.</p>
                
                <h6>1. User Eligibility and Account Creation</h6>
                <p><strong>General Eligibility:</strong> Tasty Hub welcomes all users who share a passion for food and cooking. Our platform is especially designed for food enthusiasts who want to contribute to and learn from our vibrant culinary community.</p>
                <p><strong>Account Requirements:</strong><br>
                - Users must provide accurate and truthful information during registration<br>
                - Each user is limited to one account to maintain community integrity<br>
                - Users under 13 require parental consent to create an account<br>
                - Account holders are responsible for maintaining the security of their login credentials</p>
                <p><strong>Account Termination:</strong> We reserve the right to suspend or terminate accounts that violate these terms, engage in fraudulent activity, or compromise the safety and integrity of our community.</p>
                
                <h6>2. User Responsibilities and Community Standards</h6>
                <p>As a valued member of the Tasty Hub community, you agree to uphold our standards and contribute positively to the platform:</p>
                <p><strong>Content Integrity:</strong><br>
                - Provide accurate, original, and truthful information in all recipe submissions<br>
                - Ensure recipes are tested and safe for consumption<br>
                - Include proper attribution when adapting recipes from other sources<br>
                - Avoid submitting duplicate or spam content</p>
                <p><strong>Prohibited Activities:</strong><br>
                - Engaging in misinformation, plagiarism, or deceptive practices<br>
                - Harassing, threatening, discriminating against, or abusing other users<br>
                - Posting content that is illegal, harmful, or violates intellectual property rights<br>
                - Attempting to circumvent our content moderation systems<br>
                - Using automated tools or bots to create accounts or submit content</p>
                <p><strong>Community Interaction:</strong><br>
                - Treat all community members with respect and courtesy<br>
                - Provide constructive feedback and helpful suggestions<br>
                - Report inappropriate content or behavior to our moderation team<br>
                - Respect cultural diversity in cooking styles and dietary preferences</p>
                <p><strong>Legal Compliance:</strong> Users must comply with all applicable local, national, and international laws while using our services.</p>
                
                <h6>3. User-Generated Content and Licensing</h6>
                <p><strong>Content Ownership:</strong> You retain full ownership and copyright of all original content you submit to Tasty Hub, including recipes, images, videos, and written descriptions.</p>
                <p><strong>Platform License:</strong> By submitting content, you grant Tasty Hub a non-exclusive, worldwide, royalty-free, transferable license to:</p>
                <p>- Use, reproduce, modify, adapt, and display your content on our platform<br>
                - Distribute your content through our website, mobile applications, and related services<br>
                - Create derivative works for promotional and marketing purposes<br>
                - Include your content in compilations, cookbooks, or other publications</p>
                <p><strong>Future Commercial Use:</strong> We may feature selected community recipes in printed cookbooks, digital publications, or promotional materials. Contributors will be notified in advance and receive appropriate credit. For commercial publications, we may offer compensation or revenue sharing.</p>
                <p><strong>Content Removal:</strong> You may delete your content at any time through your account settings. However, content that has been shared, republished, or incorporated into derivative works may continue to exist.</p>
                
                <h6>4. Content Monitoring, Moderation, and Approval Process</h6>
                <p><strong>Quality Assurance:</strong> Tasty Hub employs a comprehensive admin approval system to ensure all user-generated content meets our quality standards before publication. This process helps maintain the integrity and safety of our culinary community.</p>
                <p><strong>Review Process:</strong><br>
                - All submitted recipes undergo initial automated screening for obvious violations<br>
                - Content is then reviewed by our moderation team within 24-48 hours<br>
                - Recipes are evaluated for accuracy, safety, clarity, and community guidelines compliance<br>
                - Approved content is published and becomes visible to the community</p>
                <p><strong>Content Standards:</strong> Submissions must meet the following criteria:<br>
                - Clear, complete ingredient lists with accurate measurements<br>
                - Step-by-step instructions that are easy to follow<br>
                - Safe cooking methods and food handling practices<br>
                - Appropriate images that represent the actual recipe<br>
                - Original content or properly attributed adaptations</p>
                <p><strong>Rejection and Appeals:</strong> If content is rejected, you will receive notification with specific reasons. Rejected content will appear in your profile's "Declined" section. You may revise and resubmit content or appeal the decision by contacting our support team.</p>
                
                <h6>5. Intellectual Property Rights and Protection</h6>
                <p><strong>Tasty Hub Property:</strong> All platform elements including but not limited to website design, software, logos, trademarks, graphics, and proprietary features are the exclusive property of Tasty Hub and protected by copyright, trademark, and other intellectual property laws.</p>
                <p><strong>User Content Rights:</strong> While users retain ownership of their submitted recipes, they grant Tasty Hub the licensing rights outlined in Section 3. Users are responsible for ensuring they have the right to submit and license any content they share.</p>
                <p><strong>Third-Party Content:</strong> Users must respect the intellectual property rights of others. Submission of copyrighted material without permission is strictly prohibited and may result in account suspension or termination.</p>
                <p><strong>DMCA Compliance:</strong> We respond promptly to valid copyright infringement notices. Rights holders may report violations through our designated copyright agent contact information.</p>
                
                <h6>6. Limitation of Liability and Disclaimers</h6>
                <p><strong>Platform Disclaimer:</strong> Tasty Hub provides a platform for sharing recipes and culinary information. We do not guarantee the accuracy, completeness, safety, or reliability of any user-generated content. Users follow recipes and cooking advice at their own risk.</p>
                <p><strong>Health and Safety:</strong> We strongly encourage users to:<br>
                - Consider food allergies and dietary restrictions when trying new recipes<br>
                - Follow proper food safety and handling procedures<br>
                - Consult healthcare providers for specific dietary needs or health conditions<br>
                - Use common sense and cooking experience when interpreting recipes</p>
                <p><strong>Limitation of Liability:</strong> To the fullest extent permitted by law, Tasty Hub shall not be liable for any indirect, incidental, special, consequential, or punitive damages arising from your use of the platform or any user-generated content. Our total liability shall not exceed the amount paid by you (if any) for using our services.</p>
                <p><strong>Service Availability:</strong> While we strive for continuous service, we do not guarantee uninterrupted access to Tasty Hub. The platform may be temporarily unavailable due to maintenance, updates, or technical issues.</p>
                
                <h6>7. Privacy and Data Protection</h6>
                <p>Your privacy is important to us. Our collection, use, and protection of your personal information is governed by our Privacy Policy, which is incorporated into these Terms by reference. By using Tasty Hub, you consent to our data practices as described in the Privacy Policy.</p>
                
                <h6>8. Dispute Resolution and Governing Law</h6>
                <p><strong>Governing Law:</strong> These Terms and Conditions are governed by the laws of the Republic of the Philippines, without regard to conflict of law principles.</p>
                <p><strong>Dispute Resolution:</strong> Any disputes arising from these terms or your use of Tasty Hub will be resolved through:<br>
                1. Direct communication with our support team<br>
                2. Mediation if direct resolution is unsuccessful<br>
                3. Binding arbitration in the Philippines as a final resort</p>
                <p><strong>Class Action Waiver:</strong> You agree to resolve disputes individually and waive the right to participate in class action lawsuits.</p>
                
                <h6>9. Modifications to Terms and Service</h6>
                <p><strong>Terms Updates:</strong> Tasty Hub reserves the right to modify these Terms and Conditions at any time to reflect changes in our services, legal requirements, or business practices.</p>
                <p><strong>Notification Process:</strong> Significant changes will be communicated through:<br>
                - Email notification to registered users at least 30 days in advance<br>
                - Prominent notice on our website homepage<br>
                - Updated effective date at the top of these Terms</p>
                <p><strong>Acceptance:</strong> Your continued use of Tasty Hub after changes take effect constitutes acceptance of the modified Terms. If you disagree with changes, you may terminate your account before the effective date.</p>
                <p><strong>Service Modifications:</strong> We may modify, suspend, or discontinue any aspect of our services at any time. We will provide reasonable notice for significant service changes that materially affect user experience.</p>
                
                <h6>10. Account Termination and Data Retention</h6>
                <p><strong>Voluntary Termination:</strong> You may delete your account at any time through your account settings. Upon deletion, your personal information will be removed within 30 days, though some content may remain for legal or operational purposes.</p>
                <p><strong>Involuntary Termination:</strong> We may suspend or terminate accounts that violate these Terms, engage in harmful behavior, or compromise platform security. Terminated users will receive notification with specific reasons when possible.</p>
                <p><strong>Data Retention:</strong> After account termination, we may retain certain information as required by law, for fraud prevention, or to resolve disputes. Retained data is subject to our Privacy Policy.</p>
                
                <h6>11. Severability and Entire Agreement</h6>
                <p>If any provision of these Terms is found to be unenforceable or invalid, that provision will be limited or eliminated to the minimum extent necessary so that these Terms shall otherwise remain in full force and effect. These Terms, along with our Privacy Policy, constitute the entire agreement between you and Tasty Hub regarding your use of our services.</p>
                
                <h6>12. Contact Information and Support</h6>
                <p>For questions, concerns, or support regarding these Terms and Conditions or any aspect of Tasty Hub services, please contact us:</p>
                <p><strong>Email:</strong> <a href="mailto:tastyhub@gmail.com" class="text-primary hover:underline">tastyhub@gmail.com</a><br>
                <strong>Subject Line:</strong> Terms and Conditions Inquiry<br>
                <strong>Response Time:</strong> We aim to respond to all inquiries within 24-48 hours<br>
                <strong>Business Hours:</strong> Monday-Friday, 9:00 AM - 6:00 PM (Philippine Standard Time)</p>
                <p>Thank you for being part of the Tasty Hub community. We're excited to share this culinary journey with you!</p>          </div>

        </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($error)) : ?>
<script>
  document.addEventListener("DOMContentLoaded", function () {
    var errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
    errorModal.show();
  });
</script>
<?php endif; ?>


<script>
  // Auto-switch to correct tab when triggered
  const legalModal = document.getElementById('legalModal');
  legalModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const tab = button.getAttribute('data-tab');

    if (tab === "terms") {
      const termsTab = new bootstrap.Tab(document.querySelector('#terms-tab'));
      termsTab.show();
    } else {
      const privacyTab = new bootstrap.Tab(document.querySelector('#privacy-tab'));
      privacyTab.show();
    }
  });
</script>

    <style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap');
* {
  box-sizing: border-box;
  font-family: "Poppins", sans-serif;
}
html, body {
    max-width: 100% !important;
    overflow-x: hidden !important;
}
body {
    background-image: url('img/bg-hero.jpg'); 
    background-size: cover; 
    background-position: center;"
    position: relative; 
    overflow: hidden; 
}

body::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: url('img/bg-hero.jpg'); /* Background image */
    background-size: cover; 
    background-position: center;
    filter: blur(3px); /
    z-index: 1; 
}
p.error {
  color: red;
  font-size: 0.65rem;
  display: block;
  margin-bottom: 0px;
}

.container {
  margin-top: 100px;
  position: relative;
  max-width: 850px;
  width: 100%;
  border-radius: 8px;
  background: white;
  padding: 20px 25px;
  box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
  perspective: 2700px;
  z-index: 2;
}
.container .cover {
  position: absolute;
  top: 0;
  left: 50%;
  height: 100%;
  width: 50%;
  z-index: 98;
  transition: all 1s ease;
  transform-origin: left;
  transform-style: preserve-3d;
  backface-visibility: hidden;
}
.container #flipp:checked ~ .cover {
  transform: rotateY(-180deg);
}
.container #flipp:checked ~ .forms .login-form {
  pointer-events: none;
}
.container .cover .front,
.container .cover .back {
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  width: 100%;
  border-radius: 8px;
}
.cover .back {
  transform: rotateY(180deg);
}
.container .cover img {
  position: absolute;
  height: 100%;
  width: 100%;
  object-fit: cover;
  z-index: 10;
}
.container .cover .text {
  position: absolute;
  z-index: 10;
  height: 100%;
  width: 100%;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}
.container .cover .text::before {
  content: '';
  position: absolute;
  height: 100%;
  width: 100%;
  opacity: 0.5;
  background:  #FEA116;
}
.cover .text .text-1,
.cover .text .text-2 {
  z-index: 20;
  font-size: 20px;
  font-weight: 600;
  color: #fff;
  text-align: center;
}
.cover .text .text-2 {
  font-size: 15px;
  font-weight: 500;
}
.container .forms {
  height: 100%;
  width: 100%;
  background: #fff;
}
.container .form-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.form-content .login-form,
.form-content .signup-form {
  width: calc(100% / 2 - 25px);
}
.forms .form-content .title {
  position: relative;
  font-size: 24px;
  font-weight: 500;
  color: #333;
}
.forms .form-content .title:before {
  content: '';
  position: absolute;
  left: 0;
  bottom: 0;
  height: 3px;
  width: 25px;
  background: #FEA116;
}
.forms .signup-form .title:before {
  width: 20px;
}
.forms .form-content .input-boxes {
  margin-top: 20px;
}
.forms .form-content .input-box {
  display: flex;
  align-items: center;
  height: 47px;
  width: 100%;
  margin: 8px 0;
  position: relative;
}
.form-content .input-box input {
  height: 100%;
  width: 100%;
  outline: none;
  border: none;
  padding: 0 30px;
  font-size: 14px;
  font-weight: 500;
  border-bottom: 2px solid rgba(0, 0, 0, 0.2);
  transition: all 0.3s ease;
}
.form-content .input-box input:focus,
.form-content .input-box input:valid {
  border-color:  #FEA116;
}
.form-content .input-box i {
  position: absolute;
  color:  #FEA116;
  font-size: 15px;
}
.forms .form-content .text {
  font-size: 14px;
  font-weight: 500;
  color: #333;
}
.forms .form-content .text a {
  text-decoration: none;
}
.forms .form-content .text a:hover {
  text-decoration: underline;
}
.forms .form-content .button {
  color: #fff;
}
.forms .form-content .button input {
  color: #fff;
  background:  #FEA116;
  border-radius: 6px;
  padding: 0;
  cursor: pointer;
  transition: all 0.4s ease;
}
.forms .form-content .button input:hover {
  background: #FEB13D;
}

/* Disabled button styling */
.forms .form-content .button input:disabled {
  background: #ccc;
  cursor: not-allowed;
  opacity: 0.6;
}
.forms .form-content .button input:disabled:hover {
  background: #ccc;
}

.forms .form-content label {
  color: #FEA116;
  cursor: pointer;
}
.forms .form-content label:hover {
  text-decoration: underline;
}
.forms .form-content .login-text,
.forms .form-content .sign-up-text {
  text-align: center;
  margin-top: 5px;
}
.container #flipp {
  display: none;
}

/* Terms and conditions checkbox styling */
.terms-checkbox {
  display: flex;
  align-items: flex-start;
  margin: 10px 0;
  gap: 10px;
}

.terms-checkbox input[type="checkbox"] {
  width: 18px;
  height: 18px;
  margin: 0;
  flex-shrink: 0;
  accent-color: #FEA116;
  cursor: pointer;
}

.terms-label {
  font-size: 13px;
  color: #555;
  line-height: 1.4;
  cursor: pointer;
  margin: 0;
}

.terms-link {
  color: #FEA116;
  text-decoration: none;
  font-weight: 500;
}

.terms-link:hover {
  text-decoration: underline;
}


        .form-content .input-box input:focus,
        .form-content .input-box input:valid {
          border-color:  #FEA116;
        }
        .form-content .input-box i {
          position: absolute;
          color:  #FEA116;
          font-size: 17px;
        }

        /* Email hint icon styling */
        .email-hint-icon {
          position: absolute !important;
          right: 8px;
          color: #999 !important;
          font-size: 16px !important;
          cursor: pointer;
          transition: all 0.3s ease;
          z-index: 10;
        }

        .email-hint-icon:hover {
          color: #FEA116 !important;
          transform: scale(1.1);
        }

          /* Floating email hint tooltip */
          .email-hint {
            position: absolute;
            top: 38%;        /* vertically centered with input */
            left: 55%;      /* place to the right of input box */
            transform: translateY(-50%);
            z-index: 30;
            background: linear-gradient(135deg, #f8f9ff 0%, #e8f4fd 100%);
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            padding: 8px 12px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s ease;
          }
          /* Show state */
          .email-hint.show {
            opacity: 1;
            pointer-events: auto;
          }

          .email-hint-content i {
            color: #28a745 !important;
            font-size: 14px !important;
            margin-right: 8px;
          }

          .email-hint-content span {
            font-size: 12px;
            color: #555;
            line-height: 1.4;
          }
@media (max-width: 730px) {
  .container .cover {
    display: none;
  }
  .container {
    margin-top: 50px;
    border-radius: 0px;
  }
  .form-content .login-form,
  .form-content .signup-form {
    width: 100%;
  }
  .form-content .signup-form {
    display: none;
  }
  .forms .form-content .input-box {
  display: flex;
  align-items: center;
  height: 47px;
  width: 100%;
  margin: 20px 0;
  position: relative;
}
.form-content .input-box input {
  height: 100%;
  width: 100%;
  outline: none;
  border: none;
  padding: 0 30px;
  font-size: 14px;
  font-weight: 500;
  border-bottom: 2px solid rgba(0, 0, 0, 0.2);
  transition: all 0.3s ease;
}
  .container #flipp:checked ~ .forms .signup-form {
    display: block;
  }
  .container #flipp:checked ~ .forms .login-form {
    display: none;
  }
  #email-hint {
    position: absolute;
    transform: none;
    left: 200px;
    top: 180px; 
    width: 100%;
  }
}
/* Very small devices (phones under 480px) */
@media (max-width: 480px) {
  body {
    background-size: cover;
  }
  .container .cover {
    display: none;
  }
  .container {
        margin-top: 80px;
    border-radius: 0px;
  }

  .form-content .title {
    font-size: 18px;
  }

  .cover .text .text-1 {
    font-size: 18px;
  }

  .cover .text .text-2 {
    font-size: 12px;
  }

  .form-content .input-box input {
    font-size: 13px;
  }

  .forms .form-content .button input {
    font-size: 14px;
    padding: 8px;
  }
  
  .terms-label {
    font-size: 12px;
  }
  
  .terms-checkbox input[type="checkbox"] {
    width: 16px;
    height: 16px;
  }
  
  #email-hint {
    position: absolute;
    transform: none;
    left: 50px;
    top: 180px;
    width: 100%;
  }
}
    </style>


    </div>
</div>

<script>
    // When the page loads, check the URL for "signup=true"
    window.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('signup') === 'true') {
            const flippCheckbox = document.getElementById('flipp');
            if (flippCheckbox) {
                flippCheckbox.checked = true;
            }
        }
    });
</script>

<script> 
document.addEventListener("DOMContentLoaded", function() {
  const form = document.getElementById("loginForm");
  const emailInput = document.getElementById("login-email");
  const passwordInput = document.getElementById("login-password");

  form.addEventListener("submit", function(e) {
    let valid = true;

    // Clear old errors
    document.getElementById("login-email-error").textContent = "";
    document.getElementById("login-password-error").textContent = "";

    // Email validation
    const emailVal = emailInput.value.trim();
    const emailError = document.getElementById("login-email-error");

    if (emailVal === "") {
      emailError.textContent = "Email is required.";
      valid = false;
    } else if (emailVal !== "admin" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
      // Allow "admin" OR a valid email format
      emailError.textContent = "Please enter a valid email.";
      valid = false;
    }

    // Password validation
    const passVal = passwordInput.value.trim();
    const passError = document.getElementById("login-password-error");
    if (passVal === "") {
      passError.textContent = "Password is required.";
      valid = false;
    }

    // If invalid, stop submit
    if (!valid) {
      e.preventDefault();
    }
  });
});
</script>

    <script>
          let emailHintTimeout;

        // Enhanced email hint toggle function
        function toggleEmailInfo() {
            const hint = document.getElementById("email-hint");
            const icon = document.querySelector(".email-hint-icon");
            
            if (hint.classList.contains("show")) {
                hint.classList.remove("show");
                icon.style.color = "#999";
                clearTimeout(emailHintTimeout);

            } else {
                hint.classList.add("show");
                icon.style.color = "#FEA116";

                 // Auto-close after 5 seconds
                clearTimeout(emailHintTimeout);
                emailHintTimeout = setTimeout(() => {
                    hint.classList.remove("show");
                    icon.style.color = "#999";
                }, 2000); // 2000 ms = 2 sec
            }
        }

        // Close hint when clicking outside
        document.addEventListener('click', function(event) {
            const hint = document.getElementById("email-hint");
            const icon = document.querySelector(".email-hint-icon");
            const emailContainer = document.querySelector(".email-input-container");
            
            if (!emailContainer.contains(event.target) && hint.classList.contains("show")) {
                hint.classList.remove("show");
                icon.style.color = "#999";
                clearTimeout(emailHintTimeout);

            }
        });

        // Terms and Conditions checkbox functionality
        document.addEventListener('DOMContentLoaded', function() {
            const termsCheckbox = document.getElementById('termsCheck');
            const submitButton = document.getElementById('signupSubmit');
            
            // Function to toggle submit button state
            function toggleSubmitButton() {
                if (termsCheckbox.checked) {
                    submitButton.disabled = false;
                } else {
                    submitButton.disabled = true;
                }
            }
            
            // Listen for checkbox changes
            termsCheckbox.addEventListener('change', toggleSubmitButton);
            
            // Initial state check
            toggleSubmitButton();
        });

        // When the page loads, check the URL for "signup=true"
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('signup') === 'true') {
                const flippCheckbox = document.getElementById('flipp');
                if (flippCheckbox) {
                    flippCheckbox.checked = true;
                }
            }
        });

        // Add smooth animation for form flip
        document.getElementById('flipp').addEventListener('change', function() {
            const hint = document.getElementById("email-hint");
            const icon = document.querySelector(".email-hint-icon");
            if (hint && hint.classList.contains("show")) {
                hint.classList.remove("show");
                if (icon) icon.style.color = "#999";
            }
        });
    </script>

        <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="js/main.js"></script>
    </div>
</body>
</html>