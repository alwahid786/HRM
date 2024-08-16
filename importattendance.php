<?php
require 'vendor/autoload.php'; 
require 'config.php'; 

use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;


if (isset($_POST['export']) && $_POST['export'] === 'true' && isset($_FILES['attendanceFile'])) {
    $file = $_FILES['attendanceFile']['tmp_name'];
    $fileName = $_FILES['attendanceFile']['name'];
    $fileError = $_FILES['attendanceFile']['error'];

    // Check for upload errors
    if ($fileError !== UPLOAD_ERR_OK) {
        die('Error uploading file: ' . $fileError);
    }

    // Check file extension
    $allowedExtensions = ['xls', 'xlsx'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($fileExtension, $allowedExtensions)) {
        die('Invalid file type. Only .xls and .xlsx files are allowed.');
    }

    try {
        if ($fileExtension === 'xls') {
            $reader = new Xls();
        } else {
            $reader = new Xlsx();
        }

        // Load the file into PhpSpreadsheet
        $spreadsheet = $reader->load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

    
        $stmt = $conn->prepare("INSERT INTO attendance 
            (department, name, no, date_time, status, location_id, id_number, verify_code, card_no) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if ($stmt === false) {
            die('Error preparing statement: ' . $conn->error);
        }

        // Loop through the rows and insert them into the database
        foreach ($rows as $index => $row) {
            if ($index === 0) continue; 

        
            $department = htmlspecialchars($row[0]);
            $name = htmlspecialchars($row[1]);
            $no = htmlspecialchars($row[2]);

            $dateTime = htmlspecialchars($row[3]);
            $dateTimeFormatted = date('Y-m-d H:i:s', strtotime($dateTime));

            $status = htmlspecialchars($row[4]);
            $locationId = htmlspecialchars($row[5]);
            $idNumber = htmlspecialchars($row[6]);
            $verifyCode = htmlspecialchars($row[7]);
            $cardNo = htmlspecialchars($row[8]);

            // Bind the parameters
            $stmt->bind_param(
                "ssisssisi", 
                $department, $name, $no, $dateTimeFormatted, $status, $locationId, $idNumber, $verifyCode, $cardNo
            );

            if (!$stmt->execute()) {
                echo "Error inserting data for row $index: " . $stmt->error . "<br>";
            }
        }
        $stmt->close();

        echo "Data imported successfully!";
        header("location: userattendance.php");
    } catch (Exception $e) {
        die('Error loading file "' . pathinfo($fileName, PATHINFO_BASENAME) . '": ' . $e->getMessage());
    }
}
$conn->close();
?>
