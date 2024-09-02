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
$totalWorkingMinForSession  = 0;

if (!empty($startdate)) {
    $startdateQuery = date('Y-m-d', strtotime($startdate));
}
if (!empty($enddate)) {
    $enddateQuery = date('Y-m-d', strtotime($enddate));
}
$user_no = isset($_GET['user_no']) ? $_GET['user_no'] : null;
$over_time = isset($_GET['over_time']) ? $_GET['over_time'] : null;
$totalWorkingDaysofThisMonth = getWorkingDays($startdate, $enddate);
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

// Salary calculations
$perdaysalary_ = 0;
$perhoursalary_ = 0;
$perminsalary_ = 0;

if ($user_salary) {
    // if ($startdate && $enddate) {
    //     $start = new DateTime($startdate);
    //     $end = new DateTime($enddate);
    //     $interval = $start->diff($end);
    //     $perdaysalary_ = $user_salary / ($interval->days + 1);
        $perdaysalary_ = $user_salary / $totalWorkingDaysofThisMonth;
        $perhoursalary_ = $perdaysalary_ / 9;
        $perminsalary_ = $perhoursalary_ / 60;
        $perdaysalary = number_format($perdaysalary_, 2);
        $perhoursalary = number_format($perhoursalary_, 2);
        $perminsalary = number_format($perminsalary_, 2);
    // }
}else{
    $perdaysalary = number_format(0, 2);
    $perhoursalary = number_format(0, 2);
    $perminsalary = number_format(0, 2);
}
//////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////
$attendanceData = [];
if ($startdateQuery && $enddateQuery && $user_no) {
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE DATE(date_time) BETWEEN ? AND ? AND no = ?");
    $stmt->bind_param("sss", $startdateQuery, $enddateQuery, $user_no);
} elseif ( $startdateQuery && $enddateQuery ) {
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
////////////////////////////////////////////////////////////////////////////
//////////////////late checkins check//////////////////////////////////////////
$latecheckinsData = [];
if ($startdateQuery && $enddateQuery && $user_no) {
    $time_checkin = '09:30:00';
    $status_checkin = 'C/In';
    $latecheckins = $conn->prepare("SELECT * FROM attendance WHERE DATE(date_time) BETWEEN ? AND ? AND TIME(date_time) > ? AND status = ? AND no = ?");
    $latecheckins->bind_param("sssss", $startdateQuery, $enddateQuery, $time_checkin, $status_checkin, $user_no);
} else {
    $latecheckins = $conn->prepare("SELECT * FROM attendance");
}

$latecheckins->execute();
$result_checkin = $latecheckins->get_result();

if ($result_checkin->num_rows > 0) {
    while ($row = $result_checkin->fetch_assoc()) {
        $latecheckinsData[] = $row;
    }
}
$latecheckins->close();
///////////////////////////////////////////////////////////////////////////////
////////////////////////////early checkouts check//////////////////////////////
$earlycheckoutsData = 0;
// $earlycheckoutsData = [];
// if ($startdateQuery && $enddateQuery && $user_no) {
//     $time_checkout = '18:00:00';
//     $status_checkout = 'C/Out';
//     $earlycheckout = $conn->prepare("SELECT * FROM attendance WHERE DATE(date_time) BETWEEN ? AND ? AND TIME(date_time) < ? AND status = ? AND no = ? ");
//     $earlycheckout->bind_param("sssss", $startdateQuery, $enddateQuery, $time_checkout, $status_checkout, $user_no);
// } else {
//     $earlycheckout = $conn->prepare("SELECT * FROM attendance");
// }

// $earlycheckout->execute();
// $result_checkout = $earlycheckout->get_result();

// if ($result_checkout->num_rows > 0) {
//     while ($row = $result_checkout->fetch_assoc()) {
//         $earlycheckoutsData[] = $row;
//     }
// }
// $earlycheckout->close();
///////////////////////////////////////////////////////////////////////////////
////////////////////////////absents leaves check//////////////////////////////
$absentcheckoutData = [];
if ($startdateQuery && $enddateQuery && $user_no) {
    $status_checkout_absent = 'C/Out';
    $absents_checkout = $conn->prepare("SELECT * FROM attendance WHERE DATE(date_time) BETWEEN ? AND ? AND status = ? AND no = ? GROUP BY DATE(date_time)");
    $absents_checkout->bind_param("ssss", $startdateQuery, $enddateQuery, $status_checkout_absent, $user_no);
} else {
    $absents_checkout = $conn->prepare("SELECT * FROM attendance");
}

$absents_checkout->execute();
$result_checkout = $absents_checkout->get_result();

if ($result_checkout->num_rows > 0) {
    while ($row = $result_checkout->fetch_assoc()) {
        $absentcheckoutData[] = $row;
    }
}
$absents_checkout->close();
////////////////////////
$absentcheckinData = [];
if ($startdateQuery && $enddateQuery && $user_no) {
    $status_checkin_absent = 'C/In';
    $absents_checkin = $conn->prepare("SELECT * FROM attendance WHERE DATE(date_time) BETWEEN ? AND ? AND status = ? AND no = ? GROUP BY DATE(date_time)");
    $absents_checkin->bind_param("ssss", $startdateQuery, $enddateQuery, $status_checkin_absent, $user_no);
} else {
    $absents_checkin = $conn->prepare("SELECT * FROM attendance");
}

$absents_checkin->execute();
$result_checkin = $absents_checkin->get_result();

if ($result_checkin->num_rows > 0) {
    while ($row = $result_checkin->fetch_assoc()) {
        $absentcheckinData[] = $row;
    }
}
$absents_checkin->close();

$totalabsents = 0;

if (count($absentcheckinData) > count($absentcheckoutData)) {
    $totalabsents = $totalWorkingDaysofThisMonth - count($absentcheckinData);
    $totalabsents = $totalabsents - count($leavesData);
} elseif (count($absentcheckoutData) > count($absentcheckinData)) {
    $totalabsents = $totalWorkingDaysofThisMonth - count($absentcheckoutData);
    $totalabsents = $totalabsents - count($leavesData);
}

///////////////////////////////////////////////////////////////////////////////
//////////////////////////calculate total working time///////////////////////////////
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
$workingTime = 0;
if ($extraOrMissingMinutes > 0) {
    $workingTime =  floor($extraOrMissingMinutes / 60) . " hours," . ($extraOrMissingMinutes % 60) . " minutes overtime.";
} elseif ($extraOrMissingMinutes < 0) {
    $workingTime =  abs(floor($extraOrMissingMinutes / 60)) . " hours," . abs($extraOrMissingMinutes % 60) . " minutes offtime.";
}

// Convert total minutes to hours and minutes for display
$totalHoursWorked = floor($totalWorkedMinutes / 60);
$totalMinutesWorked = $totalWorkedMinutes % 60;
$totalHoursDisplay = $totalHoursWorked . ' hours ' . $totalMinutesWorked . ' minutes';

////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////working days function///////////////////////////
function getWorkingDays($startDate, $endDate)
{
    $start = new DateTime($startDate ?? 'now');
    $end = new DateTime($endDate ?? 'now');
    $end->modify('+1 day');
    $interval = new DateInterval('P1D');
    $dateRange = new DatePeriod($start, $interval, $end);
    $workingDays = 0;
    foreach ($dateRange as $date) {
        if ($date->format('N') < 6) { // 'N' returns the day of the week (1 for Monday, 7 for Sunday)
            $workingDays++;
        }
    }
    return $workingDays;
}
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
                <!-- Filter form -->
                <!-- filter form  -->
                <form id="attendanceForm" method="GET" action="userattendance.php" style="display: flex; gap: 10px; justify-content: end;">
                    <div class="col-12 row">
                        <div class="col-4">
                            <div>
                                <label class="form-label">Start Date</label>
                                <input  class="form-control" type="date" name="startdate" value="<?php echo $startdate; ?>" required>
                            </div>
                        </div>
                        <div class="col-4">
                            <div>
                                <label class="form-label">End Date</label>
                                <input  class="form-control" type="date" name="enddate" value="<?php echo $enddate ?>" required>
                            </div>
                        </div>
                        <?php if ($userType !== 'user'): ?>
                            <div class="col-4">
                                <div>
                                    <label class="form-label">Users</label>
                                    <select id="user_no" class="form-control" name="user_no" >
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

                <div class="d-flex justify-content-end">
                    <form method="POST" action="userattendance.php" style="margin-left: 15px;">
                    <input type="hidden" name="startdate" value="">
                    <input type="hidden" name="enddate" value="">
                    <input type="hidden" name="user_no" value="">
                        <button type="submit" name="showAllAttendance" class="btn btn-primary">Show All</button>
                    </form>
                    <form method="POST" action="userattendanceexportpdf.php" style="margin-left: 15px;">
                        <input type="hidden" name="startdate" value="<?php echo (isset($_GET['startdate']) ? $_GET['startdate'] : ''); ?>">
                        <input type="hidden" name="enddate" value="<?php echo (isset($_GET['enddate']) ? $_GET['enddate'] : ''); ?>">
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
                                $totalWorkingMinForSession = ($hours * 60) + $minutes;
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
                                    $LatecheckInsAndEarlyCheckOuts += $lateMinutes;
                                    echo "<span style='font-weight: 500; color: red;'>" . $lateMinutes . " minutes late</span>";
                                } else {
                                    echo 'On time';
                                }
                            } elseif ($row['status'] == 'C/Out') {
                                $clockOutTime = new DateTime($row['date_time']);
                                $shiftDuration = 9 * 60;
                                if ($totalWorkingMinForSession > $shiftDuration) {
                                    $additionalMinutes = $totalWorkingMinForSession - $shiftDuration;
                                    $hours = floor($additionalMinutes / 60);
                                    $minutes = $additionalMinutes % 60;
                                    echo "<span style='font-weight: 500; color: green;'>CheckOut, " . $hours . " hour(s) " . $minutes . " minute(s) after shift</span>";
                                } elseif ($totalWorkingMinForSession < $shiftDuration) {
                                    $remainingMinutes = $shiftDuration - $totalWorkingMinForSession;
                                    $hours = floor($remainingMinutes / 60);
                                    $minutes = $remainingMinutes % 60;
                                    $earlycheckoutsData++;
                                    echo "<span style='font-weight: 500; color: #e8a215;'>CheckOut, " . $hours . " hour(s) " . $minutes . " minute(s) before shift</span>";
                                }
                            } else {
                                echo '';
                            }
                            echo "</td>";
                            if ($status == 'C/Out') {
                                if ($totalWorkingHoursForSession < 9) {
                                    if (((9 * 60) - $totalWorkingMinForSession) > 0) {
                                        $LatecheckInsAndEarlyCheckOuts += ((9 * 60) - $totalWorkingMinForSession);
                                    }
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