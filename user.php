<?php
session_start();
require 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
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

// Check if the user type is 'user'
if ($userType != 'user') {
    header("Location: login.php");
    exit();
}

// Handle cancel request
if (isset($_GET['id'])) {
    $leaveId = intval($_GET['id']);

    // Check if the leave request belongs to the logged-in user
    $stmt = $conn->prepare("SELECT user_id FROM leaves WHERE id = ?");
    $stmt->bind_param("i", $leaveId);
    $stmt->execute();
    $stmt->bind_result($ownerId);
    $stmt->fetch();
    $stmt->close();

    if ($ownerId == $userId) {
        // Update the leave request status to 'Cancelled' and record action details
        $stmt = $conn->prepare("UPDATE leaves SET status = 'Cancelled', action_by = ?, action_date = NOW() WHERE id = ?");
        $stmt->bind_param("ii", $userId, $leaveId);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Retrieve the leave requests for the logged-in user
$stmt = $conn->prepare("SELECT id, start_date, end_date, duration, leave_type, reason, status, action_by, action_date FROM leaves WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$leaves = [];
while ($row = $result->fetch_assoc()) {
    $leaves[] = $row;
}
$stmt->close();

// Fetch usernames for action_by
$usernames = [];
foreach ($leaves as $leave) {
    if ($leave['action_by'] && !isset($usernames[$leave['action_by']])) {
        $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->bind_param("i", $leave['action_by']);
        $stmt->execute();
        $stmt->bind_result($username);
        $stmt->fetch();
        $usernames[$leave['action_by']] = $username;
        $stmt->close();
    }
}

$conn->close();
?>

<?php include_once 'partials/users/navbar.php'; ?>
<section class="container-fluid" style="padding: 60px 0 40px 0;">
   <div class="container-fluid">
   <h2>My Leave Requests</h2>
    <table id="leaveTable" class="table table-striped" style="width:100%">
        <thead>
            <tr>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Duration</th>
                <th>Leave Type</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Action By</th>
                <th>Action Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($leaves as $leave): ?>
                <?php
                if ($leave['status'] == 'Requested') {
                    $statusClass = 'bg-orange text-white';
                } elseif ($leave['status'] == 'Approved') {
                    $statusClass = 'bg-green text-white';
                } elseif ($leave['status'] == 'Cancelled') {
                    $statusClass = 'bg-red text-white';
                } else {
                    $statusClass = '';
                }
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($leave['start_date']); ?></td>
                    <td><?php echo htmlspecialchars($leave['end_date']); ?></td>
                    <td><?php echo htmlspecialchars($leave['duration']); ?></td>
                    <td><?php echo htmlspecialchars($leave['leave_type']); ?></td>
                    <td><?php echo htmlspecialchars($leave['reason']); ?></td>
                    <td>
                        <div class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars($leave['status']); ?></div>
                    </td>
                    <td>
                        <?php echo isset($usernames[$leave['action_by']]) ? htmlspecialchars($usernames[$leave['action_by']]) : 'N/A'; ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($leave['action_date']) ? date('Y-m-d H:i:s', strtotime($leave['action_date'])) : 'N/A'; ?>
                    </td>
                    <td>
                        <?php if ($leave['status'] == 'Requested'): ?>
                            <a href="?id=<?php echo $leave['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to cancel this leave request?');">Cancel</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
   </div>
</section>

<?php include_once 'partials/users/footer.php'; ?>

<script>
    $(document).ready(function() {
        $('#leaveTable').DataTable();
    });
</script>