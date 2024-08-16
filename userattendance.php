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

// Fetch data from the database
$attendanceData = [];
$sql = "SELECT * FROM attendance";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $attendanceData[] = $row;
    }
}
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
                    foreach ($attendanceData as $row) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['department']); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['no']); ?></td>
                            <td><?php echo htmlspecialchars($row['date_time']); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <td><?php echo htmlspecialchars($row['location_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['id_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['verify_code']); ?></td>
                            <td><?php echo htmlspecialchars($row['card_no']); ?></td>
                        </tr>
                    <?php }
                } else { ?>
                    <tr>
                        <td colspan="10" class="text-center">No attendance data found.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Include jQuery and DataTables JS -->
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