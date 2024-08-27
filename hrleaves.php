<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, user_type, leave_limit, leave_start_date, leave_end_date FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($username, $userType, $leaveLimit, $leaveStartDate, $leaveEndDate);
$stmt->fetch();
$stmt->close();

if ($userType != 'hradmin') {
    header("Location: login.php");
    exit();
}

// Calculate the used leaves and remaining leaves
$stmt = $conn->prepare("SELECT COALESCE(SUM(duration), 0) FROM leaves WHERE user_id = ? AND status = 'Approved'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($usedLeaves);
$stmt->fetch();
$stmt->close();

$remainingLeaves = max($leaveLimit - $usedLeaves, 0);

// Get the last leave's end date
$stmt = $conn->prepare("SELECT COALESCE(MAX(end_date), '0000-00-00') FROM leaves WHERE user_id = ? AND status = 'Approved'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($lastLeaveEndDate);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];
    $startShift = $_POST['startShift'];
    $endShift = $_POST['endShift'];
    $leaveType = $_POST['leaveType'];
    $reason = $_POST['reason'];
    $duration = calculateDuration($startDate, $endDate, $startShift, $endShift);

    // Validate leave dates
    if ($startDate < $leaveStartDate || $endDate > $leaveEndDate) {
        $error = "Your leave request must be within the allowed period: {$leaveStartDate} to {$leaveEndDate}.";
    } elseif ($startDate <= $lastLeaveEndDate) {
        $error = "You cannot request a leave on a date where you already have an approved leave.";
    } elseif ($duration > $remainingLeaves) {
        $error = "You cannot request more leave than your remaining balance.";
    } else {
        // Check if the user already has a leave request for the current day
        $today = date('Y-m-d');
        $stmt = $conn->prepare("SELECT COUNT(*) FROM leaves WHERE user_id = ? AND DATE(start_date) = ? AND status = 'Requested'");
        $stmt->bind_param("is", $userId, $today);
        $stmt->execute();
        $stmt->bind_result($existingRequests);
        $stmt->fetch();
        $stmt->close();

        if ($existingRequests > 0) {
            $error = "You already have a leave request for today.";
        } else {
            // Insert the leave request
            $stmt = $conn->prepare("INSERT INTO leaves (user_id, username, start_date, end_date, duration, leave_type, reason, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Requested')");
            $stmt->bind_param("isssdss", $userId, $username, $startDate, $endDate, $duration, $leaveType, $reason);
            $stmt->execute();
            $stmt->close();

            header("Location: hrleaves.php.");
            exit();
        }
    }
}

function calculateDuration($startDate, $endDate, $startShift, $endShift) {
    $startDate = new DateTime($startDate);
    $endDate = new DateTime($endDate);

    $dayDiff = $endDate->diff($startDate)->days;
    $duration = 0;

    if ($dayDiff === 0) {
        if ($startShift === 'halfdaymorning' && $endShift === 'halfdaymorning') {
            $duration = 0.5;
        } elseif ($startShift === 'halfdaymorning' && $endShift === 'halfdayevening') {
            $duration = 1.0;
        } elseif ($startShift === 'halfdayevening' && $endShift === 'halfdayevening') {
            $duration = 0.5;
        } elseif ($startShift === 'halfdayevening' && $endShift === 'halfdaymorning') {
            $duration = 1.0;
        }
    } else if ($dayDiff > 0) {
        if ($startShift === 'halfdaymorning') {
            $duration += 1.0;
        } elseif ($startShift === 'halfdayevening') {
            $duration += 0.5;
        }

        if ($endShift === 'halfdaymorning') {
            $duration += 0.5;
        } elseif ($endShift === 'halfdayevening') {
            $duration += 1.0;
        }

        $duration += ($dayDiff - 1) * 1;
    }

    return $duration;
}

$conn->close();
?>

<?php include_once 'partials/hr/navbar.php'; ?>   <?php if (isset($error)): ?>
        <div class="alert alert-danger mt-3"><?php echo ($error); ?></div>
    <?php endif; ?>
<section class="container" style="padding: 60px 0 40px 0;">
    <div class="d-flex justify-content-between">
        <div>
            <h2>Create Leave</h2>
        </div>
        <div>
            <label>Remaining Leaves</label>
            <input  type="text" class="form-control" id="remainingLeaves" name="remainingLeaves" value="<?php echo ($remainingLeaves); ?>" readonly>
        </div>
    </div>
    <form method="post" action="hrleaves.php" id="leaveForm">
        <div class="row mb-2">
            <div class="col-md-6">
                <label for="username" class="form-label">User Name</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo ($username); ?>" readonly>
            </div>
            <div class="col-md-6">
                <label for="leaveType" class="form-label">Leave Type</label>
                <div class="custom-select-wrapper" style="position: relative; width: 100%;">
                    <select id="leaveType" class="form-control" name="leaveType" required>
                        <option value="sickleave">Sick Leave</option>
                        <option value="paidleave">Paid Leave</option>
                        <option value="specialleave">Special Leave</option>
                    </select>

                    <span class="material-icons" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);">expand_more</span>
                </div>
            </div>
        </div>
        <div class="row mb-2">
            <div class="col-md-6">
                <label for="startDate" class="form-label">Start Date</label>
                <input  type="date" class="form-control" id="startDate" name="startDate" required onchange="calculateDuration()">
            </div>
            <div class="col-md-6">
                <label for="startShift" class="form-label">Shift</label>
                <div class="custom-select-wrapper" style="position: relative; width: 100%;">
                    <select  id="startShift" class="form-control" name="startShift" required onchange="calculateDuration()">
                        <option value="halfdaymorning">Morning</option>
                        <option value="halfdayevening">Evening</option>
                    </select>
                    <span class="material-icons" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);">expand_more</span>
                </div>
            </div>
        </div>
        <div class="row mb-2">
            <div class="col-md-6">
                <label for="endDate" class="form-label">End Date</label>
                <input type="date" class="form-control" id="endDate" name="endDate" required onchange="calculateDuration()">
            </div>
            <div class="col-md-6">
                <label for="endShift" class="form-label">Shift</label>
                <div class="custom-select-wrapper" style="position: relative; width: 100%;">
                    <select  id="endShift" class="form-control" name="endShift" required onchange="calculateDuration()">
                        <option value="halfdaymorning">Morning</option>
                        <option value="halfdayevening">Evening</option>
                    </select>
                    <span class="material-icons" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);">expand_more</span>
                </div>
            </div>
        </div>
        <div class="row mb-2">
            <div class="col-md-6">
                <label for="duration" class="form-label">Duration</label>
                <input  type="text" class="form-control" id="duration" name="duration" readonly required>

            </div>
            <div class="col-md-6">
                <label for="reason" class="form-label">Reason</label>
                <textarea  class="form-control" id="reason" name="reason" required></textarea>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 text-end">
                <button type="submit" class="btn btn-primary">Submit Leave</button>
            </div>
        </div>
    </form>
   
</section>
<?php include_once 'partials/hr/footer.php'; ?>

<script src="https://fonts.googleapis.com/icon?family=Material+Icons"></script>
<script>
    function calculateDateDifference(startDateInput, endDateInput) {
    const startDate = new Date(startDateInput);
    const endDate = new Date(endDateInput);
    if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
        return 0;
    }
    const timeDiff = endDate.getTime() - startDate.getTime();
    const dayDiff = Math.ceil(timeDiff / (1000 * 60 * 60 * 24));
    return dayDiff;
}

function calculateDuration() {
    const startDate = new Date(document.getElementById('startDate').value);
    const endDate = new Date(document.getElementById('endDate').value);
    const startShift = document.getElementById('startShift').value;
    const endShift = document.getElementById('endShift').value;

    let duration = 0;

    if (startDate && endDate) {
        const dayDiff = calculateDateDifference(startDate, endDate);
        if (dayDiff === 0) {
            if (startShift === 'halfdaymorning' && endShift === 'halfdaymorning') {
                duration = 0.5;
            } else if (startShift === 'halfdaymorning' && endShift === 'halfdayevening') {
                duration = 1.0;
            } else if (startShift === 'halfdayevening' && endShift === 'halfdayevening') {
                duration = 0.5;
            } else if (startShift === 'halfdayevening' && endShift === 'halfdaymorning') {
                duration = 1.0;
            }
        } else if (dayDiff > 0) {
            if (startShift === 'halfdaymorning') {
                duration += 1.0;
            } else if (startShift === 'halfdayevening') {
                duration += 0.5;
            }

            if (endShift === 'halfdaymorning') {
                duration += 0.5;
            } else if (endShift === 'halfdayevening') {
                duration += 1.0;
            }

            duration += (dayDiff - 1) * 1; 
        }
    }
    document.getElementById('duration').value = duration.toFixed(1);

    // Check if the requested leave exceeds the remaining leaves
    const remainingLeaves = parseFloat(document.getElementById('remainingLeaves').value);
    if (duration > remainingLeaves) {
        document.getElementById('leaveForm').querySelector('button[type="submit"]').disabled = true;
        alert('You cannot request more leave than your remaining balance.');
    } else {
        document.getElementById('leaveForm').querySelector('button[type="submit"]').disabled = false;
    }
}
document.getElementById('startDate').addEventListener('change', calculateDuration);
document.getElementById('endDate').addEventListener('change', calculateDuration);
document.getElementById('startShift').addEventListener('change', calculateDuration);
document.getElementById('endShift').addEventListener('change', calculateDuration);
</script>