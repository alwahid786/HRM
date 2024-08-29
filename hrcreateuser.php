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
    $role = $_POST['role'] ?? null;
    $salary = $_POST['salary'] ?? null;
    $userNo = $_POST['user_no'] ?? null;

    // Check if any field is empty
    if (empty($username) || empty($login) || empty($email) || empty($password) || empty($userType) || empty($hiringDate) || empty($role) || empty($salary) || empty($userNo)) {
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

    // Insert the user into the database
    $stmt = $conn->prepare("INSERT INTO users (username, login, email, password, user_type, hiring_date, role, salary, user_no) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssi", $username, $login, $email, $hashedPassword, $userType, $hiringDate, $role, $salary, $userNo);
    
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
          <label for="username" class="form-label">User Name</label>
          <input type="text" class="form-control" id="username" name="username" >
        </div>
        <div class="col-md-6">
          <label for="login" class="form-label">Login</label>
          <input type="text" class="form-control" id="login" name="login">
        </div>
      </div>
      <div class="row mb-2">
        <div class="col-md-6">
          <label for="email" class="form-label">Email</label>
          <input type="email" class="form-control" id="email" name="email" >
        </div>
        <div class="col-md-6">
          <label for="password" class="form-label">Password</label>
          <input type="password" class="form-control" id="password" name="password">
        </div>
      </div>
      <div class="row mb-2">
        <div class="col-md-6">
          <label for="userType" class="form-label">User Type</label>
          <div class="custom-select-wrapper" style="position: relative; width: 100%;">
            <select id="userType" class="form-control" name="userType" style="appearance: none; -webkit-appearance: none; -moz-appearance: none; padding-right: 40px;">
              <option value="user">User</option>
            </select>
            <span class="material-icons" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #555; font-size: 20px;">arrow_drop_down</span>
          </div>
        </div>
        <div class="col-md-6">
          <label for="hiringDate" class="form-label">Hiring Date</label>
          <input type="date" class="form-control" id="hiringDate" name="hiringDate">
        </div>
      </div>
      <div class="row mb-2">
        <div class="col-md-4">
          <label class="form-label">User Role</label>
          <input type="text" class="form-control" name="role" >
        </div>
        <div class="col-md-4">
          <label  class="form-label">User Salary</label>
          <input type="text" class="form-control" name="salary">
        </div>
        <div class="col-md-4">
          <label  class="form-label">User No</label>
          <input type="number" class="form-control" name="user_no">
        </div>
      </div>
      <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary me-2">Submit</button>
        <a href="admin.php" class="btn btn-danger">Cancel</a>
      </div>
    </form>
</section>

<?php include_once 'partials/hr/footer.php'; ?>
