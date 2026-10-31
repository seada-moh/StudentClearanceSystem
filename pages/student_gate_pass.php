<?php
require '../config/db.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') { header("Location: ../login.php"); exit(); }

$student_id = $conn->real_escape_string($_SESSION['user_id']); 
$req = $conn->query("SELECT * FROM clearance_requests WHERE student_id = '$student_id'")->fetch_assoc();

include '../includes/header.php';
?>

<h2><i class="fas fa-file-pdf"></i> Official Exit Gate Pass</h2>

<div class="card" style="text-align: center; max-width: 600px; margin: 0 auto; padding: 40px;">
    <?php if (!$req): ?>
        <i class="fas fa-exclamation-circle" style="font-size: 50px; color: #f39c12; margin-bottom: 20px;"></i>
        <h3 style="color: #c0392b;">No Clearance Record Found</h3>
        <p>You have not applied for clearance yet.</p>
        
    <?php elseif ($req['registrar_status'] == 'approved'): ?>
        <!-- ONLY UNLOCKS IF REGISTRAR IS 100% APPROVED -->
        <i class="fas fa-check-circle" style="font-size: 60px; color: #27ae60; margin-bottom: 20px;"></i>
        <h3 style="color: #27ae60;">Clearance Fully Approved!</h3>
        <p style="font-size: 16px; margin-bottom: 30px;">Your clearance process is complete. Click the button below to generate your official PDF Gate Pass. <b>Please show this document to the University Security Guard at the gate to exit the campus.</b></p>
        
        <a href="generate_pdf.php?student_id=<?= urlencode($student_id) ?>" target="_blank" style="text-decoration:none;">
            <button class="btn-primary" style="background:#27ae60; padding:15px 30px; font-size:18px; width: 100%; border-radius: 8px; cursor: pointer; box-shadow: 0 5px 15px rgba(39, 174, 96, 0.4);">
                <i class="fas fa-print"></i> View / Print Gate Pass (PDF)
            </button>
        </a>
        
    <?php else: ?>
        <!-- LOCKED STATE FOR PENDING / REJECTED STUDENTS -->
        <i class="fas fa-lock" style="font-size: 50px; color: #e74c3c; margin-bottom: 20px;"></i>
        <h3 style="color: #c0392b;">Gate Pass Locked</h3>
        <p style="font-size: 16px; background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24; border: 1px solid #f5c6cb;">
            Your Gate Pass is locked because your clearance is not fully approved by the Registrar yet. <br><br>
            <i>Even if some departments have approved you, you must wait for all required steps to finish. Please check your <b>View Status</b> tab.</i>
        </p>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>