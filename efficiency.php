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

if ($userType === 'user') {
    $query = "
        SELECT u.id, u.user_no, u.username, u.email, u.user_type, u.role, u.status,
               COALESCE(uep.points, 0) AS efficiency_points,
               COALESCE(AVG(uep1.points), 0) AS efficiency_average
        FROM users u
        LEFT JOIN (
            SELECT user_id, points
            FROM user_efficiency_points uep1
            WHERE uep1.id = (
                SELECT MAX(uep2.id)
                FROM user_efficiency_points uep2
                WHERE uep2.user_id = uep1.user_id
            )
        ) uep ON u.id = uep.user_id
        LEFT JOIN user_efficiency_points uep1 ON u.id = uep1.user_id
        WHERE u.id = ?
        GROUP BY u.id, u.user_no, u.username, u.email, u.user_type, u.role, u.status, uep.points
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
} else {
    $query = "
        SELECT u.id, u.user_no, u.username, u.email, u.user_type, u.role, u.status,
               COALESCE(uep.points, 0) AS efficiency_points,
               COALESCE(AVG(uep1.points), 0) AS efficiency_average
        FROM users u
        LEFT JOIN (
            SELECT user_id, points
            FROM user_efficiency_points uep1
            WHERE uep1.id = (
                SELECT MAX(uep2.id)
                FROM user_efficiency_points uep2
                WHERE uep2.user_id = uep1.user_id
            )
        ) uep ON u.id = uep.user_id
        LEFT JOIN user_efficiency_points uep1 ON u.id = uep1.user_id
        WHERE u.user_type != 'admin' AND u.status != 'blocked'
        GROUP BY u.id, u.user_no, u.username, u.email, u.user_type, u.role, u.status, uep.points
    ";
    $stmt = $conn->prepare($query);
}

$stmt->execute();
$result_user_data = $stmt->get_result();

if ($result_user_data === false) {
    echo "Error: " . $conn->error;
}

// Fetch Efficiency Categories
$fetchEfficiencyCategories = "SELECT * FROM efficiency_categories";
$efficiencysql = $conn->query($fetchEfficiencyCategories);

if ($efficiencysql === false) {
    echo "Error: " . $conn->error;
} else {
    $efficiencyData = [];
    while ($row = $efficiencysql->fetch_assoc()) {
        $efficiencyData[] = $row;
    }
}

// Fetch user efficiency details if the user type is 'user'
$userEfficiencyDetails = [];
if ($userType === 'user') {
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
}

// calculate totall efficiency points 
// $Total_Efficiency_Points_Query = "SELECT SUM(efficiency_points) AS total_points FROM efficiency_categories";
// $Total_Efficiency_Points = $conn->query($Total_Efficiency_Points_Query);
// $total_points = 0;
// if ($Total_Efficiency_Points->num_rows > 0) {
//     $row = $Total_Efficiency_Points->fetch_assoc();
//     $total_points = $row['total_points'];
// }

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
        <?php
         if ($userType ==  'admin' &&  $userType ==  'hradmin') { ?>
            <h2>All Users</h2> 
         <?php
        } else {
          ?>
          <h2>User</h2>
    <?php
        }
      ?>
        <table id="admindatatable" class="table table-striped" style="width:100%">
            <thead>
                <tr>
                    <th>User No</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>User Type</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Efficiency Points</th>
                    <th>Efficiency Average</th>
                    <?php if ($userType !== 'user'): ?>
                        <th>Edit</th>
                    <?php endif; ?>
                    <?php if ($userType !== 'user'): ?>
                    <th>Efficiency History</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result_user_data->fetch_assoc()): ?>
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
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['user_type']); ?></td>
                        <td><?php echo htmlspecialchars($row['role']); ?></td>
                        <td>
                            <div class="<?php echo htmlspecialchars($statusClass); ?>"><?php echo htmlspecialchars($row['status']); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($row['efficiency_points']); ?></td>
                        <td><?php echo number_format($row['efficiency_average'], 2); ?></td>
                        <?php if ($userType !== 'user'): ?>
                            <td class="d-flex justify-content-center align-items-center">
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#leavelimitModal"
                                    data-userid="<?php echo htmlspecialchars($row['id']); ?>">
                                    <img src="images/icons/edit.png" width="24px" height="24px">
                                </button>
                            </td>
                        <?php endif; ?>
                        <?php if ($userType !== 'user'): ?>
                        <td>
                            <a href="efficiencydetails.php?user_id=<?php echo urlencode($row['id']); ?>">
                                <img src="images/icons/history.png" width="24px" height="24px" class="img-fluid">
                            </a>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($userType === 'user'): ?>
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
                        <th>Points Assigned</th>
                        <th>Total Points</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userEfficiencyDetails as $detail): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($detail['username']); ?></td>
                            <td><?php echo htmlspecialchars($detail['email']); ?></td>
                            <td><?php echo htmlspecialchars($detail['user_type']); ?></td>
                            <td><?php echo htmlspecialchars($detail['role']); ?></td>
                            <td><?php echo htmlspecialchars($detail['assigned_points']); ?></td>
                            <td><?php echo htmlspecialchars($detail['total_points']); ?></td>
                            <td><?php echo $detail['created_at'] ? date('Y-M-d', strtotime($detail['created_at'])) : ''; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php else: ?>
<?php endif; ?>


<!-- Update User Efficiency Modal -->
<div class="modal fade" id="leavelimitModal" tabindex="-1" aria-labelledby="leavelimitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: linear-gradient(to right, #35592d, #1e5d75) !important; color: #fff;">
            <div class="modal-header">
                <h5 class="modal-title" id="leavelimitModalLabel">Update Efficiency Score</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if (!empty($successMessage)) : ?>
                    <div class="alert alert-success"><?php echo $successMessage; ?></div>
                <?php endif; ?>
                <form id="efficiencyForm" method="post" action="">
                    <input type="hidden" id="modal-user-id" name="user_id" value="">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12">
                                <?php if (!empty($efficiencyData)) : ?>
                                    <?php foreach ($efficiencyData as $data) : ?>
                                        <div class="mb-3">
                                            <input type="text" name="efficiency_name[]" value="<?php echo ($data['efficiency']) . ' (' . ($data['efficiency_points']) . ' points)'; ?>" class="form-control" readonly>
                                            <input type="hidden" name="efficiency_id[]" value="<?php echo ($data['id']); ?>">
                                            <input type="number" name="assigned_points[]" class="form-control mt-2" min="0" max="<?php echo ($data['efficiency_points']) ?>" placeholder="Enter points for <?php echo ($data['efficiency']); ?>" required>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <p>No data available</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success">Update Efficiency Points</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var efficiencyForm = document.getElementById('efficiencyForm');
        var modal = document.getElementById('leavelimitModal');
        var modalUserIdInput = document.getElementById('modal-user-id');

        modal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var userId = button.getAttribute('data-userid');
            modalUserIdInput.value = userId;
        });

        efficiencyForm.addEventListener('submit', function(event) {
            event.preventDefault();
            var formData = new FormData(efficiencyForm);

            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    console.log(data);
                    if (data.includes('successfully')) {
                        efficiencyForm.reset();
                        var modalInstance = bootstrap.Modal.getInstance(modal);
                        modalInstance.hide();
                        location.reload();
                    } else {
                        alert('An error occurred');
                    }
                });
        });
    });
</script>

<?php $conn->close(); ?>