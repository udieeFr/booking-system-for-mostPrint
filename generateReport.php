    <?php
    session_start();

    if (!isset($_SESSION['loggedin']) || $_SESSION['user_type'] !== 'staff') {
        header("Location: v_login.php?error=unauthorized");
        exit();
    }

    $conn = new mysqli("localhost", "root", "", "mostdb");

    $reportType = $_POST['reportType'] ?? null;
    $results = [];

    // Helper function to display table from query result
    function renderTable($headers, $rows) {
        echo "<table border='1' style='width:100%; border-collapse: collapse; margin-top:20px;'>";
        echo "<thead><tr>";
        foreach ($headers as $header) {
            echo "<th style='background:#f2f2f2; padding:8px;'>$header</th>";
        }
        echo "</tr></thead><tbody>";

        foreach ($rows as $row) {
            echo "<tr>";
            foreach ($row as $cell) {
                echo "<td style='padding:8px;'>" . htmlspecialchars($cell) . "</td>";
            }
            echo "</tr>";
        }

        echo "</tbody></table>";
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <title>Generate Reports</title>
    <style>
        body {
        font-family: Arial;
        padding: 30px;
        background: #f4f4f4;
        }
        h2 {
        text-align: center;
        color: #333;
        }
        select, button {
        padding: 10px;
        width: 100%;
        margin: 10px 0 20px;
        font-size: 16px;
        }
        table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        }
        th, td {
        padding: 10px;
        border: 1px solid #ccc;
        text-align: left;
        }
        .container {
        max-width: 900px;
        margin: auto;
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }
    </style>
    </head>
    <body>

    <div class="container">
    <h2>📊 Generate Reports</h2>

    <form method="post">
        <label for="reportType"><strong>Select Report Type:</strong></label>
        <select name="reportType" id="reportType" required>
        <option value="">-- Choose Report --</option>
        <option value="customer_history">Customer Order History</option>
        <option value="service_summary">Service Usage Summary</option>
        <option value="revenue_by_service">Revenue by Service</option>
        <option value="revenue_by_staff">Revenue by Staff</option>
        </select>

        <?php if ($reportType === 'customer_history'): ?>
        <label for="customerID"><strong>Select Customer:</strong></label>
        <select name="customerID" id="customerID" required>
            <option value="">-- Select Customer --</option>
            <?php
            $customers = $conn->query("SELECT CustomerID, CustomerName FROM customers ORDER BY CustomerName");
            while ($c = $customers->fetch_assoc()):
            ?>
            <option value="<?= $c['CustomerID'] ?>"><?= $c['CustomerName'] ?></option>
            <?php endwhile; ?>
        </select>
        <?php endif; ?>

        <button type="submit">Generate Report</button>
    </form>

    <?php if ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
        <?php
        switch ($reportType):
        case 'customer_history':
            $customerID = $_POST['customerID'];
            $sql = "
            SELECT o.OrderID, c.CustomerName, od.ServiceID, s.ServiceType, od.Price, od.Status, p.PayDate
            FROM orders o
            JOIN customers c ON o.CustomerID = c.CustomerID
            JOIN order_details od ON o.OrderID = od.OrderID
            LEFT JOIN payments p ON o.OrderID = p.OrderID
            LEFT JOIN services s ON od.ServiceID = s.ServiceID
            WHERE o.CustomerID = ?
            ORDER BY o.OrderDate DESC
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $customerID);
            $stmt->execute();
            $result = $stmt->get_result();
            $results = $result->fetch_all(MYSQLI_ASSOC);
            $headers = ['Order ID', 'Customer', 'Service', 'Service Name', 'Price', 'Status', 'Payment Date'];
            break;

        case 'service_summary':
            $sql = "
            SELECT od.ServiceID, s.ServiceType, COUNT(*) AS total_orders
            FROM order_details od
            JOIN services s ON od.ServiceID = s.ServiceID
            GROUP BY od.ServiceID
            ";
            $result = $conn->query($sql);
            $results = $result->fetch_all(MYSQLI_ASSOC);
            $headers = ['Service ID', 'Service Name', 'Total Orders'];
            break;

        case 'revenue_by_service':
            $sql = "
            SELECT od.ServiceID, s.ServiceType, SUM(od.Price) AS total_revenue
            FROM order_details od
            JOIN services s ON od.ServiceID = s.ServiceID
            WHERE od.Status = 'Done'
            GROUP BY od.ServiceID
            ";
            $result = $conn->query($sql);
            $results = $result->fetch_all(MYSQLI_ASSOC);
            $headers = ['Service ID', 'Service Name', 'Total Revenue (RM)'];
            break;

        case 'revenue_by_staff':
            $sql = "
            SELECT st.StaffName, od.ServiceID, s.ServiceType, SUM(od.Price) AS total_revenue
            FROM order_details od
            JOIN staffs st ON od.StaffID = st.StaffID
            JOIN services s ON od.ServiceID = s.ServiceID
            WHERE od.Status = 'Done'
            GROUP BY st.StaffID, od.ServiceID
            ";
            $result = $conn->query($sql);
            $results = $result->fetch_all(MYSQLI_ASSOC);
            $headers = ['Staff', 'Service ID', 'Service Name', 'Total Revenue (RM)'];
            break;

        default:
            $results = [];
        endswitch;
        ?>

        <?php if (!empty($results)): ?>
        <?php renderTable($headers, $results); ?>
        <?php else: ?>
        <p>No data found for this report.</p>
        <?php endif; ?>
    <?php endif; ?>
    </div>

    </body>
    </html>