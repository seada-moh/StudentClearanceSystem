<?php
require '../config/db.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'student' && $_SESSION['role'] != 'dormmate')) { header("Location: ../login.php"); exit(); }

$student_id = $conn->real_escape_string($_SESSION['user_id']); 
$req = $conn->query("SELECT * FROM clearance_requests WHERE student_id = '$student_id'")->fetch_assoc();

include '../includes/header.php';
?>

<h2><i class="fas fa-tasks"></i> Clearance Approval Status</h2>

<?php if (!$req): ?>
    <div class="alert error" style="background:#f8d7da; color:#721c24; padding:15px; border-radius:5px;"><i class="fas fa-exclamation-circle"></i> You have not applied for clearance yet. Please go to "Apply Clearance".</div>
<?php else: ?>
    <div class="card">
        
        <?php if ($req['reason'] == 'Ceremonies / Short Leave' || $req['reason'] == 'Year Break'): ?>
            <div style="background:#e8f4f8; color:#2c3e50; padding:15px; border-left:5px solid #3498db; margin-bottom: 20px; font-weight: bold;">
                <i class="fas fa-info-circle"></i> Note: You applied for "<?= htmlspecialchars($req['reason']) ?>". Your request is on the Short Route.
            </div>
        <?php endif; ?>

        <table class="table">
            <tr><th>Department / Office</th><th>Status</th></tr>
            <?php
            $offices =['dormmate', 'proctor', 'librarian', 'cafeteria', 'student_service', 'dept_head'];
            
            // VERIFICATION: Check if EVERYTHING is approved
            $is_fully_approved = true;
            
            foreach($offices as $office) {
                $status = $req[$office.'_status'];
                if($status !== 'approved') $is_fully_approved = false;
                
                $bg_color = ($status == 'approved') ? '#27ae60' : (($status == 'rejected') ? '#c0392b' : '#f39c12');
                echo "<tr>
                        <td>".ucwords(str_replace('_', ' ', $office))."</td>
                        <td><span style='background:$bg_color; color:white; padding:5px 12px; border-radius:15px; font-size:12px; font-weight:bold; text-transform:uppercase;'>".ucfirst($status)."</span></td>
                      </tr>";
            }
            
            // Check Registrar
            if ($req['registrar_status'] !== 'approved') $is_fully_approved = false;

            $reg_bg = ($req['registrar_status'] == 'approved') ? '#27ae60' : (($req['registrar_status'] == 'rejected') ? '#c0392b' : '#f39c12');
            ?>
            <tr style="background:#fdfdfd; border-top: 3px solid #ecf0f1;">
                <td style="font-size: 16px;"><b>Registrar (Final Approval)</b></td>
                <td><span style="background:<?= $reg_bg ?>; color:white; padding:6px 15px; border-radius:15px; font-size:13px; font-weight:bold; text-transform:uppercase; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"><b><?= ucfirst($req['registrar_status']) ?></b></span></td>
            </tr>
        </table><br>
        
        <?php if ($is_fully_approved): ?>
            <!-- BUTTON ONLY SHOWS IF EVERY SINGLE DEPARTMENT IS 'APPROVED' -->
            <a href="generate_pdf.php?student_id=<?= urlencode($student_id) ?>" target="_blank" style="text-decoration:none;">
                <button class="btn-primary" style="background:#27ae60; padding:15px 25px; font-size:16px; width:100%; box-shadow: 0 4px 10px rgba(39, 174, 96, 0.4); border-radius: 8px;">
                    <i class="fas fa-download"></i> Download Official Gate Pass (PDF)
                </button>
            </a>
        <?php elseif(in_array('rejected', $req)): ?>
            <div style="background:#f8d7da; color:#721c24; padding:15px; border-left:5px solid #f5c6cb; border-radius:4px;">
                <i class="fas fa-times-circle"></i> Your clearance was rejected. Please check the <b>Notifications</b> tab.
            </div>
        <?php else: ?>
            <div style="background:#fff3cd; color:#856404; padding:15px; border-left:5px solid #ffc107; border-radius:4px;">
                <i class="fas fa-lock"></i> Gate Pass Locked. You must wait for all departments (including Dormmate and Proctor) to approve before downloading your final clearance.
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>