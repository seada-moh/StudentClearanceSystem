<?php
require 'config/db.php';

if (!isset($_GET['id'])) {
    die("<h2 style='text-align:center; color:red; margin-top:50px;'>Invalid Security Token.</h2>");
}

$student_id = $conn->real_escape_string($_GET['id']);

$query = "SELECT r.*, s.sname, s.fathername, s.gfathername, s.department, s.block_no, s.dorm_no, s.photo, s.sex 
          FROM clearance_requests r 
          JOIN students s ON r.student_id = s.student_id 
          WHERE r.student_id = '$student_id'";
          
$res = $conn->query($query);

if($res->num_rows == 0) {
    die("<div style='text-align:center; padding: 50px; font-family:sans-serif;'>
            <h1 style='color:#e74c3c; font-size:50px;'>❌ EXIT DENIED</h1>
            <h3>Error: This QR Code is FAKE or Invalid. No clearance record found in the university database!</h3>
         </div>");
}

$data = $res->fetch_assoc();
$fullname = trim($data['sname'] . ' ' . $data['fathername'] . ' ' . $data['gfathername']);
$is_approved = ($data['registrar_status'] == 'approved');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Verification - DTUSCMS</title>
    <link rel="stylesheet" href="assets/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #ecf0f1; margin: 0; padding: 20px; display: flex; justify-content: center; }
        .verify-card { background: white; width: 100%; max-width: 500px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); overflow: hidden; margin-bottom: 30px; }
        
        .header { padding: 30px 20px; text-align: center; color: white; }
        .header.approved { background: linear-gradient(135deg, #27ae60, #2ecc71); }
        .header.rejected { background: linear-gradient(135deg, #c0392b, #e74c3c); }
        
        .header i { font-size: 60px; margin-bottom: 15px; text-shadow: 2px 2px 4px rgba(0,0,0,0.2); }
        .header h1 { margin: 0; font-size: 32px; letter-spacing: 1px; text-transform: uppercase; font-weight: 900;}
        .header p { margin: 5px 0 0 0; font-size: 16px; opacity: 0.9; font-weight: bold;}

        .content { padding: 30px; text-align: center; }
        .student-photo { width: 180px; height: 180px; border-radius: 50%; object-fit: cover; border: 6px solid white; box-shadow: 0 8px 20px rgba(0,0,0,0.3); margin-top: -100px; margin-bottom: 20px; background: white; }
        
        h2 { color: #2c3e50; margin: 0 0 5px 0; font-size: 26px; font-weight: 900;}
        .id-badge { display: inline-block; background: #34495e; color: white; padding: 8px 20px; border-radius: 30px; font-weight: bold; font-family: monospace; font-size: 18px; margin-bottom: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; text-align: left; background: #f8f9fa; padding: 20px; border-radius: 10px; border: 1px solid #e9ecef; margin-bottom: 20px; }
        .info-item { font-size: 14px; }
        .info-item span { display: block; color: #7f8c8d; font-weight: bold; text-transform: uppercase; font-size: 11px; margin-bottom: 2px; }
        .info-item strong { color: #2c3e50; font-size: 16px; }

        .items-box { background: #fff3cd; border-left: 5px solid #f1c40f; padding: 15px; text-align: left; border-radius: 4px; margin-bottom: 20px; }
        .items-box h4 { margin: 0 0 10px 0; color: #856404; font-size: 14px; text-transform: uppercase; }
        .items-box p { margin: 0; color: #2c3e50; font-size: 15px; line-height: 1.5; font-style: italic; font-weight: bold;}

        .legal-footer { font-size: 13px; color: #2c3e50; border-top: 2px solid #eee; padding-top: 20px; line-height: 1.6; text-align: justify;}
    </style>
</head>
<body>

    <div class="verify-card">
        <?php if($is_approved): ?>
            <div class="header approved"><i class="fas fa-check-circle"></i><h1>CLEARED TO EXIT</h1><p>Verified Database Match</p></div>
        <?php else: ?>
            <div class="header rejected"><i class="fas fa-times-circle"></i><h1>EXIT DENIED</h1><p>Clearance Process is Not Complete</p></div>
        <?php endif; ?>

        <div class="content">
            <img src="uploads/<?= htmlspecialchars($data['photo']) ?>" class="student-photo" alt="Student Photo">
            
            <h2><?= strtoupper($fullname) ?></h2>
            <div class="id-badge"><?= $student_id ?></div>

            <div class="info-grid">
                <div class="info-item"><span>Gender</span><strong><?= ($data['sex'] == 'M' ? 'Male' : 'Female') ?></strong></div>
                <div class="info-item"><span>Department</span><strong><?= $data['department'] ?></strong></div>
                <div class="info-item"><span>Location</span><strong>Block <?= $data['block_no'] ?>, Dorm <?= $data['dorm_no'] ?></strong></div>
                <div class="info-item"><span>Reason</span><strong><?= $data['reason'] ?></strong></div>
            </div>

            <?php if($is_approved): ?>
                <div class="items-box">
                    <h4><i class="fas fa-suitcase"></i> Permitted Items to Take Out:</h4>
                    <p><?= nl2br(htmlspecialchars($data['personal_items'])) ?></p>
                </div>
                
                <div class="legal-footer">
                    <i class="fas fa-balance-scale" style="color:#27ae60; font-size: 20px; margin-bottom: 10px; display:block; text-align:center;"></i> 
                    <b>OFFICIAL LEGAL NOTICE:</b> The Office of the Registrar strictly verifies that the student pictured above has fulfilled all financial, academic, and property obligations to Debre Tabor University. The student is legally granted permission to exit the university campus with the exact items listed above.
                    <br><br>
                    <b>Approval Registered On:</b> <span style="color:#27ae60; font-weight:bold;"><?= date("F j, Y, g:i A", strtotime($data['registrar_date'])) ?></span>
                </div>
            <?php else: ?>
                <div class="legal-footer" style="color: #c0392b; text-align: center;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 30px; margin-bottom: 10px;"></i><br>
                    <b>WARNING:</b> This student's clearance is currently marked as <b>PENDING</b> or <b>REJECTED</b> in the system. Do not allow campus exit.
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>