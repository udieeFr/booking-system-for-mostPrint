<?php
session_start();

if (!isset($_SESSION['manualOrderData'])) {
    die("No order data found.");
}

$data = $_SESSION['manualOrderData'];

// These are set in confirmManualOrder.php
$customerName = $data['customerName'] ?? '';
$serviceID = $data['serviceID'] ?? '';
$totalPrice = $data['totalPrice'] ?? 0;
$tempPdfPath = $data['tempPdfPath'] ?? '';

if (!$customerName || !$serviceID || !file_exists($tempPdfPath)) {
    die("Invalid or missing data.");
}

$conn = new mysqli("localhost", "root", "", "mostdb");

// Generate real OrderID
$sql = "SELECT MAX(CAST(SUBSTR(OrderID, 2) AS UNSIGNED)) AS max_id FROM orders WHERE OrderID LIKE 'O%'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$newIDNumber = $row['max_id'] ? $row['max_id'] + 1 : 1;
$orderID = 'O' . str_pad($newIDNumber, 3, '0', STR_PAD_LEFT);

// Move PDF to permanent folder
$fileName = $orderID . 'Document.' . pathinfo($tempPdfPath, PATHINFO_EXTENSION);
rename($tempPdfPath, "uploads/" . $fileName);

// Get staff ID and date
$staffID = $_SESSION['user_id'];
$orderDate = date('Y-m-d');
$status = 'In Progress';

// Insert or get CustomerID
$stmt = $conn->prepare("SELECT CustomerID FROM customers WHERE CustomerName = ?");
$stmt->bind_param("s", $customerName);
$stmt->execute();
$stmt->bind_result($customerID);
$stmt->fetch();
$stmt->close();

if (!$customerID) {
    $stmt = $conn->prepare("INSERT INTO customers (CustomerName, PhoneNumber) VALUES (?, '')");
    $stmt->bind_param("s", $customerName);
    $stmt->execute();
    $customerID = $conn->insert_id;
    $stmt->close();
}

// Insert into orders
$stmt2 = $conn->prepare("INSERT INTO orders (OrderID, CustomerID, OrderDate) VALUES (?, ?, ?)");
$stmt2->bind_param("sis", $orderID, $customerID, $orderDate);
$stmt2->execute();
$stmt2->close();

// Insert into order_details
$stmt3 = $conn->prepare("INSERT INTO order_details (OrderID, ServiceID, Status, FileName, Price, StaffID) VALUES (?, ?, ?, ?, ?, ?)");

$stmt3->bind_param("ssssdi", $orderID, $serviceID, $status, $fileName, $totalPrice, $staffID);
$stmt3->execute();
$stmt3->close();

// Handle payment proof
$proofFileName = null;

if (isset($_FILES['paymentProof']) && $_FILES['paymentProof']['error'] === UPLOAD_ERR_OK) {
    $proofExt = pathinfo($_FILES['paymentProof']['name'], PATHINFO_EXTENSION);
    $proofFileName = $orderID . 'Proof.' . $proofExt;
    move_uploaded_file($_FILES['paymentProof']['tmp_name'], "payments/" . $proofFileName);
}

// Insert into payments
$paymentID = 'P' . substr($orderID, 1); // e.g., P001
$payDate = $orderDate;

$stmt4 = $conn->prepare("INSERT INTO payments (PaymentID, OrderID, PayDate, PaymentProof) VALUES (?, ?, ?, ?)");
$stmt4->bind_param("ssss", $paymentID, $orderID, $payDate, $proofFileName);
$stmt4->execute();
$stmt4->close();

echo "<h2 style='text-align:center; color:green;'>✅ Order Confirmed!</h2>";
echo "<p style='text-align:center;'><a href='staffHome.php'>Back to Dashboard</a></p>";

unset($_SESSION['manualOrderData']);
$conn->close();
?>