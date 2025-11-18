<?php
session_start();
require 'db.php'; // Include database connection

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: signin.php");
    exit;
}

// Function to update the recipe status
function update_recipe_status($conn, $id, $status) {
    // Prepare the SQL statement to prevent SQL injection
    $sql = "UPDATE recipe SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // Bind parameters
        $stmt->bind_param("si", $status, $id);
        
        // Execute the statement
        if ($stmt->execute()) {
            return true; // Update successful
        } else {
            return false; // Update failed
        }
    } else {
        return false; // Statement preparation failed
    }
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $status = $_POST['status'];

    // Convert 'declined' to 'rejected' for SQL update
    if ($status === 'declined') {
        $status = 'rejected';
    }

    // Validate the status to ensure it's one of the expected values
    $valid_statuses = ['approved', 'pending', 'rejected']; // Updated to include 'rejected'
    if (in_array($status, $valid_statuses)) {
        // Call the function to update the recipe status
        if (update_recipe_status($conn, $id, $status)) {
            // Redirect back to the admin dashboard
            header("Location: admin_dashboard.php");
            exit;
        } else {
            // Handle error (you can redirect or show an error message)
            echo "Error updating recipe status. Please try again.";
        }
    } else {
        // Handle invalid status
        echo "Invalid status provided.";
    }
} else {
    // Handle invalid request method
    echo "Invalid request method.";
}
?>