<?php
session_start();
require 'config.php';


if (!isset($_SESSION['user_id'])) {
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
    }
}

// Retrieve all users data without admin data 
$stmt = $conn->prepare("SELECT id, user_no, username, login, email, user_type, hiring_date, role, salary, created_at, status, leave_limit, leave_start_date, leave_end_date 
                        FROM users WHERE user_type != 'admin'");
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include_once 'partials/admin/navbar.php'; ?>

<section class="container-fluid" style="padding: 60px 0 40px 0;">
    <div class="container-fluid">
        <h2>All Users</h2>
        <table id="admindatatable" class="table table-striped" style="width:100%">
            <thead>
                <tr>
                    <th>User No</th>
                    <th>Username</th>
                    <th>Login</th>
                    <th>Email</th>
                    <th>User Type</th>
                    <th>Hiring Date</th>
                    <th>Role</th>
                    <th>Salary</th>
                    <th>Created At</th>
                    <th>Status</th>
                    <th>Edit</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
                    $statusClass = '';
                    if ($row['status'] == 'active') {
                        $statusClass = 'bg-green text-white';
                    } elseif ($row['status'] == 'blocked') {
                        $statusClass = 'bg-red text-white';
                    }
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['user_no']); ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['login']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['user_type']); ?></td>
                        <td><?php echo htmlspecialchars($row['hiring_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['role']); ?></td>
                        <td><?php echo htmlspecialchars($row['salary']); ?></td>
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        <td>
                            <div class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['status']); ?></div>
                        </td>
                        <td class="d-flex justify-content-center align-items-center">
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#leavelimitModal"
                                data-userid="<?php echo htmlspecialchars($row['id']); ?>"
                                data-leavelimit="<?php echo htmlspecialchars($row['leave_limit']); ?>"
                                data-leavestartdate="<?php echo htmlspecialchars($row['leave_start_date']); ?>"
                                data-leaveenddate="<?php echo htmlspecialchars($row['leave_end_date']); ?>">
                                <img src="images/icons/edit.png" width="24px" height="24px">
                            </button>
                        </td>
                        <td>
                            <?php if ($row['status'] == 'active'): ?>
                                <form method="post" action="">
                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                    <button type="submit" name="action" value="block" class="btn btn-warning btn-sm">Block</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="">
                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($row['id']); ?>">
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

<?php include_once 'partials/admin/footer.php'; ?>

<?php
$stmt->close();
$conn->close();
?>

<!-- Leave Limit Modal -->
<div class="modal fade" id="leavelimitModal" tabindex="-1" aria-labelledby="leavelimitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: linear-gradient(to right, #35592d, #1e5d75) !important; color: #fff;">
            <div class="modal-header">
                <h5 class="modal-title" id="leavelimitModalLabel">Update Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="leave_limit.php">
                    <div class="mb-3">
                        <input type="hidden" name="user_id" id="modalUserId" value="">
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
                        <label for="leave_end_date" class="form-label">Salary</label>
                        <input type="number" name="salary" id="modalsalary" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="leave_end_date" class="form-label">Email</label>
                        <input type="email" name="email" id="modalemail" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="leave_end_date" class="form-label">login</label>
                        <input type="text" name="login" id="modallogin" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-info btn-sm">Update Limit</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('#leavelimitModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var userId = button.data('userid');
            var leaveLimit = button.data('leavelimit');
            var leaveStartDate = button.data('leavestartdate');
            var leaveEndDate = button.data('leaveenddate');

            var modal = $(this);
            modal.find('#modalUserId').val(userId);
            modal.find('#modalLeaveLimit').val(leaveLimit);
            modal.find('#modalLeaveStartDate').val(leaveStartDate);
            modal.find('#modalLeaveEndDate').val(leaveEndDate);
        });
    });
</script>