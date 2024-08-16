<?php
require 'config.php';
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form values
    $login = $_POST['login'] ?? null;
    $password = $_POST['password'] ?? null;

    // Check if any field is empty
    if (empty($login) || empty($password)) {
        die("Please fill in all fields.");
    }

    // Prepare and execute the query to fetch user data
    $stmt = $conn->prepare("SELECT id, password, user_type, status FROM users WHERE login = ?");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $stmt->bind_result($userId, $hashedPassword, $userType, $status);
    $stmt->fetch();
    $stmt->close();

    // Check if user exists and status is active
    if ($hashedPassword) {
        if ($status === 'blocked') {
            echo "Your account is blocked.";
        } elseif (password_verify($password, $hashedPassword)) {
            // Store user information in session
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_type'] = $userType;

            // Redirect based on user type
            if ($userType === 'admin') {
                // Admin
                header("Location: admin.php");
            } elseif ($userType === 'hradmin') {
                // HR Admin
                header("Location: hr.php");
            } elseif ($userType === 'user') {
                // User
                header("Location: user.php");
            } else {
                // Default redirection for unauthorize user's
                header("Location: login.php");
            }
            exit();
        } else {
            echo "Invalid login or password.";
        }
    } else {
        echo "Invalid login or password.";
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hrm</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.0.0-beta2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .btn-color {
            background-color: #0e1c36;
            color: #fff;
        }

        .vh-100 {
            height: 100vh;
        }

        .bgcolor {
            background: linear-gradient(to right, #35592d, #1e5d75) !important;
        }

        .form-box {
            padding: 20px;
            border: 1px #73dc00 solid;
            border-radius: 4px;
            box-shadow: 0 6px 10px 0 #222;
            background: linear-gradient(100deg, #73dc00, #01ceff);
        }

        .circle-background {
            display: inline-block;
            background: #0e1c36;
            padding: 20px;
            border-radius: 50%;
        }

        .circle-background img {
            display: block;
            height: fit-content;
            background: #0e1c36;
            border: none;
            /* width: 150px; */
            /* height: 150px; */
            border-radius: 52px;
        }


        .profile-image-pic {
            height: 200px;
            /* width: 200px; */
            object-fit: cover;
            padding: 18px;
        }
    </style>
</head>

<body class="bgcolor">
    <div class="container vh-100 d-flex align-items-center justify-content-center">
        <div class="row w-100">
            <div class="col-md-6 offset-md-3">
                <h2 class="text-center text-white mt-2">Tetra Technologies</h2>
                <div class="text-center text-white">Leave Management System</div>
                <div class="card my-3 form-box">
                    <form action="login.php" method="POST" class="card-body cardbody-color p-lg-5">
                        <div class="text-center">
                            <!-- Container for the circular background -->
                            <div class="circle-background">
                                <img src="images/icons/company_logo.png" class="img-fluid profile-image-pic img-thumbnail" width="200px" alt="profile">
                            </div>
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" id="login" name="login" placeholder="login" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-color px-5 mb-2 w-100">Login</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>

</html>