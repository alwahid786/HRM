<?php
require 'config.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Retrieve the user's type from the session or database
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($userType);
$stmt->fetch();
$stmt->close();

// Check if the user type is either admin or hradmin
if ($userType != 'hradmin') {
    die("Access denied. You do not have permission to view this page.");
    header('location: login.php');
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form values
    $username = $_POST['username'] ?? null;
    $login = $_POST['login'] ?? null;
    $email = $_POST['email'] ?? null;
    $password = $_POST['password'] ?? null;
    $userType = $_POST['userType'] ?? null;
    $hiringDate = $_POST['hiringDate'] ?? null;

    // Check if any field is empty
    if (empty($username) || empty($login) || empty($email) || empty($password) || empty($userType) || empty($hiringDate)) {
        die("Please fill in all fields.");
    }

    // Check if the login already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE login = ?");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        die("Error: A user with this login already exists.");
    }
    $stmt->close();

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, login, email, password, user_type, hiring_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $username, $login, $email, $hashedPassword, $userType, $hiringDate);
    if ($stmt->execute()) {
        header("Location: hrcreateuser.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
}
?>

<?php include_once 'partials/hr/navbar.php'; ?>

<section class="container" style="padding: 60px 0 40px 0;">
    <h2>HR Admin Create User</h2>
    <form method="post" action="hrcreateuser.php">
        <div class="row mb-2">
            <div class="col-md-6">
                <label for="username" class="form-label">Name</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="col-md-6">
                <label for="login" class="form-label">Login</label>
                <input type="text" class="form-control" id="login" name="login" required>
            </div>
        </div>
        <div class="row mb-2">
            <div class="col-md-6">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email"  name="email" required>
            </div>
            <div class="col-md-6">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
        </div>
        <div class="row mb-2">
            <div class="col-md-6">
                <label for="userType" class="form-label">User Type</label>
                <div class="custom-select-wrapper" style="position: relative; width: 100%;">
                    <input id="userType" class="form-control" name="userType" style="appearance: none; -webkit-appearance: none; -moz-appearance: none; padding-right: 40px;" value="user" required>
                </div>
            </div>
            <div class="col-md-6">
                <label for="hiringDate"  class="form-label">Hiring Date</label>
                <input type="date" class="form-control" id="hiringDate" name="hiringDate" required>
            </div>
        </div>
        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary me-2">Submit</button>
            <a href="hr.php" class="btn btn-danger">Cancel</a>
        </div>
    </form>
</section>

<?php include_once 'partials/hr/footer.php'; ?>
