<?php
require 'config/db.php';
// session_start() is already securely handled by db.php now!

// URL BOUNCER: If already logged in, push them back to their dashboard
if (isset($_SESSION['user_id'])) {
    $r = $_SESSION['role'] ?? '';
    header("Location: pages/" . ($r=='admin'?'admin':($r=='student'?'student':($r=='registrar'?'registrar':'approver'))) . "_dashboard.php"); 
    exit();
}

$error = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $uname = trim($_POST['username']);
    $pass = $_POST['password'];

    $tables = [
        'admins' =>['col'=>'username', 'allow_user'=>true, 'role'=>'admin', 'dash'=>'admin_dashboard.php'],
        'students' =>['col'=>'student_id', 'allow_user'=>false, 'role'=>'student', 'dash'=>'student_dashboard.php'],
        'proctors' =>['col'=>'proctor_id', 'allow_user'=>true, 'role'=>'proctor', 'dash'=>'approver_dashboard.php'],
        'librarians' =>['col'=>'librarian_id', 'allow_user'=>true, 'role'=>'librarian', 'dash'=>'approver_dashboard.php'],
        'cafeterias' =>['col'=>'cafeteria_id', 'allow_user'=>true, 'role'=>'cafeteria', 'dash'=>'approver_dashboard.php'],
        'student_services' =>['col'=>'student_service_id', 'allow_user'=>true, 'role'=>'student_service', 'dash'=>'approver_dashboard.php'],
        'dept_heads' =>['col'=>'dept_head_id', 'allow_user'=>true, 'role'=>'dept_head', 'dash'=>'approver_dashboard.php'],
        'registrars' =>['col'=>'registrar_id', 'allow_user'=>true, 'role'=>'registrar', 'dash'=>'registrar_dashboard.php']
    ];

    $user_found = false;

    foreach ($tables as $table => $c) {
        if ($c['allow_user']) {
            $stmt = $conn->prepare("SELECT * FROM $table WHERE ({$c['col']} = ? OR username = ?) AND is_deleted = 0");
            $stmt->bind_param("ss", $uname, $uname);
        } else {
            $stmt = $conn->prepare("SELECT * FROM $table WHERE {$c['col']} = ? AND is_deleted = 0");
            $stmt->bind_param("s", $uname);
        }
        
        $stmt->execute(); 
        $res = $stmt->get_result();

        if ($res->num_rows == 1) {
            $user = $res->fetch_assoc();
            if (password_verify($pass, $user['password'])) {
                
                // ==============================================================
                // ANTI-HACKER SHIELD: Prevent Session Hijacking & Fixation
                // ==============================================================
                session_regenerate_id(true); 
                
                $_SESSION['user_id'] = $user[$c['col']]; 
                $_SESSION['role'] = $c['role'];
                $_SESSION['photo'] = $user['photo'] ?? 'default.png';
                $_SESSION['name'] = $user['fname'] ?? $user['sname'] ?? $user['full_name'] ?? 'User';
                $_SESSION['block_no'] = $user['block_no'] ?? null;
                $_SESSION['dorm_no'] = $user['dorm_no'] ?? null;
                $_SESSION['department'] = $user['department'] ?? null;
                
                header("Location: pages/" . $c['dash']); 
                exit();
            } else {
                $error = "Invalid Password!"; 
                $user_found = true; 
                break;
            }
        }
    }
    if(!$user_found && !$error) $error = "System ID or Username not found or account is deactivated!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title> Login - DTUSCMS</title>
   <link rel="stylesheet" href="assets/css/all.min.css">
    <style>
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('assets/libre.jpg') no-repeat center center fixed; background-size: cover; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; overflow: hidden; }
        .brand-logo-login { position: absolute; top: 25px; left: 30px; display: flex; align-items: center; gap: 12px; text-decoration: none; color: white; font-size: 22px; font-weight: 900; letter-spacing: 1.5px; transition: 0.3s; text-shadow: 2px 2px 4px rgba(0,0,0,0.8); z-index: 100;}
        .brand-logo-login:hover { color: #2ecc71; transform: scale(1.02); }
        .logo-circle-login { width: 45px; height: 45px; background: url('assets/libre.jpg') center center/cover; border-radius: 50%; border: 3px solid #2ecc71; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 10px rgba(0,0,0,0.8); }
        .logo-circle-login i { color: white; font-size: 18px; text-shadow: 2px 2px 4px rgba(0,0,0,0.9); }
        .marquee-container { width: 100%; position: absolute; top: 50px; overflow: hidden; white-space: nowrap; z-index: 1; }
        .marquee-text { display: inline-block; color: #2ecc71; font-size: 38px; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; text-shadow: 2px 2px 2px rgba(247, 26, 11, 0.97), -1px -1px 0 #fdfcf9ea, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000; animation: scroll-left 25s linear infinite; }
        @keyframes scroll-left { 0% { transform: translateX(100vw); } 100% { transform: translateX(-100%); } }
        .login-box { background: rgba(245, 237, 237, 0.9); width: 100%; max-width: 360px; padding: 40px; border-radius: 12px; box-shadow: 0 15px 35px rgba(250, 213, 3, 0.84); z-index: 10; margin-top: 50px; }
        .login-box:hover { transform: translateY(-15px);}
        .login-header { text-align: center; margin-bottom: 30px; }
        .login-header i { font-size: 50px; color: #033eff; margin-bottom: 10px; }
        .login-header h2 { margin: 0; color: #2c3e50; font-size: 24px; font-weight: 800; }
        .input-group { margin-bottom: 20px; position: relative; }
        .input-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #282e28; font-size: 14px; }
        .input-icon { position: absolute; left: 15px; top: 38px; color: #0431f7; }
        .input-field { width: 100%; padding: 12px 15px 12px 40px; border: 2px solid #cbee08da; border-radius: 8px; font-size: 15px; box-sizing: border-box; transition: 0.3s; background: #fdfdfd; }
        .input-field:focus { border-color: #0694f3; outline: none; box-shadow: 0 0 10px rgba(52, 152, 219, 0.3); background: white; }
        .toggle-password { position: absolute; right: 15px; top: 38px; color: #49d10acb; cursor: pointer; }
        .btn-login { width: 100%; padding: 15px; font-size: 16px; background: #3498db; border: none; border-radius: 8px; color: white; cursor: pointer; font-weight: bold; transition: 0.3s; margin-top: 5px; }
        .btn-login:hover { background: #3ef706c2; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4); }
        .alert-error { color: #721c24; background: #f8d7da; padding: 12px; border-radius: 5px; margin-bottom: 20px; text-align: center; border: 1px solid #f5c6cb; font-size: 17px; font-weight:bold; }
        .forgot-pass { display: block; text-align: right; margin-top: -10px; margin-bottom: 20px;border-radius:10px; border: none;  color: #e74c3c; text-decoration: none; font-size: 15px; font-weight: bold; }
        .forgot-pass:hover { background: #dcf310d2; color: rgba(24, 8, 252, 0.95); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(255, 255, 254, 0.99); }
    </style>
</head>
<body>
    <a href="index.php" class="brand-logo-login"><div class="logo-circle-login"><i class="fas fa-building"></i></div>DTUSCMS</a>
    <div class="marquee-container"><div class="marquee-text">WELCOME TO DTU STUDENT CLEARANCE MANAGEMENT SYSTEM</div></div>

    <div class="login-box">
        <div class="login-header"><i class="fas fa-university"></i><h2>DTU SCMS Login</h2></div>
        <?php if($error): ?><div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>
        
        <form method="POST">
            <div class="input-group">
                <label>System ID or Username:</label>
                <i class="fas fa-user input-icon"></i>
                <input type="text" name="username" class="input-field" placeholder="E.g., DTU1234" required>
            </div>
            <div class="input-group">
                <label>Password:</label>
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password" id="pass" class="input-field" placeholder="Enter password" required>
                <i class="fas fa-eye toggle-password" id="eye" onclick="t()"></i>
            </div>
            <a href="forgot_password.php" class="forgot-pass">Forgot Password?</a>
            <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> Login here</button>
        </form>
    </div>
    <script>
        function t() { var p = document.getElementById("pass"), e = document.getElementById("eye"); 
        if (p.type === "password") { p.type = "text"; e.className = "fas fa-eye-slash toggle-password"; } 
        else { p.type = "password"; e.className = "fas fa-eye toggle-password"; } }
    </script>
</body>
</html>