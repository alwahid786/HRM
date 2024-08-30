<?php
session_start();
require 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Handle block/unblock action
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['user_id']) && isset($_POST['action'])) {
        $userIdToChange = $_POST['user_id'];
        $action = $_POST['action'];
        $status = ($action == 'block') ? 'blocked' : 'active';

        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $userIdToChange);

        if ($stmt->execute()) {
            header("Location: adminuserlist.php");
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['update_user_id'])) {
        // Update user details from modal form
        $userIdToUpdate = $_POST['update_user_id'];
        $leaveLimit = $_POST['leave_limit'];
        $leaveStartDate = $_POST['leave_start_date'];
        $leaveEndDate = $_POST['leave_end_date'];
        $salary = $_POST['salary'];
        $email = $_POST['email'];
        $login = $_POST['login'];

        // Check if all fields are filled
        if (empty($userIdToUpdate) || empty($leaveLimit) || empty($leaveStartDate) || empty($leaveEndDate) || empty($salary) || empty($email) || empty($login)) {
            echo "Error: All fields are required.";
            exit();
        }

        // Validate leave limit (integer)
        if (!filter_var($leaveLimit, FILTER_VALIDATE_INT)) {
            echo "Error: Invalid leave limit.";
            exit();
        }

        // Validate salary (integer)
        if (!filter_var($salary, FILTER_VALIDATE_FLOAT)) {
            echo "Error: Invalid salary.";
            exit();
        }

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "Error: Invalid email format.";
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

        $stmt = $conn->prepare("UPDATE users SET leave_limit = ?, leave_start_date = ?, leave_end_date = ?, salary = ?, email = ?, login = ? WHERE id = ?");
        $stmt->bind_param("issdssi", $leaveLimit, $leaveStartDate, $leaveEndDate, $salary, $email, $login, $userIdToUpdate);

        if ($stmt->execute()) {
            header("Location: adminuserlist.php");
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Retrieve all users data without admin data 
$stmt = $conn->prepare("SELECT id, user_no, username, login, email, user_type, hiring_date, role, salary, created_at, status, leave_limit, leave_start_date, leave_end_date 
                        FROM users WHERE user_type != 'admin'");
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include_once 'partials/admin/navbar.php'; ?>

<style>
    table,
    table thead,
    table thead tr,
    table thead tr th,
    table tbody tr td {
        border: none;
    }

    table thead,
    table thead tr,
    table thead tr th {
        background-color: #37daaf2e !important;
        height: 50px;
        /* justify-content: center; */
        /* vertical-align: center; */
    }

    .active-badge {
        background-color: #28c30ca6;
        display: flex;
        align-items: center;
        justify-content: center;

    }

    .inactive-badge {
        background-color: #ff000087;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-blue {
        background-color: #55b6fb57;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .block-badge {
        background-color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid red;
        color: red;
        font-weight: 600;

    }

    .block-badge:hover {
        background-color: #ff000087;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid red;
        color: black;
        font-weight: 600;
    }
</style>
<section class="container-fluid p-5">
    <div class="container-fluid bg-white p-3" style="border-radius: 10px;box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;">
        <h2>All Users</h2>
        <table cellspacing="0" cellpadding="0" id="admindatatable" class="table mt-3" style="width:100%">
            <thead>
                <tr>
                    <th style="border-top-left-radius: 10px; text-align: center; justify-content: center;">User No</th>
                    <th style="text-align: center; justify-content: center;">Username</th>
                    <th style="text-align: center; justify-content: center;">Login</th>
                    <th style="text-align: center; justify-content: center;">Email</th>
                    <th style="text-align: center; justify-content: center;">User Type</th>
                    <th style="text-align: center; justify-content: center;">Hiring Date</th>
                    <th style="text-align: center; justify-content: center;">Role</th>
                    <th style="text-align: center; justify-content: center;">Salary</th>
                    <th style="text-align: center; justify-content: center;">Created At</th>
                    <th style="text-align: center; justify-content: center;">Status</th>
                    <th style="text-align: center; justify-content: center;">Edit</th>
                    <th style="border-top-right-radius: 10px; text-align: center; justify-content: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
                    $statusClass = '';
                    if ($row['status'] == 'active') {
                        $statusClass = 'active-badge text-white';
                    } elseif ($row['status'] == 'blocked') {
                        $statusClass = 'inactive-badge text-white';
                    }
                    ?>
                    <tr>
                        <td><?php echo ($row['user_no']); ?></td>
                        <td><?php echo ($row['username']); ?></td>
                        <td><?php echo ($row['login']); ?></td>
                        <td><?php echo ($row['email']); ?></td>
                        <td><?php echo ($row['user_type']); ?></td>
                        <td><?php echo ($row['hiring_date']); ?></td>
                        <td><?php echo ($row['role']); ?></td>
                        <td><?php echo ($row['salary']); ?></td>
                        <td><?php echo ($row['created_at']); ?></td>
                        <td>
                            <div class="<?php echo $statusClass; ?> rounded-pill"><?php echo ($row['status']); ?></div>
                        </td>
                        <td class="d-flex justify-content-center align-items-center">
                            <button type="button" class="btn btn-blue" data-bs-toggle="modal" data-bs-target="#leavelimitModal"
                                data-userid="<?php echo ($row['id']); ?>"
                                data-leavelimit="<?php echo ($row['leave_limit']); ?>"
                                data-leavestartdate="<?php echo ($row['leave_start_date']); ?>"
                                data-leaveenddate="<?php echo ($row['leave_end_date']); ?>"
                                data-salary="<?php echo ($row['salary']); ?>"
                                data-email="<?php echo ($row['email']); ?>"
                                data-login="<?php echo ($row['login']); ?>">
                                <img src="images/icons/edit.png" width="24px" height="24px">
                            </button>
                        </td>
                        <td>
                            <?php if ($row['status'] == 'active'): ?>
                                <form method="post" action="">
                                    <input type="hidden" name="user_id" value="<?php echo ($row['id']); ?>">
                                    <button type="submit" name="action" value="block" class="btn block-badge btn-sm">Block</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="">
                                    <input type="hidden" name="user_id" value="<?php echo ($row['id']); ?>">
                                    <button type="submit" name="action" value="unblock" class="btn btn-success btn-sm">Unblock</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Leave Limit Modal -->
<div class="modal fade" id="leavelimitModal" tabindex="-1" aria-labelledby="leavelimitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: linear-gradient(to right, #35592d, #1e5d75) !important; color: #fff;">
            <div class="modal-header">
                <h5 class="modal-title" id="leavelimitModalLabel">Update Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="adminuserlist.php">
                    <input type="hidden" name="update_user_id" id="modalUserId">
                    <div class="mb-3">
                        <label for="leave_limit" class="form-label">Leave Limit</label>
                        <input type="number" name="leave_limit" id="modalLeaveLimit" class="form-control" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="leave_start_date" class="form-label">Start Date</label>
                        <input type="date" name="leave_start_date" id="modalLeaveStartDate" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="leave_end_date" class="form-label">End Date</label>
                        <input type="date" name="leave_end_date" id="modalLeaveEndDate" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="salary" class="form-label">Salary</label>
                        <input type="number" step="0.01" name="salary" id="modalSalary" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" id="modalEmail" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="login" class="form-label">Login</label>
                        <input type="text" name="login" id="modalLogin" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('button[data-bs-toggle="modal"]').forEach(button => {
            button.addEventListener('click', function() {
                const userId = button.getAttribute('data-userid');
                const leaveLimit = button.getAttribute('data-leavelimit');
                const leaveStartDate = button.getAttribute('data-leavestartdate');
                const leaveEndDate = button.getAttribute('data-leaveenddate');
                const salary = button.getAttribute('data-salary');
                const email = button.getAttribute('data-email');
                const login = button.getAttribute('data-login');

                document.getElementById('modalUserId').value = userId;
                document.getElementById('modalLeaveLimit').value = leaveLimit;
                document.getElementById('modalLeaveStartDate').value = leaveStartDate;
                document.getElementById('modalLeaveEndDate').value = leaveEndDate;
                document.getElementById('modalSalary').value = salary;
                document.getElementById('modalEmail').value = email;
                document.getElementById('modalLogin').value = login;
            });
        });
    });
</script>


<?php include_once 'partials/admin/footer.php'; ?>

<?php
$stmt->close();
$conn->close();
?>