<?php
session_start();
require 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['user_id']) && isset($_POST['efficiency_id']) && isset($_POST['assigned_points'])) {
        $userId = $_POST['user_id'];
        $efficiencyIds = $_POST['efficiency_id'];
        $assignedPoints = $_POST['assigned_points'];
        $created_at = date('Y-m-d H:i:s');

       
        $totalPoints = 0;
        $efficiencyPointsMap = [];

        $efficiencyStmt = $conn->prepare("SELECT id, efficiency_points FROM efficiency_categories WHERE id IN (" . implode(',', array_fill(0, count($efficiencyIds), '?')) . ")");
        $efficiencyStmt->bind_param(str_repeat('i', count($efficiencyIds)), ...$efficiencyIds);
        $efficiencyStmt->execute();
        $efficiencyResults = $efficiencyStmt->get_result();

        while ($row = $efficiencyResults->fetch_assoc()) {
            $efficiencyPointsMap[$row['id']] = $row['efficiency_points'];
        }
        $efficiencyStmt->close();

        $conn->begin_transaction(); 

        try {
            foreach ($efficiencyIds as $index => $efficiencyId) {
                $points = isset($assignedPoints[$index]) ? $assignedPoints[$index] : 0;
                $totalPoints += $points;

                $stmt = $conn->prepare("INSERT INTO user_efficiencies (user_id, efficiency_category_id, points, created_at) VALUES (?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param('iiis', $userId, $efficiencyId, $points, $created_at);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    $conn->rollback();
                    throw new Exception("Error preparing statement: " . $conn->error);
                }
            }

           
            $maxTotalPoints = array_sum($efficiencyPointsMap);
            $normalizedScore = ($totalPoints / $maxTotalPoints) * 100;

            $updateStmt = $conn->prepare("INSERT INTO user_efficiency_points (user_id, points, total_points, created_at) VALUES (?, ?, ?, ?)
                                           ON DUPLICATE KEY UPDATE points = VALUES(points), total_points = VALUES(total_points), created_at = VALUES(created_at)");
            if ($updateStmt) {
                $updateStmt->bind_param('iiis', $userId, $normalizedScore, $totalPoints, $created_at);
                $updateStmt->execute();
                $updateStmt->close();
            } else {
                $conn->rollback();
                throw new Exception("Error preparing statement: " . $conn->error);
            }

            $conn->commit();
            $successMessage = "Efficiency scores updated successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            echo "Transaction failed: " . $e->getMessage();
        }
    }
}

// Retrieve all users data with their efficiency points
$query = "
    SELECT u.id, u.user_no, u.username, u.email, u.user_type, u.role, u.status, 
           COALESCE(uep.points, 0) AS efficiency_points
    FROM users u
    LEFT JOIN (
        SELECT user_id, MAX(points) AS points
        FROM user_efficiency_points
        GROUP BY user_id
    ) uep ON u.id = uep.user_id
    WHERE u.user_type != 'admin' AND u.status != 'blocked'
";
$result_user_data = $conn->query($query);

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
                    <th>Efficiency Points</th>
                    <th>Edit</th>
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
                        <td class="d-flex justify-content-center align-items-center">
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#leavelimitModal"
                                data-userid="<?php echo ($row['id']); ?>">
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
                <?php if (!empty($successMessage)) : ?>
                    <div class="alert alert-success"><?php echo $successMessage; ?></div>
                <?php endif; ?>
                <form id="efficiencyForm" method="post" action="">
                    <input type="hidden" id="modal-user-id" name="user_id" value="">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-6">
                                <?php if (!empty($efficiencyData)) : ?>
                                    <?php foreach ($efficiencyData as $data) : ?>
                                        <div class="mb-3">
                                            <input type="text" name="efficiency_name[]" value="<?php echo htmlspecialchars($data['efficiency']) . ' (' . htmlspecialchars($data['efficiency_points']) . ' points)'; ?>" class="form-control" readonly>
                                            <input type="hidden" name="efficiency_id[]" value="<?php echo htmlspecialchars($data['id']); ?>">
                                            <input type="number" name="assigned_points[]" class="form-control mt-2" placeholder="Enter points for <?php echo htmlspecialchars($data['efficiency']); ?>" required>
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
    document.addEventListener('DOMContentLoaded', function () {
        var efficiencyForm = document.getElementById('efficiencyForm');
        var modal = document.getElementById('leavelimitModal');
        var modalUserIdInput = document.getElementById('modal-user-id');

        modal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var userId = button.getAttribute('data-userid');
            modalUserIdInput.value = userId;
        });

        efficiencyForm.addEventListener('submit', function (event) {
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
