<?php
require '../config/db.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') die("Access Denied");

$student_id = $conn->real_escape_string($_GET['student_id']);

if ($student_id !== $_SESSION['user_id']) die("Unauthorized Access");

$query = "SELECT r.*, s.sname, s.fathername, s.gfathername, s.department, s.block_no, s.dorm_no, s.photo, s.sex, s.academic_year, s.year_of_study 
          FROM clearance_requests r 
          JOIN students s ON r.student_id = s.student_id 
          WHERE r.student_id = '$student_id' AND r.registrar_status = 'approved'";
          
$res = $conn->query($query);
if($res->num_rows == 0) die("Error: Clearance not fully approved yet or access denied!");
$data = $res->fetch_assoc();

$fullname = trim($data['sname'] . ' ' . $data['fathername'] . ' ' . $data['gfathername']);

// =========================================================================
// SECURITY FIX: QR Code must be a URL linking to verify.php!
// It grabs the actual IP address of the computer running XAMPP.
// =========================================================================
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$base_dir = dirname(dirname($_SERVER['PHP_SELF'])); 
$verify_url = $protocol . "://" . $host . $base_dir . "/verify.php?id=" . urlencode($student_id);

$safe_qr_data_for_js = json_encode($verify_url);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gate Pass - <?= htmlspecialchars($student_id) ?></title>
    <script src="../assets/qrcode.min.js"></script>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; padding: 40px; background: #ecf0f1; color: #2c3e50; }
        .document-container { max-width: 850px; margin: auto; background: white; padding: 40px 50px; border: 2px solid #34495e; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border-radius: 8px; }
        .header-container { display: flex; justify-content: space-between; align-items: center; border-bottom: 4px double #2c3e50; padding-bottom: 25px; margin-bottom: 30px; }
        .photo-box { width: 120px; height: 140px; border: 3px solid #34495e; padding: 4px; background: white; border-radius: 5px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .photo-box img { width: 100%; height: 100%; object-fit: cover; border-radius: 2px; }
        .header-text { text-align: center; flex: 1; padding: 0 20px; }
        .header-text h1 { margin: 0; font-size: 26px; color: #2c3e50; text-transform: uppercase; font-weight: 900; letter-spacing: 1px; }
        .header-text h2 { margin: 8px 0 0 0; font-size: 20px; color: #c0392b; letter-spacing: 2px; text-transform: uppercase; font-weight: 800; }
        .header-text p { margin: 10px 0 0 0; font-weight: bold; font-size: 14px; color: #7f8c8d; }
        .qr-box { width: 110px; height: 110px; padding: 5px; background: white; border: 3px solid #34495e; border-radius: 5px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: center; }
        .details-table { width: 100%; margin-bottom: 30px; border-collapse: collapse; }
        .details-table td { padding: 12px; font-size: 16px; border-bottom: 1px solid #ecf0f1; }
        .details-table td:first-child { font-weight: bold; width: 35%; color: #34495e; background: #f9f9f9; border-right: 1px solid #ecf0f1; }
        .items-box { padding: 25px; border: 2px dashed #95a5a6; min-height: 100px; background: #fcfcfc; margin-bottom: 30px; border-radius: 5px; }
        .signatures { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 50px; text-align: center; }
        .stamp-box { width: 130px; height: 130px; border: 4px solid #c0392b; border-radius: 50%; margin: 0 auto 15px auto; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #c0392b; font-weight: 900; font-size: 18px; transform: rotate(-15deg); opacity: 0.85; text-align: center; box-shadow: inset 0 0 0 2px #c0392b; }
        .print-btn { display: block; width: 300px; margin: 0 auto 30px auto; background: #27ae60; color: white; padding: 15px; text-align: center; border: none; cursor: pointer; border-radius: 50px; font-size: 18px; font-weight: bold; transition: 0.3s; box-shadow: 0 5px 15px rgba(39, 174, 96, 0.4); }
        .print-btn:hover { background: #2ecc71; transform: translateY(-3px); box-shadow: 0 8px 20px rgba(39, 174, 96, 0.6); }
        @media print { body { background: white; padding: 0; } .document-container { border: none; box-shadow: none; padding: 0; max-width: 100%; } .no-print { display: none !important; } }
    </style>
</head>
<body>
    <button class="no-print print-btn" onclick="window.print()">🖨️ Print Official Gate Pass</button>

    <div class="document-container">
        <div class="header-container">
            <div class="photo-box"><img src="../uploads/<?= htmlspecialchars($data['photo']) ?>" alt="Student Photo"></div>
            <div class="header-text">
                <h1>Debre Tabor University</h1>
                <h2>Official Exit Gate Pass</h2>
                <p>To be presented to the University Security Guard</p>
            </div>
            <div class="qr-box" id="qrcode"></div>
        </div>

        <table class="details-table">
            <tr><td>Date of Issue:</td><td><b><?= date("F j, Y, g:i A") ?></b></td></tr>
            <tr><td>Student Full Name:</td><td style="text-transform: uppercase; font-weight:900; color:#2c3e50;"><?= htmlspecialchars($fullname) ?></td></tr>
            <tr><td>ID Number:</td><td style="font-family: monospace; font-size: 18px; font-weight: bold;"><?= htmlspecialchars($student_id) ?></td></tr>
            <tr><td>Department:</td><td><?= htmlspecialchars($data['department']) ?></td></tr>
            <tr><td>Campus Location:</td><td>Block <b><?= htmlspecialchars($data['block_no']) ?></b> | Dorm <b><?= htmlspecialchars($data['dorm_no']) ?></b></td></tr>
            <tr><td>Reason for Leaving:</td><td style="color: #e67e22; font-weight: bold;"><?= htmlspecialchars($data['reason']) ?></td></tr>
        </table>

        <h3 style="color: #2c3e50; border-bottom: 2px solid #ecf0f1; padding-bottom: 10px;">Registered Personal Property:</h3>
        <div class="items-box"><i style="font-size: 16px; line-height: 1.8; color: #34495e;"><?= nl2br(htmlspecialchars($data['personal_items'])) ?></i></div>

        <p style="text-align: justify; font-size: 15px; color: #7f8c8d; line-height: 1.6; background: #fdfdfd; padding: 15px; border-left: 4px solid #3498db;">
            <b>Security Guard Notice:</b> This digitally generated Gate Pass confirms that the above-named student has successfully cleared all university departments. <b>Scan the QR code in the top right corner with your smartphone camera to instantly verify the authenticity of this document and view the student's photo.</b>
        </p>

        <div class="signatures">
            <div style="text-align: left;">
                <p style="margin-bottom: 5px;">______________________________</p>
                <p style="margin: 0;"><b>System Administrator</b></p>
                <p style="font-size:12px; color:#95a5a6; margin-top: 5px;">Digitally Verified by DTUSCMS</p>
            </div>
            
            <div>
                <div class="stamp-box"><span>OFFICIAL</span><span style="font-size:22px;">APPROVED</span></div>
                <p style="margin: 0; font-size: 18px;"><b>Office of the Registrar</b></p>
                <p style="font-size:12px; color:#95a5a6; margin-top: 5px;">Final Clearance Granted</p>
            </div>
        </div>
    </div>

    <script>
        var qrData = <?= $safe_qr_data_for_js ?>;
        new QRCode(document.getElementById("qrcode"), {
            text: qrData, width: 100, height: 100, colorDark : "#000000", colorLight : "#ffffff", correctLevel : QRCode.CorrectLevel.L
        });
    </script>
</body>
</html>