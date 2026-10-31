<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }

$profile_photo = !empty($_SESSION['photo']) ? $_SESSION['photo'] : 'default.png';
$display_name = $_SESSION['name'] ?? 'User';
$role_display = strtoupper(str_replace('_', ' ', $_SESSION['role']));

// GET UNREAD NOTIFICATION COUNT
$current_user_id = $_SESSION['user_id'] ?? '';
$notif_count = 0;
$chat_count = 0;
if($current_user_id != '') {
    $n_query = $conn->query("SELECT COUNT(*) as c FROM notifications WHERE recipient_id = '$current_user_id' AND is_read = 0");
    if($n_query) $notif_count = $n_query->fetch_assoc()['c'];
    
    $c_query = $conn->query("SELECT COUNT(*) as c FROM chat_messages WHERE recipient_id = '$current_user_id' AND is_read = 0");
    if($c_query) $chat_count = $c_query->fetch_assoc()['c'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>DTUSCMS</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/css/all.min.css"> <!-- Works offline if downloaded -->
    <script src="../assets/chart.js"></script> <!-- Works offline if downloaded -->
    <style>
        .badge-notif { background: #e74c3c; color: white; padding: 2px 7px; border-radius: 50%; font-size: 12px; margin-left: 8px; vertical-align: top; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .sidebar-brand { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 20px; background: #111827; text-decoration: none; color: white; font-size: 22px; font-weight: 900; letter-spacing: 1.5px; border-bottom: 3px solid #2ecc71; transition: 0.3s; }
        .sidebar-brand:hover { background: #1f2937; color: #2ecc71; }
        .sidebar-logo-circle { width: 45px; height: 45px; background: url('../assets/bg.jpg') center center/cover; border-radius: 50%; border: 3px solid #2ecc71; display: flex; align-items: center; justify-content: center; }
        .sidebar-logo-circle i { color: white; font-size: 18px; text-shadow: 2px 2px 4px rgba(0,0,0,0.9); }
        .btn-logout-custom { background: #e74c3c; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; transition: 0.4s; display: flex; align-items: center; gap: 8px; border: 2px solid transparent; }
        .btn-logout-custom:hover { background: #27ae60; border-color: #2ecc71; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(39, 174, 96, 0.4); }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="../index.php" class="sidebar-brand">
            <div class="sidebar-logo-circle"><i class="fas fa-building"></i></div>
            DTUSCMS
        </a>

        <div style="text-align: center; padding: 25px 0; background: #16202c; border-bottom: 1px solid #2a3b52;">
            <img src="../uploads/<?= htmlspecialchars($profile_photo) ?>" style="width: 80px; height: 80px; border-radius: 50%; border: 3px solid #1abc9c; object-fit: cover; margin-bottom: 10px; background: white;">
            <div style="color: #1abc9c; font-weight: bold; font-size: 16px; letter-spacing: 1px;"><?= $role_display ?></div>
        </div>
        
        <?php if($_SESSION['role'] == 'admin'): ?>
            <a href="admin_dashboard.php"><i class="fas fa-chart-bar"></i> Dashboard Stats</a>
            <a href="admin_add_student.php"><i class="fas fa-user-graduate"></i> Add Student</a>
            <a href="admin_add_staff.php"><i class="fas fa-user-tie"></i> Add Staff</a>
            <a href="admin_bulk_upload.php"><i class="fas fa-file-csv"></i> Bulk Upload (CSV)</a>
            <a href="view_users.php"><i class="fas fa-users"></i> View Users</a>
            
        <?php elseif($_SESSION['role'] == 'student'): ?>
            <a href="student_dashboard.php"><i class="fas fa-paper-plane"></i> Apply Clearance</a>
            <a href="student_status.php"><i class="fas fa-tasks"></i> View Status</a>
            <a href="approver_dashboard.php"><i class="fas fa-clipboard-list"></i> Pending Requests</a>
            <a href="student_notifications.php"><i class="fas fa-bell"></i> Notifications <?= $notif_count > 0 ? "<span class='badge-notif'>$notif_count</span>" : "" ?></a>
            <a href="student_gate_pass.php" style="background:#27ae60; color:white; border-left: 5px solid #2ecc71;"><i class="fas fa-file-pdf"></i> Gate Pass (PDF)</a>

        <?php elseif($_SESSION['role'] == 'dormmate'): ?>
            <a href="student_dashboard.php"><i class="fas fa-paper-plane"></i> Apply Clearance</a>
            <a href="student_status.php"><i class="fas fa-tasks"></i> View Status</a>
            <a href="approver_dashboard.php"><i class="fas fa-clipboard-list"></i> Pending Requests</a>
            <a href="approver_view_students.php"><i class="fas fa-users"></i> View Students</a>
            <a href="approver_notifications.php"><i class="fas fa-bell"></i> Notifications <?= $notif_count > 0 ? "<span class='badge-notif'>$notif_count</span>" : "" ?></a>
            <a href="student_gate_pass.php" style="background:#27ae60; color:white; border-left: 5px solid #2ecc71;"><i class="fas fa-file-pdf"></i> Gate Pass (PDF)</a>
            
        <?php elseif($_SESSION['role'] == 'registrar'): ?>
            <a href="registrar_dashboard.php"><i class="fas fa-stamp"></i> Final Approvals</a>
            <a href="approver_view_students.php"><i class="fas fa-users"></i> View Students</a>
            <a href="approver_notifications.php"><i class="fas fa-bell"></i> Notifications <?= $notif_count > 0 ? "<span class='badge-notif'>$notif_count</span>" : "" ?></a>
            
        <?php else: ?>
            <a href="approver_dashboard.php"><i class="fas fa-clipboard-list"></i> Pending Requests</a>
            <a href="approver_view_students.php"><i class="fas fa-users"></i> View Students</a>
            <a href="approver_notifications.php"><i class="fas fa-bell"></i> Notifications <?= $notif_count > 0 ? "<span class='badge-notif'>$notif_count</span>" : "" ?></a>
        <?php endif; ?>

        <!-- ALL ROLES GET MESSAGES AND PROFILE -->
        <a href="chat.php"><i class="fas fa-envelope"></i> Messages <?= $chat_count > 0 ? "<span class='badge-notif' style='background:#3498db;'>$chat_count</span>" : "" ?></a>
        <a href="profile.php" style="border-left: 5px solid #f39c12;"><i class="fas fa-user-edit"></i> Edit Profile (View)</a>
    </div>
    
    <div class="main-content">
        <div class="top-nav" style="display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 15px 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
            <div style="display: flex; align-items: center; gap: 15px;">
                <img src="../uploads/<?= htmlspecialchars($profile_photo) ?>" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #ddd;">
                <span style="font-size: 16px; color: #2c3e50;"><b>Welcome, <?= htmlspecialchars($display_name) ?></b></span>
            </div>
            <a href="../logout.php" class="btn-logout-custom"><i class="fas fa-power-off"></i> Logout</a>
        </div>
        <div class="content-body" style="padding: 30px;">