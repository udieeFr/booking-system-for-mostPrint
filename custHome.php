<?php
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['loggedin']) || $_SESSION['user_type'] !== 'customer') {
    header("Location: v_login.php?error=unauthorized");
    exit();
}

// Database connection
$db_server = "localhost";
$db_user = "root";
$db_pass = ""; // ← No password
$db_name = "mostdb";

$conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$customerID = $_SESSION['user_id'];

// Fetch active orders (Pending or In Progress)
$pending_sql = "
    SELECT od.OrderID, od.ServiceID, od.Status, od.Price, s.ServiceType, od.FileName, o.OrderDate
    FROM order_details od
    JOIN orders o ON od.OrderID = o.OrderID
    JOIN services s ON od.ServiceID = s.ServiceID
    WHERE o.CustomerID = ?
      AND od.Status IN ('Pending', 'In Progress')
    ORDER BY FIELD(od.Status, 'In Progress', 'Pending')
";

$pending_stmt = mysqli_prepare($conn, $pending_sql);
mysqli_stmt_bind_param($pending_stmt, "i", $customerID);
mysqli_stmt_execute($pending_stmt);
$pending_result = mysqli_stmt_get_result($pending_stmt);

$active_orders = [];
while ($row = mysqli_fetch_assoc($pending_result)) {
    $active_orders[] = $row;
}

// Fetch recently completed orders (within 7 days)
$completed_sql = "
    SELECT od.OrderID, od.ServiceID, od.Status, od.Price, s.ServiceType, o.OrderDate
    FROM order_details od
    JOIN orders o ON od.OrderID = o.OrderID
    JOIN services s ON od.ServiceID = s.ServiceID
    WHERE o.CustomerID = ?
      AND od.Status = 'Done'
      AND o.OrderDate >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY o.OrderDate DESC
    LIMIT 5
";

$completed_stmt = mysqli_prepare($conn, $completed_sql);
mysqli_stmt_bind_param($completed_stmt, "i", $customerID);
mysqli_stmt_execute($completed_stmt);
$completed_result = mysqli_stmt_get_result($completed_stmt);

$recent_orders = [];
while ($row = mysqli_fetch_assoc($completed_result)) {
    $recent_orders[] = $row;
}

// Fetch all completed orders (for left column)
$all_completed_sql = "
    SELECT od.OrderID, od.ServiceID, od.Status, od.Price, s.ServiceType, o.OrderDate
    FROM order_details od
    JOIN orders o ON od.OrderID = o.OrderID
    JOIN services s ON od.ServiceID = s.ServiceID
    WHERE o.CustomerID = ?
      AND od.Status = 'Done'
    ORDER BY o.OrderDate DESC
";

$all_completed_stmt = mysqli_prepare($conn, $all_completed_sql);
mysqli_stmt_bind_param($all_completed_stmt, "i", $customerID);
mysqli_stmt_execute($all_completed_stmt);
$all_completed_result = mysqli_stmt_get_result($all_completed_stmt);

$completed_orders = [];
while ($row = mysqli_fetch_assoc($all_completed_result)) {
    $completed_orders[] = $row;
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Printing Company - Customer Home</title>
  <style>
    /* Consistent with login page styles */
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: url('img/background.jpg');
      background-size: cover;
      color: #333;
    }

    header {
      background: rgba(219, 203, 146, 0.85);
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

    .btn {
      display: inline-block;
      padding: 8px 16px;
      background-color: #4CAF50;
      color: white;
      border: none;
      border-radius: 5px;
      text-decoration: none;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .btn:hover {
      background-color: #45a049;
    }

    .create-order-btn {
      position: absolute;
      right: 20px;
      top: 20px;
      background-color: #007BFF;
      color: white;
      padding: 10px 20px;
      border-radius: 5px;
      text-decoration: none;
      transition: background-color 0.3s;
    }

    .create-order-btn:hover {
      background-color: #0069d9;
    }

    .dashboard-container {
      display: flex;
      justify-content: center;
      padding: 30px;
      gap: 20px;
      flex-wrap: wrap;
    }

    .order-section {
      background: rgba(255, 255, 255, 0.85);
      border-radius: 10px;
      padding: 20px;
      width: 30%;
      min-width: 300px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      backdrop-filter: blur(5px);
    }

    .order-section h2 {
      text-align: center;
      margin-top: 0;
      color: #333;
      border-bottom: 1px solid #ddd;
      padding-bottom: 10px;
    }

    .order-item {
      background: rgba(255, 255, 255, 0.9);
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 15px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .order-item h3 {
      margin: 0 0 5px;
      color: #007BFF;
    }

    .status-badge {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: bold;
      margin-left: 8px;
    }

    .status-pending {
      background-color: #ffc107;
      color: #333;
    }

    .status-inprogress {
      background-color: #17a2b8;
      color: white;
    }

    .status-done {
      background-color: #28a745;
      color: white;
    }

    .file-link {
      color: #0066cc;
      text-decoration: none;
    }

    .file-link:hover {
      text-decoration: underline;
    }

    .empty-state {
      text-align: center;
      color: #6c757d;
      padding: 20px 0;
    }

    @media (max-width: 900px) {
      .dashboard-container {
        flex-direction: column;
        align-items: center;
      }
      .order-section {
        width: 90%;
      }
    }

    /* Toast notification - matches login page */
    .toast {
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      background: #333;
      color: #fff;
      padding: 12px 20px;
      border-radius: 5px;
      z-index: 1000;
      opacity: 0;
      transition: opacity 0.5s ease;
      pointer-events: none;
      min-width: 250px;
      text-align: center;
    }

    .toast.show {
      opacity: 1;
    }

    .toast.success {
      background-color: #28a745;
    }

    .toast.error {
      background-color: #dc3545;
    }
  </style>
</head>
<body>


<div id="toast" class="toast"></div>

<header>
  <div class="welcome">Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?></div>
  <h1>Your Printing Dashboard</h1>
  <a href="logout.php" class="btn">Logout</a>
  <a href="createOrder.php" class="create-order-btn">➕ Create New Order</a>
</header>

<div class="dashboard-container">
  <!-- All Completed Orders -->
  <div class="order-section">
    <h2>All Completed Orders</h2>
    <?php if (!empty($completed_orders)): ?>
      <?php foreach ($completed_orders as $order): ?>
        <div class="order-item">
          <h3>Order #<?= htmlspecialchars($order['OrderID']) ?></h3>
          <p>Service: <?= htmlspecialchars($order['ServiceType']) ?></p>
          <p>Completed: <?= date('M j, Y', strtotime($order['OrderDate'])) ?></p>
          <p>Price: RM<?= number_format($order['Price'], 2) ?></p>
          <p>Status: <span class="status-badge status-done"><?= htmlspecialchars($order['Status']) ?></span></p>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-state">No completed orders yet</div>
    <?php endif; ?>
  </div>

  <!-- Active Orders -->
  <div class="order-section">
    <h2>Active Orders</h2>
    <?php if (!empty($active_orders)): ?>
      <?php foreach ($active_orders as $order): ?>
        <div class="order-item">
          <h3>Order #<?= htmlspecialchars($order['OrderID']) ?></h3>
          <p>Service: <?= htmlspecialchars($order['ServiceType']) ?></p>
          <p>Date: <?= date('M j, Y', strtotime($order['OrderDate'])) ?></p>
          <p>Price: RM<?= number_format($order['Price'], 2) ?></p>
          <p>Status: 
            <span class="status-badge <?= 
              $order['Status'] === 'Pending' ? 'status-pending' : 'status-inprogress'
            ?>">
              <?= htmlspecialchars($order['Status']) ?>
            </span>
          </p>
          <?php if (!empty($order['FileName'])): ?>
            <p><a href="uploads/<?= htmlspecialchars($order['FileName']) ?>" class="file-link" target="_blank">📄 Download File</a></p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-state">No active orders</div>
    <?php endif; ?>
  </div>

  <!-- Recently Completed Orders -->
  <div class="order-section">
    <h2>Recently Completed</h2>
    <?php if (!empty($recent_orders)): ?>
      <?php foreach ($recent_orders as $order): ?>
        <div class="order-item">
          <h3>Order #<?= htmlspecialchars($order['OrderID']) ?></h3>
          <p>Service: <?= htmlspecialchars($order['ServiceType']) ?></p>
          <p>Completed: <?= date('M j, Y', strtotime($order['OrderDate'])) ?></p>
          <p>Price: RM<?= number_format($order['Price'], 2) ?></p>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-state">No recent completions</div>
    <?php endif; ?>
  </div>
</div>

<script>
  // Toast notification function (matches login page)
  function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = 'toast ' + type;
    toast.classList.add('show');

    setTimeout(() => {
      toast.classList.remove('show');
    }, 3000);
  }

  // Check for success/error messages in URL
  window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
      showToast(urlParams.get('success'));
    }
    if (urlParams.has('error')) {
      showToast(urlParams.get('error'), 'error');
    }
  });
</script>

</body>
</html>