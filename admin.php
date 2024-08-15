<?php
session_start();
require 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($userType);
$stmt->fetch();
$stmt->close();

if ($userType != 'admin') {
    header("Location: login.php");
    exit();
}

$stmt = $conn->prepare("SELECT l.id, u.username, l.start_date, l.end_date, l.duration, l.leave_type, l.reason, l.status,
                                COALESCE(a.username, 'N/A') AS action_by, l.action_date
                         FROM leaves l
                         JOIN users u ON l.user_id = u.id
                         LEFT JOIN users a ON l.action_by = a.id");
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include_once 'partials/admin/navbar.php'; ?>
<section class="container-fluid" style="padding: 60px 0 40px 0;">
  <div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center">
            <h2 class="text-dark">All Leave Requests</h2>
            <div>
                <form method="post" action="excelexport.php" class="d-inline">
                    <input type="hidden" name="export" value="true">
                    <button type="submit" class="btn" style="background-color: #11965c;"><img src="images/icons/excel.png" width="25px" height="25px"></button>
                </form>
                <a href="exportpdf.php" class="btn btn-danger ms-2" style="background-color: #f5180c;"><img src="images/icons/pdf.png" width="25px" height="25px"></a>
            </div>
        </div>
        <table id="admindatatable" class="table table-striped" style="width:100%; border-radius: 20px !important;">
            <thead>
                <tr>
                    <th>id</th>
                    <th>Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Duration</th>
                    <th>Leave Type</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Action By</th>
                    <th>Action Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
                        if ($row['status'] == 'Requested') {
                            $statusClass = 'bg-orange text-white';
                        } elseif ($row['status'] == 'Approved') {
                            $statusClass = 'bg-green text-white';
                        } elseif ($row['status'] == 'Rejected' || $row['status'] == 'Cancelled') {
                            $statusClass = 'bg-red text-white';
                        } else {
                            $statusClass = '';
                        }
                    ?>
                    <tr>
                        <?php
                          $userType != 'admin';
                        ?>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['start_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['end_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['duration']); ?></td>
                        <td><?php echo htmlspecialchars($row['leave_type']); ?></td>
                        <td><?php echo htmlspecialchars($row['reason']); ?></td>
                        <td><div class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['status']); ?></div></td>
                        <td><?php echo htmlspecialchars($row['action_by']); ?></td>
                        <td><?php echo htmlspecialchars($row['action_date']); ?></td>
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