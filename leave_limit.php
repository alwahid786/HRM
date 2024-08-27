<?php
session_start();
require 'config.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize input
    $userIdToUpdate = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $leaveLimit = filter_input(INPUT_POST, 'leave_limit', FILTER_VALIDATE_INT);
    $leaveStartDate = filter_input(INPUT_POST, 'leave_start_date', FILTER_SANITIZE_STRING);
    $leaveEndDate = filter_input(INPUT_POST, 'leave_end_date', FILTER_SANITIZE_STRING);

    // Check if all fields are filled
    if ($userIdToUpdate === false || $leaveLimit === false || empty($leaveStartDate) || empty($leaveEndDate)) {
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

    // update data 
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
