<?php
session_start();
require 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to the login page
    header("Location: login.php");
    exit();
}

// Retrieve the user's ID from the session
$userId = $_SESSION['user_id'];

// Prepare and execute the query to fetch the user's data
$stmt = $conn->prepare("SELECT id, username, login, email, user_type, hiring_date, created_at, status FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($id, $username, $login, $email, $userType, $hiringDate, $createdAt, $status);
$stmt->fetch();
$stmt->close();

// Prepare and execute the query to fetch the user's leave data
$stmt = $conn->prepare("SELECT id, start_date, end_date, duration, leave_type, reason, status, action_by, action_date FROM leaves WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$leaveResult = $stmt->get_result();
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

<section class="container" style="padding: 60px 0 40px 0;">
    <h2>User Profile</h2>
    <table class="table table-bordered">
        <tr>
            <th>ID</th>   
            <td><?php echo htmlspecialchars($id); ?></td>
        </tr>
        <tr>
            <th>User</th>
            <td><?php echo htmlspecialchars($username); ?></td>
        </tr>
        <tr>
            <th>Login</th>
            <td><?php echo htmlspecialchars($login); ?></td>
        </tr>
        <tr>
            <th>Email</th>
            <td><?php echo htmlspecialchars($email); ?></td>
        </tr>
        <tr>
            <th>User Type</th>
            <td><?php echo htmlspecialchars($userType); ?></td>
        </tr>
        <tr>
            <th>Hiring Date</th>
            <td><?php echo htmlspecialchars($hiringDate); ?></td>
        </tr>
        <tr>
            <th>Created At</th>
            <td><?php echo htmlspecialchars($createdAt); ?></td>
        </tr>
        <tr>
            <th>Status</th>
            <td><?php echo htmlspecialchars($status); ?></td>
        </tr>
    </table>
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

<?php
$stmt->close();
$conn->close();
?>
