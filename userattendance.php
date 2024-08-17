<?php
session_start();
require 'vendor/autoload.php';
require 'config.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check user type
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($userType);
$stmt->fetch();
$stmt->close();

if ($userType != 'admin' && $userType != 'hradmin') {
    header("Location: login.php");
    exit();
}

$importedData = [];

// If a file is uploaded and processed
if (isset($_POST['export']) && $_POST['export'] == 'true' && isset($_FILES['attendanceFile'])) {
    $file = $_FILES['attendanceFile']['tmp_name'];
    $fileName = $_FILES['attendanceFile']['name'];
    $fileError = $_FILES['attendanceFile']['error'];

    // Check for upload errors
    if ($fileError !== UPLOAD_ERR_OK) {
        die('Error uploading file: ' . $fileError);
    }

    // Check file extension
    $allowedExtensions = ['xls', 'xlsx'];
    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

    if (!in_array($fileExtension, $allowedExtensions)) {
        die('Invalid file type. Only .xls and .xlsx files are allowed.');
    }

    try {
        // Load the spreadsheet
        $spreadsheet = IOFactory::load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        $importedData = $worksheet->toArray();
    } catch (Exception $e) {
        die('Error loading file "' . pathinfo($fileName, PATHINFO_BASENAME) . '": ' . $e->getMessage());
    }
}

// Fetch attendance data from the database
$attendanceData = [];
$startdate = isset($_GET['startdate']) ? $_GET['startdate'] : null;
$enddate = isset($_GET['enddate']) ? $_GET['enddate'] : null;

if ($startdate && $enddate) {
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE DATE(date_time) BETWEEN ? AND ?");
    $stmt->bind_param("ss", $startdate, $enddate);
} else {
    $stmt = $conn->prepare("SELECT * FROM attendance");
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $attendanceData[] = $row;
    }
}

//  for show all attendance data 
// Reset filters if the user requesting to show all data user press Show All button then get all data  
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['showAllAttendance'])) {
    header("Location: userattendance.php");
    exit();
}
$stmt->close();
?>

<?php
// Include the navbar based on user type
if ($userType === 'admin') {
    include_once 'partials/admin/navbar.php';
} elseif ($userType === 'hradmin') {
    include_once 'partials/hr/navbar.php';
}
?>

<!-- Include DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">

<section class="container-fluid" style="padding: 60px 0 40px 0;">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="text-dark">All Attendance Records</h2>
            <div>
                <form method="post" action="importattendance.php" enctype="multipart/form-data" style="display: flex; gap: 10px;">
                    <div>
                        <input class="form-control" type="file" name="attendanceFile" accept=".xls,.xlsx" required>
                        <input type="hidden" name="export" value="true">
                    </div>
                    <div>
                        <button type="submit" class="btn" style="background-color: #11965c;">
                            <img src="images/icons/excel.png" width="16px" height="16px">
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row d-flex justify-content-end">
                <!-- Filter form -->
                <form method="GET" action="userattendance.php" style="display: flex; gap: 10px; justify-content:end;">
                    <div class="col-2">
                        <div>
                            <label class="form-label">Start Date</label>
                            <input class="form-control" placeholder="YYYY-MM-DD" type="text" name="startdate" value="<?php echo isset($_GET['startdate']) ? htmlspecialchars($_GET['startdate']) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="d-flex" style="gap: 15px;">
                            <div>
                                <label class="form-label">End Date</label>
                                <input class="form-control" placeholder="YYYY-MM-DD" type="text" name="enddate" value="<?php echo isset($_GET['enddate']) ? htmlspecialchars($_GET['enddate']) : ''; ?>" required>
                            </div>
                            <div style="padding-top: 32px;">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </div>
                        <br>
                    </div>
                </form>
                <div class="d-flex justify-content-end">
                    <form method="POST" action="" style="margin-left: 15px;">
                        <button type="submit" name="showAllAttendance" class="btn btn-primary">Show All</button>
                    </form>
                    <form method="POST" action="userattendanceexportpdf.php" style="margin-left: 15px;">
                        <input type="hidden" name="startdate" value="<?php echo htmlspecialchars(isset($_GET['startdate']) ? $_GET['startdate'] : ''); ?>">
                        <input type="hidden" name="enddate" value="<?php echo htmlspecialchars(isset($_GET['enddate']) ? $_GET['enddate'] : ''); ?>">
                        <button type="submit" name="exportPdf" class="btn btn-primary">Export to PDF</button>
                    </form>
                </div>
            </div>
        </div>
        <table id="userattendancedatatable" class="table table-striped" style="width:100%; border-radius: 20px !important;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Department</th>
                    <th>Name</th>
                    <th>No</th>
                    <th>Date/Time</th>
                    <th>Status</th>
                    <th>Location ID</th>
                    <th>ID Number</th>
                    <th>Verify Code</th>
                    <th>Card No</th>
                </tr>
            </thead>
            <tbody>
    <?php if (!empty($attendanceData)) {
        foreach ($attendanceData as $row) {
            // Determine the status class based on the current row's status
            if ($row['status'] == 'C/In') {
                $statusClass = 'bg-green text-white';
            } elseif ($row['status'] == 'C/Out') {
                $statusClass = 'bg-red text-white';
            } else {
                $statusClass = '';
            }
            ?>
            <tr>
                <td><?php echo htmlspecialchars($row['id']); ?></td>
                <td><?php echo htmlspecialchars($row['department']); ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['no']); ?></td>
                <td><?php echo htmlspecialchars($row['date_time']); ?></td>
                <td><div class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['status']); ?></div></td>
                <td><?php echo htmlspecialchars($row['location_id']); ?></td>
                <td><?php echo htmlspecialchars($row['id_number']); ?></td>
                <td><?php echo htmlspecialchars($row['verify_code']); ?></td>
                <td><?php echo htmlspecialchars($row['card_no']); ?></td>
            </tr>
        <?php }
    } else { ?>
        <tr>
            <td colspan="10" class="text-center">No attendance data found for the selected date range.</td>
        </tr>
    <?php } ?>
</tbody>

        </table>
    </div>
</section>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

<script>
    $(document).ready(function() {
        $('#userattendancedatatable').DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true
        });
    });
</script>
<script>
    $(document).ready(function() {
        $('#admindatatable').DataTable();
    });
</script>




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