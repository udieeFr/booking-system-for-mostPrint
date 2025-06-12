<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['user_type'] !== 'staff') {
    header("Location: v_login.php?error=unauthorized");
    exit();
}

$conn = new mysqli("localhost", "root", "", "mostdb");

// Handle order approval if form submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['approve_order'])) {
    $orderID = $_POST['order_id'];
    $staffID = $_SESSION['user_id'];
    $paymentMethod = $_POST['payment_method'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update order status and set StaffID
        $stmt = $conn->prepare("UPDATE order_details SET Status = 'In Progress', StaffID = ? WHERE OrderID = ?");
        $stmt->bind_param("ss", $staffID, $orderID);
        $stmt->execute();
        $stmt->close();

        // Update payment method
        $stmt2 = $conn->prepare("UPDATE payments SET PaymentMethod = ? WHERE OrderID = ?");
        $stmt2->bind_param("ss", $paymentMethod, $orderID);
        $stmt2->execute();
        $stmt2->close();

        // Commit transaction
        $conn->commit();
        
        // Refresh page to show updated list
        header("Location: staffHome.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        die("Error approving order: " . $e->getMessage());
    }
}

// Fetch pending orders
$sql = "
    SELECT o.OrderID, c.CustomerName, od.ServiceID, od.FileName, p.PaymentProof 
    FROM orders o
    JOIN customers c ON o.CustomerID = c.CustomerID
    JOIN order_details od ON o.OrderID = od.OrderID
    LEFT JOIN payments p ON o.OrderID = p.OrderID
    WHERE od.Status = 'Pending'
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Staff Dashboard - Pending Orders</title>
  <style>
    body { font-family: Arial; padding: 30px; }
    .order { 
        border: 1px solid #ccc; 
        padding: 20px; 
        margin-bottom: 20px; 
        position: relative;
    }
    .order h3 { margin-top: 0; }
    a { display: inline-block; margin-top: 10px; }
    .proof-preview {
        max-width: 200px;
        max-height: 200px;
        border: 1px solid #ddd;
        margin: 10px 0;
    }
    .approval-form {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px dashed #ccc;
    }
    select, button {
        padding: 8px;
        margin-top: 5px;
    }
    button.approve-btn {
        background-color: #4CAF50;
        color: white;
        border: none;
        padding: 8px 15px;
        cursor: pointer;
    }
  </style>
</head>
<body>

<h1>Pending Orders</h1>

<?php while ($row = $result->fetch_assoc()): ?>
  <div class="order">
    <h3>Order ID: <?= htmlspecialchars($row['OrderID']) ?></h3>
    <p><strong>Customer:</strong> <?= htmlspecialchars($row['CustomerName']) ?></p>
    <p><strong>Service:</strong> <?= htmlspecialchars($row['ServiceID']) ?></p>
    
    <?php if ($row['FileName']): ?>
      <p><a href="uploads/<?= htmlspecialchars($row['FileName']) ?>" target="_blank">📄 Download Print File</a></p>
    <?php endif; ?>

    <?php if ($row['PaymentProof']): ?>
      <p><a href="payments/<?= htmlspecialchars($row['PaymentProof']) ?>" target="_blank" download>💳 Download Payment Proof</a></p>
      <?php 
      $ext = pathinfo($row['PaymentProof'], PATHINFO_EXTENSION);
      if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif'])): ?>
        <img src="payments/<?= htmlspecialchars($row['PaymentProof']) ?>" class="proof-preview" alt="Payment Proof">
      <?php endif; ?>
    <?php endif; ?>

    <div class="approval-form">
      <form method="post">
        <input type="hidden" name="order_id" value="<?= htmlspecialchars($row['OrderID']) ?>">
        
        <label for="payment_method_<?= htmlspecialchars($row['OrderID']) ?>">Payment Method:</label>
        <select name="payment_method" id="payment_method_<?= htmlspecialchars($row['OrderID']) ?>" required>
          <option value="">-- Select --</option>
          <option value="Cash">Cash</option>
          <option value="eWallet">eWallet</option>
          <option value="Online Banking">Online Banking</option>
        </select>
        
        <button type="submit" name="approve_order" class="approve-btn">✔️ Approve Order</button>
      </form>
    </div>
  </div>
<?php endwhile; ?>

</body>
</html>