<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['user_type'] !== 'customer') {
    header("Location: v_login.php?error=unauthorized");
    exit();
}

// Pricing Rules
$pricingRules = [
    'S001' => 0.10,
    'S002' => 0.05,
    'S003' => 0.50,
];

$pageCount = 0;
$totalPrice = 0;
$serviceID = $fileName = $ext = null;

// Handle POST from createOrder.php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $serviceID = $_POST['serviceID'];
    $tmp_name = $_FILES['printFile']['tmp_name'];
    $originalName = $_FILES['printFile']['name'];

    if (!$serviceID || !$tmp_name) {
        die("Missing service or file.");
    }

    // Connect to DB to get next OrderID
    $conn = new mysqli("localhost", "root", "", "mostdb");
    if ($conn->connect_error) {
        die("DB Error: " . $conn->connect_error);
    }

    $sql = "SELECT MAX(CAST(SUBSTR(OrderID, 2) AS UNSIGNED)) AS max_id FROM orders WHERE OrderID LIKE 'O%'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $newIDNumber = $row['max_id'] ? $row['max_id'] + 1 : 1;
    $orderID = 'O' . str_pad($newIDNumber, 3, '0', STR_PAD_LEFT);

    // Load PDF parser
    require_once __DIR__ . '/vendor/autoload.php';
    $parser = new \Smalot\PdfParser\Parser();

    // Save uploaded PDF temporarily
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    if (!file_exists('uploads/temp')) {
        mkdir('uploads/temp', 0777, true);
    }
    $tempPdfPath = "uploads/temp/{$orderID}DocumentTemp.{$ext}";
    move_uploaded_file($tmp_name, $tempPdfPath);

    try {
        // Use fully qualified class name
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($tempPdfPath);
        $details = $pdf->getDetails();
        $pageCount = isset($details['Pages']) ? $details['Pages'] : count($pdf->getPages());
        $pricePerUnit = $pricingRules[$serviceID] ?? 0;
        $totalPrice = round($pageCount * $pricePerUnit, 2);
    } catch (Exception $e) {
        die("Error reading PDF: " . $e->getMessage());
    }

    // Store in session
    $_SESSION['orderData'] = [
        'orderID'     => $orderID,
        'serviceID'   => $serviceID,
        'fileName'    => "{$orderID}Document.{$ext}",
        'pageCount'   => $pageCount,
        'totalPrice'  => $totalPrice,
        'pdfTempPath' => $tempPdfPath,
    ];

    // Close DB connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Confirm Order</title>
  <style>
    body { font-family: Arial; padding: 30px; max-width: 500px; margin: auto; background:rgb(215, 174, 26);  }
    h2, label { text-align: center; display: block; margin-top: 20px; }
    p { text-align: center; font-size: 16px; color: green; }
    input[type="file"], select, button {
      width: 100%;
      padding: 10px;
      margin-top: 10px;
      font-size: 16px;
    }
    .payment-info {
      text-align: center;
      margin-top: 20px;
    }
    .payment-info img {
      max-width: 200px;
      height: auto;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>

<h2>Step 2: Confirm Order</h2>

<p><strong>Page Count:</strong> <?= htmlspecialchars($pageCount) ?></p>
<p><strong>Total Price:</strong> RM<?= number_format($totalPrice, 2) ?></p>

<div class="payment-info">
    <img src="img/qrcode.png" alt="QR Code for Payment">
    <p>Bank Name: Bank Rakyat</p>
    <p>Account Name: Atan Most</p>
    <p>Account Number: 9991242910932392</p>
</div>

<form action="submitOrder.php" method="post" enctype="multipart/form-data">

  <!-- Hidden fields -->
  <input type="hidden" name="serviceID" value="<?= htmlspecialchars($serviceID) ?>">
  <input type="hidden" name="fileName" value="<?= htmlspecialchars($orderID . 'Document.' . $ext) ?>">
  <input type="hidden" name="totalPrice" value="<?= htmlspecialchars($totalPrice) ?>">

  <label for="paymentMethod">Payment Method:</label>
  <select name="paymentMethod" id="paymentMethod" required>
    <option value="">-- Select --</option>
    <option value="Cash">Cash</option>
    <option value="eWallet">eWallet</option>
    <option value="Online Banking">Online Banking</option>
  </select>

  <label for="paymentProof">Upload Payment Proof:</label>
  <input type="file" name="paymentProof" id="paymentProof" accept="image/*,application/pdf" required>

  <div style="text-align:center; margin-top: 20px;">
    <button type="submit">Confirm & Submit Order</button>
  </div>
</form>

</body>
</html>