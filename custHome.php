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

// Fetch completed orders for this customer
$sql = "
    SELECT od.OrderID, od.ServiceID, od.Status, od.Price 
    FROM order_details od
    JOIN orders o ON od.OrderID = o.OrderID
    WHERE o.CustomerID = ?
      AND od.Status = 'Done'
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $customerID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$completed_orders = [];

while ($row = mysqli_fetch_assoc($result)) {
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
      padding: 10px;
      margin-bottom: 10px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .order-item h3 {
      margin: 0 0 5px;
      font-size: 16px;
      color: #007BFF;
    }

    .order-item p {
      margin: 4px 0;
      font-size: 13px;
      color: #555;
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

    .btn {
      display: inline-block;
      background-color: #28a745;
      color: white;
      padding: 12px 24px;
      text-decoration: none;
      border-radius: 6px;
      font-size: 16px;
      transition: background-color 0.3s ease;
      margin-top: 20px;
    }

    .btn:hover {
      background-color: #218838;
    }

    .logout {
      float: right;
      margin-right: 20px;
      background-color: #dc3545;
    }

    .logout:hover {
      background-color: #c82333;
    }

    .welcome {
      position: absolute;
      left: 20px;
      top: 20px;
      color: white;
      font-weight: bold;
    }
  </style>
</head>
<body>

<header>
  <div class="welcome">Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?></div>
  <h1>Welcome to Your Printing Company</h1>
  <p>Your one-stop solution for all printing needs</p>
  <a href="v_login.php" class="btn logout">Logout</a>
</header>

<div class="top-section">
  <!-- Previous Orders (Left) -->
  <div class="order-category">
    <h2>Previous Orders</h2>
    <div class="order-list">
      <?php if (!empty($completed_orders)): ?>
        <?php foreach ($completed_orders as $order): ?>
          <div class="order-item">
            <h3>Order ID: <?= htmlspecialchars($order['OrderID']) ?></h3>
            <p>Service ID: <?= htmlspecialchars($order['ServiceID']) ?></p>
            <p>Status: <?= htmlspecialchars($order['Status']) ?></p>
            <p>Price: $<?= htmlspecialchars($order['Price']) ?></p>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No previous orders found.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Pending Orders (Middle) -->
  <div class="order-category">
    <h2>Pending Orders</h2>
    <div class="order-list">
      <p>Coming soon...</p>
    </div>
  </div>

  <!-- Completed Orders (Right) -->
  <div class="order-category">
    <h2>Recently Completed Orders</h2>
    <div class="order-list">
      <p>Coming soon...</p>
    </div>
  </div>
</div>

<div style="text-align:center; padding: 40px 20px;">
  <a href="createOrder.php" class="btn">➕ Create New Order</a>
</div>

</body>
</html>