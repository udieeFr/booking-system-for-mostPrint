<?php
session_start();

// Ensure user is logged in and is a staff member
if (!isset($_SESSION['loggedin']) || $_SESSION['user_type'] !== 'staff') {
    header("Location: v_login.php?error=unauthorized");
    exit();
}

// Get the order ID from the URL parameter
$orderID = isset($_GET['orderID']) ? $_GET['orderID'] : null;

// Check if orderID is provided
if (empty($orderID)) {
    header("Location: staffHome.php?error=Order+ID+is+required");
    exit();
}

// Connect to the database
$conn = new mysqli("localhost", "root", "", "mostdb");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Prepare SQL statement to update order status
$stmt = $conn->prepare("UPDATE order_details SET Status = 'Done' WHERE OrderID = ?");
$stmt->bind_param("s", $orderID);

// Execute the prepared statement
if ($stmt->execute()) {
    // Redirect back to the staff dashboard with a success message
    header("Location: staffHome.php?success=Order+marked+as+done");
    exit();
} else {
    // Redirect back with an error message
    header("Location: staffHome.php?error=" . urlencode("Failed to mark order as done: " . $stmt->error));
    exit();
}

// Close statement and connection
$stmt->close();
$conn->close();
?>