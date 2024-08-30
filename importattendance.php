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

        
            $department = ($row[0]);
            $name = ($row[1]);
            $no = ($row[2]);

            // $dateTime = ($row[3]);
            // $dateTimeFormatted = date('Y-d-m H:i:s', strtotime($dateTime));

            $dateString = ($row[3]);
            $dateTime = DateTime::createFromFormat('d/m/Y H:i:s', $dateString);
            $dateTimeFormatted = $dateTime->format('Y-m-d H:i:s');

            $status = ($row[4]);
            $locationId = ($row[5]);
            $idNumber = ($row[6]);
            $verifyCode = ($row[7]);
            $cardNo = ($row[8]);

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
