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
    if ($startdate && $enddate) {
        $start = new DateTime($startdate);
        $end = new DateTime($enddate);
        $interval = $start->diff($end);

        $perdaysalary_ = $user_salary / ($interval->days + 1);
        $perhoursalary_ = $perdaysalary_ / 9;
        $perminsalary_ = $perhoursalary_ / 60;
        $perdaysalary = number_format($perdaysalary_, 2);
        $perhoursalary = number_format($perhoursalary_, 2);
        $perminsalary = number_format($perminsalary_, 2);
    }
}
//////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////
$attendanceData = [];
if ($startdateQuery && $enddateQuery && $user_no) {
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE DATE(date_time) BETWEEN ? AND ? AND no = ?");
    $stmt->bind_param("sss", $startdateQuery, $enddateQuery, $user_no);
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
//////////////////////////////////////
////////////// convert stamp image to base64 to generate pdf for salary slips /////////////////////////
 // Convert image to base64
 function convertImageToBase64($imagePath)
 {
     $imageData = file_get_contents($imagePath);
     $base64Image = base64_encode($imageData);
     $imageInfo = getimagesize($imagePath);
     $mimeType = $imageInfo['mime'];
     return "data:$mimeType;base64,$base64Image";
 }

 // image path 
 $imagePath = 'images/icons/stamp.png';
 $stamp_base64 = convertImageToBase64($imagePath);
///////////////////////////////////////////////////////////////////////////////////////////
// Include the navbar based on user type
if ($userType === 'admin') {
    include_once 'partials/admin/navbar.php';
} elseif ($userType === 'hradmin') {
    include_once 'partials/hr/navbar.php';
} elseif ($userType === 'user') {
    include_once 'partials/users/navbar.php';
}

?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
<!-- 

<h3>Total Working Hours: <?php echo $totalHoursDisplay; ?></h3>
<h4><?php echo $comparisonResult; ?></h4> -->

<section class="container-fluid" style="padding: 60px 0 40px 0;">
    <div class="container-fluid">
        <?php if ($userType == 'admin' || $userType == 'hradmin') { ?>
            <div class="d-flex justify-content-center align-items-center">
                <h2 class="text-dark">Generate Payroll</h2>
            </div>
        <?php } elseif ($userType == 'user') { ?>
            <div class="d-flex justify-content-center align-items-center">
                <h2 class="text-dark">Generate Payroll</h2>
            </div>
        <?php } ?>

        <div class="container-fluid">
            <div class="row d-flex justify-content-end">

                <!-- <div class="container-fluid">
                    <div class="row d-flex justify-content-end">
                        <div class="col-2">
                            <label class="form-label">Total Hours Worked</label>
                            <input readonly id="totalhours" class="form-control" type="text" value="<?php echo ($totalHoursDisplay); ?>">
                        </div>
                        <div class="col-2">
                            <label class="form-label">Comparison</label>
                            <input readonly id="comparisonResult" class="form-control" type="text" value="<?php echo ($comparisonResult); ?>">
                        </div>
                    </div>
                </div> -->

                <!-- filter form  -->
                <form id="attendanceForm" method="GET" action="userpayroll.php" style="display: flex; gap: 10px; justify-content: end;">
                    <div class="col-12 row">
                        <div class="col-4">
                            <div>
                                <label class="form-label">Start Date</label>
                                <input id="StartDate" class="form-control" type="date" name="startdate" value="<?php echo $startdate; ?>" required>
                            </div>
                        </div>
                        <div class="col-4">
                            <div>
                                <label class="form-label">End Date</label>
                                <input id="EndDate" class="form-control" type="date" name="enddate" value="<?php echo $enddate ?>" required>
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
                            <input type="hidden" name="user_no" value="<?php echo $userNo; ?>">
                        <?php endif; ?>
                        <?php if ($userType !== 'user'): ?>
                            <div class="col-4">
                                <div>
                                    <label class="form-label">OverTime Mins(If Any)</label>
                                    <input class="form-control" type="number" name="over_time" value="<?php echo $over_time ?>">
                                </div>
                            </div>
                            <div class="col-4">
                                <div>
                                    <label class="form-label">Compensation(If Any)</label>
                                    <input class="form-control" type="number" name="compensation" value="<?php echo $compensation; ?>">
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="col-4">
                            <div style="padding-top: 32px;">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </div>
                    </div>
                </form>


            </div>
        </div>
        <?php if ($startdateQuery && $enddateQuery && $user_no) { ?>

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

        <?php  } ?>

    </div>
</section>

<div class="payslip-container" id="salaryslipContent">
    <div class="payslip-header">
        <h1>Payslip</h1>
        <p>Tetra Technologies<br>
            Corporate Office , Kot lakhpat</p>
    </div>

    <div class="company-info">
        <table class="info-table">
            <tr>
                <td class="width-25 boldtext">Employee Name</td>
                <td class="width-25"><?php echo $user_name; ?></td>
                <td class="width-25 boldtext">From Date</td>
                <td>
                    <?php
                    $date = new DateTime($startdate ?? 'now');
                    echo $date->format('d-M-Y');
                    ?>
                </td>
            </tr>
            <tr>
                <td class="boldtext">Designation</td>
                <td><?php echo $user_role; ?></td>
                <td class="boldtext">To Date</td>
                <td>
                    <?php
                    $date = new DateTime($enddate ?? 'now');
                    echo $date->format('d-M-Y');
                    ?>
                </td>
            </tr>
            <tr>
                <td class="boldtext">Absents</td>
                <td><?php echo ($totalabsents); ?> </td>
                <td class="boldtext">Leaves</td>
                <td><?php echo count($leavesData); ?> </td>
            </tr>
            <tr>
                <td class="boldtext">Late Check Ins</td>
                <td><?php echo count($latecheckinsData); ?> </td>
                <td class="boldtext">Early Check Outs</td>
                <!-- <td><?php //echo count($earlycheckoutsData); 
                            ?> </td> -->
                <td><?php echo $earlycheckoutsData; ?> </td>
            </tr>
            <!-- <tr>
                <td colspan="2">Working Hours/min</td>
                <td colspan="2"><?php echo $workingTime ? $workingTime : 0; ?></td>
            </tr> -->
            <tr>
                <td colspan="2" class="boldtext">Late CheckIns and Early CheckOuts Total Time</td>
                <td colspan="2">
                    <?php
                    if ($LatecheckInsAndEarlyCheckOuts > 0) {
                        $LatecheckInsAndEarlyCheckOuts_hours = floor($LatecheckInsAndEarlyCheckOuts / 60);
                        $LatecheckInsAndEarlyCheckOuts_minutes = $LatecheckInsAndEarlyCheckOuts % 60;
                        echo "<span style='font-weight: 500; color: black;'>" . $LatecheckInsAndEarlyCheckOuts_hours . " hour(s) " . $LatecheckInsAndEarlyCheckOuts_minutes . " minute(s)</span>";
                    } else {
                        echo 0;
                    }
                    ?></td>
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
                <th class="width-65">Basic Salary</th>
                <th><?php echo $user_salary ?></th>
            </tr>
            <tr>
                <td class="width-65">Overtime</td>
                <td><?php
                    if ($over_time > 0) {
                        echo $over_time . ' mins';
                    } else {
                        echo 0;
                    }
                    ?></td>
            </tr>
            <tr>
                <td class="width-65">Overtime Amount</td>
                <td><?php
                    $user_salary_allownce = 0;
                    if ($over_time > 0) {
                        $overtimeamount = (($over_time * $perminsalary_) * 1.5);
                        echo number_format($overtimeamount);
                        $user_salary_allownce = $overtimeamount + $user_salary;
                    } else {
                        echo 0;
                    }
                    $user_salary_allownce = $user_salary_allownce ? $user_salary_allownce : $user_salary;
                    ?></td>
            </tr>
            <tr class="total-row">
                <td>Total Earnings</td>
                <td><?php echo $user_salary_allownce > 0 ? number_format($user_salary_allownce) : $user_salary_allownce ?></td>
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
                <td class="width-65">Late Checkin + Early Checkout Min</td>
                <td><?php
                    if ($LatecheckInsAndEarlyCheckOuts > 0) {
                        echo   abs($LatecheckInsAndEarlyCheckOuts) . ' mins';
                    } else {
                        echo 0;
                    } ?></td>
            </tr>
            <tr>
                <td class="width-65">Late Checkin + Early Checkout Fine</td>
                <td><?php
                    $offtimeamount = 0;
                    if ($LatecheckInsAndEarlyCheckOuts > 0) {
                        $offtimeamount = (($LatecheckInsAndEarlyCheckOuts * $perminsalary_));
                        echo number_format(abs($offtimeamount));
                    } else {
                        echo 0;
                    }
                    ?></td>
            </tr>
            <tr>
                <td class="width-65">Absent Deduction</td>
                <td><?php
                    $user_salary_absent = 0;
                    $absentamount = 0;
                    if ($totalabsents > 0) {
                        $absentamount = (($totalabsents * $perdaysalary_));
                        echo number_format(abs($absentamount));
                        $user_salary_absent = $user_salary_allownce - abs($absentamount);
                    } else {
                        echo 0;
                    }
                    ?></td>
            </tr>
            <tr class="total-row">
                <td>Total Deductions</td>
                <?php $total_deduction = (abs($offtimeamount) + $absentamount) ?>
                <td><?php echo number_format($total_deduction) ?></td>
            </tr>
            <?php $compensation = $compensation ?  $compensation : 0; ?>
            <?php if ($compensation) { ?>
                <tr class="total-row">
                    <td>Compensation</td>
                    <td><?php echo number_format($compensation) ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <div class="net-pay">
        Net Pay: <?php echo number_format(abs(($user_salary_allownce + $compensation) - $total_deduction)) ?><br>
    </div>


    <div style="align-content: center; margin-top:25px;">
        <div class="stamp">
            <img src="<?php echo $stamp_base64; ?>" width="100px" height="100px" alt="Stamp">
        </div>
    </div>


    <div class="footer-slip">
        This is a system generated payslip
    </div>
</div>

<div class="d-flex justify-content-center">
    <form id="exportForm" action="export_salaryslip.php" method="post">
        <button class="btn btn-primary" type="submit">Export Salary Slip</button>
        <input type="hidden" name="html_content" id="htmlContent" />
    </form>
</div>

<script>
    document.getElementById('exportForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const payslipContent = document.getElementById('salaryslipContent').outerHTML;
        document.getElementById('htmlContent').value = payslipContent;

        this.submit();
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>

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