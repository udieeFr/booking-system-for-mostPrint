<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['user_type'] !== 'staff') {
    header("Location: v_login.php?error=unauthorized");
    exit();
}

$orderID = $_GET['orderID'] ?? '';
if (!$orderID) die("Invalid Order ID");

$staffID = $_SESSION['user_id']; // Get the staff ID from session
$conn = new mysqli("localhost", "root", "", "mostdb");

// Fetch order details to get PaymentProof
$sql = "
    SELECT p.PaymentProof 
    FROM payments p
    WHERE p.OrderID = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $orderID);
$stmt->execute();
$stmt->bind_result($paymentProof);
$stmt->fetch();
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $paymentMethod = $_POST['payment_method'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update order status to In Progress and set StaffID
        $stmt2 = $conn->prepare("UPDATE order_details SET Status = 'In Progress', StaffID = ? WHERE OrderID = ?");
        $stmt2->bind_param("ss", $staffID, $orderID);
        $stmt2->execute();
        $stmt2->close();

        // Update payment method
        $stmt3 = $conn->prepare("UPDATE payments SET PaymentMethod = ? WHERE OrderID = ?");
        $stmt3->bind_param("ss", $paymentMethod, $orderID);
        $stmt3->execute();
        $stmt3->close();

        // Commit transaction
        $conn->commit();

        echo "<h2 style='text-align:center; color:green;'>✅ Order Approved!</h2>";
        echo "<p style='text-align:center;'><a href='staffHome.php'>Back to Dashboard</a></p>";
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        die("Error approving order: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Approve Order</title>
  <style>
    body { font-family: Arial; padding: 30px; max-width: 600px; margin: 0 auto; }
    label { display: block; margin-top: 15px; }
    input, select, button { width: 100%; padding: 8px; margin-top: 5px; }
    button { 
        margin-top: 20px; 
        padding: 10px 20px; 
        background-color: #4CAF50;
        color: white;
        border: none;
        cursor: pointer;
    }
    .proof-container { margin: 20px 0; }
    .proof-container img { max-width: 100%; border: 1px solid #ddd; }
  </style>
</head>
<body>

<h2>Approve Order #<?= htmlspecialchars($orderID) ?></h2>

<div class="proof-container">
<?php if ($paymentProof): ?>
  <h3>Payment Proof</h3>
  <?php
  $ext = pathinfo($paymentProof, PATHINFO_EXTENSION);
  if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif'])): ?>
    <img src="payments/<?= htmlspecialchars($paymentProof) ?>" alt="Payment Proof">
  <?php endif; ?>
  <p><a href="payments/<?= htmlspecialchars($paymentProof) ?>" target="_blank" download>📥 Download Payment Proof</a></p>
<?php else: ?>
  <p>No payment proof found.</p>
<?php endif; ?>
</div>

<form method="post">
  <label for="payment_method">Payment Method:</label>
  <select name="payment_method" id="payment_method" required>
    <option value="">-- Select Payment Method --</option>
    <option value="Cash">Cash</option>
    <option value="eWallet">eWallet</option>
    <option value="Online Banking">Online Banking</option>
  </select>

  <button type="submit">✅ Approve Order</button>
</form>

</body>
</html>