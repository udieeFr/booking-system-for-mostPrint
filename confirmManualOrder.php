<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['user_type'] !== 'staff') {
    header("Location: v_login.php?error=unauthorized");
    exit();
}

// Pricing Rules per service
$pricingRules = [
    'S001' => 0.10,
    'S002' => 0.05,
    'S003' => 0.50,
];

$pageCount = 0;
$totalPrice = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customerName = $_POST['customerName'];
    $phoneNumber = $_POST['phoneNumber'];
    $serviceID = $_POST['serviceID'];
    $queueSkip = isset($_POST['queueSkip']);

    // Save data in session
    $_SESSION['manualOrderData'] = [
        'customerName' => $customerName,
        'phoneNumber' => $phoneNumber,
        'serviceID' => $serviceID,
        'queueSkip' => $queueSkip,
    ];

    // Handle PDF upload
    if (isset($_FILES['printFile']) && $_FILES['printFile']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['printFile']['tmp_name'];
        $originalName = $_FILES['printFile']['name'];

        // Save temporarily
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $tempPdfPath = "uploads/temp/" . uniqid() . '.' . $ext;

        move_uploaded_file($tmp_name, $tempPdfPath);

        // Use PDF parser
        require_once __DIR__ . '/vendor/autoload.php';
        $parser = new \Smalot\PdfParser\Parser();

        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($tempPdfPath);
            $details = $pdf->getDetails();
            $pageCount = isset($details['Pages']) ? $details['Pages'] : count($pdf->getPages());
        } catch (Exception $e) {
            die("Error reading PDF: " . $e->getMessage());
        }

        // Calculate base price
        $baseRate = $pricingRules[$serviceID] ?? 0;
        $totalPrice = round($pageCount * $baseRate, 2);
        if ($queueSkip) {
            $totalPrice = round($totalPrice * 1.5, 2);
        }

        // Store in session
        $_SESSION['manualOrderData']['pageCount'] = $pageCount;
        $_SESSION['manualOrderData']['totalPrice'] = $totalPrice;
        $_SESSION['manualOrderData']['tempPdfPath'] = $tempPdfPath;
    } else {
        die("PDF file is required.");
    }
} elseif (isset($_SESSION['manualOrderData'])) {
    $pageCount = $_SESSION['manualOrderData']['pageCount'] ?? 0;
    $totalPrice = $_SESSION['manualOrderData']['totalPrice'] ?? 0;
} else {
    header("Location: manualBookingForm.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Confirm Manual Order</title>
  <style>
    body { font-family: Arial; padding: 30px; max-width: 500px; margin: auto; background: #f9f9f9; }
    h2, label { text-align: center; display: block; margin-top: 20px; }
    p { text-align: center; font-size: 16px; color: green; }
    label { margin-top: 15px; }
    input[type="file"] {
      width: 100%;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    button {
      margin-top: 20px;
      padding: 10px 20px;
      width: 100%;
      background-color: #28a745;
      color: white;
      border: none;
      border-radius: 4px;
      font-size: 16px;
      cursor: pointer;
    }
    button:hover {
      background-color: #218838;
    }
  </style>
</head>
<body>

<h2>Step 2: Confirm Order</h2>

<p><strong>Page Count:</strong> <?= htmlspecialchars($pageCount) ?></p>
<p><strong>Total Price:</strong> RM<?= number_format($totalPrice, 2) ?></p>

<form action="submitManualOrder.php" method="post" enctype="multipart/form-data">
  <input type="hidden" name="confirmed" value="1">

  <label for="paymentProof">Upload Payment Proof (PDF/Image):</label>
  <input type="file" name="paymentProof" id="paymentProof" accept="image/*,application/pdf" required>

  <div style="text-align:center; margin-top: 20px;">
    <button type="submit">✅ Save & Confirm Order</button>
  </div>
</form>

</body>
</html>