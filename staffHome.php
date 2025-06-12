<?php
session_start();

// Ensure user is logged in and is a staff member
if (!isset($_SESSION['loggedin']) || $_SESSION['user_type'] !== 'staff') {
    header("Location: v_login.php?error=unauthorized");
    exit();
}

$conn = new mysqli("localhost", "root", "", "mostdb");

// Fetch pending orders
$sqlPending = "
    SELECT o.OrderID, c.CustomerName, od.ServiceID, od.FileName 
    FROM orders o
    JOIN customers c ON o.CustomerID = c.CustomerID
    JOIN order_details od ON o.OrderID = od.OrderID
    WHERE od.Status = 'Pending'
";

$resultPending = $conn->query($sqlPending);

// Fetch in-progress orders with payment proof (fixed missing comma)
$sqlInProgress = "
    SELECT o.OrderID, c.CustomerName, c.PhoneNumber, od.ServiceID, od.FileName, p.PaymentProof 
    FROM orders o
    JOIN customers c ON o.CustomerID = c.CustomerID
    JOIN order_details od ON o.OrderID = od.OrderID
    LEFT JOIN payments p ON o.OrderID = p.OrderID
    WHERE od.Status = 'In Progress'
";

$resultInProgress = $conn->query($sqlInProgress);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Staff Dashboard</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: url('img/background.jpg');
      background-size: cover;
      color: #333;
    }

    header {
      background: linear-gradient(90deg, #ff6b35, #f7dc6f); /* Orange to Yellow Gradient */
      color: #333;
      padding: 20px;
      text-align: center;
      position: relative;
      backdrop-filter: blur(5px);
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .welcome {
      position: absolute;
      left: 20px;
      top: 20px;
      font-weight: bold;
      font-size: 16px;
    }

    h1 {
      margin: 0;
      font-size: 24px;
    }

    .dashboard {
      display: flex;
      gap: 20px;
      padding: 30px;
      justify-content: space-between;
      flex-wrap: wrap;
    }

    .column {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 0 5px rgba(0,0,0,0.1);
      padding: 20px;
      width: 31%;
      box-sizing: border-box;
      min-width: 300px;
    }

    h2 {
      font-size: 18px;
      color: #333;
      border-bottom: 1px solid #ddd;
      padding-bottom: 10px;
      margin-top: 0;
    }

    .order-card {
      background: #f9f9f9;
      border: 1px solid #eee;
      padding: 15px;
      margin-bottom: 15px;
      border-radius: 6px;
    }

    .order-card p {
      margin: 5px 0;
    }

    .btn {
      display: inline-block;
      padding: 8px 12px;
      margin-top: 10px;
      text-decoration: none;
      color: white;
      background-color: #007BFF;
      border-radius: 4px;
      font-size: 14px;
      border: none;
      cursor: pointer;
    }

    .btn.green {
      background-color: #28a745;
    }

    .btn.red {
      background-color: #dc3545;
    }

    .view-details-btn {
      background-color: #6c757d;
      color: white;
      border: none;
      padding: 8px 12px;
      border-radius: 4px;
      cursor: pointer;
      margin-top: 10px;
    }

    .view-details-btn:hover {
      background-color: #5a6268;
    }

    .manual-booking,
    .report-section {
      background: #f9f9f9;
      padding: 15px;
      border-radius: 6px;
      border: 1px solid #ddd;
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.4);
    }

    .modal-content {
      background-color: #fefefe;
      margin: 15% auto;
      padding: 20px;
      border: 1px solid #ccc;
      width: 80%;
      max-width: 600px;
      border-radius: 8px;
    }

    .close-modal {
      float: right;
      font-size: 24px;
      font-weight: bold;
      cursor: pointer;
      color: #333;
    }

    .close-modal:hover {
      color: #dc3545;
    }
    
    @media (max-width: 1000px) {
      .dashboard {
        flex-direction: column;
        align-items: stretch;
      }
      .column {
        width: 100%;
      }
    }
  </style>
</head>
<body>

<header>
  <div class="welcome">Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Staff') ?></div>
  <h1>Staff Dashboard</h1>
</header>

<div class="dashboard">

  <!-- LEFT COLUMN: PENDING ORDERS -->
  <div class="column">
    <h2>Pending Orders</h2>

    <?php if ($resultPending && $resultPending->num_rows > 0): ?>
      <?php while ($row = $resultPending->fetch_assoc()): ?>
        <div class="order-card">
          <p><strong>Order ID:</strong> <?= htmlspecialchars($row['OrderID']) ?></p>
          <p><strong>Customer:</strong> <?= htmlspecialchars($row['CustomerName']) ?></p>
          <p><strong>Service:</strong> <?= htmlspecialchars($row['ServiceID']) ?></p>

          <?php if (!empty($row['FileName'])): ?>
            <a href="uploads/<?= htmlspecialchars($row['FileName']) ?>" download class="btn">📄 Download File</a>
          <?php else: ?>
            <p style="color:red;">❌ No file uploaded</p>
          <?php endif; ?>

          <a href="approveOrder.php?orderID=<?= urlencode($row['OrderID']) ?>" class="btn green">Approve Order</a>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p>No pending orders.</p>
    <?php endif; ?>
  </div>

  <!-- MIDDLE COLUMN: IN PROGRESS ORDERS -->
  <div class="column">
    <h2>In Progress Orders</h2>

    <?php if ($resultInProgress && $resultInProgress->num_rows > 0): ?>
      <?php while ($row = $resultInProgress->fetch_assoc()): ?>
        <div class="order-card">
          <p><strong>Order ID:</strong> <?= htmlspecialchars($row['OrderID']) ?></p>
          <p><strong>Customer:</strong> <?= htmlspecialchars($row['CustomerName']) ?></p>
          <p><strong>Service:</strong> <?= htmlspecialchars($row['ServiceID']) ?></p>

          <?php if (!empty($row['FileName'])): ?>
            <a href="uploads/<?= htmlspecialchars($row['FileName']) ?>" download class="btn">📄 Download Print File</a>
          <?php else: ?>
            <p style="color:red;">❌ No print file</p>
          <?php endif; ?>

          <?php if (!empty($row['PaymentProof'])): ?>
            <a href="payments/<?= htmlspecialchars($row['PaymentProof']) ?>" download class="btn red">💳 Download Payment Proof</a>
          <?php else: ?>
            <p style="color:red;">❌ No payment proof</p>
          <?php endif; ?>   

          <a href="markAsDone.php?orderID=<?= urlencode($row['OrderID']) ?>" class="btn red">✅ Mark as Done</a>
          <button class="view-details-btn" onclick="showModal('orderDetailsModal', <?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)">View Details</button>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p>No in-progress orders.</p>
    <?php endif; ?>
  </div>

  <!-- RIGHT COLUMN: ACTIONS -->
  <div class="column">
    <h2>Quick Actions</h2>

    <!-- MANUAL BOOKING BOX -->
    <div class="manual-booking">
      <h3 style="margin-top: 0;">Manual Booking</h3>
      <p>Create a new order for a customer who came in person.</p>
      <a href="manualBookingForm.php" class="btn green">➕ Create Manual Booking</a>
    </div>

    <br>

    <!-- REPORT GENERATION BOX -->
    <div class="report-section">
      <h3 style="margin-top: 0;">Generate Reports</h3>
      <p>Export daily/weekly/monthly reports of all orders or staff performance.</p>
      <a href="generateReport.php" class="btn red">📊 Generate Report</a>
    </div>
  </div>

</div>

<!-- Modal for Order Details -->
<div id="orderDetailsModal" class="modal">
  <div class="modal-content">
    <span class="close-modal" onclick="closeModal('orderDetailsModal')">&times;</span>
    <h3>Order Details</h3>
    <p><strong>Order ID:</strong> <span id="modal-order-id"></span></p>
    <p><strong>Customer:</strong> <span id="modal-customer-name"></span></p>
    <p><strong>Phone Number:</strong> <span id="modal-customer-phone"></span></p>
    <p><strong>Service:</strong> <span id="modal-service-id"></span></p>
    <p><strong>Status:</strong> In Progress</p>
    <p><strong>Print File:</strong> <span id="modal-file-name"></span></p>
    <p><strong>Payment Proof:</strong> <span id="modal-payment-proof"></span></p>
  </div>
</div>

<script>
function showModal(modalId, orderData) {
  const modal = document.getElementById(modalId);
  if (!modal) return;

  modal.style.display = "block";

  // Populate modal content
  document.getElementById('modal-order-id').textContent = orderData.OrderID || 'N/A';
  document.getElementById('modal-customer-name').textContent = orderData.CustomerName || 'N/A';
  document.getElementById('modal-customer-phone').textContent = orderData.PhoneNumber || '❌ Not available';
  document.getElementById('modal-service-id').textContent = orderData.ServiceID || 'N/A';
  document.getElementById('modal-file-name').textContent = orderData.FileName || '❌ Not uploaded';
  document.getElementById('modal-payment-proof').textContent = orderData.PaymentProof || '❌ Not uploaded';
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) modal.style.display = "none";
}

// Close modal when clicking outside
window.onclick = function(event) {
  if (event.target.classList.contains('modal')) {
    event.target.style.display = "none";
  }
}
</script>

</body>
</html>