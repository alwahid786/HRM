<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userIdToUpdate = $_POST['user_id'];
    $leaveLimit = $_POST['leave_limit'];
    $leaveStartDate = $_POST['leave_start_date'];
    $leaveEndDate = $_POST['leave_end_date'];

    // Check if all fields are filled
    if (empty($userIdToUpdate) || empty($leaveLimit) || empty($leaveStartDate) || empty($leaveEndDate)) {
        echo "Error: All fields are required.";
        exit();
    }

    // Validate date formats
    $startDate = DateTime::createFromFormat('Y-m-d', $leaveStartDate);
    $endDate = DateTime::createFromFormat('Y-m-d', $leaveEndDate);

    if (!$startDate || !$endDate) {
        echo "Error: Invalid date format.";
        exit();
    }

    if ($startDate > $endDate) {
        echo "Error: Start date cannot be later than end date.";
        exit();
    }

    // Prepare the SQL query to update leave limit and leave period
    $stmt = $conn->prepare("UPDATE users SET leave_limit = ?, leave_start_date = ?, leave_end_date = ? WHERE id = ?");
    $stmt->bind_param("issi", $leaveLimit, $leaveStartDate, $leaveEndDate, $userIdToUpdate);

    if ($stmt->execute()) {
        header("Location: adminuserlist.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

$conn->close();
?>
