<?php
session_start();
require 'vendor/autoload.php';
require 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Prepare and execute query to get both user_type and user_no
$stmt = $conn->prepare("SELECT user_type, user_no FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($userType, $userNo);
$stmt->fetch();
$stmt->close();

if ($userType != 'admin' && $userType != 'hradmin' && $userType != 'user') {
    header("Location: login.php");
    exit();
}

$usersQuery = "SELECT user_no, username FROM users";
$usersData = $conn->query($usersQuery);

// Fetch attendance data from the database
$startdate = isset($_GET['startdate']) ? $_GET['startdate'] : null;
$enddate = isset($_GET['enddate']) ? $_GET['enddate'] : null;
$startdateQuery = null;
$enddateQuery = null;
$LatecheckInsAndEarlyCheckOuts = 0;

if (!empty($startdate)) {
    $startdateQuery = date('Y-m-d', strtotime($startdate));
}
if (!empty($enddate)) {
    $enddateQuery = date('Y-m-d', strtotime($enddate));
}
$user_no = isset($_GET['user_no']) ? $_GET['user_no'] : null;
$over_time = isset($_GET['over_time']) ? $_GET['over_time'] : null;
$compensation = isset($_GET['compensation']) ? $_GET['compensation'] : null;


// Fetch user information
if (isset($user_no)) {
    $user_info = $conn->prepare("SELECT * FROM users WHERE user_no = ?");
    $user_info->bind_param("s", $user_no);
    $user_info->execute();
    $result_userInfo = $user_info->get_result();
    if ($row = $result_userInfo->fetch_assoc()) {
        $user_name = $row['username'];
        $user_role = $row['role'];
        $user_salary = $row['salary'];
    }
    $user_info->close();
} else {
    $user_name = null;
    $user_role = null;
    $user_salary = null;
}

// Fetch leaves data
$leavesData = [];
if ($startdateQuery && $enddateQuery && $user_no) {
    $user_leaves = $conn->prepare("SELECT * FROM leaves WHERE DATE(start_date) > ? AND DATE(end_date) < ? AND user_no = ? AND status = ?");
    $leave_status = 'approved';
    $user_leaves->bind_param("ssss", $startdateQuery, $enddateQuery, $user_no, $leave_status);
} else {
    $user_leaves = $conn->prepare("SELECT * FROM leaves");
}

$user_leaves->execute();
$result_leaves = $user_leaves->get_result();

if ($result_leaves->num_rows > 0) {
    while ($row = $result_leaves->fetch_assoc()) {
        $leavesData[] = $row;
    }
}
$user_leaves->close();

//////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////
$attendanceData = [];
if ($startdateQuery && $enddateQuery && $user_no) {
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE DATE(date_time) BETWEEN ? AND ? AND no = ?");
    $stmt->bind_param("sss", $startdateQuery, $enddateQuery, $user_no);
} elseif ($startdateQuery && $enddateQuery) {
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE DATE(date_time) BETWEEN ? AND ?");
    $stmt->bind_param("ss", $startdateQuery, $enddateQuery);
} else {
    $stmt = $conn->prepare("SELECT * FROM attendance ");
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $attendanceData[] = $row;
    }
}
$stmt->close();


//////////////////////////////////////////////////////////////////////////
////////////////////////////categories ///////////////////////////////////

// add categories
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['efficiency_category'])) {
    $new_category = $conn->real_escape_string($_POST['efficiency_category']);
    $efficiency_points = $conn->real_escape_string($_POST['efficiency_points']);
    $created_at = date('Y-m-d H:i:s');
    $sql_stmt = "INSERT INTO efficiency_categories (efficiency, efficiency_points, created_at) VALUES ('$new_category', '$efficiency_points', '$created_at')";
    
    if ($conn->query($sql_stmt) === TRUE) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo 'Error: ' . $conn->error;
    }
}


// update categoories 
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_efficiency_category'])) {

    $updatedCategory = $conn->real_escape_string($_POST['update_efficiency_category']);
    $updatedefficiencypoints = (int)$_POST['update_efficiency_points'];
    $categoryId = (int)$_POST['category_id'];
    $update_sql = "UPDATE efficiency_categories SET efficiency = '$updatedCategory', efficiency_points = $updatedefficiencypoints WHERE id = $categoryId";

    if ($conn->query($update_sql) === true) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "Error updating category: " . $conn->error;
    }
}


// delete categories 
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_category_id'])) {
    $deleteCategoryId = (int)$_POST['delete_category_id'];

    $delete_sql = "DELETE FROM efficiency_categories WHERE id = $deleteCategoryId";

    if ($conn->query($delete_sql) === true) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "Error deleting category: " . $conn->error;
    }
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




////////////////////////////////////////////////////////////////////////////

// Include the navbar based on user type
if ($userType === 'admin') {
    include_once 'partials/admin/navbar.php';
} elseif ($userType === 'hradmin') {
    include_once 'partials/hr/navbar.php';
} elseif ($userType === 'user') {
    include_once 'partials/users/navbar.php';
}

?>


<!-- Include DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">

<section class="container-fluid" style="padding: 60px 0 40px 0;">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="text-dark">Emplopyees Efficiency</h2>

            <!-- Add Categories Button -->
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#efficiencyModal">
                Add Categories
            </button>
        </div>
        <!-- <div class="container-fluid">
            <div class="row d-flex justify-content-end">
                <form id="attendanceForm" method="GET" action="userattendance.php" style="display: flex; gap: 10px; justify-content: end;">
                    <div class="col-12 row">
                        <div class="col-4">
                            <div>
                                <label class="form-label">Start Date</label>
                                <input class="form-control" type="date" name="startdate" value="<?php echo $startdate; ?>" required>
                            </div>
                        </div>
                        <div class="col-4">
                            <div>
                                <label class="form-label">End Date</label>
                                <input class="form-control" type="date" name="enddate" value="<?php echo $enddate ?>" required>
                            </div>
                        </div>
                        <?php if ($userType !== 'user'): ?>
                            <div class="col-4">
                                <div>
                                    <label class="form-label">Users</label>
                                    <select id="user_no" class="form-control" name="user_no">
                                        <option value="">Select a User</option>
                                        <?php
                                        if ($usersData->num_rows > 0) {
                                            while ($user = $usersData->fetch_assoc()) {
                                                echo '<option value="' . $user['user_no'] . '" ' .
                                                    (($user_no == $user['user_no']) ? 'selected' : '') . '>' .
                                                    $user['username'] . '</option>';
                                            }
                                        } else {
                                            echo '<option value="">No users found</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="user_no" value="<?php echo $userNo; ?>">
                        <?php endif; ?>
                        <div class="col-4">
                            <div style="padding-top: 32px;">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div> -->
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Efficiency Categories</th>
                                <th>Efficiency Points</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($efficiencyData)) : ?>
                                <?php foreach ($efficiencyData as $data) : ?>
                                    <tr>
                                        <td><?php echo $data['id']; ?></td>
                                        <td><?php echo $data['efficiency']; ?></td>
                                        <td><?php echo $data['efficiency_points']; ?></td>
                                        <td>
                                            <!-- Button to open the modal -->
                                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#updateEfficiencyModal<?php echo $data['id']; ?>">
                                                Update Category
                                            </button>
                                            <form action="efficiencyCategories.php" method="post" style="display:inline;">
                                                <input type="hidden" name="delete_category_id" value="<?php echo htmlspecialchars($data['id']); ?>">
                                                <button type="submit" class="btn btn-warning">Delete</button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Update Category Modal -->
                                    <div class="modal fade" id="updateEfficiencyModal<?php echo $data['id']; ?>" tabindex="-1" aria-labelledby="efficiencyModalLabel<?php echo $data['id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="efficiencyModalLabel<?php echo $data['id']; ?>">Update Category</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form action="efficiencyCategories.php" method="post">
                                                        <div class="mb-3">
                                                            <label for="efficiencyCategory<?php echo $data['id']; ?>" class="form-label">New Efficiency Category</label>
                                                            <input type="text" name="update_efficiency_category" id="efficiencyCategory<?php echo $data['id']; ?>" class="form-control" value="<?php echo $data['efficiency']; ?>" required>
                                                            <input type="number" name="update_efficiency_points" id="efficiencyPoints<?php echo $data['id']; ?>" class="form-control" value="<?php echo $data['efficiency_points']; ?>" required>
                                                            <input type="hidden" name="category_id" value="<?php echo $data['id']; ?>">
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-primary">Save changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="3">No data available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>




<!-- Add Category Modal -->
<form action="efficiencyCategories.php" method="POST">
    <div class="modal fade" id="efficiencyModal" tabindex="-1" aria-labelledby="efficiencyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="efficiencyModalLabel">Add Categories</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="efficiencyCategory" class="form-label">New Efficiency Category</label>
                        <input type="text" name="efficiency_category" id="efficiencyCategory" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="efficiencyPoints" class="form-label">Efficiency Points</label>
                        <input type="number" name="efficiency_points" id="efficiencyPoints" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>
</form>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>



<?php
// Include the footer based on user type
if ($userType === 'admin') {
    include_once 'partials/admin/footer.php';
} elseif ($userType === 'hradmin') {
    include_once 'partials/hr/footer.php';
}
?>

<?php
$conn->close();
?>