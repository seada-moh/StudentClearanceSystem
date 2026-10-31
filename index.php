<?php
require 'config/db.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (isset($_SESSION['user_id'])) {
    $r = $_SESSION['role'] ?? '';
    header("Location: pages/" . ($r=='admin'?'admin':($r=='student'?'student':($r=='registrar'?'registrar':'approver'))) . "_dashboard.php"); exit();
}

$page = $_GET['page'] ?? 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DTU Clearance Management System</title>
    <link rel="stylesheet" href="assets/css/all.min.css">
    <style>
        /* CORE STYLES */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #0f172a; color: #ecf0f1; display: flex; flex-direction: column; min-height: 100vh; }

        /* TOP NAVIGATION BAR WITH LOGO */
        .navbar { position: fixed; top: 0; width: 100%; background: rgba(17, 24, 39, 0.95); padding: 15px 40px; z-index: 1000; box-shadow: 0 4px 15px rgba(0,0,0,0.6); display: flex; justify-content: space-between; align-items: center; backdrop-filter: blur(5px); }
        .nav-links { display: flex; gap: 30px; align-items: center; }
        .navbar a.nav-item { color: #ecf0f1; text-decoration: none; font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; transition: 0.3s; padding: 8px 10px; border-bottom: 3px solid transparent; }
        .navbar a.nav-item:hover, .navbar a.active { color: #2ecc71; border-bottom: 3px solid #2ecc71; }
        
        /* BRAND LOGO DESIGN */
        .brand-logo { display: flex; align-items: center; gap: 12px; text-decoration: none; color: white; font-size: 22px; font-weight: 900; letter-spacing: 1.5px; transition: 0.3s; }
        .brand-logo:hover { color: #2ecc71; transform: scale(1.02); }
        .logo-circle { width: 45px; height: 45px; background: url('assets/bg.jpg') center center/cover; border-radius: 50%; border: 3px solid #2ecc71; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 10px rgba(46, 204, 113, 0.5); }
        .logo-circle i { color: white; font-size: 18px; text-shadow: 2px 2px 4px rgba(0,0,0,0.9); }

        /* HOME PAGE HERO */
        .hero { height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; background: linear-gradient(rgba(0, 0, 0, 0.48), rgba(0, 0, 0, 0.47)), url('assets/bg.jpg') no-repeat center center/cover; padding: 0 20px; }
        .animated-title { font-size: 55px; font-weight: 900; text-transform: uppercase; margin-bottom: 20px; background: linear-gradient(45deg, #1fee75, #3498db, #fcca05, #ff2903); background-size: 300%; -webkit-background-clip: text; -webkit-text-fill-color: transparent; animation: gradient-animation 3s ease infinite; letter-spacing: 2px; }
        @keyframes gradient-animation { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        .hero-desc { color: #bdc3c7; font-size: 22px; max-width: 850px; line-height: 1.6; margin-bottom: 50px; text-shadow: 2px 2px 4px rgba(0,0,0,0.9); }
        
        .btn-goto-login { background: #27ae60; color: white; padding: 20px 45px; font-size: 20px; font-weight: bold; text-decoration: none; border-radius: 50px; transition: 0.3s; box-shadow: 0 0 20px rgba(39, 174, 96, 0.6); animation: pulse 2s infinite; border: 2px solid transparent; }
        .btn-goto-login:hover { background: transparent; border: 2px solid #27ae60; color: #27ae60; transform: translateY(-5px); box-shadow: 0 15px 25px rgba(0,0,0,0.8); animation: none; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(39, 174, 96, 0.7); } 70% { box-shadow: 0 0 0 20px rgba(39, 174, 96, 0); } 100% { box-shadow: 0 0 0 0 rgba(108, 124, 114, 0.79); } }

        /* MINI HERO (ABOUT / CONTACT) */
        .mini-hero { height: 40vh; display: ontent: flex; flex-direction: column; align-items: center; justify-ccenter; text-align: center; background: linear-gradient(rgba(126, 121, 121, 0.61), rgba(145, 140, 140, 0.51)), url('assets/bg.jpg') no-repeat center center/cover; margin-top: 80px; border-bottom: 5px solid #37fa06; }
        .mini-hero h1 { color: #f5fff9; font-size: 50px; text-transform: uppercase; letter-spacing: 2px; }
        .mini-hero p { color: #f5eeee; font-size: 22px; margin-top: 10px; }

        /* PAGE CONTENT */
        .page-content { padding: 50px 15px; flex: 1; display: flex; flex-direction: column; align-items: center; background: #23232954; }
        .about-text-container { max-width: 1000px; background: #f85b12d8; padding: 50px; border-radius: 15px; box-shadow: 0 15px 35px rgba(0,0,0,0.5); border-left: 8px solid #3498db; margin-bottom: 50px; }
        .about-text-container h2 { color: #3498db; margin-bottom: 20px; font-size: 28px; }
        .about-text-container p { color: #fcfaf9; font-size: 18px; line-height: 1.8; margin-bottom: 12px; text-align: justify; }

        .grid-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; width: 100%; max-width: 1200px; }
        .card { padding: 40px 30px; border-radius: 15px; text-align: center; transition: 0.4s; box-shadow: 0 10px 20px rgba(0,0,0,0.4); position: relative; overflow: hidden; border: 1px solid rgba(230, 245, 14, 0.92); background: linear-gradient(135deg, #454e45a4, #5663528c); }
        .card:hover { transform: translateY(-15px); box-shadow: 0 20px 40px rgba(0,0,0,0.6); }
        .card.green { background: linear-gradient(135deg, #14532d, #064e3b); border-bottom: 4px solid #2ecc71; }
        .card.blue { background: linear-gradient(135deg, #1e3a8a, #172554); border-bottom: 4px solid #3498db; }
        .card.orange { background: linear-gradient(135deg, #7c2d12, #451a03); border-bottom: 4px solid #e67e22; }
        .card.purple { background: linear-gradient(135deg, #4c1d95, #312e81); border-bottom: 4px solid #9b59b6; }
        .card i { font-size: 55px; margin-bottom: 20px; color: #fff; text-shadow: 0 0 15px rgba(255,255,255,0.5); }
        .card h3 { color: #f8fafc; margin-bottom: 15px; font-size: 24px; letter-spacing: 1px; }
        .card p { color: #f7fdf5; font-size: 16px; line-height: 1.6; }

        /* FOOTER */
        .footer { background: #000; color: #ecf0f1; padding: 25px 20px; text-align: center; margin-top: auto; border-top: 2px solid #333; }

        @media (max-width: 768px) {
            .navbar { flex-direction: column; padding: 15px; gap: 15px; }
            .nav-links { flex-wrap: wrap; justify-content: center; gap: 15px; }
            .animated-title { font-size: 35px; }
        }
    </style>
</head>
<body>

    <!-- DYNAMIC TOP NAVIGATION WITH LOGO -->
    <div class="navbar">
        <a href="index.php?page=home" class="brand-logo">
            <div class="logo-circle"><i class="fas fa-building"></i></div>
            DTUSCMS
        </a>
        <div class="nav-links">
            <a href="?page=home" class="nav-item <?= $page == 'home' ? 'active' : '' ?>"><i class="fas fa-home"></i> Home</a>
            <a href="?page=about" class="nav-item <?= $page == 'about' ? 'active' : '' ?>"><i class="fas fa-info-circle"></i> About Us</a>
            <a href="?page=contact" class="nav-item <?= $page == 'contact' ? 'active' : '' ?>"><i class="fas fa-envelope"></i> Contact</a>
        </div>
    </div>

    <!-- ===================== HOME PAGE ===================== -->
    <?php if($page == 'home'): ?>
        <div class="hero">
            <h1 class="animated-title">Welcome to DTU Student Clearance System</h1>
            <p class="hero-desc">
                A state-of-the-art, dynamic web platform engineered to automate the traditional clearance process. We ensure a seamless, secure, and highly efficient experience for graduating students, dormmates, and administrative staff.
            </p>
            <a href="login.php" class="btn-goto-login"><i class="fas fa-user-shield"></i> Go to Login page</a>
        </div>

    <!-- ===================== ABOUT US PAGE ===================== -->
    <?php elseif($page == 'about'): ?>
        <div class="mini-hero"><h1>About The System</h1><p>Transforming university administration through technology.</p></div>
        <div class="page-content">
            <div class="about-text-container">
                <h2><i class="fas fa-laptop-code"></i> Transforming DTU's Clearance Process</h2>
                <p>The <b>Debre Tabor University (DTU) Student Clearance Management System</b> eradicates the physical paper trail and long queues traditionally associated with end-of-year clearance.</p>
                <p>By bridging the gap between students, dorms, libraries, and the registrar, this system coordinates <b>strict sequential approvals</b> in real-time. Students no longer walk from office to office; requests route instantly.</p>
                <p><b>Advanced Security & Validation:</b> The system employs strict Role-Based Access Control. Proctors can only approve students within their exact assigned block, ensuring privacy and data integrity.</p>
            </div>
            <div class="grid-container">
                <div class="card dark"><i class="fas fa-bolt" style="color:#f1c40f;"></i><h3>Automated Workflows</h3><p>Dynamic routing based on clearance reason.</p></div>
                <div class="card dark"><i class="fas fa-bell" style="color:#3498db;"></i><h3>Real-Time Alerts</h3><p>Students receive live notifications instantly.</p></div>
                <div class="card dark"><i class="fas fa-file-pdf" style="color:#e74c3c;"></i><h3>Digital Gate Pass</h3><p>A verifiable PDF Gate Pass is unlocked for campus exit.</p></div>
            </div>
        </div>

    <!-- ===================== CONTACT PAGE ===================== -->
    <?php elseif($page == 'contact'): ?>
        <div class="mini-hero"><h1>Contact Support</h1><p>Get in touch with the DTU administration team.</p></div>
        <div class="page-content">
            <div class="grid-container">
                <div class="card green"><i class="fas fa-map-marker-alt"></i><h3>Our Location</h3><p>Debre Tabor University<br>Main Campus, Admin Building</p></div>
                <div class="card blue"><i class="fas fa-envelope-open-text"></i><h3>Email Support</h3><p>admin@dtu.edu.et<br>registrar@dtu.edu.et</p></div>
                <div class="card orange"><i class="fas fa-phone-alt"></i><h3>Call Us</h3><p>+251 960 848 291(Enete)<br>+251 983 217 299(office)<br> Mon - Fri, 8:00 AM - 5:00 PM</p></div>
                <div class="card purple"><i class="fab fa-telegram-plane"></i><h3>Telegram Channel</h3><p>@DTU_Clearance_Bot<br>@DTU_Students_Official</p></div>
            </div>
        </div>
    <?php endif; ?>

    <div class="footer"><p>&copy; <?= date("Y") ?> Debre Tabor University Student Clearance Management System(DTUSCMS). All Rights Reserved.</p></div>
</body>
</html>