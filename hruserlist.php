
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

if ($userType != 'hradmin') {
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
            header("Location: hruserlist.php");
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Retrieve all users
$stmt = $conn->prepare("SELECT id, username, login, email, user_type, hiring_date, created_at, status FROM users");
$stmt->execute();
$result = $stmt->get_result();
?>
<?php
 include_once 'partials/hr/navbar.php';
?>
    
    <section class="container-fluid" style="padding: 60px 0 40px 0;">
      <div class="container-fluid">
      <h2>All Users</h2>
    <table id="admindatatable" class="table table-striped" style="width:100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Login</th>
                <th>Email</th>
                <th>User Type</th>
                <th>Hiring Date</th>
                <th>Created At</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                        if ($row['status'] == 'active') {
                            $statusClass = 'bg-green text-white';
                        } elseif ($row['status'] == 'blocked') {
                            $statusClass = 'bg-red text-white';
                        } else {
                            $statusClass = '';
                        }
                    ?>
                <tr>
                    <td><?php echo ($row['id']); ?></td>
                    <td><?php echo ($row['username']); ?></td>
                    <td><?php echo ($row['login']); ?></td>
                    <td><?php echo ($row['email']); ?></td>
                    <td><?php echo ($row['user_type']); ?></td>
                    <td><?php echo ($row['hiring_date']); ?></td>
                    <td><?php echo ($row['created_at']); ?></td>
                    <td><div class="<?php echo $statusClass; ?>"><?php echo ($row['status']); ?></div></td>
                    <td>
                        <?php if ($row['status'] == 'active'): ?>
                            <form method="post" action="">
                                <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="action" value="block" class="btn btn-warning btn-sm">Block</button>
                            </form>
                        <?php else: ?>
                            <form method="post" action="">
                                <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
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
<?php
 include_once 'partials/hr/footer.php';
?>