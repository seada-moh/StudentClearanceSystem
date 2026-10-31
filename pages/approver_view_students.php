<?php
require '../config/db.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

$valid_approvers =['dormmate', 'proctor', 'librarian', 'cafeteria', 'student_service', 'dept_head', 'registrar'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $valid_approvers)) { header("Location: ../index.php"); exit(); }

$role = $_SESSION['role'];
$status_col = $role . "_status";
$date_col = $role . "_date";

// STRICT LOCATION FILTERING FOR MATH & LIST
$base_where = "1=1";

if ($role == 'proctor' && isset($_SESSION['block_no'])) {
    $block = (int)$_SESSION['block_no'];
    $base_where = "s.block_no = $block";
} elseif ($role == 'dormmate' && isset($_SESSION['dorm_no'])) {
    $dorm = (int)$_SESSION['dorm_no'];
    $block = (int)$_SESSION['block_no'];
    $base_where = "s.block_no = $block AND s.dorm_no = $dorm";
} elseif ($role == 'dept_head' && isset($_SESSION['department'])) {
    $dept = $conn->real_escape_string($_SESSION['department']);
    $base_where = "s.department = '$dept'";
}

// FIXED MATH LOGIC
$total_req = $conn->query("SELECT COUNT(*) as c FROM clearance_requests r JOIN students s ON r.student_id = s.student_id WHERE $base_where")->fetch_assoc()['c'];
$total_app = $conn->query("SELECT COUNT(*) as c FROM clearance_requests r JOIN students s ON r.student_id = s.student_id WHERE $base_where AND r.$status_col = 'approved'")->fetch_assoc()['c'];
$total_rej = $conn->query("SELECT COUNT(*) as c FROM clearance_requests r JOIN students s ON r.student_id = s.student_id WHERE $base_where AND r.$status_col = 'rejected'")->fetch_assoc()['c'];

$students = $conn->query("SELECT r.*, s.sname, s.fathername, s.department, s.block_no, s.dorm_no FROM clearance_requests r JOIN students s ON r.student_id = s.student_id WHERE $base_where ORDER BY r.request_date DESC");

include '../includes/header.php';
?>
<h2><i class="fas fa-chart-pie"></i> View Students & Statistics</h2>
<p style="margin-bottom: 20px; color: #7f8c8d;">
    <?php 
        if($role == 'proctor') echo "<b>Strict View:</b> Showing students ONLY in your assigned Block ({$_SESSION['block_no']}).";
        elseif($role == 'dormmate') echo "<b>Strict View:</b> Showing students ONLY in your Room (Block {$_SESSION['block_no']} - Dorm {$_SESSION['dorm_no']}).";
        else echo "Showing all clearance requests university-wide.";
    ?>
</p>

<!-- ACCURATE STATS CARDS -->
<div style="display: flex; gap: 20px; margin-bottom: 30px;">
    <div style="flex:1; background:#fff; padding:20px; border-radius:8px; border-left:5px solid #3498db; box-shadow:0 4px 6px rgba(0,0,0,0.1);"><h3 style="margin:0; color:#7f8c8d; font-size:14px; text-transform:uppercase;">Total Requests</h3><p style="margin:5px 0 0 0; font-size:28px; font-weight:bold; color:#2c3e50;"><?= $total_req ?></p></div>
    <div style="flex:1; background:#fff; padding:20px; border-radius:8px; border-left:5px solid #27ae60; box-shadow:0 4px 6px rgba(0,0,0,0.1);"><h3 style="margin:0; color:#7f8c8d; font-size:14px; text-transform:uppercase;">Approved by You</h3><p style="margin:5px 0 0 0; font-size:28px; font-weight:bold; color:#27ae60;"><?= $total_app ?></p></div>
    <div style="flex:1; background:#fff; padding:20px; border-radius:8px; border-left:5px solid #c0392b; box-shadow:0 4px 6px rgba(0,0,0,0.1);"><h3 style="margin:0; color:#7f8c8d; font-size:14px; text-transform:uppercase;">Rejected by You</h3><p style="margin:5px 0 0 0; font-size:28px; font-weight:bold; color:#c0392b;"><?= $total_rej ?></p></div>
</div>

<div class="card table-responsive">
    <table class="table">
        <tr><th>Student Name</th><th>Location</th><th>Your Status</th><th>Action Date</th><th>Rejection Reason</th></tr>
        <?php if($students && $students->num_rows > 0): while($s = $students->fetch_assoc()): 
            $status = $s[$status_col];
            $bg = ($status == 'approved') ? '#27ae60' : (($status == 'rejected') ? '#c0392b' : '#f39c12');
        ?>
        <tr>
            <td><b><?= htmlspecialchars($s['sname'] . " " . $s['fathername']) ?></b><br><small><?= htmlspecialchars($s['student_id']) ?></small></td>
            <td>Block: <?= htmlspecialchars($s['block_no']) ?> | Dorm: <?= htmlspecialchars($s['dorm_no']) ?></td>
            <td><span style="background:<?= $bg ?>; color:white; padding:5px 10px; border-radius:15px; font-size:12px; font-weight:bold; text-transform:uppercase;"><?= ucfirst($status) ?></span></td>
            <td><small><?= $s[$date_col] ? date('M j, Y g:i A', strtotime($s[$date_col])) : 'Not acted yet' ?></small></td>
            <td><?= ($status == 'rejected') ? "<small style='color:#c0392b; font-weight:bold;'>".htmlspecialchars($s['rejection_reason'])."</small>" : "-" ?></td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="5" style="text-align:center; padding:20px; color:#7f8c8d;">No students found in your jurisdiction.</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php include '../includes/footer.php'; ?>