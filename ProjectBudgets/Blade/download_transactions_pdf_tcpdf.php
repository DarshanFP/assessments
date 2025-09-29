<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../includes/dbh.inc.php';
require_once '../../includes/auth_check.php';

// Ensure the user is logged in
if (!isLoggedIn()) {
    header("Location: ../../index.php");
    exit();
}

// Include TCPDF (adjust path based on your installation)
require_once '../../vendor/tcpdf/tcpdf.php';

// Fetch project ID from the URL
$projectId = intval($_GET['project_id']);
$userId = $_SESSION['user_id'];

try {
    // Fetch project details
    $projectStmt = $pdo->prepare("SELECT project_name, total_budget FROM Projects WHERE project_id = :project_id");
    $projectStmt->execute([':project_id' => $projectId]);
    $project = $projectStmt->fetch(PDO::FETCH_ASSOC);

    // Fetch all expense entries for the project with budget information
    $expenseStmt = $pdo->prepare("
        SELECT ee.expense_id, ee.particular, ee.amount_expensed, ee.expensed_at, su.full_name AS created_by,
               be.amount_this_phase AS amount_allocated,
               (SELECT SUM(ee2.amount_expensed) FROM ExpenseEntries ee2 WHERE ee2.entry_id = ee.entry_id) AS total_expensed
        FROM ExpenseEntries ee
        LEFT JOIN ssmntUsers su ON ee.created_by = su.id
        LEFT JOIN BudgetEntries be ON ee.entry_id = be.entry_id
        WHERE ee.project_id = :project_id
        ORDER BY ee.expensed_at ASC
    ");
    $expenseStmt->execute([':project_id' => $projectId]);
    $expenseEntries = $expenseStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total expense
    $totalExpense = array_sum(array_column($expenseEntries, 'amount_expensed'));

    // Group transactions by month
    $monthlyTransactions = [];
    $monthlyTotals = [];
    $cumulativeExpense = 0; // Track cumulative expense
    
    foreach ($expenseEntries as $entry) {
        $monthYear = date('F Y', strtotime($entry['expensed_at']));
        $monthKey = date('Y-m', strtotime($entry['expensed_at']));
        
        if (!isset($monthlyTransactions[$monthKey])) {
            $monthlyTransactions[$monthKey] = [
                'month_name' => $monthYear,
                'transactions' => [],
                'cumulative_expense' => 0,
                'cumulative_available' => 0
            ];
            $monthlyTotals[$monthKey] = 0;
        }
        
        $monthlyTransactions[$monthKey]['transactions'][] = $entry;
        $monthlyTotals[$monthKey] += $entry['amount_expensed'];
    }

    // Sort by month (oldest first)
    ksort($monthlyTransactions);
    ksort($monthlyTotals);
    
    // Calculate cumulative totals for each month
    foreach ($monthlyTransactions as $monthKey => &$monthData) {
        $cumulativeExpense += $monthlyTotals[$monthKey];
        $monthData['cumulative_expense'] = $cumulativeExpense;
        $monthData['cumulative_available'] = $project['total_budget'] - $cumulativeExpense;
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

// Create new PDF document with proper Unicode support
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Assessment System');
$pdf->SetAuthor('Assessment System');
$pdf->SetTitle('Transaction Report - ' . $project['project_name']);

// Set default header data
$pdf->SetHeaderData('', 0, 'Assessment System', 'Transaction Report');

// Set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins - reduced for better fit
$pdf->SetMargins(10, 20, 10);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 20);

// Set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Set font with Unicode support
$pdf->SetFont('helvetica', '', 9);

// Add a page
$pdf->AddPage();

// Report Header
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'Project Transactions Report', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, $project['project_name'], 0, 1, 'C');
$pdf->Cell(0, 6, 'Generated on: ' . date('d M Y, h:i A'), 0, 1, 'C');
$pdf->Ln(3);

// Summary Section
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'Summary', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 9);

$summaryData = [
    ['Total Transactions', count($expenseEntries)],
    ['Months with Transactions', count($monthlyTransactions)],
    ['Total Expensed', 'Rs. ' . number_format($totalExpense, 2)],
    ['Available Funds', 'Rs. ' . number_format($project['total_budget'] - $totalExpense, 2)]
];

foreach ($summaryData as $row) {
    $pdf->Cell(45, 5, $row[0] . ':', 0, 0, 'L');
    $pdf->Cell(0, 5, $row[1], 0, 1, 'L');
}

$pdf->Ln(5);

// Function to calculate cell height for wrapped text
function calculateCellHeight($pdf, $text, $width, $fontSize) {
    $pdf->SetFont('helvetica', '', $fontSize);
    $lines = $pdf->getNumLines($text, $width);
    return $lines * ($fontSize * 0.4); // Approximate line height
}

// Month-wise Transactions
if (!empty($monthlyTransactions)) {
    foreach ($monthlyTransactions as $monthKey => $monthData) {
        // Month Header with better text wrapping
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(74, 111, 209); // #4a6fd1
        $pdf->SetTextColor(255, 255, 255);
        
        $monthHeader = $monthData['month_name'] . ' - Total: Rs. ' . number_format($monthlyTotals[$monthKey], 2);
        $pdf->Cell(0, 6, $monthHeader, 1, 1, 'L', true);
        
        // Reset colors
        $pdf->SetTextColor(0, 0, 0);
        
        // Table Header with text wrapping
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(248, 250, 252); // #f8fafc
        
        $headers = ['#', 'Particular', 'Current Expenses', 'Amount Allocated', 'Total Expensed', 'Available Funds', 'Date', 'Created By'];
        $widths = [8, 35, 22, 22, 22, 22, 18, 25];
        
        // Calculate maximum height needed for headers
        $maxHeaderHeight = 0;
        foreach ($headers as $i => $header) {
            $height = calculateCellHeight($pdf, $header, $widths[$i], 8);
            $maxHeaderHeight = max($maxHeaderHeight, $height);
        }
        
        // Draw header row with calculated height
        foreach ($headers as $i => $header) {
            $pdf->MultiCell($widths[$i], $maxHeaderHeight, $header, 1, 'C', true, 0);
        }
        $pdf->Ln();
        
        // Table Data with text wrapping for particular column
        $pdf->SetFont('helvetica', '', 7);
        $serialNumber = 1;
        
        foreach ($monthData['transactions'] as $entry) {
            // Calculate height needed for this row (mainly for particular column)
            $particularHeight = calculateCellHeight($pdf, $entry['particular'], $widths[1], 7);
            $rowHeight = max(5, $particularHeight); // Minimum 5 units height
            
            $pdf->Cell($widths[0], $rowHeight, $serialNumber++, 1, 0, 'C');
            $pdf->MultiCell($widths[1], $rowHeight, $entry['particular'], 1, 'L', false, 0);
            $pdf->Cell($widths[2], $rowHeight, 'Rs. ' . number_format($entry['amount_expensed'], 2), 1, 0, 'R');
            $pdf->Cell($widths[3], $rowHeight, 'Rs. ' . number_format($entry['amount_allocated'] ?? 0, 2), 1, 0, 'R');
            $pdf->Cell($widths[4], $rowHeight, 'Rs. ' . number_format($monthData['cumulative_expense'], 2), 1, 0, 'R');
            $pdf->Cell($widths[5], $rowHeight, 'Rs. ' . number_format($monthData['cumulative_available'], 2), 1, 0, 'R');
            $pdf->Cell($widths[6], $rowHeight, date('d/m/Y', strtotime($entry['expensed_at'])), 1, 0, 'C');
            $pdf->Cell($widths[7], $rowHeight, $entry['created_by'], 1, 0, 'L');
            $pdf->Ln();
        }
        
        // Monthly Total with better alignment
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(248, 250, 252);
        $pdf->Cell($widths[0] + $widths[1], 5, 'Monthly Total:', 1, 0, 'R', true);
        $pdf->Cell($widths[2], 5, 'Rs. ' . number_format($monthlyTotals[$monthKey], 2), 1, 0, 'R', true);
        $pdf->Cell($widths[3], 5, 'Rs. ' . number_format(array_sum(array_column($monthData['transactions'], 'amount_allocated')), 2), 1, 0, 'R', true);
        $pdf->Cell($widths[4], 5, 'Rs. ' . number_format($monthData['cumulative_expense'], 2), 1, 0, 'R', true);
        $pdf->Cell($widths[5], 5, 'Rs. ' . number_format($monthData['cumulative_available'], 2), 1, 0, 'R', true);
        $pdf->Cell($widths[6] + $widths[7], 5, '', 1, 1, 'L', true);
        
        $pdf->Ln(3);
    }
    
    // Grand Total
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor(74, 111, 209);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 10, 'Grand Total: Rs. ' . number_format($totalExpense, 2), 1, 1, 'C', true);
    
} else {
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 8, 'No transactions found for this project.', 0, 1, 'C');
}

// Footer
$pdf->SetY(-15);
$pdf->SetFont('helvetica', 'I', 7);
$pdf->SetTextColor(128, 128, 128);
$pdf->Cell(0, 8, 'This report was generated automatically by the Assessment System', 0, 0, 'C');
$pdf->Ln();
$pdf->Cell(0, 8, 'Report ID: ' . $projectId . '_' . date('YmdHis'), 0, 0, 'C');

// Output PDF
$pdf->Output($projectId . '.pdf', 'D');
?>
