<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;
session_start();
require 'config.php';


// Get filter parameters from the POST request
$startdate = isset($_POST['startdate']) ? $_POST['startdate'] : null;
$enddate = isset($_POST['enddate']) ? $_POST['enddate'] : null;

// Fetch filtered attendance data from the database
$attendanceData = [];
if ($startdate && $enddate) {
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE DATE(date_time) BETWEEN ? AND ?");
    $stmt->bind_param("ss", $startdate, $enddate);
} else {
    $stmt = $conn->prepare("SELECT * FROM attendance");
}
$stmt->execute();
$result = $stmt->get_result();

// Initialize DOMPDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

// Create HTML content
$html = '<h2>Attendance Records</h2>';
$html .= '<table border="1" width="100%" style="border-collapse: collapse;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Department</th>
                    <th>Name</th>
                    <th>No</th>
                    <th>Date/Time</th>
                    <th>Status</th>
                    <th>Location ID</th>
                    <th>ID Number</th>
                    <th>Verify Code</th>
                    <th>Card No</th>
                </tr>
            </thead>
            <tbody>';

while ($row = $result->fetch_assoc()) {
    $html .= '<tr>
                <td>' . htmlspecialchars($row['id']) . '</td>
                <td>' . htmlspecialchars($row['department']) . '</td>
                <td>' . htmlspecialchars($row['name']) . '</td>
                <td>' . htmlspecialchars($row['no']) . '</td>
                <td>' . htmlspecialchars($row['date_time']) . '</td>
                <td>' . htmlspecialchars($row['status']) . '</td>
                <td>' . htmlspecialchars($row['location_id']) . '</td>
                <td>' . htmlspecialchars($row['id_number']) . '</td>
                <td>' . htmlspecialchars($row['verify_code']) . '</td>
                <td>' . htmlspecialchars($row['card_no']) . '</td>
              </tr>';
}

$html .= '</tbody></table>';

// Load HTML into DOMPDF
$dompdf->loadHtml($html);

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'landscape');

// Render the PDF
$dompdf->render();

// Output the generated PDF (1 = download and 0 = preview)
$dompdf->stream("userattendancereport.pdf", array("Attachment" => 1));
exit();
?>
