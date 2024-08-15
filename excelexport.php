<?php
require 'vendor/autoload.php'; 
require 'config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (isset($_POST['export']) && $_POST['export'] == 'true') {
    // Fetch all leave requests from the database
    $stmt = $conn->prepare("SELECT l.id, u.username, l.start_date, l.end_date, l.duration, l.leave_type, l.reason, l.status,
                                    COALESCE(a.username, 'N/A') AS action_by, l.action_date
                             FROM leaves l
                             JOIN users u ON l.user_id = u.id
                             LEFT JOIN users a ON l.action_by = a.id");
    $stmt->execute();
    $result = $stmt->get_result();

    // Create a new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set the header row values
    $sheet->setCellValue('A1', 'ID');
    $sheet->setCellValue('B1', 'Name');
    $sheet->setCellValue('C1', 'Start Date');
    $sheet->setCellValue('D1', 'End Date');
    $sheet->setCellValue('E1', 'Duration');
    $sheet->setCellValue('F1', 'Leave Type');
    $sheet->setCellValue('G1', 'Reason');
    $sheet->setCellValue('H1', 'Status');
    $sheet->setCellValue('I1', 'Action By');
    $sheet->setCellValue('J1', 'Action Date');

    $rowNumber = 2;
    while ($row = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $rowNumber, $row['id']);
        $sheet->setCellValue('B' . $rowNumber, $row['username']);
        $sheet->setCellValue('C' . $rowNumber, $row['start_date']);
        $sheet->setCellValue('D' . $rowNumber, $row['end_date']);
        $sheet->setCellValue('E' . $rowNumber, $row['duration']);
        $sheet->setCellValue('F' . $rowNumber, $row['leave_type']);
        $sheet->setCellValue('G' . $rowNumber, $row['reason']);
        $sheet->setCellValue('H' . $rowNumber, $row['status']);
        $sheet->setCellValue('I' . $rowNumber, $row['action_by']);
        $sheet->setCellValue('J' . $rowNumber, $row['action_date']);
        $rowNumber++;
    }

    // Set the filename
    $filename = "leave_requests_" . date('YmdHis') . ".xlsx";

    // Redirect output to a client’s web browser (Excel)
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    // Save the spreadsheet to the output
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    $stmt->close();
    $conn->close();
    exit();
}
?>
