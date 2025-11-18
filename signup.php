<?php
session_start();
include 'db.php';

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $errors = [
        "username" => "",
        "email" => "",
        "password" => "",
        "confirm_password" => ""
    ];

    // ✅ Username validation
    if (empty($username) || !preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors['username'] = "Username must be 3–20 characters and can only contain letters, numbers, and underscores.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors['username'] = "Username is already taken.";
        }
        $stmt->close();
    }

    // ✅ Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors['email'] = "Email is already registered.";
        }
        $stmt->close();
    }

    // ✅ Password validation
    if (strlen($password) < 8) {
        $errors['password'] = "Password must be at least 8 characters long.";
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match.";
    }

    // ✅ If errors exist
    if (!empty(array_filter($errors))) {
        $_SESSION['signup_errors'] = $errors;
        $_SESSION['signup_old'] = $_POST;
        header("Location: signin.php?signup=true");
        exit;
    }

    // ✅ Save unverified user
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, is_verified) VALUES (?, ?, ?, 0)");
    $stmt->bind_param("sss", $username, $email, $hashed_password);

    if ($stmt->execute()) {
        // ✅ Generate OTP
        $otp = rand(100000, 999999);
        $stmt2 = $conn->prepare("INSERT INTO user_otps (email, otp, created_at) VALUES (?, ?, NOW())");
        $stmt2->bind_param("ss", $email, $otp);
        $stmt2->execute();

        // ✅ Send OTP via PHPMailer (Hostinger SMTP)
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.hostinger.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'info@tastyhub.site'; // your email
            $mail->Password = 'Tastyhub@28'; // your email password
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
        } catch (Exception $e) {
            error_log("Mail Error: " . $mail->ErrorInfo);
        }

        $_SESSION['pending_email'] = $email;
        header("Location: verify_otp.php");
        exit;
    } else {
        $_SESSION['signup_errors']['general'] = "An error occurred during registration.";
        header("Location: signin.php?signup=true");
        exit;
    }
}
?>
