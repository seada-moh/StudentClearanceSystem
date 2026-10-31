<?php
require '../config/db.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'student' && $_SESSION['role'] != 'dormmate')) { header("Location: ../login.php"); exit(); }

$applicant_id = $conn->real_escape_string($_SESSION['user_id']); 
$applicant_name = $conn->real_escape_string($_SESSION['name']);
$block = (int)$_SESSION['block_no'];
$dorm = (int)$_SESSION['dorm_no'];

$req = $conn->query("SELECT * FROM clearance_requests WHERE student_id = '$applicant_id'")->fetch_assoc();
$form_error = "";

// 1. HANDLE EDIT REQUEST ACTION
if (isset($_GET['action']) && $_GET['action'] == 'edit_application') {
    // Completely delete the old request so they can start fresh
    $conn->query("DELETE FROM clearance_requests WHERE student_id = '$applicant_id'");
    header("Location: student_dashboard.php");
    exit();
}

// 2. HANDLE SUBMIT REQUEST ACTION
if (isset($_POST['request'])) {
    $date = date('Y-m-d H:i:s');
    $reason = $_POST['reason'];
    $items = $_POST['items'];
    $can_proceed = true;
    
    // Check if they already have an active request
    if ($req && !in_array('rejected', $req)) {
        $can_proceed = false; 
        $form_error = "You already have an active request processing.";
    } elseif ($req && $reason == 'End of Academic Year' && $req['reason'] == 'End of Academic Year' && !in_array('rejected', $req)) {
        if (date('Y', strtotime($req['request_date'])) == date('Y')) {
            $can_proceed = false; 
            $form_error = "You have already completed End of Year clearance for this academic year.";
        }
    }
    
    if ($can_proceed) {
        if (!$req || (isset($_POST['new_request']) && $_POST['new_request'] == 'yes')) {
            // NEW APPLICATION
            $dorm_s = 'pending'; $proc_s = 'pending'; 
            
            // For short leaves, Library to Dept Head are auto-approved. 
            // Registrar stays pending until Proctor approves!
            $lib_s = ($reason == 'Ceremonies / Short Leave' || $reason == 'Year Break') ? 'approved' : 'pending';
            $caf_s = $lib_s; $ss_s = $lib_s; $dh_s = $lib_s; 
            $reg_s = 'pending'; // MUST ALWAYS BE PENDING INITIALLY
            
            $stmt = $conn->prepare("INSERT INTO clearance_requests (student_id, reason, personal_items, dormmate_status, proctor_status, librarian_status, cafeteria_status, student_service_status, dept_head_status, registrar_status, request_date, rejection_reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '') ON DUPLICATE KEY UPDATE reason=VALUES(reason), personal_items=VALUES(personal_items), dormmate_status=VALUES(dormmate_status), proctor_status=VALUES(proctor_status), librarian_status=VALUES(librarian_status), cafeteria_status=VALUES(cafeteria_status), student_service_status=VALUES(student_service_status), dept_head_status=VALUES(dept_head_status), registrar_status=VALUES(registrar_status), request_date=VALUES(request_date), rejection_reason=''");
            $stmt->bind_param("sssssssssss", $applicant_id, $reason, $items, $dorm_s, $proc_s, $lib_s, $caf_s, $ss_s, $dh_s, $reg_s, $date);
            $stmt->execute();
            
            $msg = "Your roommate <b>$applicant_name</b> (ID: $applicant_id) has requested clearance for <b>$reason</b>. Please review it in your Pending Requests tab.";
            $safe_msg = $conn->real_escape_string($msg);
            
            $s_q = $conn->query("SELECT student_id FROM students WHERE block_no=$block AND dorm_no=$dorm AND student_id != '$applicant_id' AND is_deleted=0");
            while($p = $s_q->fetch_assoc()) $conn->query("INSERT INTO notifications (recipient_id, sender_info, message, type) VALUES ('".$p['student_id']."', 'Roommate Request', '$safe_msg', 'info')");
            
        } else {
            // RE-APPLICATION AFTER REJECTION
            $offices = ['dormmate', 'proctor', 'librarian', 'cafeteria', 'student_service', 'dept_head', 'registrar'];
            $rejected_by = '';
            foreach($offices as $office) { if ($req[$office.'_status'] == 'rejected') { $rejected_by = $office; break; } }

            $status_col = $rejected_by . "_status";
            $date_col = $rejected_by . "_date";
            $conn->query("UPDATE clearance_requests SET $status_col = 'pending', $date_col = NULL, personal_items = '" . $conn->real_escape_string($items) . "', request_date = '$date', rejection_reason = '' WHERE student_id = '$applicant_id'");
            
            $msg = "<b>$applicant_name</b> (ID: $applicant_id) has resolved their issue and re-applied. Please review their status.";
            $safe_msg = $conn->real_escape_string($msg);
            
            if($rejected_by == 'dormmate') {
                $s_q = $conn->query("SELECT student_id FROM students WHERE block_no=$block AND dorm_no=$dorm AND student_id != '$applicant_id' AND is_deleted=0");
                while($p = $s_q->fetch_assoc()) $conn->query("INSERT INTO notifications (recipient_id, sender_info, message, type) VALUES ('".$p['student_id']."', 'Re-Application', '$safe_msg', 'info')");
            }
        }
        header("Location: student_status.php"); exit();
    }
}
include '../includes/header.php';
?>
<div class="card" style="max-width: 700px; position: relative;">
    
    <!-- EDIT BUTTON: Appears if they have an active request -->
    <?php if ($req && !in_array('rejected', $req) && $req['registrar_status'] != 'approved'): ?>
        <a href="?action=edit_application" onclick="return confirm('Are you sure you want to edit your application? This will reset all your current approvals back to pending, and you will have to start over from the Dormmate step.');" style="position: absolute; top: 30px; right: 30px; background: #e74c3c; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 13px; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
            <i class="fas fa-edit"></i> Edit My Application
        </a>
    <?php endif; ?>

    <h2><i class="fas fa-paper-plane"></i> Apply for Clearance</h2>
    
    <?php if ($form_error != ""): ?><div style="background:#f8d7da; color:#721c24; padding:15px; border-radius:5px; margin-bottom:20px; border:1px solid #f5c6cb;"><i class="fas fa-ban"></i> <b>Action Denied:</b> <?= $form_error ?></div>
    
    <?php elseif ($req && !in_array('rejected', $req)): ?>
        <div style="background:#d4edda; color:#155724; padding:15px; border-radius:5px; margin-bottom:20px; border:1px solid #c3e6cb;">
            <i class="fas fa-info-circle"></i> You already have an active clearance request processing. Check the <b>View Status</b> tab. If you made a mistake, click the red <b>Edit My Application</b> button above to start over.
        </div>
        
    <?php elseif ($req && in_array('rejected', $req)): ?>
        <div style="background:#fff3cd; color:#856404; padding:15px; border-radius:5px; margin-bottom:20px; border:1px solid #ffeeba;">
            <i class="fas fa-exclamation-triangle"></i> <b>Smart Re-Application:</b> Your request was previously rejected. Updating your items and clicking submit below will send your request <b>back to the specific department that rejected you</b>.
        </div>
    <?php endif; ?>

    <form method="POST" style="margin-top:20px;">
        <?php if($req && in_array('rejected', $req)): ?>
            <input type="hidden" name="reason" value="<?= htmlspecialchars($req['reason']) ?>">
            <div style="margin-bottom: 20px; background:#f4f7f6; padding:15px; border-radius:5px; border:1px solid #ddd;">
                <label style="font-weight:bold; color:#2c3e50;">Reason for leaving:</label><br><span style="font-size:16px; color:#7f8c8d;"><?= htmlspecialchars($req['reason']) ?> (Locked during Re-Application)</span>
            </div>
        <?php else: ?>
            <div style="margin-bottom: 20px;">
                <label style="font-weight:bold; color:#2c3e50; display:block; margin-bottom:8px;">Reason for leaving:</label>
                <!-- Disabled if they already have an active request so they can't change it without hitting Edit -->
                <select name="reason" required <?= ($req && !in_array('rejected', $req)) ? 'disabled' : '' ?> style="padding:12px; width:100%; border-radius:5px; border:1px solid #bdc3c7; font-size:15px;">
                    <option value="End of Academic Year" <?= ($req['reason']??'')=='End of Academic Year'?'selected':'' ?>>End of Academic Year</option>
                    <option value="Graduation" <?= ($req['reason']??'')=='Graduation'?'selected':'' ?> style="font-weight:bold; color:#27ae60;">Graduation (Permanent)</option>
                    <option value="Transfer to Other University" <?= ($req['reason']??'')=='Transfer to Other University'?'selected':'' ?> style="font-weight:bold; color:#e67e22;">Transfer (Permanent)</option>
                    <option value="Withdrawal" <?= ($req['reason']??'')=='Withdrawal'?'selected':'' ?>>Withdrawal</option>
                    <option value="Academic Dismissal" <?= ($req['reason']??'')=='Academic Dismissal'?'selected':'' ?>>Academic Dismissal</option>
                    <option value="Disciplinary Case" <?= ($req['reason']??'')=='Disciplinary Case'?'selected':'' ?>>Disciplinary Case</option>
                    <option value="Year Break" <?= ($req['reason']??'')=='Year Break'?'selected':'' ?> style="color:#2980b9; font-weight:bold;">Year Break (Short Route)</option>
                    <option value="Ceremonies / Short Leave" <?= ($req['reason']??'')=='Ceremonies / Short Leave'?'selected':'' ?> style="color:#2980b9; font-weight:bold;">Ceremonies / Short Leave (Short Route)</option>
                </select>
            </div>
        <?php endif; ?>
        <div style="margin-bottom: 25px;">
            <label style="font-weight:bold; color:#2c3e50; display:block; margin-bottom:8px;">Register Personal Items to take out:</label>
            <textarea name="items" rows="5" required <?= ($req && !in_array('rejected', $req)) ? 'readonly' : '' ?> style="padding:12px; width:100%; border-radius:5px; border:1px solid #bdc3c7; font-family:sans-serif;"><?= isset($req['personal_items']) ? htmlspecialchars($req['personal_items']) : '' ?></textarea>
        </div>
        
        <?php if (!$req || in_array('rejected', $req)): ?>
            <button type="submit" name="request" class="btn-primary" style="padding:15px; font-size:16px; width:100%;">
                <i class="fas <?= ($req && in_array('rejected', $req)) ? 'fa-sync-alt' : 'fa-check-circle' ?>"></i> <?= ($req && in_array('rejected', $req)) ? 'Resubmit Fixed Application' : 'Submit Application' ?>
            </button>
        <?php endif; ?>
    </form>
</div>
<?php include '../includes/footer.php'; ?>