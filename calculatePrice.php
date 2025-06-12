<?php
require_once __DIR__ . '/vendor/autoload.php';
use Smalot\PdfParser\Parser;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['print_file'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$serviceID = $_POST['serviceID'];

$pricingRules = [
    'S001' => 0.10,
    'S002' => 0.05,
    'S003' => 0.50,
];

if (!array_key_exists($serviceID, $pricingRules)) {
    echo json_encode(['error' => 'Invalid service']);
    exit;
}

$tmp_name = $_FILES['print_file']['tmp_name'];
$filename = $_FILES['print_file']['name'];
$uploadDir = 'uploads/temp/';
$tempPath = $uploadDir . uniqid() . '_' . basename($filename);

// Move uploaded file to temp folder
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (!move_uploaded_file($tmp_name, $tempPath)) {
    echo json_encode(['error' => 'File upload failed']);
    exit;
}

try {
    $parser = new Parser();
    $pdf = $parser->parseFile($tempPath);
    $details = $pdf->getDetails();
    $pageCount = isset($details['Pages']) ? $details['Pages'] : count($pdf->getPages());

    $pricePerPage = $pricingRules[$serviceID];
    $totalPrice = $pageCount * $pricePerPage;

    // Clean up temp file
    unlink($tempPath);

    echo json_encode([
        'page_count' => $pageCount,
        'total_price' => round($totalPrice, 2),
    ]);

} catch (Exception $e) {
    unlink($tempPath);
    echo json_encode(['error' => 'Error reading PDF: ' . $e->getMessage()]);
}
?>