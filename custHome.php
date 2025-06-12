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
    SELECT od.OrderID, od.ServiceID, od.Status, od.Price, s.ServiceType, od.FileName
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
    SELECT od.OrderID, od.ServiceID, od.Status, od.Price, s.ServiceType
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
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: #f4f4f4;
    }

    header {
      background-color: #007BFF;
      color: white;
      padding: 20px;
      text-align: center;
      position: relative;
    }

    .create-order-btn {
      position: absolute;
      right: 20px;
      top: 20px;
      background-color: #ffc107;
      color: #fff;
      padding: 10px 20px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: bold;
      transition: background-color 0.3s ease;
    }

    .create-order-btn:hover {
      background-color: #e0a800;
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

    p {
      margin-top: 5px;
      font-size: 14px;
    }

    .top-section {
      display: flex;
      justify-content: space-around;
      padding: 40px 20px;
      background-color: #fff;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .order-category {
      width: 30%;
      background-color: #e9ecef;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .order-category h2 {
      text-align: center;
      margin-bottom: 15px;
      font-size: 20px;
      color: #333;
    }

    .order-item {
      background-color: #fff;
      border-radius: 6px;
      padding: 15px;
      margin-bottom: 15px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .order-item h3 {
      margin: 0 0 5px;
      font-size: 16px;
      color: #007BFF;
    }

    .order-item p {
      margin: 6px 0;
      font-size: 14px;
      color: #555;
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

    @media (max-width: 900px) {
      .top-section {
        flex-direction: column;
        align-items: stretch;
      }

      .order-category {
        width: 100%;
        margin-bottom: 20px;
      }
    }

    .empty-state {
      text-align: center;
      color: #6c757d;
      padding: 20px 0;
    }
  </style>
</head>
<body>

<header>
  <div class="welcome">Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?></div>
  <h1>Your Printing Dashboard</h1>
  <p>Your one-stop solution for all printing needs</p>
  <a href="logout.php" class="btn logout">Logout</a>
  <a href="createOrder.php" class="create-order-btn">➕ Create New Order</a>
</header>

<div class="top-section">
  <!-- Previous Orders (Left) -->
  <div class="order-category">
    <h2>All Completed Orders</h2>
    <div class="order-list">
      <?php if (!empty($completed_orders)): ?>
        <?php foreach ($completed_orders as $order): ?>
          <div class="order-item">
            <h3>Order #<?= htmlspecialchars($order['OrderID']) ?></h3>
            <p>Service: <?= htmlspecialchars($order['ServiceType']) ?></p>
            <p>Status: <span class="status-badge status-done"><?= htmlspecialchars($order['Status']) ?></span></p>
            <p>Price: RM<?= number_format($order['Price'], 2) ?></p>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state">No completed orders yet</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Active Orders (Middle) -->
  <div class="order-category">
    <h2>Active Orders</h2>
    <div class="order-list">
      <?php if (!empty($active_orders)): ?>
        <?php foreach ($active_orders as $order): ?>
          <div class="order-item">
            <h3>Order #<?= htmlspecialchars($order['OrderID']) ?></h3>
            <p>Service: <?= htmlspecialchars($order['ServiceType']) ?></p>
            <p>Status: 
              <span class="status-badge <?= 
                $order['Status'] === 'Pending' ? 'status-pending' : 'status-inprogress'
              ?>">
                <?= htmlspecialchars($order['Status']) ?>
              </span>
            </p>
            <p>Price: RM<?= number_format($order['Price'], 2) ?></p>
            <?php if (!empty($order['FileName'])): ?>
              <p><a href="uploads/<?= htmlspecialchars($order['FileName']) ?>" class="file-link" target="_blank">📄 Download File</a></p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state">No active orders</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recently Completed Orders (Right) -->
  <div class="order-category">
    <h2>Recently Completed</h2>
    <div class="order-list">
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
</div>

</body>
</html>