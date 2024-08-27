<?php
session_start();
require 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to the login page
    header("Location: login.php");
    exit();
}

// Retrieve the user's type from the session or database
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($userType);
$stmt->fetch();
$stmt->close();

// Check if the user type is hradmin
if ($userType != 'hradmin') {
    // If not hradmin, redirect to the login page
    header("Location: login.php");
    exit();
}

// Handle form submission for approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['leave_id']) && isset($_POST['action'])) {
        $leaveId = $_POST['leave_id'];
        $action = $_POST['action'];
        $status = ($action == 'approve') ? 'Approved' : 'Rejected';
        $actionBy = $userId;
        $actionDate = date('Y-m-d H:i:s');

        // Update the leave request status and action details
        $stmt = $conn->prepare("UPDATE leaves SET status = ?, action_by = ?, action_date = ? WHERE id = ?");
        $stmt->bind_param("sisi", $status, $actionBy, $actionDate, $leaveId);

        if ($stmt->execute()) {
            // Redirect back to the same page to refresh the data
            header("Location: hrapproval.php");
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Retrieve all pending leave requests except those created by the current hradmin
$stmt = $conn->prepare("SELECT l.id, u.username, l.start_date, l.end_date, l.duration, l.leave_type, l.reason, l.status
                         FROM leaves l
                         JOIN users u ON l.user_id = u.id
                         WHERE l.status = 'Requested' AND l.user_id != ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

// Count the number of pending leave requests
$pendingCount = $result->num_rows;

include_once 'partials/hr/navbar.php';
?>

<section class="container-fluid" style="padding: 60px 0 40px 0;">
     <div class="container-fluid">
     <h2>Manage Leave Requests</h2>
    <table id="admindatatable" class="table table-striped" style="width:100%">
        <thead>
            <tr>
                <th>User Name</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Duration</th>
                <th>Leave Type</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo ($row['username']); ?></td>
                    <td><?php echo ($row['start_date']); ?></td>
                    <td><?php echo ($row['end_date']); ?></td>
                    <td><?php echo ($row['duration']); ?></td>
                    <td><?php echo ($row['leave_type']); ?></td>
                    <td><?php echo ($row['reason']); ?></td>
                    <td><?php echo ($row['status']); ?></td>
                    <td>
                        <form method="post" action="">
                            <input type="hidden" name="leave_id" value="<?php echo ($row['id']); ?>">
                            <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                            <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
     </div>
</section>

<?php
include_once 'partials/hr/footer.php';
?>
