<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['user_type'] !== 'customer') {
    header("Location: v_login.php?error=unauthorized");
    exit();
}

$orderData = $_SESSION['orderData'] ?? null;
if (!$orderData) {
    die("No order data found. Please go back.");
}

$conn = new mysqli("localhost", "root", "", "mostdb");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$customerID = $_SESSION['user_id'];
$orderDate = date('Y-m-d');
$status = 'Pending';

$orderID = $orderData['orderID'];
$serviceID = $orderData['serviceID'];
$totalPrice = $orderData['totalPrice'];
$fileName = $orderData['fileName']; // Get the filename from session
$tempPdfPath = $orderData['pdfTempPath'];

// Handle payment proof upload
$proofFileName = null;

if (isset($_FILES['paymentProof']) && $_FILES['paymentProof']['error'] === UPLOAD_ERR_OK) {
    // Create payments directory if it doesn't exist
    if (!file_exists('payments')) {
        mkdir('payments', 0777, true);
    }
    
    $proofExt = pathinfo($_FILES['paymentProof']['name'], PATHINFO_EXTENSION);
    $proofFileName = "{$orderID}Proof.{$proofExt}";
    $proofFinalPath = "payments/" . $proofFileName;

    if (!move_uploaded_file($_FILES['paymentProof']['tmp_name'], $proofFinalPath)) {
        die("Failed to save payment proof.");
    }
} else {
    die("Payment proof is required.");
}

// Move PDF from temp to final folder
$finalPdfPath = "uploads/" . $fileName;
if (file_exists($tempPdfPath)) {
    // Create uploads directory if it doesn't exist
    if (!file_exists('uploads')) {
        mkdir('uploads', 0777, true);
    }
    rename($tempPdfPath, $finalPdfPath);
} else {
    die("Temporary PDF not found.");
}

// Start transaction
$conn->begin_transaction();

try {
    // Insert into orders
    $stmt = $conn->prepare("INSERT INTO orders (OrderID, CustomerID, OrderDate) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $orderID, $customerID, $orderDate);
    $stmt->execute();

    // Insert into order_details - ADDED FILENAME HERE
    $stmt2 = $conn->prepare("INSERT INTO order_details (OrderID, ServiceID, Status, Price, FileName) VALUES (?, ?, ?, ?, ?)");
    $stmt2->bind_param("sssds", $orderID, $serviceID, $status, $totalPrice, $fileName);
    $stmt2->execute();

    // Insert into payments
    $paymentID = 'P' . substr($orderID, 1); // P001
    $payDate = $orderDate;
    $paymentMethod = $_POST['paymentMethod'];

    $stmt3 = $conn->prepare("INSERT INTO payments (PaymentID, OrderID, PaymentMethod, PayDate, PaymentProof) VALUES (?, ?, ?, ?, ?)");
    $stmt3->bind_param("sssss", $paymentID, $orderID, $paymentMethod, $payDate, $proofFileName);
    $stmt3->execute();

    // Commit transaction
    $conn->commit();

    echo "<h2 style='text-align:center; color:green;'>✅ Order Submitted Successfully!</h2>";
    echo "<p style='text-align:center;'>Order ID: $orderID</p>";
    echo "<p style='text-align:center;'><a href='custHome.php'>Back to Dashboard</a></p>";

    unset($_SESSION['orderData']);
} catch (Exception $e) {
    $conn->rollback();
    die("Error processing order: " . $e->getMessage());
}

$conn->close();
?>