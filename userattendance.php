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
// $attendanceData = [];
// $startdate = isset($_GET['startdate']) ? $_GET['startdate'] : null;
// $enddate = isset($_GET['enddate']) ? $_GET['enddate'] : null;

// if ($startdate && $enddate) {
//     $stmt = $conn->prepare("SELECT * FROM attendance WHERE DATE(date_time) BETWEEN ? AND ?");
//     $stmt->bind_param("ss", $startdate, $enddate);
// } else {
//     $stmt = $conn->prepare("SELECT * FROM attendance");
// }
// $stmt->execute();
// $result = $stmt->get_result();

// if ($result->num_rows > 0) {
//     while ($row = $result->fetch_assoc()) {
//         $attendanceData[] = $row;
//     }
// }


// Fetch user information if user_no is set
if (isset($_GET['user_no'])) {
    $user_no = $_GET['user_no'];
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

// Fetch users for the dropdown
$usersQuery = "SELECT user_no, username FROM users";
$usersData = $conn->query($usersQuery);

// Initialize filter variables
$startdate = isset($_GET['startdate']) ? $_GET['startdate'] : null;
$enddate = isset($_GET['enddate']) ? $_GET['enddate'] : null;
$user_no = isset($_GET['user_no']) ? $_GET['user_no'] : null;

// Prepare the SQL query based on filters
$sql = "SELECT * FROM attendance WHERE 1=1";

if ($startdate) {
    $startdateQuery = date('Y-m-d', strtotime($startdate));
    $sql .= " AND DATE(date_time) >= ?";
}
if ($enddate) {
    $enddateQuery = date('Y-m-d', strtotime($enddate));
    $sql .= " AND DATE(date_time) <= ?";
}
if ($user_no) {
    $sql .= " AND no = ?";
}

$stmt = $conn->prepare($sql);

$params = [];
$types = "";

// Bind parameters based on filters
if ($startdate) {
    $params[] = $startdateQuery;
    $types .= "s";
}
if ($enddate) {
    $params[] = $enddateQuery;
    $types .= "s";
}
if ($user_no) {
    $params[] = $user_no;
    $types .= "s";
}

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$attendanceData = $result->fetch_all(MYSQLI_ASSOC);



/////////////////////////////////////////////////////////////////
///////////search with only startdate or enddate //////////////
// Initialize filter variables
$basicstartDate = isset($_GET['basicstartDate']) ? $_GET['basicstartDate'] : null;
$basicendDate = isset($_GET['basicendDate']) ? $_GET['basicendDate'] : null;

// Prepare the SQL query based on filters
$sql = "SELECT * FROM attendance WHERE 1=1";

$params = [];
$types = "";

// Add filters to the query
if ($basicstartDate) {
    $basicstartDateQuery = date('Y-m-d', strtotime($basicstartDate));
    $sql .= " AND DATE(date_time) >= ?";
    $params[] = $basicstartDateQuery;
    $types .= "s";
}
if ($basicendDate) {
    $basicendDateQuery = date('Y-m-d', strtotime($basicendDate));
    $sql .= " AND DATE(date_time) <= ?";
    $params[] = $basicendDateQuery;
    $types .= "s";
}
$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$attendanceData = $result->fetch_all(MYSQLI_ASSOC);
/////////////////////////////////////////////////////////
///////////////////////////////////////////////////////
//  for show all attendance data 
// Reset filters if the user requesting to show all data user press Show All button then get all data  
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['showAllAttendance'])) {
    header("Location: userattendance.php");
    exit();
}



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
                <form id="DateRangeForm" method="post" action="importattendance.php" enctype="multipart/form-data" style="display: flex; gap: 10px;">
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
                <!-- Filter Form -->
                <form id="attendanceForm" method="GET" action="userattendance.php" style="display: flex; gap: 10px; justify-content: end;">
                    <div class="col-12 row">
                        <div class="col-4">
                            <div>
                                <label class="form-label">Start Date</label>
                                <input id="StartDate" class="form-control" type="date" name="startdate" value="<?php echo htmlspecialchars($startdate); ?>" required>
                            </div>
                        </div>
                        <div class="col-4">
                            <div>
                                <label class="form-label">End Date</label>
                                <input id="EndDate" class="form-control" type="date" name="enddate" value="<?php echo htmlspecialchars($enddate); ?>" required>
                            </div>
                        </div>
                        <?php if ($userType !== 'user'): ?>
                            <div class="col-4">
                                <div>
                                    <label class="form-label">Users</label>
                                    <select id="user_no" class="form-control" name="user_no" required>
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
                            <input type="hidden" name="user_no" value="<?php echo htmlspecialchars($userNo); ?>">
                        <?php endif; ?>
                        <div class="col-4">
                            <div style="padding-top: 32px;">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </div>
                    </div>
                </form>


                <form id="attendanceForm" method="GET" action="userattendance.php" style="display: flex; gap: 10px; justify-content: end;">
                    <div class="col-12 row">
                        <div class="col-4">
                            <div>
                                <label class="form-label">Start Date</label>
                                <input id="basicStartDate" class="form-control" type="date" name="basicstartDate" value="<?php echo ($basicstartDate); ?>" required>
                            </div>
                        </div>
                        <div class="col-4">
                            <div>
                                <label class="form-label">End Date</label>
                                <input id="basicEndDate" class="form-control" type="date" name="basicendDate" value="<?php echo ($basicendDate); ?>" required>
                            </div>
                        </div>
                        <div class="col-4">
                            <div style="padding-top: 32px;">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </div>
                    </div>
                </form>



                <div class="d-flex justify-content-end">
                    <form method="POST" action="" style="margin-left: 15px;">
                        <button type="submit" name="showAllAttendance" class="btn btn-primary">Show All</button>
                    </form>
                    <form method="POST" action="userattendanceexportpdf.php" style="margin-left: 15px;">
                        <input type="hidden" name="startdate" value="<?php echo htmlspecialchars($startdate); ?>">
                        <input type="hidden" name="enddate" value="<?php echo htmlspecialchars($enddate); ?>">
                        <button type="submit" name="exportPdf" class="btn btn-primary">Export to PDF</button>
                    </form>
                    <a href="userpayroll.php" class="btn btn-dark text-white">User's Payroll</a>
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
                    <th>Late & After Minutes</th>
                    <th>Total Shift Hours</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $userShifts = [];

                if (!empty($attendanceData)) {
                    foreach ($attendanceData as $row) {
                        $date = new DateTime($row['date_time']);
                        $dateKey = $date->format('Y-m-d');

                        if ($row['status'] == 'C/In') {
                            $userShifts[$dateKey]['check_in'] = $date;
                        } elseif ($row['status'] == 'C/Out') {
                            $userShifts[$dateKey]['check_out'] = $date;
                        }
                    }
                    $totalWorkingHours = [];
                    $checkInTime = null;

                    foreach ($attendanceData as $row) {
                        $status = $row['status'];
                        $dateTime = new DateTime($row['date_time']);

                        if ($status == 'C/In') {
                            $checkInTime = $dateTime;
                        } elseif ($status == 'C/Out' && $checkInTime !== null) {
                            $interval = $checkInTime->diff($dateTime);
                            $hours = $interval->h;
                            $minutes = $interval->i;

                            $totalWorkingHoursForSession = $hours . ' hours ' . $minutes . ' minutes';
                            $checkInTime = null;
                        } else {
                            $totalWorkingHoursForSession = 0;
                        }

                        if ($row['status'] == 'C/In') {
                            $statusClass = 'bg-green text-white';
                        } elseif ($row['status'] == 'C/Out') {
                            $statusClass = 'bg-red text-white';
                        } else {
                            $statusClass = '';
                        }

                        echo "<tr>";
                        echo "<td>" . ($row['id']) . "</td>";
                        echo "<td>" . ($row['department']) . "</td>";
                        echo "<td>" . ($row['name']) . "</td>";
                        echo "<td>" . ($row['no']) . "</td>";
                        echo "<td>" . ($row['date_time']) . "</td>";
                        echo "<td><div class='" . $statusClass . "'>" . ($row['status']) . "</div></td>";
                        echo "<td>" . ($row['location_id']) . "</td>";
                        echo "<td>" . ($row['id_number']) . "</td>";
                        echo "<td>" . ($row['verify_code']) . "</td>";
                        echo "<td>" . ($row['card_no']) . "</td>";
                        echo "<td>";

                        // Late Minutes (Red background)
                        if ($row['status'] == 'C/In') {
                            $dateTime = new DateTime($row['date_time']);
                            $compareTime = new DateTime($dateTime->format('Y-m-d') . ' 09:00:00');
                            $LateTime = new DateTime($dateTime->format('Y-m-d') . ' 09:30:00');
                            if ($dateTime > $LateTime) {
                                $interval = $compareTime->diff($dateTime);
                                $lateMinutes = $interval->h * 60 + $interval->i;
                                echo "<span style='font-weight: 500; color: red;'>" . $lateMinutes . " minutes late</span>";
                            } else {
                                echo 'On time';
                            }
                        } elseif ($row['status'] == 'C/Out') {
                            $clockOutTime = new DateTime($row['date_time']);
                            $sixPM = new DateTime($clockOutTime->format('Y-m-d') . ' 18:00:00');
                            if ($clockOutTime > $sixPM) {
                                $interval = $sixPM->diff($clockOutTime);
                                $additionalMinutes = $interval->h * 60 + $interval->i;
                                echo "<span style='font-weight: 500; color: green;'>CheckOut, " . $additionalMinutes . " minutes after 6:00 PM</span>";
                            } elseif ($clockOutTime < $sixPM) {
                                $interval = $sixPM->diff($clockOutTime);
                                $additionalMinutes = $interval->h * 60 + $interval->i;
                                echo "<span style='font-weight: 500; color: #e8a215;'>CheckOut, " . $additionalMinutes . " minutes before 6:00 PM</span>";
                            }
                        } else {
                            echo '';
                        }
                        echo "</td>";
                        if ($status == 'C/Out') {
                            if ($totalWorkingHoursForSession < 9) {
                                echo "<td style='background-color: #ede909 ;' >" . $totalWorkingHoursForSession . "</td>";
                            } else {
                                echo "<td>" . $totalWorkingHoursForSession . "</td>";
                            }
                        } else {
                            echo "<td>--</td>";
                        }

                        echo "</tr>";
                    }
                } else {
                    echo "<tr>
                    <td colspan='12' class='text-center'>No attendance data found for the selected date range.</td>
                    </tr>";
                }
                ?>
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


<!-- <script>
    document.getElementById('attendanceForm').addEventListener('submit', function(event) {
        var startDateInput = document.getElementById('StartDate');
        var endDateInput = document.getElementById('EndDate');

        var startDateValue = startDateInput.value;
        var endDateValue = endDateInput.value;

        if (startDateValue) {
            var startDateParts = startDateValue.split('-');
            if (startDateParts.length === 3) {
                // Format to YYYY-DD-MM
                startDateInput.value = startDateParts[0] + '-' + startDateParts[2] + '-' + startDateParts[1];
            }
        }

        if (endDateValue) {
            var endDateParts = endDateValue.split('-');
            if (endDateParts.length === 3) {
                // Format to YYYY-DD-MM
                endDateInput.value = endDateParts[0] + '-' + endDateParts[2] + '-' + endDateParts[1];
            }
        }
    });
</script> -->
<script>
    $(document).ready(function() {
        $('#userattendancedatatable').DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true,
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