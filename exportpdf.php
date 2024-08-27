<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

session_start();
require 'config.php';

// Retrieve all leave requests with action details
$stmt = $conn->prepare("SELECT l.id, u.username, l.start_date, l.end_date, l.duration, l.leave_type, l.reason, l.status,
                                COALESCE(a.username, 'N/A') AS action_by, l.action_date
                         FROM leaves l
                         JOIN users u ON l.user_id = u.id
                         LEFT JOIN users a ON l.action_by = a.id");
$stmt->execute();
$result = $stmt->get_result();

// Initialize DOMPDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

// Create HTML content
$html = '<h2>All Leave Requests</h2>';
$html .= '<table border="1" width="100%" style="border-collapse: collapse;">
            <thead>
                <tr>
                    <th>id</th>
                    <th>Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Duration</th>
                    <th>Leave Type</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Action By</th>
                    <th>Action Date</th>
                </tr>
            </thead>
            <tbody>';

while ($row = $result->fetch_assoc()) {
    $html .= '<tr>
                <td>' . ($row['id']) . '</td>
                <td>' . ($row['username']) . '</td>
                <td>' . ($row['start_date']) . '</td>
                <td>' . ($row['end_date']) . '</td>
                <td>' . ($row['duration']) . '</td>
                <td>' . ($row['leave_type']) . '</td>
                <td>' . ($row['reason']) . '</td>
                <td>' . ($row['status']) . '</td>
                <td>' . ($row['action_by']) . '</td>
                <td>' . ($row['action_date']) . '</td>
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
$dompdf->stream("LeaveRequests.pdf", array("Attachment" => 1));
exit();
?>
