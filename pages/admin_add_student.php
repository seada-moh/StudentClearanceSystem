<?php
require '../config/db.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { header("Location: ../login.php"); exit(); }

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_student'])) {
    
    $id_num = trim($_POST['id_num']);
    $raw_pass = $_POST['password']; 
    $sname = trim($_POST['sname']);
    $fathername = trim($_POST['fathername']);
    $gfathername = trim($_POST['gfathername']);
    $sex = $_POST['sex'];
    $email = trim($_POST['email']);
    $department = trim($_POST['department']);
    $academic_year = trim($_POST['academic_year']);
    $year_of_study = $_POST['year_of_study']; 
    $semester = (int)$_POST['semester'];
    $section = trim($_POST['section']);
    $block = (int)$_POST['block_no'];
    $dorm = (int)$_POST['dorm_no'];
    $capacity = 6; 
    
    try {
        if(!preg_match('/^DTU[0-9]+$/', $id_num)) throw new Exception("Invalid ID Format. Student ID must start with 'DTU' followed only by numbers.");

        if (!preg_match('/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[^a-zA-Z0-9]).{4,8}$/', $raw_pass)) {
            throw new Exception("Password Security Failed: Password must be 4 to 8 characters long, and include at least one letter, one number, and one special character (e.g., Abc@12).");
        }
        $pass = password_hash($raw_pass, PASSWORD_DEFAULT);

        // EXPECTED EMAIL NOW USES ID NUMBER
        $expected_email = strtolower($id_num) . "@dtu.edu.et";
        if($email !== $expected_email) throw new Exception("Invalid Email Format! The email MUST be exactly: <b>$expected_email</b>");

        $cap_check = $conn->query("SELECT COUNT(*) as c FROM students WHERE block_no=$block AND dorm_no=$dorm AND is_deleted=0");
        if($cap_check && $cap_check->fetch_assoc()['c'] >= $capacity) throw new Exception("Registration Denied: Dorm Room $dorm in Block $block is completely full.");

        $gender_check = $conn->query("SELECT sex FROM students WHERE block_no=$block AND is_deleted=0 LIMIT 1");
        if($gender_check && $gender_check->num_rows > 0) {
            $block_gender = $gender_check->fetch_assoc()['sex'];
            if($block_gender != $sex) {
                $sex_str = ($sex == 'M') ? 'Male' : 'Female';
                throw new Exception("Registration Denied: You are trying to assign a $sex_str student to a block reserved for the opposite gender.");
            }
        }

        $photo = "default.png";
        if (!empty($_FILES['photo']['name'])) { 
            $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed_exts = array("jpg", "jpeg", "png");
            if(!in_array($file_ext, $allowed_exts)) throw new Exception("Invalid Photo Format. Only JPG, JPEG, and PNG files are allowed.");
            
            $photo = $id_num . "_st_" . time() . "." . $file_ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], "../uploads/" . $photo); 
        }
        
        $stmt = $conn->prepare("INSERT INTO students (student_id, password, sname, fathername, gfathername, email, photo, sex, academic_year, department, year_of_study, semester, section, block_no, dorm_no, dorm_capacity) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssssssssssiii", $id_num, $pass, $sname, $fathername, $gfathername, $email, $photo, $sex, $academic_year, $department, $year_of_study, $semester, $section, $block, $dorm, $capacity);
        
        if($stmt->execute()) {
            $conn->query("INSERT INTO activity_logs (action, role) VALUES ('added', 'student')");
            $msg = "<div class='alert success' style='background:#d4edda; color:#155724; padding:15px; border-radius:5px; margin-bottom:20px;'><i class='fas fa-check-circle'></i> <b>Success!</b> Student ($sname) successfully registered!</div>";
            $_POST = array(); 
        } else {
            throw new Exception("Database Error: Student ID '$id_num' might already exist.");
        }

    } catch (Exception $e) { $msg = "<div class='alert error' style='background:#f8d7da; color:#721c24; padding:15px; border-radius:5px; margin-bottom:20px; border:1px solid #f5c6cb;'><i class='fas fa-exclamation-triangle'></i> " . $e->getMessage() . "</div>"; }
}
include '../includes/header.php';
?>

<style>
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    .input-box { position: relative; margin-bottom: 5px; }
    .input-box label { display: block; font-size: 14px; font-weight: bold; color: #2c3e50; margin-bottom: 8px; }
    .input-box i.icon { position: absolute; left: 15px; top: 40px; color: #95a5a6; font-size: 16px; }
    .input-box input, .input-box select { width: 100%; padding: 12px 15px 12px 42px; border: 2px solid #ecf0f1; border-radius: 8px; box-sizing: border-box; font-size: 15px; transition: 0.3s; background: #fcfcfc; color: #34495e;}
    .input-box input:focus, .input-box select:focus { border-color: #3498db; outline: none; background: white; box-shadow: 0 0 8px rgba(52, 152, 219, 0.2); }
    .full-width { grid-column: 1 / -1; }
    .err-text { color: #e74c3c; font-size: 13px; font-weight: bold; display: none; margin-top: 5px; background: #fdf5f6; padding: 5px; border-radius: 4px; border-left: 3px solid #e74c3c; }
    .password-hint { font-size: 12px; color: #7f8c8d; display: block; margin-top: 5px; }
    .toggle-password { position: absolute; right: 15px; top: 40px; color: #95a5a6; cursor: pointer; font-size: 18px;}
    .toggle-password:hover { color: #2c3e50; }
</style>

<h2><i class="fas fa-user-graduate" style="color: #3498db;"></i> Register New Student</h2>
<?= $msg ?>

<form method="POST" enctype="multipart/form-data" class="form-grid">
    <input type="hidden" name="register_student" value="1">
    
    <div class="input-box">
        <label>Student ID Number</label><i class="fas fa-id-card icon"></i>
        <!-- ID FIELD TRIGGERS EMAIL GENERATION NOW -->
        <input type="text" name="id_num" id="id_input" value="<?= htmlspecialchars($_POST['id_num'] ?? '') ?>" placeholder="E.g., DTU1234" onkeyup="checkID(this, 'e_id', '^DTU[0-9]+$'); generateEmail();" required>
        <div id="e_id" class="err-text">ID MUST start with 'DTU' followed by numbers!</div>
    </div>
    
    <div class="input-box">
        <label>Secure Password</label><i class="fas fa-lock icon"></i>
        <input type="password" name="password" id="pass_input" pattern="(?=.*[a-zA-Z])(?=.*\d)(?=.*[^a-zA-Z0-9]).{4,8}" title="Must be 4 to 8 characters, include at least one letter, one number, and one special character." placeholder="Create password" required>
        <!-- NEW EYE ICON TOGGLE -->
        <i class="fas fa-eye toggle-password" id="eye_icon" onclick="togglePass()"></i>
        <span class="password-hint">4-8 chars. Needs letter, number & special char (e.g., Dtu@1).</span>
    </div>
    
    <div class="input-box">
        <label>Student First Name</label><i class="fas fa-user icon"></i>
        <input type="text" name="sname" value="<?= htmlspecialchars($_POST['sname'] ?? '') ?>" onkeyup="checkLet(this, 'e_sn');" placeholder="First Name" required>
        <div id="e_sn" class="err-text">Invalid: Please type letters only!</div>
    </div>

    <div class="input-box"><label>Father's Name</label><i class="fas fa-user-tie icon"></i><input type="text" name="fathername" value="<?= htmlspecialchars($_POST['fathername'] ?? '') ?>" onkeyup="checkLet(this, 'e_fn')" placeholder="Middle Name" required><div id="e_fn" class="err-text">Invalid: Please type letters only!</div></div>
    <div class="input-box"><label>Grandfather's Name</label><i class="fas fa-user-clock icon"></i><input type="text" name="gfathername" value="<?= htmlspecialchars($_POST['gfathername'] ?? '') ?>" onkeyup="checkLet(this, 'e_gn')" placeholder="Last Name" required><div id="e_gn" class="err-text">Invalid: Please type letters only!</div></div>
    <div class="input-box"><label>Biological Gender</label><i class="fas fa-venus-mars icon"></i><select name="sex" style="cursor: pointer;"><option value="M" <?= (isset($_POST['sex']) && $_POST['sex'] == 'M') ? 'selected' : '' ?>>Male</option><option value="F" <?= (isset($_POST['sex']) && $_POST['sex'] == 'F') ? 'selected' : '' ?>>Female</option></select></div>
    
    <div class="input-box">
        <label>Official Email (Auto-Generated by ID)</label><i class="fas fa-envelope icon" style="color:#27ae60;"></i>
        <input type="email" name="email" id="email_input" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required readonly style="background:#e8f4f8; border-color:#27ae60; font-weight:bold; color:#27ae60; cursor:not-allowed;" title="Email is generated automatically based on the System ID.">
    </div>
    
    <div class="input-box"><label>Profile Photograph</label><i class="fas fa-camera icon"></i><input type="file" name="photo" accept="image/jpeg, image/png, image/jpg" required style="padding-left:40px; padding-top:9px; cursor: pointer;"></div>
    <div class="input-box"><label>Department Name</label><i class="fas fa-building icon"></i><input type="text" name="department" value="<?= htmlspecialchars($_POST['department'] ?? '') ?>" onkeyup="checkLet(this, 'e_dep')" placeholder="E.g., Software Engineering" required><div id="e_dep" class="err-text">Invalid: Please type letters only!</div></div>
    <div class="input-box"><label>Academic Year (Entry Year)</label><i class="fas fa-calendar-alt icon"></i><input type="number" name="academic_year" value="<?= htmlspecialchars($_POST['academic_year'] ?? date('Y')) ?>" min="2010" max="2030" step="1" placeholder="E.g., 2024" required></div>
    <div class="input-box"><label>Current Year of Study</label><i class="fas fa-layer-group icon"></i><select name="year_of_study" required style="cursor: pointer;"><?php $saved_year = $_POST['year_of_study'] ?? '1'; $years = ['Remedial', '1', '2', '3', '4', '5', '6', '7']; foreach($years as $y) { $selected = ($saved_year == $y) ? 'selected' : ''; $label = ($y == 'Remedial') ? 'Remedial Program' : "Year $y"; echo "<option value='$y' $selected>$label</option>"; } ?></select></div>
    <div class="input-box"><label>Current Semester (Num)</label><i class="fas fa-book-open icon"></i><input type="text" name="semester" value="<?= htmlspecialchars($_POST['semester'] ?? '') ?>" onkeyup="checkNum(this, 'e_sem')" placeholder="E.g., 2" required><div id="e_sem" class="err-text">Invalid: Please type numbers only!</div></div>
    <div class="input-box"><label>Class Section</label><i class="fas fa-users icon"></i><input type="text" name="section" value="<?= htmlspecialchars($_POST['section'] ?? '') ?>" onkeyup="checkAlphaNum(this, 'e_sec')" placeholder="E.g., A or 1" required><div id="e_sec" class="err-text">Invalid: No special characters allowed!</div></div>
    <div class="input-box"><label>Assigned Block No (Num)</label><i class="fas fa-cubes icon"></i><input type="text" name="block_no" value="<?= htmlspecialchars($_POST['block_no'] ?? '') ?>" onkeyup="checkNum(this, 'e_blk')" placeholder="E.g., 18" required><div id="e_blk" class="err-text">Invalid: Please type numbers only!</div></div>
    <div class="input-box"><label>Assigned Dorm No (Num)</label><i class="fas fa-door-closed icon"></i><input type="text" name="dorm_no" value="<?= htmlspecialchars($_POST['dorm_no'] ?? '') ?>" onkeyup="checkNum(this, 'e_drm')" placeholder="E.g., 101" required><div id="e_drm" class="err-text">Invalid: Please type numbers only!</div></div>

    <div class="full-width" style="margin-top: 10px;"><button type="submit" class="btn-primary" style="width:100%; padding:16px; background:#27ae60; font-size: 18px; letter-spacing: 1px; box-shadow: 0 4px 15px rgba(39, 174, 96, 0.4);"><i class="fas fa-user-plus"></i> Securely Register Student</button></div>
</form>

<script>
    function checkLet(el, errId) { let r = /[^A-Za-z\s]/g; if(r.test(el.value)){ document.getElementById(errId).style.display='block'; el.value=el.value.replace(r,''); setTimeout(()=>document.getElementById(errId).style.display='none', 3500); } }
    function checkNum(el, errId) { let r = /[^0-9]/g; if(r.test(el.value)){ document.getElementById(errId).style.display='block'; el.value=el.value.replace(r,''); setTimeout(()=>document.getElementById(errId).style.display='none', 3500); } }
    function checkAlphaNum(el, errId) { let r = /[^A-Za-z0-9]/g; if(r.test(el.value)){ document.getElementById(errId).style.display='block'; el.value=el.value.replace(r,''); setTimeout(()=>document.getElementById(errId).style.display='none', 3500); } }
    function checkID(el, errId, regexStr) { let r = new RegExp(regexStr); if(!r.test(el.value) && el.value.length > 3) { document.getElementById(errId).style.display='block'; } else { document.getElementById(errId).style.display='none'; } }

    // Toggle Password Visibility
    function togglePass() {
        var p = document.getElementById("pass_input");
        var e = document.getElementById("eye_icon");
        if (p.type === "password") { p.type = "text"; e.className = "fas fa-eye-slash toggle-password"; } 
        else { p.type = "password"; e.className = "fas fa-eye toggle-password"; }
    }

    // Auto-Generate Email based on System ID!
    function generateEmail() {
        let id = document.getElementById('id_input').value.toLowerCase().replace(/\s/g, '');
        if(id.length > 0) { document.getElementById('email_input').value = id + "@dtu.edu.et"; } 
        else { document.getElementById('email_input').value = ""; }
    }
</script>
<?php include '../includes/footer.php'; ?>