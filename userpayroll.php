<?php
session_start();
require 'vendor/autoload.php';
require 'config.php';

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

$usersQuery = "SELECT user_no, username FROM users";
$usersData = $conn->query($usersQuery);

// Fetch attendance data from the database
$attendanceData = [];
$startdate = isset($_GET['startdate']) ? $_GET['startdate'] : null;
$enddate = isset($_GET['enddate']) ? $_GET['enddate'] : null;
$user_no = isset($_GET['user_no']) ? $_GET['user_no'] : null;


if ( isset($user_no)) {
 // Prepare the SQL query to fetch the username
$stmt = $conn->prepare("SELECT * FROM users WHERE user_no = ?");
$stmt->bind_param("s", $user_no);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $user_name = $row['username'];
    $user_role = $row['role'];
    $user_salary = $row['salary'];
}
$stmt->close();
} else {
    $user_name = null;
    $user_role = null;
    $user_salary = null;
}

if ($startdate && $enddate && $user_no) {
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE DATE(date_time) BETWEEN ? AND ? AND no = ?");
    $stmt->bind_param("sss", $startdate, $enddate, $user_no);
} elseif ($startdate && $enddate) {
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE DATE(date_time) BETWEEN ? AND ?");
    $stmt->bind_param("ss", $startdate, $enddate);
} elseif ($user_no) {
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE no = ?");
    $stmt->bind_param("s", $user_no);
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
$stmt->close();


// Initialize variables
$totalMinutesWorked = 0;
$requiredHoursPerDay = 9 * 60;
$daysWorked = [];
$checkInTime = null;
$totalLateMinutes = 0;

// Calculate total shift hours and late minutes
foreach ($attendanceData as $row) {
    $status = $row['status'];
    $dateTime = new DateTime($row['date_time']);
    $currentDate = $dateTime->format('Y-m-d');

    if ($status == 'C/In') {
        $checkInTime = $dateTime;
    } elseif ($status == 'C/Out' && $checkInTime !== null) {
        $interval = $checkInTime->diff($dateTime);
        $minutesWorked = ($interval->h * 60) + $interval->i;

        // Add worked minutes to the respective day
        if (!isset($daysWorked[$currentDate])) {
            $daysWorked[$currentDate] = 0;
        }
        $daysWorked[$currentDate] += $minutesWorked;

        // Calculate late minutes
        $compareTime = new DateTime($checkInTime->format('Y-m-d') . ' 09:00:00');
        $lateTime = new DateTime($checkInTime->format('Y-m-d') . ' 09:30:00');
        if ($checkInTime > $lateTime) {
            $intervalLate = $compareTime->diff($checkInTime);
            $lateMinutes = $intervalLate->h * 60 + $intervalLate->i;
            $totalLateMinutes += $lateMinutes;
        }
        $checkInTime = null;
    }
}

$totalWorkedMinutes = array_sum($daysWorked);
$numberOfDays = count($daysWorked);
$requiredTotalMinutes = $numberOfDays * $requiredHoursPerDay;

// Determine overtime or remaining hours
$extraOrMissingMinutes = $totalWorkedMinutes - $requiredTotalMinutes;
if ($extraOrMissingMinutes > 0) {
    $comparisonResult = "User worked " . floor($extraOrMissingMinutes / 60) . " hours and " . ($extraOrMissingMinutes % 60) . " minutes overtime.";
} elseif ($extraOrMissingMinutes < 0) {
    $comparisonResult = "User worked " . abs(floor($extraOrMissingMinutes / 60)) . " hours and " . abs($extraOrMissingMinutes % 60) . " minutes less.";
} else {
    $comparisonResult = "User worked exactly the required hours.";
}

// Convert total minutes to hours and minutes for display
$totalHoursWorked = floor($totalWorkedMinutes / 60);
$totalMinutesWorked = $totalWorkedMinutes % 60;
$totalHoursDisplay = $totalHoursWorked . ' hours ' . $totalMinutesWorked . ' minutes';

// Include the navbar based on user type
if ($userType === 'admin') {
    include_once 'partials/admin/navbar.php';
} elseif ($userType === 'hradmin') {
    include_once 'partials/hr/navbar.php';
}

?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
<!-- 

<h3>Total Working Hours: <?php echo $totalHoursDisplay; ?></h3>
<h4><?php echo $comparisonResult; ?></h4> -->

<section class="container-fluid" style="padding: 60px 0 40px 0;">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="text-dark">All Attendance Records</h2>
        </div>
        <div class="container-fluid">
            <div class="row d-flex justify-content-end">

                <div class="container-fluid">
                    <div class="row d-flex justify-content-end">
                        <div class="col-2">
                            <label class="form-label">Total Hours Worked</label>
                            <input readonly id="totalhours" class="form-control" type="text" value="<?php echo htmlspecialchars($totalHoursDisplay); ?>">
                        </div>
                        <div class="col-2">
                            <label class="form-label">Comparison</label>
                            <input readonly id="comparisonResult" class="form-control" type="text" value="<?php echo htmlspecialchars($comparisonResult); ?>">
                        </div>
                    </div>
                </div>

                <!-- Filter form -->
                <form id="attendanceForm" method="GET" action="userpayroll.php" style="display: flex; gap: 10px; justify-content: end;">
                    <div class="col-2">
                        <div>
                            <label class="form-label">Start Date</label>
                            <input id="StartDate" class="form-control" type="date" name="startdate" value="<?php echo htmlspecialchars($startdate); ?>" required>
                        </div>
                    </div>
                    <div class="col-2">
                        <div>
                            <label class="form-label">End Date</label>
                            <input id="EndDate" class="form-control" type="date" name="enddate" value="<?php echo htmlspecialchars($enddate); ?>" required>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="d-flex" style="gap: 15px;">
                            <div>
                                <label class="form-label">Users</label>
                                <select id="user_no" class="form-control" name="user_no" required>
                                    <option value="">Select a User</option>
                                    <?php
                                    if ($usersData->num_rows > 0) {
                                        while ($user = $usersData->fetch_assoc()) {
                                            echo '<option value="' . htmlspecialchars($user['user_no']) . '" ' . 
                                            (($user_no == $user['user_no']) ? 'selected' : '') . '>' . 
                                             $user['username'] . '</option>';
                                                                               }
                                    } else {
                                        echo '<option value="">No users found</option>';
                                    }
                                    ?>
                                </select>
                            </div>

                            <div style="padding-top: 32px;">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </div>
                        <br>
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
                        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['department']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['no']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['date_time']) . "</td>";
                        echo "<td><div class='" . $statusClass . "'>" . htmlspecialchars($row['status']) . "</div></td>";
                        echo "<td>" . htmlspecialchars($row['location_id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['id_number']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['verify_code']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['card_no']) . "</td>";
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




<div class="payslip-container">
            <div class="payslip-header">
                <h1>Payslip</h1>
                <p>Tetra Technologies<br>
                    Corporate Office , Kot lakhpat</p>
            </div>

            <div class="company-info">
                <table class="info-table">
                    <tr>
                        <td>Employee Name</td>
                        <td><?php echo $user_name; ?></td>
                    <td>From Date</td>
                        <td>
                            <?php                     
                                $date = new DateTime($startdate);
                                echo $date->format('d-M-Y');
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Designation</td>
                        <td><?php echo $user_role; ?></td>
                    <td>To Date</td>
                        <td>
                        <?php                     
                                $date = new DateTime($enddate);
                                echo $date->format('d-M-Y');
                        ?>                        
                        </td>
                    </tr>
                </table>
            </div>

            <table>
                <thead>
                    <tr>
                        <th colspan="2">Earnings</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Basic</td>
                        <td><?php echo $user_salary ?></td>
                    </tr>
                    <tr>
                        <td>Overtime Min</td>
                        <td>10</td>
                    </tr>
                    <tr>
                        <td>Overtime Amount</td>
                        <td>400</td>
                    </tr>
                    <tr class="total-row">
                        <td>Total Earnings</td>
                        <td>11600</td>
                    </tr>
                </tbody>
            </table>

            <table>
                <thead>
                    <tr>
                        <th colspan="2">Deductions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Absents</td>
                        <td>1200</td>
                    </tr>
                    <tr>
                        <td>Leaves</td>
                        <td>500</td>
                    </tr>
                    <tr>
                        <td>Late Check Ins</td>
                        <td>400</td>
                    </tr>
                    <tr>
                        <td>Early Check Outs</td>
                        <td>400</td>
                    </tr>
                    <tr class="total-row">
                        <td>Total Deductions</td>
                        <td>2100</td>
                    </tr>
                </tbody>
            </table>

            <div class="net-pay">
                Net Pay: 9500<br>
                Nine Thousand Five Hundred
            </div>

            <div class="signatures">
                <div class="signature">
                    ____________________________<br>
                    Employer Signature
                </div>
                <div class="signature">
                    ____________________________<br>
                    Employee Signature
                </div>
            </div>

            <div class="footer-slip">
                This is a system generated payslip
            </div>
        </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>

<script>
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
</script>
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