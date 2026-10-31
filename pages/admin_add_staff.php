<?php
require '../config/db.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { header("Location: ../login.php"); exit(); }

$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_staff'])) {
    $role = $_POST['role'];
    $id_num = trim($_POST['id_num']);
    $raw_pass = $_POST['password'];
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $email = trim($_POST['email']);
    
    try {
        // STRICT ID FORMAT VALIDATION
        $valid_id = false;
        if($role=='proctor') $valid_id = preg_match('/^DTUp[0-9]+$/', $id_num);
        elseif($role=='librarian') $valid_id = preg_match('/^DTUl[0-9]+$/', $id_num);
        elseif($role=='cafeteria') $valid_id = preg_match('/^DTUc[0-9]+$/', $id_num);
        elseif($role=='student_service') $valid_id = preg_match('/^DTUss[0-9]+$/', $id_num);
        elseif($role=='dept_head') $valid_id = preg_match('/^DTUd[0-9]+$/', $id_num);
        elseif($role=='registrar') $valid_id = preg_match('/^DTUr[0-9]+$/', $id_num);
        if(!$valid_id) throw new Exception("Invalid ID! E.g. Proctor must be DTUp123, Librarian DTUl123.");

        // STRICT PASSWORD CHECK
        if (!preg_match('/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[^a-zA-Z0-9]).{4,8}$/', $raw_pass)) {
            throw new Exception("Password must be 4 to 8 characters long, and include at least one letter, one number, and one special character.");
        }
        $pass = password_hash($raw_pass, PASSWORD_DEFAULT);

        // STRICT EMAIL VALIDATION (Now Uses ID Number!)
        $expected_email = strtolower($id_num) . "@dtu.edu.et";
        if($email !== $expected_email) throw new Exception("Invalid Email! The email must be exactly: $expected_email");

        $photo = "default.png";
        if (!empty($_FILES['photo']['name'])) { $photo = $id_num."_".time().".".pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION); move_uploaded_file($_FILES['photo']['tmp_name'], "../uploads/".$photo); }

        if ($role == 'proctor') {
            $block = (int)$_POST['block_no'];
            $sex = $_POST['sex'];

            $gender_check = $conn->query("SELECT sex FROM students WHERE block_no=$block AND is_deleted=0 LIMIT 1");
            if($gender_check->num_rows > 0 && $gender_check->fetch_assoc()['sex'] != $sex) {
                $s_str = ($sex=='M')?'Male':'Female'; throw new Exception("A $s_str Proctor MUST be assigned to a $s_str block.");
            }

            $stmt = $conn->prepare("INSERT INTO proctors (proctor_id, username, password, fname, lname, sex, email, block_no, photo) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssssss", $id_num, $_POST['username'], $pass, $fname, $lname, $sex, $email, $block, $photo);
        } elseif ($role == 'dept_head') {
            $stmt = $conn->prepare("INSERT INTO dept_heads (dept_head_id, username, password, fname, lname, sex, faculty, department, email, photo) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssssssssss", $id_num, $_POST['username'], $pass, $fname, $lname, $_POST['sex'], $_POST['faculty'], $_POST['department'], $email, $photo);
        } else {
            $table = ($role == 'student_service') ? 'student_services' : $role . 's';
            $id_col = $role . "_id";
            $stmt = $conn->prepare("INSERT INTO $table ($id_col, username, password, fname, lname, sex, email, photo) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssssssss", $id_num, $_POST['username'], $pass, $fname, $lname, $_POST['sex'], $email, $photo);
        }
        
        if($stmt->execute()) {
            $conn->query("INSERT INTO activity_logs (action, role) VALUES ('added', '$role')");
            $msg = "<div class='alert success' style='background:#d4edda; color:#155724; padding:15px; border-radius:5px; margin-bottom:20px;'><i class='fas fa-check-circle'></i> Staff successfully registered!</div>";
            $_POST = array();
        }
    } catch (Exception $e) { $msg = "<div class='alert error' style='background:#f8d7da; color:#721c24; padding:15px; border-radius:5px; margin-bottom:20px;'><i class='fas fa-exclamation-triangle'></i> " . $e->getMessage() . "</div>"; }
}
$selected_role = $_POST['role'] ?? 'proctor'; 
include '../includes/header.php';
?>
<style>
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    .input-box { position: relative; margin-bottom: 10px; }
    .input-box label { display: block; font-size: 13px; font-weight: bold; color: #34495e; margin-bottom: 5px; }
    .input-box i.icon { position: absolute; left: 15px; top: 35px; color: #95a5a6; font-size: 16px; }
    .input-box input, .input-box select { width: 100%; padding: 12px 15px 12px 40px; border: 1px solid #bdc3c7; border-radius: 6px; box-sizing: border-box; font-size: 14px; transition: 0.3s; }
    .input-box input:focus, .input-box select:focus { border-color: #3498db; box-shadow: 0 0 5px rgba(52, 152, 219, 0.3); outline: none; }
    .full-width { grid-column: 1 / -1; }
    .err-text { color: #e74c3c; font-size: 12px; font-weight: bold; display: none; margin-top: 5px; }
    .toggle-password { position: absolute; right: 15px; top: 35px; color: #95a5a6; cursor: pointer; font-size: 18px;}
    .toggle-password:hover { color: #2c3e50; }
</style>

<h2><i class="fas fa-user-tie"></i> Add New Staff Member</h2>
<?= $msg ?>

<form method="POST" enctype="multipart/form-data" class="form-grid">
    <input type="hidden" name="register_staff" value="1">
    
    <div class="input-box full-width">
        <label>Select Staff Role</label><i class="fas fa-user-tag icon"></i>
        <select name="role" id="roleSel" onchange="tf()" required style="background:#e8f4f8; font-weight:bold;">
            <option value="proctor" <?= ($selected_role == 'proctor') ? 'selected' : '' ?>>Block Proctor</option>
            <option value="librarian" <?= ($selected_role == 'librarian') ? 'selected' : '' ?>>Librarian</option>
            <option value="cafeteria" <?= ($selected_role == 'cafeteria') ? 'selected' : '' ?>>Cafeteria Staff</option>
            <option value="student_service" <?= ($selected_role == 'student_service') ? 'selected' : '' ?>>Student Service Directorate</option>
            <option value="dept_head" <?= ($selected_role == 'dept_head') ? 'selected' : '' ?>>Department Head</option>
            <option value="registrar" <?= ($selected_role == 'registrar') ? 'selected' : '' ?>>Registrar</option>
        </select>
    </div>

    <!-- ID FIELD TRIGGERS EMAIL GENERATION -->
    <div class="input-box"><label>Staff ID Number</label><i class="fas fa-id-badge icon"></i><input type="text" name="id_num" id="id_field" value="<?= htmlspecialchars($_POST['id_num']??'') ?>" onkeyup="generateEmail();" required></div>
    
    <div class="input-box"><label>Username (For Login)</label><i class="fas fa-user-circle icon"></i><input type="text" name="username" value="<?= htmlspecialchars($_POST['username']??'') ?>" required></div>
    
    <!-- NEW EYE ICON TOGGLE -->
    <div class="input-box"><label>Secure Password</label><i class="fas fa-lock icon"></i><input type="password" name="password" id="pass_input" pattern="(?=.*[a-zA-Z])(?=.*\d)(?=.*[^a-zA-Z0-9]).{4,8}" title="Must be 4 to 8 characters, include a letter, number, and special character." required><i class="fas fa-eye toggle-password" id="eye_icon" onclick="togglePass()"></i></div>
    
    <div class="input-box"><label>First Name</label><i class="fas fa-user icon"></i><input type="text" name="fname" value="<?= htmlspecialchars($_POST['fname']??'') ?>" onkeyup="checkLet(this, 'e_fn')" required><div id="e_fn" class="err-text">Please enter letters only!</div></div>
    <div class="input-box"><label>Last Name</label><i class="fas fa-user-tie icon"></i><input type="text" name="lname" value="<?= htmlspecialchars($_POST['lname']??'') ?>" onkeyup="checkLet(this, 'e_ln')" required><div id="e_ln" class="err-text">Please enter letters only!</div></div>
    <div class="input-box"><label>Gender</label><i class="fas fa-venus-mars icon"></i><select name="sex"><option value="M" <?= (isset($_POST['sex'])&&$_POST['sex']=='M')?'selected':'' ?>>Male</option><option value="F" <?= (isset($_POST['sex'])&&$_POST['sex']=='F')?'selected':'' ?>>Female</option></select></div>
    
    <div class="input-box"><label>Email (Auto-Generated)</label><i class="fas fa-envelope icon" style="color:#3498db;"></i><input type="email" name="email" id="email_input" value="<?= htmlspecialchars($_POST['email']??'') ?>" required readonly style="background:#e8f4f8; font-weight:bold; border-color:#3498db; cursor:not-allowed;"><div id="e_em" class="err-text">Email format must match ID!</div></div>
    <div class="input-box"><label>Profile Photo</label><i class="fas fa-camera icon"></i><input type="file" name="photo" accept="image/*" required style="padding-left:40px;"></div>

    <div class="input-box dyn block-field" style="display:none;"><label>Assigned Block No (Num)</label><i class="fas fa-cubes icon"></i><input type="text" name="block_no" value="<?= htmlspecialchars($_POST['block_no']??'') ?>" onkeyup="checkNum(this, 'e_bn')"><div id="e_bn" class="err-text">Please enter numbers only!</div></div>
    <div class="input-box dyn dept-field" style="display:none;"><label>Faculty / College</label><i class="fas fa-university icon"></i><input type="text" name="faculty" value="<?= htmlspecialchars($_POST['faculty']??'') ?>" onkeyup="checkLet(this, 'e_fac')"><div id="e_fac" class="err-text">Please enter letters only!</div></div>
    <div class="input-box dyn dept-field" style="display:none;"><label>Department Name</label><i class="fas fa-building icon"></i><input type="text" name="department" value="<?= htmlspecialchars($_POST['department']??'') ?>" onkeyup="checkLet(this, 'e_dep')"><div id="e_dep" class="err-text">Please enter letters only!</div></div>

    <div class="full-width" style="margin-top: 15px;"><button type="submit" class="btn-primary" style="width:100%; padding:15px; font-size:16px; border-radius:6px; background:#3498db;"><i class="fas fa-plus-circle"></i> Register Staff Member</button></div>
</form>

<script>
    function checkLet(el, errId) { let r = /[^A-Za-z\s]/g; if(r.test(el.value)){ document.getElementById(errId).style.display='block'; el.value=el.value.replace(r,''); setTimeout(()=>document.getElementById(errId).style.display='none', 3000); } }
    function checkNum(el, errId) { let r = /[^0-9]/g; if(r.test(el.value)){ document.getElementById(errId).style.display='block'; el.value=el.value.replace(r,''); setTimeout(()=>document.getElementById(errId).style.display='none', 3000); } }

    function togglePass() {
        var p = document.getElementById("pass_input");
        var e = document.getElementById("eye_icon");
        if (p.type === "password") { p.type = "text"; e.className = "fas fa-eye-slash toggle-password"; } 
        else { p.type = "password"; e.className = "fas fa-eye toggle-password"; }
    }

    function generateEmail() {
        let id = document.getElementById('id_field').value.toLowerCase().replace(/\s/g, '');
        if(id.length > 0) document.getElementById('email_input').value = id + "@dtu.edu.et";
        else document.getElementById('email_input').value = "";
    }

    function tf() {
        let r = document.getElementById('roleSel').value;
        document.querySelectorAll('.dyn').forEach(el => el.style.display = 'none');
        
        if(r === 'proctor') { document.querySelectorAll('.block-field').forEach(e => e.style.display = 'block'); document.getElementById('id_field').placeholder="E.g., DTUp101"; } 
        else if(r === 'dept_head') { document.querySelectorAll('.dept-field').forEach(e => e.style.display = 'block'); document.getElementById('id_field').placeholder="E.g., DTUd101"; }
        else if(r === 'librarian') document.getElementById('id_field').placeholder="E.g., DTUl101";
        else if(r === 'cafeteria') document.getElementById('id_field').placeholder="E.g., DTUc101";
        else if(r === 'student_service') document.getElementById('id_field').placeholder="E.g., DTUss101";
        else if(r === 'registrar') document.getElementById('id_field').placeholder="E.g., DTUr101";
    }
    
    window.onload = tf;
</script>
<?php include '../includes/footer.php'; ?>