<?php
require 'config/db.php';
$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $role = $_POST['role'];
    $id_num = $_POST['id_num'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // PHP REGEX VALIDATION (Extra Security)
    $valid_id = false;
    if($role=='student' || $role=='dormmate') $valid_id = preg_match('/^DTU[0-9]+$/', $id_num);
    elseif($role=='proctor') $valid_id = preg_match('/^DTUp[0-9]+$/', $id_num);
    elseif($role=='librarian') $valid_id = preg_match('/^DTUl[0-9]+$/', $id_num);
    elseif($role=='cafeteria') $valid_id = preg_match('/^DTUc[0-9]+$/', $id_num);
    elseif($role=='student_service') $valid_id = preg_match('/^DTUss[0-9]+$/', $id_num);
    elseif($role=='dept_head') $valid_id = preg_match('/^DTUd[0-9]+$/', $id_num);
    elseif($role=='registrar') $valid_id = preg_match('/^DTUr[0-9]+$/', $id_num);

    if(!$valid_id) {
        $msg = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> Invalid ID format for the selected role!</div>";
    } else {
        try {
            $photo = "default.png";
            // Check gender & capacity rules for Students/Dormmates/Proctors
            if (in_array($role, ['student', 'dormmate', 'proctor'])) {
                $block = (int)$_POST['block_no'];
                $sex = $_POST['sex'];
                
                // Gender match check
                $gender_check = $conn->query("SELECT sex FROM students WHERE block_no=$block LIMIT 1");
                if($gender_check->num_rows > 0 && $gender_check->fetch_assoc()['sex'] != $sex) {
                    throw new Exception("Block $block is strictly reserved for the opposite gender.");
                }

                if ($role == 'student' || $role == 'dormmate') {
                    $dorm = (int)$_POST['dorm_no'];
                    $cap_check = $conn->query("SELECT COUNT(*) as c FROM students WHERE block_no=$block AND dorm_no=$dorm");
                    if($cap_check->fetch_assoc()['c'] >= 6) throw new Exception("Dorm Room $dorm is full (Max 6).");
                } elseif ($role == 'proctor') {
                    $proc_check = $conn->query("SELECT COUNT(*) as c FROM proctors WHERE block_no=$block");
                    if($proc_check->fetch_assoc()['c'] > 0) throw new Exception("Block $block already has a Proctor.");
                }
            }

            // INSERTION LOGIC
            if ($role == 'student') {
                $stmt = $conn->prepare("INSERT INTO students (student_id, password, sname, fathername, gfathername, email, photo, sex, academic_year, department, year_of_study, semester, section, block_no, dorm_no) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->bind_param("ssssssssssiisii", $id_num, $pass, $_POST['sname'], $_POST['fathername'], $_POST['gfathername'], $_POST['email'], $photo, $_POST['sex'], $_POST['academic_year'], $_POST['department'], $_POST['year_of_study'], $_POST['semester'], $_POST['section'], $_POST['block_no'], $_POST['dorm_no']);
            } elseif ($role == 'dormmate') {
                $stmt = $conn->prepare("INSERT INTO dorm_mates (mate_id, password, fname, lname, email, sex, department, block_no, dorm_no, photo) VALUES (?,?,?,?,?,?,?,?,?,?)");
                $stmt->bind_param("ssssssssss", $id_num, $pass, $_POST['fname'], $_POST['lname'], $_POST['email'], $_POST['sex'], $_POST['department'], $_POST['block_no'], $_POST['dorm_no'], $photo);
            } elseif ($role == 'proctor') {
                $stmt = $conn->prepare("INSERT INTO proctors (proctor_id, username, password, fname, lname, sex, email, block_no, photo) VALUES (?,?,?,?,?,?,?,?,?)");
                $stmt->bind_param("sssssssss", $id_num, $_POST['username'], $pass, $_POST['fname'], $_POST['lname'], $_POST['sex'], $_POST['email'], $_POST['block_no'], $photo);
            } else {
                // Other Staff
                $table = ($role == 'student_service') ? 'student_services' : $role . 's';
                $id_col = $role . "_id";
                if($role == 'dept_head') {
                    $stmt = $conn->prepare("INSERT INTO dept_heads (dept_head_id, username, password, fname, lname, sex, faculty, department, email, photo) VALUES (?,?,?,?,?,?,?,?,?,?)");
                    $stmt->bind_param("ssssssssss", $id_num, $_POST['username'], $pass, $_POST['fname'], $_POST['lname'], $_POST['sex'], $_POST['faculty'], $_POST['department'], $_POST['email'], $photo);
                } else {
                    $stmt = $conn->prepare("INSERT INTO $table ($id_col, username, password, fname, lname, sex, email, photo) VALUES (?,?,?,?,?,?,?,?)");
                    $stmt->bind_param("ssssssss", $id_num, $_POST['username'], $pass, $_POST['fname'], $_POST['lname'], $_POST['sex'], $_POST['email'], $photo);
                }
            }
            
            if($stmt->execute()) {
                $msg = "<div class='alert success'><i class='fas fa-check-circle'></i> Account Created! <a href='index.php'>Go to Login</a></div>";
            }
        } catch (Exception $e) { $msg = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> " . $e->getMessage() . "</div>"; }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sign Up - DTUSCMS</title>
    <link rel="stylesheet" href="assets/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #ecf0f1; padding: 40px 0; }
        .reg-container { max-width: 800px; margin: auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .input-box { margin-bottom: 10px; }
        .input-box label { display: block; font-size: 16px; font-weight: bold; color: #041cf7f1; margin-bottom: 5px; }
        .input-box input, .input-box select { width: 100%; padding: 12px; border: 2px solid #ecf0f1; border-radius: 6px; box-sizing: border-box; font-size: 14px; }
        .full-width { grid-column: 1 / -1; }
        .btn-submit { background: #2ecc71; color: white; padding: 15px; border: none; border-radius: 6px; width: 100%; font-weight: bold; font-size: 16px; cursor: pointer; }
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; font-weight: bold; text-align: center; }
        .alert.error { background: #f8d7da; color: #721c24; }
        .alert.success { background: #d4edda; color: #155724; }
    </style>
</head>
<body>
    <div class="reg-container">
        <h2 style="text-align: center; color: #2c3e50; margin-bottom: 30px;"><i class="fas fa-user-plus"></i> Create New Account</h2>
        <?= $msg ?>
        
        <form method="POST" class="form-grid" id="regForm">
            <input type="hidden" name="register" value="1">
            
            <div class="input-box full-width">
                <label>I am a:</label>
                <select name="role" id="roleSelector" onchange="toggleFields()" required style="background:#e8f4f8; font-weight:bold;">
                    <option value="student">Student</option>
                    <option value="dormmate">Dormmate</option>
                    <option value="proctor">Proctor</option>
                    <option value="librarian">Librarian</option>
                    <option value="cafeteria">Cafeteria Staff</option>
                    <option value="student_service">Student Service</option>
                    <option value="dept_head">Department Head</option>
                    <option value="registrar">Registrar</option>
                </select>
            </div>

            <!-- Identifiers -->
            <div class="input-box"><label>System ID:</label><input type="text" name="id_num" id="id_num" required placeholder="e.g., DTU1234"></div>
            <div class="input-box dynamic staff-field" style="display:none;"><label>Username (For Login):</label><input type="text" name="username" placeholder="Choose a username"></div>
            <div class="input-box"><label>Password:</label><input type="password" name="password" required></div>
            <div class="input-box"><label>Email:</label><input type="email" name="email" required></div>
            <div class="input-box"><label>Gender:</label><select name="sex"><option value="M">Male</option><option value="F">Female</option></select></div>

            <!-- Names (JS Validation to prevent numbers) -->
            <div class="input-box"><label id="lbl_first">First Name:</label><input type="text" name="sname" id="sname" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" required></div>
            <div class="input-box"><label id="lbl_last">Last Name:</label><input type="text" name="fathername" id="fathername" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" required></div>
            <div class="input-box dynamic student-only"><label>Grandfather Name:</label><input type="text" name="gfathername" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')"></div>

            <!-- Numbers (JS Validation to prevent letters) -->
            <div class="input-box dynamic loc-field"><label>Block No (Numbers only):</label><input type="text" name="block_no" oninput="this.value = this.value.replace(/[^0-9]/g, '')"></div>
            <div class="input-box dynamic dorm-field"><label>Dorm No (Numbers only):</label><input type="text" name="dorm_no" oninput="this.value = this.value.replace(/[^0-9]/g, '')"></div>
            
            <div class="input-box dynamic dept-field"><label>Department:</label><input type="text" name="department" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')"></div>
            <div class="input-box dynamic dept-head-only" style="display:none;"><label>Faculty:</label><input type="text" name="faculty"></div>

            <div class="input-box dynamic student-only"><label>Academic Year:</label><input type="text" name="academic_year"></div>
            <div class="input-box dynamic student-only"><label>Year of Study:</label><input type="text" name="year_of_study" oninput="this.value = this.value.replace(/[^0-9]/g, '')"></div>
            <div class="input-box dynamic student-only"><label>Semester:</label><input type="text" name="semester" oninput="this.value = this.value.replace(/[^0-9]/g, '')"></div>
            <div class="input-box dynamic student-only"><label>Section:</label><input type="text" name="section" oninput="this.value = this.value.replace(/[^A-Za-z]/g, '')"></div>

            <div class="full-width"><button type="submit" class="btn-submit">Register Account</button></div>
            <div class="full-width" style="text-align:center;"><a href="index.php">Back to Login</a></div>
        </form>
    </div>

    <!-- JAVASCRIPT FOR DYNAMIC UI AND ID VALIDATION -->
    <script>
        function toggleFields() {
            let r = document.getElementById('roleSelector').value;
            document.querySelectorAll('.dynamic').forEach(el => el.style.display = 'none');
            
            // Rename fields for staff vs student
            if(r === 'student') {
                document.getElementById('lbl_first').innerText = "Student Name:";
                document.getElementById('lbl_last').innerText = "Father Name:";
                document.getElementById('sname').name = "sname";
                document.getElementById('fathername').name = "fathername";
            } else {
                document.getElementById('lbl_first').innerText = "First Name:";
                document.getElementById('lbl_last').innerText = "Last Name:";
                document.getElementById('sname').name = "fname";
                document.getElementById('fathername').name = "lname";
            }

            // Show appropriate fields
            if(r === 'student') { document.querySelectorAll('.student-only, .loc-field, .dorm-field, .dept-field').forEach(el => el.style.display = 'block'); document.getElementById('id_num').placeholder = "DTU1234"; }
            else if(r === 'dormmate') { document.querySelectorAll('.loc-field, .dorm-field, .dept-field').forEach(el => el.style.display = 'block'); document.getElementById('id_num').placeholder = "DTU1234"; }
            else { document.querySelectorAll('.staff-field').forEach(el => el.style.display = 'block'); }
            
            if(r === 'proctor') { document.querySelectorAll('.loc-field').forEach(el => el.style.display = 'block'); document.getElementById('id_num').placeholder = "DTUp123"; }
            if(r === 'dept_head') { document.querySelectorAll('.dept-field, .dept-head-only').forEach(el => el.style.display = 'block'); document.getElementById('id_num').placeholder = "DTUd123"; }
            if(r === 'librarian') document.getElementById('id_num').placeholder = "DTUl123";
            if(r === 'cafeteria') document.getElementById('id_num').placeholder = "DTUc123";
            if(r === 'student_service') document.getElementById('id_num').placeholder = "DTUss123";
            if(r === 'registrar') document.getElementById('id_num').placeholder = "DTUr123";
        }

        // JS ID Validation on Submit
        document.getElementById('regForm').onsubmit = function(e) {
            let r = document.getElementById('roleSelector').value;
            let id = document.getElementById('id_num').value;
            let regex = /^DTU[0-9]+$/; // Default for student/dormmate
            
            if(r === 'proctor') regex = /^DTUp[0-9]+$/;
            else if(r === 'librarian') regex = /^DTUl[0-9]+$/;
            else if(r === 'cafeteria') regex = /^DTUc[0-9]+$/;
            else if(r === 'student_service') regex = /^DTUss[0-9]+$/;
            else if(r === 'dept_head') regex = /^DTUd[0-9]+$/;
            else if(r === 'registrar') regex = /^DTUr[0-9]+$/;

            if(!regex.test(id)) {
                alert("Invalid ID Format! Please follow the placeholder pattern (e.g., " + document.getElementById('id_num').placeholder + ")");
                e.preventDefault();
            }
        };

        window.onload = toggleFields;
    </script>
</body>
</html>