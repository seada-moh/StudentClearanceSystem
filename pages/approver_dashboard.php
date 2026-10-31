<?php
require '../config/db.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

$valid_approvers =['student', 'dormmate', 'proctor', 'librarian', 'cafeteria', 'student_service', 'dept_head'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $valid_approvers)) { header("Location: ../login.php"); exit(); }

$role = $_SESSION['role'];
$logic_role = ($role == 'student') ? 'dormmate' : $role; 
$status_col = $logic_role . "_status";
$date_col = $logic_role . "_date";

$my_id = $conn->real_escape_string($_SESSION['user_id']);
$my_name = $conn->real_escape_string($_SESSION['name']);
$sender_role_display = ($role == 'student') ? "Roommate" : ucwords(str_replace('_', ' ', $role));

$sender_loc = "";
if($logic_role == 'dormmate') $sender_loc = "Block " . $_SESSION['block_no'] . ", Dorm " . $_SESSION['dorm_no'];
if($logic_role == 'proctor') $sender_loc = "Block " . $_SESSION['block_no'];

$sender_signature = "<b>$sender_role_display: $my_name</b> (ID: $my_id" . ($sender_loc ? ", $sender_loc" : "") . ")";
$sender_info_short = "$sender_role_display ($my_name)";

if (isset($_GET['action']) && isset($_GET['student_id'])) {
    $s_id = $conn->real_escape_string($_GET['student_id']);
    $now = date('Y-m-d H:i:s');
    
    $st_data = $conn->query("SELECT sname, fathername, department, block_no, dorm_no FROM students WHERE student_id='$s_id'")->fetch_assoc();
    $s_fullname = $st_data['sname'] . " " . $st_data['fathername'];
    $s_block = $st_data['block_no'];
    $s_dorm = $st_data['dorm_no'];
    $s_dept = $conn->real_escape_string($st_data['department']);
    $student_info_string = "<b>$s_fullname</b> (ID: $s_id, Block $s_block, Dorm $s_dorm)";
    
    // Check if this is a "Short Route" request
    $reason_q = $conn->query("SELECT reason FROM clearance_requests WHERE student_id='$s_id'")->fetch_assoc();
    $is_short_route = ($reason_q['reason'] == 'Ceremonies / Short Leave' || $reason_q['reason'] == 'Year Break');

    if ($_GET['action'] == 'approve') {
        $conn->query("UPDATE clearance_requests SET $status_col = 'approved', $date_col = '$now' WHERE student_id = '$s_id'");
        
        $msg_to_student = "Your clearance was <b>APPROVED</b> by $sender_signature. Your request is moving to the next step.";
        $safe_msg_to_student = $conn->real_escape_string($msg_to_student); 
        $conn->query("INSERT INTO notifications (recipient_id, sender_info, message, type) VALUES ('$s_id', '$sender_info_short', '$safe_msg_to_student', 'success')");

        $next_step_query = "";
        
        // --- DYNAMIC ROUTING & SHORT ROUTE REGISTRAR TRIGGER ---
        if($logic_role == 'dormmate') { 
            $next_step_query = "SELECT proctor_id AS target_id FROM proctors WHERE block_no = $s_block AND is_deleted=0"; 
        } 
        elseif($logic_role == 'proctor') { 
            if($is_short_route) {
                // If Proctor approves a Short Route, AUTO-APPROVE THE REGISTRAR instantly!
                $conn->query("UPDATE clearance_requests SET registrar_status = 'approved', registrar_date = '$now' WHERE student_id = '$s_id'");
                
                // Send the Final Gate Pass Notification to the Student
                $final_msg = "<b>CONGRATULATIONS!</b> Your short-leave clearance request was fully approved by the Proctor. Your Official Gate Pass is now unlocked and ready to download.";
                $safe_final_msg = $conn->real_escape_string($final_msg);
                $conn->query("INSERT INTO notifications (recipient_id, sender_info, message, type) VALUES ('$s_id', 'System Auto-Approval', '$safe_final_msg', 'success')");
            } else {
                // Standard Route goes to Librarian
                $next_step_query = "SELECT librarian_id AS target_id FROM librarians WHERE is_deleted=0"; 
            }
        } 
        elseif($logic_role == 'librarian') { $next_step_query = "SELECT cafeteria_id AS target_id FROM cafeterias WHERE is_deleted=0"; } 
        elseif($logic_role == 'cafeteria') { $next_step_query = "SELECT student_service_id AS target_id FROM student_services WHERE is_deleted=0"; } 
        elseif($logic_role == 'student_service') { $next_step_query = "SELECT dept_head_id AS target_id FROM dept_heads WHERE department = '$s_dept' AND is_deleted=0"; } 
        elseif($logic_role == 'dept_head') { $next_step_query = "SELECT registrar_id AS target_id FROM registrars WHERE is_deleted=0"; }

        // Send Notification to Next Staff (If applicable)
        if($next_step_query != "") {
            $msg_to_staff = "Student $student_info_string has been <b>APPROVED</b> by $sender_signature. Please review it now in your Pending Requests queue.";
            $safe_msg_to_staff = $conn->real_escape_string($msg_to_staff); 
            
            $staff_targets = $conn->query($next_step_query);
            if($staff_targets) {
                while($st = $staff_targets->fetch_assoc()) {
                    $t_id = $st['target_id'];
                    $conn->query("INSERT INTO notifications (recipient_id, sender_info, message, type) VALUES ('$t_id', '$sender_info_short', '$safe_msg_to_staff', 'info')");
                }
            }
        }

    } elseif ($_GET['action'] == 'reject') {
        $reason = $conn->real_escape_string($_GET['reason']);
        $conn->query("UPDATE clearance_requests SET $status_col = 'rejected', $date_col = '$now', rejection_reason = '$reason' WHERE student_id = '$s_id'");
        
        $msg_to_student = "Your clearance was <b>REJECTED</b> by $sender_signature. <br><br><b>Reason provided:</b> <i>$reason</i><br><br>You must fix this issue and click `Resubmit Application`.";
        $safe_msg_to_student = $conn->real_escape_string($msg_to_student); 
        $conn->query("INSERT INTO notifications (recipient_id, sender_info, message, type) VALUES ('$s_id', '$sender_info_short', '$safe_msg_to_student', 'danger')");
    }
    header("Location: approver_dashboard.php"); exit();
}

$prev_step_map = ['dormmate'=>null, 'proctor'=>'dormmate_status', 'librarian'=>'proctor_status', 'cafeteria'=>'librarian_status', 'student_service'=>'cafeteria_status', 'dept_head'=>'student_service_status'];
$prev_col = $prev_step_map[$logic_role];
$where_clause = "r.$status_col = 'pending'";

if ($logic_role == 'proctor') { $where_clause .= " AND u.block_no = " . (int)$_SESSION['block_no']; } 
elseif ($logic_role == 'dormmate') { $where_clause .= " AND u.dorm_no = " . (int)$_SESSION['dorm_no'] . " AND u.block_no = " . (int)$_SESSION['block_no']; } 
elseif ($logic_role == 'dept_head') { $where_clause .= " AND u.department = '" . $conn->real_escape_string($_SESSION['department']) . "'"; }

$query = "SELECT r.*, u.sname, u.fathername, u.department, u.block_no, u.dorm_no, u.photo FROM clearance_requests r JOIN students u ON r.student_id = u.student_id WHERE $where_clause AND r.student_id != '$my_id'";
$requests = $conn->query($query);

include '../includes/header.php';
?>
<h2><i class="fas fa-clipboard-list"></i> Pending Clearances</h2>
<table class="table card">
    <tr><th>Photo</th><th>Details</th><th>Reason</th><th>Action</th></tr>
    <?php if($requests && $requests->num_rows > 0): while($r = $requests->fetch_assoc()): 
        $is_ready = true; if ($prev_col !== null && $r[$prev_col] !== 'approved') { $is_ready = false; $waiting_for = ucwords(str_replace('_status', '', $prev_col)); }
    ?>
    <tr>
        <td><img src="../uploads/<?= htmlspecialchars($r['photo']) ?>" style="width:50px;height:50px;border-radius:50%;object-fit:cover;"></td>
        <td><b><?= htmlspecialchars($r['sname'] . " " . $r['fathername']) ?></b><br><small><?= htmlspecialchars($r['student_id']) ?></small><br><small>Block: <?= htmlspecialchars($r['block_no']) ?> | Dorm: <?= htmlspecialchars($r['dorm_no']) ?></small></td>
        <td><?= htmlspecialchars($r['reason']) ?></td>
        <td style="white-space:nowrap;">
            <?php if ($is_ready): ?>
                <a href="?action=approve&student_id=<?= urlencode($r['student_id']) ?>" class="btn-primary" style="background:#27ae60;text-decoration:none;"><i class="fas fa-check"></i> Approve</a>
                <button onclick="rej('<?= addslashes($r['student_id']) ?>')" class="btn-primary" style="background:#c0392b;border:none;cursor:pointer;"><i class="fas fa-times"></i> Reject</button>
            <?php else: ?><span style="background:#f39c12;color:white;padding:8px;border-radius:4px;font-size:12px;"><i class="fas fa-hourglass-half"></i> Waiting for <?= $waiting_for ?></span><?php endif; ?>
        </td>
    </tr>
    <?php endwhile; else: ?>
    <tr><td colspan="4" style="text-align:center;padding:30px;">No pending requests waiting for your approval.</td></tr>
    <?php endif; ?>
</table>
<script>function rej(id) { let r = prompt("Reason for rejection:"); if (r != null && r.trim() !== "") { window.location.href="?action=reject&student_id="+encodeURIComponent(id)+"&reason="+encodeURIComponent(r); } }</script>
<?php include '../includes/footer.php'; ?>