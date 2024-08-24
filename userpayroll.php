<?php
session_start();
require 'vendor/autoload.php';
require 'config.php';

// use PhpOffice\PhpSpreadsheet\IOFactory;

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

// Fetch attendance data from the database
$attendanceData = [];
$startdate = isset($_GET['startdate']) ? $_GET['startdate'] : null;
$enddate = isset($_GET['enddate']) ? $_GET['enddate'] : null;
$user_no = isset($_GET['user_no']) ? $_GET['user_no'] : null;

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

// Calculate total shift hours
$totalHours = 0;
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
        $totalHours += ($hours * 60) + $minutes; 
        $checkInTime = null;
    }
}

// Convert total minutes to hours and minutes
$totalHoursDisplay = floor($totalHours / 60) . ' hours ' . ($totalHours % 60) . ' minutes';

// Include the navbar based on user type
if ($userType === 'admin') {
    include_once 'partials/admin/navbar.php';
} elseif ($userType === 'hradmin') {
    include_once 'partials/hr/navbar.php';
}
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">

<section class="container-fluid" style="padding: 60px 0 40px 0;">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="text-dark">All Attendance Records</h2>
        </div>
        <div class="container-fluid">
            <div class="row d-flex justify-content-end">
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
                                <label class="form-label">User No</label>
                                <input id="user_no" class="form-control" type="number" name="user_no" value="<?php echo htmlspecialchars($user_no); ?>" required>
                            </div>
                            <div style="padding-top: 32px;">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </div>
                        <br>
                    </div>
                </form>
                <div class="col-2">
                    <label class="form-label">Total Hours</label>
                    <input readonly id="totalhours" class="form-control" type="text" name="totalhours" value="<?php echo htmlspecialchars($totalHoursDisplay); ?>">
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

<!-- Include DataTables JS -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        $('#userattendancedatatable').DataTable();
    });
</script>


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