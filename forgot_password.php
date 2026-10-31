<?php
require 'config/db.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

$error = null;
$success = null;
$step = 1; // Step 1: Verify Identity | Step 2: Reset Password

// REMOVED 'dorm_mates' table since they are now stored in 'students'
$tables = [
    'admins' => 'username', 
    'students' => 'student_id', 
    'proctors' => 'proctor_id', 
    'librarians' => 'librarian_id', 
    'cafeterias' => 'cafeteria_id',
    'student_services' => 'student_service_id', 
    'dept_heads' => 'dept_head_id', 
    'registrars' => 'registrar_id'
];

// PROCESS STEP 1: Verify Identity
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_identity'])) {
    $uname = trim($_POST['username']);
    $email = trim($_POST['email']);
    $user_found = false;

    foreach ($tables as $table => $id_col) {
        // Also check if the user is not deleted (is_deleted = 0)
        // AND allow staff to use either their username or ID
        if ($table == 'students') {
            $stmt = $conn->prepare("SELECT * FROM $table WHERE $id_col = ? AND email = ? AND is_deleted = 0");
            $stmt->bind_param("ss", $uname, $email);
        } else {
            $stmt = $conn->prepare("SELECT * FROM $table WHERE ($id_col = ? OR username = ?) AND email = ? AND is_deleted = 0");
            $stmt->bind_param("sss", $uname, $uname, $email);
        }
        
        if (!$stmt) continue; 
        
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows == 1) {
            // Identity Verified! Move to Step 2
            $_SESSION['reset_id'] = $uname;
            $_SESSION['reset_table'] = $table;
            $_SESSION['reset_col'] = $id_col;
            $step = 2;
            $user_found = true;
            break;
        }
    }
    
    if(!$user_found) {
        $error = "Verification failed! System ID and Email do not match an active account.";
    }
}

// PROCESS STEP 2: Save New Password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $new_pass = $_POST['new_pass'];
    $confirm_pass = $_POST['confirm_pass'];

    if ($new_pass !== $confirm_pass) {
        $error = "Passwords do not match! Please try again.";
        $step = 2; // Keep them on step 2
    } else {
        // Hash and Update
        $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
        $table = $_SESSION['reset_table'];
        $id_col = $_SESSION['reset_col'];
        $user_id = $_SESSION['reset_id'];

        // If they verified using 'username', we must update using 'username'
        $update_col = ($table != 'students') ? "($id_col = ? OR username = ?)" : "$id_col = ?";

        if ($table != 'students') {
            $stmt = $conn->prepare("UPDATE $table SET password = ? WHERE ($id_col = ? OR username = ?)");
            $stmt->bind_param("sss", $hashed_password, $user_id, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE $table SET password = ? WHERE $id_col = ?");
            $stmt->bind_param("ss", $hashed_password, $user_id);
        }
        
        if ($stmt->execute()) {
            $success = "Password successfully reset! You can now securely log in.";
            // Clear the reset sessions for security
            unset($_SESSION['reset_id']);
            unset($_SESSION['reset_table']);
            unset($_SESSION['reset_col']);
            $step = 3; // Step 3 is just the success message
        } else {
            $error = "Database error. Please contact the administrator.";
            $step = 2;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - DTUSCMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('assets/bg.jpg') no-repeat center center fixed; background-size: cover; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; overflow: hidden; }
        
        .brand-logo-login { position: absolute; top: 25px; left: 30px; display: flex; align-items: center; gap: 12px; text-decoration: none; color: white; font-size: 22px; font-weight: 900; letter-spacing: 1.5px; transition: 0.3s; text-shadow: 2px 2px 4px rgba(0,0,0,0.8); z-index: 100;}
        .brand-logo-login:hover { color: #2ecc71; transform: scale(1.02); }
        .logo-circle-login { width: 45px; height: 45px; background: url('assets/bg.jpg') center center/cover; border-radius: 50%; border: 3px solid #2ecc71; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 10px rgba(0,0,0,0.8); }
        .logo-circle-login i { color: white; font-size: 18px; text-shadow: 2px 2px 4px rgba(0,0,0,0.9); }

        .login-box { background: rgba(255, 255, 255, 0.95); width: 100%; max-width: 450px; padding: 40px; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.5); box-sizing: border-box; z-index: 10; }
        .login-header { text-align: center; margin-bottom: 25px; }
        .login-header i { font-size: 50px; color: #e74c3c; margin-bottom: 10px; }
        .login-header h2 { margin: 0; color: #2c3e50; font-size: 22px; font-weight: 800; }
        .input-group { margin-bottom: 20px; position: relative; }
        .input-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #34495e; font-size: 14px; }
        .input-icon { position: absolute; left: 15px; top: 38px; color: #95a5a6; }
        .input-field { width: 100%; padding: 12px 15px 12px 40px; border: 2px solid #ecf0f1; border-radius: 8px; font-size: 15px; box-sizing: border-box; transition: 0.3s; background: #fdfdfd; }
        .input-field:focus { border-color: #e74c3c; outline: none; box-shadow: 0 0 10px rgba(231, 76, 60, 0.3); background: white; }
        .btn-submit { width: 100%; padding: 15px; font-size: 16px; background: #e74c3c; border: none; border-radius: 8px; color: white; cursor: pointer; font-weight: bold; transition: 0.3s; margin-top: 10px; box-shadow: 0 4px 6px rgba(231, 76, 60, 0.3); }
        .btn-submit:hover { background: #c0392b; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(231, 76, 60, 0.5); }
        .btn-back { display: block; text-align: center; margin-top: 20px; color: #3498db; text-decoration: none; font-weight: bold; transition: 0.3s; }
        .btn-back:hover { color: #2980b9; text-decoration: underline; }
        .alert-error { color: #721c24; background: #f8d7da; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; border: 1px solid #f5c6cb; font-size: 14px; font-weight: bold; }
        .alert-success { color: #155724; background: #d4edda; padding: 25px; border-radius: 8px; margin-bottom: 20px; text-align: center; border: 1px solid #c3e6cb; font-size: 16px; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

    <a href="index.php" class="brand-logo-login">
        <div class="logo-circle-login"><i class="fas fa-building"></i></div>
        DTUSCMS
    </a>

    <div class="login-box">
        
        <?php if($step == 1): ?>
            <!-- STEP 1: IDENTITY VERIFICATION -->
            <div class="login-header">
                <i class="fas fa-user-shield"></i>
                <h2>Account Verification</h2>
                <p style="color: #7f8c8d; font-size: 14px; margin-top: 10px; font-weight: 500;">Please enter your registered details to securely reset your password.</p>
            </div>

            <?php if($error): ?><div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>

            <form method="POST">
                <input type="hidden" name="verify_identity" value="1">
                
                <div class="input-group">
                    <label>System ID or Username:</label>
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="username" class="input-field" placeholder="E.g., DTU1234 or Username" required>
                </div>
                
                <div class="input-group">
                    <label>Registered Email Address:</label>
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="email" class="input-field" placeholder="Enter your email" required>
                </div>
                
                <button type="submit" class="btn-submit"><i class="fas fa-search"></i> Verify My Account</button>
                <a href="login.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Login Page</a>
            </form>

        <?php elseif($step == 2): ?>
            <!-- STEP 2: ENTER NEW PASSWORD -->
            <div class="login-header">
                <i class="fas fa-key" style="color:#f39c12;"></i>
                <h2>Create New Password</h2>
                <div style="background: #e8f4f8; padding: 10px; border-radius: 5px; border-left: 4px solid #3498db; margin-top: 15px;">
                    <p style="color: #2c3e50; font-weight: bold; font-size: 14px; margin: 0;"><i class="fas fa-check-circle" style="color:#27ae60;"></i> Identity Verified: <?= htmlspecialchars($_SESSION['reset_id']) ?></p>
                </div>
            </div>

            <?php if($error): ?><div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>

            <form method="POST">
                <input type="hidden" name="reset_password" value="1">
                
                <div class="input-group">
                    <label>New Password:</label>
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="new_pass" class="input-field" placeholder="Enter new password" required>
                </div>
                
                <div class="input-group">
                    <label>Confirm Password:</label>
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="confirm_pass" class="input-field" placeholder="Confirm new password" required>
                </div>
                
                <button type="submit" class="btn-submit" style="background:#27ae60;"><i class="fas fa-save"></i> Save New Password</button>
                <a href="login.php" class="btn-back" style="color: #95a5a6;"><i class="fas fa-times"></i> Cancel</a>
            </form>

        <?php elseif($step == 3): ?>
            <!-- STEP 3: SUCCESS -->
            <div class="alert-success">
                <i class="fas fa-check-circle" style="font-size: 50px; margin-bottom: 15px; display:block; color: #27ae60;"></i>
                <?= $success ?>
            </div>
            <a href="login.php" class="btn-submit" style="background:#3498db; text-align:center; text-decoration:none; display:block;"><i class="fas fa-sign-in-alt"></i> Go to Login Page</a>
        <?php endif; ?>

    </div>
</body>
</html>