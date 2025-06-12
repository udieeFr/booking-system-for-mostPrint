<?php
require_once __DIR__ . '/vendor/autoload.php';

use Smalot\PdfParser\Parser;

// Example values (you'll replace these dynamically)
$filePath = 'uploads/O001_document.pdf'; // Replace with actual uploaded file path
$serviceID = 'S001'; // 'S001' = Printing, 'S002' = Binding, 'S003' = Laminating

// Define pricing rules
$pricingRules = [
    'S001' => 0.10, // Printing
    'S002' => 0.05, // Binding
    'S003' => 0.50, // Laminating
];

if (!file_exists($filePath)) {
    die("File not found.");
}

try {
    $parser = new Parser();
    $pdf = $parser->parseFile($filePath);
    $details = $pdf->getDetails();

    // Get number of pages
    $pageCount = isset($details['Pages']) ? $details['Pages'] : count($pdf->getPages());

    echo "📄 Number of pages: <strong>" . htmlspecialchars($pageCount) . "</strong><br>";

    // Get price per page based on service
    if (!isset($pricingRules[$serviceID])) {
        die("Invalid service ID.");
    }

    $pricePerPage = $pricingRules[$serviceID];
    $totalPrice = $pageCount * $pricePerPage;

    echo "💰 Price per page: RM" . number_format($pricePerPage, 2) . "<br>";
    echo "✅ Estimated Total Price: <strong>RM" . number_format($totalPrice, 2) . "</strong>";

} catch (Exception $e) {
    die("Error reading PDF: " . $e->getMessage());
}
?>