<?php
require '../config/db.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'registrar') { header("Location: ../login.php"); exit(); }

$my_name = $conn->real_escape_string($_SESSION['name']);

// FINAL APPROVAL ACTION & AUTO-DELETE FOR PERMANENT DEPARTURES
if (isset($_GET['action']) && $_GET['action'] == 'approve' && isset($_GET['student_id'])) {
    $s_id = $conn->real_escape_string($_GET['student_id']);
    $now = date('Y-m-d H:i:s'); 
    
    $reason_check = $conn->query("SELECT reason FROM clearance_requests WHERE student_id = '$s_id'")->fetch_assoc();
    $reason = $reason_check['reason'];

    // 1. Grant Final Approval
    $conn->query("UPDATE clearance_requests SET registrar_status = 'approved', registrar_date = '$now' WHERE student_id = '$s_id'");
    
    // 2. Notify Student
    $msg_to_student = "<b>CONGRATULATIONS!</b> Your clearance request for <i>$reason</i> was fully approved by the Office of the Registrar. <br><br><b>Your Official Gate Pass is now unlocked and ready to download.</b> Please print it immediately.";
    $safe_msg = $conn->real_escape_string($msg_to_student);
    $conn->query("INSERT INTO notifications (recipient_id, sender_info, message, type) VALUES ('$s_id', 'Office of the Registrar', '$safe_msg', 'success')");
    
    // 3. AUTO-DELETE FOR GRADUATIONS / TRANSFERS
    if ($reason == 'Graduation' || $reason == 'Transfer to Other University') {
        $conn->query("DELETE FROM students WHERE student_id = '$s_id'");
        $conn->query("INSERT INTO activity_logs (action, role) VALUES ('deleted', 'student')");
    }

    header("Location: registrar_dashboard.php"); 
    exit();
}

// =========================================================================
// STRICT QUERY FIX: The Registrar ONLY sees the student if ALL 6 previous 
// departments (Dormmate, Proctor, Librarian, Cafeteria, Student Service, Dept Head) 
// are marked as 'approved'.
// =========================================================================
$query = "SELECT r.*, s.sname, s.fathername, s.student_id, s.department, s.photo 
          FROM clearance_requests r 
          JOIN students s ON r.student_id = s.student_id 
          WHERE r.dormmate_status = 'approved' 
          AND r.proctor_status = 'approved' 
          AND r.librarian_status = 'approved' 
          AND r.cafeteria_status = 'approved' 
          AND r.student_service_status = 'approved' 
          AND r.dept_head_status = 'approved' 
          AND r.registrar_status = 'pending'";

$requests = $conn->query($query);

include '../includes/header.php';
?>

<h2><i class="fas fa-stamp"></i> Registrar Office - Final Clearance</h2>
<p style="margin-bottom: 20px; color: #34495e; font-weight: bold;">
    <i class="fas fa-info-circle" style="color: #3498db;"></i> Students listed below have successfully cleared all 6 prerequisite departments (Including Dormmate and Proctor).
</p>

<table class="table card" style="border-collapse: collapse; width: 100%; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden;">
    <tr style="background: #2c3e50; color: white;">
        <th style="padding: 15px; text-align: left;">Photo</th>
        <th style="padding: 15px; text-align: left;">Student Name</th>
        <th style="padding: 15px; text-align: left;">ID Number</th>
        <th style="padding: 15px; text-align: left;">Department</th>
        <th style="padding: 15px; text-align: left;">Reason</th>
        <th style="padding: 15px; text-align: center;">Action</th>
    </tr>
    
    <?php if($requests && $requests->num_rows > 0): while($r = $requests->fetch_assoc()): ?>
    <tr style="border-bottom: 1px solid #ecf0f1; transition: 0.3s;" onmouseover="this.style.background='#f9f9f9'" onmouseout="this.style.background='white'">
        <td style="padding: 15px;"><img src="../uploads/<?= htmlspecialchars($r['photo'] ?? 'default.png') ?>" style="width: 55px; height: 55px; border-radius: 50%; object-fit: cover; border: 2px solid #ddd;"></td>
        <td style="padding: 15px; font-weight: bold; color: #2c3e50;"><?= htmlspecialchars($r['sname'] . " " . $r['fathername']) ?></td>
        <td style="padding: 15px; font-family: monospace; font-size: 15px;"><?= htmlspecialchars($r['student_id']) ?></td>
        <td style="padding: 15px;"><?= htmlspecialchars($r['department']) ?></td>
        
        <td style="padding: 15px; font-weight: bold; color: <?= ($r['reason'] == 'Graduation' || $r['reason'] == 'Transfer to Other University') ? '#e67e22' : '#7f8c8d' ?>;">
            <?= htmlspecialchars($r['reason']) ?>
            <?php if($r['reason'] == 'Graduation' || $r['reason'] == 'Transfer to Other University'): ?>
                <br><small style="color:#c0392b;">* Auto-Delete on Approval</small>
            <?php endif; ?>
        </td>
        
        <td style="padding: 15px; text-align: center; white-space: nowrap;">
            <a href="?action=approve&student_id=<?= urlencode($r['student_id']) ?>" style="text-decoration: none;">
                <button style="background: #27ae60; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 14px; box-shadow: 0 2px 4px rgba(39, 174, 96, 0.4); transition: 0.3s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <i class="fas fa-check-double"></i> Grant Final Clearance
                </button>
            </a>
        </td>
    </tr>
    <?php endwhile; else: ?>
    <tr>
        <td colspan="6" style="text-align: center; padding: 40px; color: #7f8c8d; font-size: 16px;">
            <i class="fas fa-box-open" style="font-size: 40px; margin-bottom: 15px; display: block; color: #bdc3c7;"></i>
            No students are currently ready for final clearance.
        </td>
    </tr>
    <?php endif; ?>
</table>

<?php include '../includes/footer.php'; ?>