<?php
$servername = "localhost";
$username = "u147049380_tasty_hub"; // Change if needed
$password = "Tastyhub@28"; // Change if needed
$dbname = "u147049380_tasty_hub";

$conn = new mysqli($servername, $username, $password, $dbname);

$conn->query("SET time_zone = '+08:00'");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
