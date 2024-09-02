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

if ($userType != 'admin' && $userType != 'hradmin' && $userType != 'user') {
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


// calculate totall efficiency points 
$Total_Efficiency_Points_Query = "SELECT SUM(efficiency_points) AS total_points FROM efficiency_categories";
$Total_Efficiency_Points = $conn->query($Total_Efficiency_Points_Query);
$total_points = 0;
if ($Total_Efficiency_Points->num_rows > 0) {
    $row = $Total_Efficiency_Points->fetch_assoc();
    $total_points = $row['total_points'];
}
?>

<?php
// Include the navbar based on user type
if ($userType === 'admin') {
    include_once 'partials/admin/navbar.php';
} elseif ($userType === 'hradmin') {
    include_once 'partials/hr/navbar.php';
} else {
    include_once 'partials/users/navbar.php';
}
?>

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
                        <td><?php echo $total_points; ?></td>
                        <td><?php echo $detail['created_at'] ? date('Y-M-d', strtotime($detail['created_at'])) : ''; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php
// Include the footer based on user type
if ($userType === 'admin') {
    include_once 'partials/admin/footer.php';
} elseif ($userType === 'hradmin') {
    include_once 'partials/hr/footer.php';
} else {
    include_once 'partials/users/footer.php';
}
?>