<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['user_id'])) {
    $userId = intval($_GET['user_id']);
    $query = "
    SELECT u.username, u.email, u.user_no, u.user_type, u.role, u.status,
           uep.points AS assigned_points, uep.total_points, uep.created_at
    FROM users u
    LEFT JOIN user_efficiency_points uep ON u.id = uep.user_id
    WHERE u.id = ?
";


    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userEfficiencyDetails = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    echo "No user ID specified.";
    exit();
}
?>

<?php include_once 'partials/admin/navbar.php'; ?>

<section class="container-fluid" style="padding: 60px 0 40px 0;">
    <div class="container-fluid">
        <h2>Efficiency Details for User:</h2>
        <table class="table table-striped" style="width:100%">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>User Type</th>
                    <th>Role</th>
                    <!-- <th>Status</th> -->
                    <th>Points Assigned</th>
                    <th>Total Points</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($userEfficiencyDetails as $detail): ?>
                    <tr>
                        <td><?php echo ($detail['username']); ?></td>
                        <td><?php echo ($detail['email']); ?></td>
                        <td><?php echo ($detail['user_type']); ?></td>
                        <td><?php echo ($detail['role']); ?></td>
                        <!-- <td><?php echo ($detail['status']); ?></td> -->
                        <td><?php echo ($detail['assigned_points']); ?></td>
                        <td><?php echo ($detail['total_points']); ?></td>
                        <td><?php echo date('Y-M-d', strtotime($detail['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

