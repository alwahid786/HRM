<?php
session_start();
require 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}


// Retrieve all users data without admin data 
$stmt = $conn->prepare("SELECT id, user_no, username, login, email, user_type, hiring_date, role, salary, created_at, status, leave_limit, leave_start_date, leave_end_date 
                        FROM users WHERE user_type != 'admin' AND status != 'blocked'");
$stmt->execute();
$result = $stmt->get_result();

/////////////////////////////////////////////////////////////////////////
// //////////////////////// fetch efficiency categories//////////////////
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
                    <th>Email</th>
                    <th>User Type</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Edit</th>
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
                        <td><?php echo ($row['user_no']); ?></td>
                        <td><?php echo ($row['username']); ?></td>
                        <td><?php echo ($row['email']); ?></td>
                        <td><?php echo ($row['user_type']); ?></td>
                        <td><?php echo ($row['role']); ?></td>
                        <td>
                            <div class="<?php echo $statusClass; ?>"><?php echo ($row['status']); ?></div>
                        </td>
                        <td class="d-flex justify-content-center align-items-center">
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#leavelimitModal"
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
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Update User Efficiency Modal -->
<div class="modal fade" id="leavelimitModal" tabindex="-1" aria-labelledby="leavelimitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: linear-gradient(to right, #35592d, #1e5d75) !important; color: #fff;">
            <div class="modal-header">
                <h5 class="modal-title" id="leavelimitModalLabel">Update Efficiency Score</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-6">
                        <form method="post" action="efficiency.php">
                            <?php if (!empty($efficiencyData)) : ?>
                                <?php foreach ($efficiencyData as $data) : ?>
                                        <div class="mb-3" style="width: 110%;">
                                            <input type="text" name="efficiency_name[]" value="<?php echo ($data['efficiency']) . ' (' . ($data['efficiency_points']) . ')'; ?>" class="form-control" readonly>
                                        </div>
                                        <input type="hidden" name="efficiency_id[]" min="0" max="<?php $data['efficiency_points'] ?>" value="<?php echo ($data['id']); ?>">
                                <?php endforeach; ?>
                            <?php else : ?>
                                <p>No data available</p>
                            <?php endif; ?>
                            </form>
                        </div>
                        <div class="col-6">
                            <?php if (!empty($efficiencyData)) : ?>
                                <?php foreach ($efficiencyData as $data) : ?>
                                    <div class="mb-3" style="width: 70%;">
                                        <input type="text" name="blank_input[]" class="form-control" placeholder="Enter Points">
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <form method="post" action="efficiency.php">
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </form>
            </div>
        </div>
    </div>
</div>






<?php include_once 'partials/admin/footer.php'; ?>

<?php
$stmt->close();
$conn->close();
?>