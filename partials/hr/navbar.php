<?php

include 'config.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Retrieve the pending leave request count for the badge
$stmt = $conn->prepare("SELECT COUNT(*) FROM leaves WHERE status = 'Requested'");
$stmt->execute();
$stmt->bind_result($pendingCount);
$stmt->fetch();
$stmt->close();

// Retrieve the username for the logged-in user
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
// $stmt->close();

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['changePassword'])) {
    $newPassword = $_POST['newPassword'];
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update the user's password
    $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $updateStmt->bind_param('si', $hashedPassword, $userId);
    if ($updateStmt->execute()) {
        echo "Password is updated successfully!";
    } else {
        echo "Error updating password.";
    }
    $updateStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hr - Index</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
         body{
            padding: 60px 0 40px 0;
             background: #f4f4f4; 
        }

        nav {
            background: linear-gradient(to right, #35592d, #1e5d75) !important;
            color: #fff;
        }

        .dropdownbg {
            background: linear-gradient(to bottom, #81ff43, #2affc3);
        }

        .dropdownbg li a:hover {
            background: #35592e;
            color: #fff;
        }

        /* Table Header */
        /* #admindatatable thead th {
            background-color: #216891;
            color: #ffffff !important;
            text-align: center;
        } */

        /* Table Body Rows */
        #admindatatable tbody tr {
            background-color: #216891;
            color: #ffffff;
            text-align: center;
        }

        /* Table Cells */
        #admindatatable tbody td {
            background-color: #fff;
            color: black;
            text-align: center;
        }

        /* Table Borders */
        #admindatatable,
        #admindatatable td,
        #admindatatable th {
            border-color: #216891;
            text-align: center;
            text-align: center;
        }

        /* Table Hover Effect */
        #admindatatable tbody tr:hover {
            background-color: #1a2274;
            color: #ffffff;
        }

        /* Sorting Icons Color */
        .dataTables_wrapper .dataTables_sort_icon,
        .dataTables_wrapper .sorting:after,
        .dataTables_wrapper .sorting_desc:after,
        .dataTables_wrapper .sorting_asc:after {
            color: #ffffff !important;
        }

        /* Optional: Adjust the position or size of the sort icons */
        .dataTables_wrapper .sorting:after,
        .dataTables_wrapper .sorting_desc:after,
        .dataTables_wrapper .sorting_asc:after {
            font-size: 12px;
            margin-left: 5px;
        }

        /* Optional: Customize Pagination and Other Controls */
        /* .dataTables_wrapper .dataTables_paginate .paginate_button,
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            background-color: #216891 !important;
            color: #ffffff;
            border: 1px solid #216891 !important;
        }
           */
        .dataTables_filter {
            display: flex;
            justify-content: end;
        }

        /* .dataTables_wrapper .dataTables_paginate .paginate_button:hover,
        .dataTables_wrapper .dataTables_length select:hover,
        .dataTables_wrapper .dataTables_filter input:hover {
            background-color: #1a5274;
            color: #ffffff;
            border: 1px solid #1a5274;
        } */

        .bg-green {
            background-color: green !important;
            border-radius: 20px;
            padding: 2px 6px;
            text-align: center;
        }

        .bg-orange {
            background-color: orange !important;
            border-radius: 20px;
            padding: 2px 6px;
            text-align: center;
        }

        .bg-red {
            background-color: red !important;
            border-radius: 20px;
            padding: 2px 6px;
            text-align: center;
        }

        .text-white {
            color: white !important;
            border-radius: 20px;
        }

        .footer .col-12{
            box-shadow: 10px -5px 0 0 rgba(0, 0, 0, 0.3s);
            background: linear-gradient(to right, #35592d, #1e5d75) !important;
            color: #fff !important;
        }


        .payslip-container {
            width: 700px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #000;
        }
        .payslip-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .payslip-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        .company-info, .employee-info {
            margin-bottom: 20px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 8px 5px;
        }
        .info-table td:first-child {
            text-align: left;
        }
        .info-table td:last-child {
            text-align: right;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid black;
        }
        th {
            background-color: #f3f3f3;
        }
        .total-row td {
            font-weight: bold;
        }
        .net-pay {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin: 20px 0;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        .signature {
            text-align: center;
            width: 40%;
        }
        .footer-slip {
            text-align: center;
            margin-top: 50px;
            font-style: italic;
            font-size: 12px;
        }
        .stamp {
            display: flex;
            justify-content: end; 
            align-items:end; 
            margin: 0 20px 0 0; 
        }
    </style>
</head>
<body>

    <!-- navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container-fluid">
        <span><img src="images/icons/brand.png"></span><a class="navbar-brand text-white" href="#">Tetra Technologies</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavDropdown">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="hr.php"><img src="images/icons/menu.png" width="25px" height="25px"></a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Hr Admin
                        </a>
                        <ul class="dropdown-menu dropdownbg">
                            <li><a class="dropdown-item" href="hrcreateuser.php">Create User</a></li>
                            <li><a class="dropdown-item" href="hruserlist.php">List of Users</a></li>
                            <li><a class="dropdown-item" href="userattendance.php">User's Attendance</a></li>
                            <li><a class="dropdown-item" href="userpayroll.php">User Payroll</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="hrapproval.php">
                            Approval
                            <?php if ($pendingCount > 0): ?>
                                <span class="badge badge-info bg-danger"><?php echo $pendingCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="hrleaves.php">Leaves</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item text-white d-flex justify-content-center align-items-center" style="margin: 5px 6px 0 0;">
                        <h5><?php echo $username; ?></h5>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="profile.php"><img src="images/icons/profile.png" width="25px" height="25px"></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal"><img src="images/icons/password.png" width="25px" height="25px"></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="logout.php"><img src="images/icons/logout.png" width="25px" height="25px"></a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style=" background: linear-gradient(to right, #35592d, #1e5d75) !important; color: #fff;">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Form for changing password -->
                    <form>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input style="background-color: #216891;" type="password" class="form-control" id="newPassword">
                        </div>
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>