<?php
require '../config/db.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { header("Location: ../index.php"); exit(); }

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_csv'])) {
    $role = $_POST['role'];
    $file = $_FILES['csv_file']['tmp_name'];
    
    if ($_FILES['csv_file']['size'] > 0) {
        $file_open = fopen($file, "r");
        fgetcsv($file_open); // Skip the header row
        
        $success_count = 0;
        $error_count = 0;

        while (($data = fgetcsv($file_open, 1000, ",")) !== FALSE) {
            
            // Shared Data Columns for Everyone
            $id = $conn->real_escape_string($data[0]);
            
            // Password Validation (Must be 4-8 chars, 1 letter, 1 num, 1 special)
            $raw_pass = $data[1];
            if (!preg_match('/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[^a-zA-Z0-9]).{4,8}$/', $raw_pass)) {
                $error_count++; continue;
            }
            $pass = password_hash($raw_pass, PASSWORD_DEFAULT);
            
            $fname = $conn->real_escape_string($data[2]);
            $lname = $conn->real_escape_string($data[3]);
            $sex = $conn->real_escape_string($data[4]);
            $email = $conn->real_escape_string($data[5]);
            $photo = "default.png";

            try {
                if ($role == 'student') {
                    // STUDENT CSV FORMAT: ID, Pass, First, Father, Grandfather, Sex, Email, Dept, Block, Dorm, AcadYear, YearOfStudy, Sem, Section
                    $gname = $conn->real_escape_string($data[4]); // Students use GName in col 4 instead of Sex
                    $sex = $conn->real_escape_string($data[5]);
                    $email = $conn->real_escape_string($data[6]);
                    $dept = $conn->real_escape_string($data[7]);
                    $block = (int)$data[8];
                    $dorm = (int)$data[9];
                    $acad_year = $conn->real_escape_string($data[10]);
                    $yos = $conn->real_escape_string($data[11]);
                    $sem = (int)$data[12];
                    $sec = $conn->real_escape_string($data[13]);
                    
                    $stmt = $conn->prepare("INSERT INTO students (student_id, password, sname, fathername, gfathername, sex, email, department, block_no, dorm_no, academic_year, year_of_study, semester, section, dorm_capacity, photo) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,6,?)");
                    $stmt->bind_param("ssssssssiisisss", $id, $pass, $fname, $lname, $gname, $sex, $email, $dept, $block, $dorm, $acad_year, $yos, $sem, $sec, $photo);
                
                } elseif ($role == 'proctor') {
                    // PROCTOR FORMAT: ID, Pass, Fname, Lname, Sex, Email, Username, BlockNo
                    $username = $conn->real_escape_string($data[6]);
                    $block = (int)$data[7];
                    
                    $stmt = $conn->prepare("INSERT INTO proctors (proctor_id, username, password, fname, lname, sex, email, block_no, photo) VALUES (?,?,?,?,?,?,?,?,?)");
                    $stmt->bind_param("sssssssis", $id, $username, $pass, $fname, $lname, $sex, $email, $block, $photo);
                
                } elseif ($role == 'dept_head') {
                    // DEPT HEAD FORMAT: ID, Pass, Fname, Lname, Sex, Email, Username, Faculty, Department
                    $username = $conn->real_escape_string($data[6]);
                    $faculty = $conn->real_escape_string($data[7]);
                    $dept = $conn->real_escape_string($data[8]);

                    $stmt = $conn->prepare("INSERT INTO dept_heads (dept_head_id, username, password, fname, lname, sex, email, faculty, department, photo) VALUES (?,?,?,?,?,?,?,?,?,?)");
                    $stmt->bind_param("ssssssssss", $id, $username, $pass, $fname, $lname, $sex, $email, $faculty, $dept, $photo);

                } else {
                    // ALL OTHER STAFF FORMAT: ID, Pass, Fname, Lname, Sex, Email, Username
                    $username = $conn->real_escape_string($data[6]);
                    $table = ($role == 'student_service') ? 'student_services' : $role . 's';
                    $id_col = $role . "_id";

                    $stmt = $conn->prepare("INSERT INTO $table ($id_col, username, password, fname, lname, sex, email, photo) VALUES (?,?,?,?,?,?,?,?)");
                    $stmt->bind_param("ssssssss", $id, $username, $pass, $fname, $lname, $sex, $email, $photo);
                }
                
                if($stmt->execute()) $success_count++;
                else $error_count++;

            } catch(Exception $e) { $error_count++; }
        }
        fclose($file_open);
        
        if ($success_count > 0) {
            $conn->query("INSERT INTO activity_logs (action, role) VALUES ('added', '$role')");
        }

        $msg = "<div class='alert success' style='background:#d4edda; padding:15px; color:#155724; border-radius:8px;'><i class='fas fa-check-circle'></i> <b>Upload Complete!</b><br>Successfully Added: $success_count<br>Failed (Bad Format or Duplicate ID): $error_count</div>";
    }
}

include '../includes/header.php';
?>
<h2><i class="fas fa-file-csv"></i> Bulk User Upload</h2>
<?= $msg ?>

<div class="card" style="max-width: 800px;">
    <div style="background: #e8f4f8; color: #2c3e50; padding: 20px; border-left: 5px solid #3498db; margin-bottom: 25px; border-radius: 4px;">
        <h3 style="margin-top: 0; color: #3498db;"><i class="fas fa-info-circle"></i> CSV Formatting Rules</h3>
        <p style="margin-bottom: 10px;">To ensure the upload succeeds, your Excel/CSV file must <b>exactly match</b> the column order for the selected role. Do not include column headers in your file.</p>
        
        <ul style="margin-left:20px; font-size:14px; line-height: 1.8;">
            <li><b>Students:</b> ID, Password, FirstName, FatherName, GrandfatherName, Sex, Email, Dept, BlockNo, DormNo, AcadYear, YearOfStudy, Semester, Section</li>
            <li><b>Proctors:</b> ID, Password, FirstName, LastName, Sex, Email, Username, BlockNo</li>
            <li><b>Dept Heads:</b> ID, Password, FirstName, LastName, Sex, Email, Username, Faculty, Department</li>
            <li><b>Other Staff:</b> ID, Password, FirstName, LastName, Sex, Email, Username</li>
        </ul>
        <p style="color: #e74c3c; font-weight: bold; margin-top: 10px; font-size: 13px;">* Passwords MUST be 4-8 chars and contain a letter, number, and special character (e.g., Dtu@123).</p>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="upload_csv" value="1">
        
        <label style="font-weight:bold; display:block; margin-bottom:8px; color: #34495e;">Select Role being Uploaded:</label>
        <select name="role" required style="width:100%; padding:15px; margin-bottom: 25px; border-radius:8px; border:2px solid #ecf0f1; outline:none; font-size:15px; cursor:pointer;">
            <option value="student">Students</option>
            <option value="proctor">Proctors</option>
            <option value="librarian">Librarians</option>
            <option value="cafeteria">Cafeteria Staff</option>
            <option value="student_service">Student Services</option>
            <option value="dept_head">Department Heads</option>
            <option value="registrar">Registrars</option>
        </select>
        
        <label style="font-weight:bold; display:block; margin-bottom:8px; color: #34495e;">Select CSV File:</label>
        <input type="file" name="csv_file" accept=".csv" required style="width:100%; padding:12px; margin-bottom: 25px; border:2px dashed #bdc3c7; border-radius:8px; background: #fdfdfd; cursor:pointer;">
        
        <button type="submit" class="btn-primary" style="background:#27ae60; width:100%; padding:15px; font-size:16px; border-radius:8px; box-shadow: 0 4px 10px rgba(39, 174, 96, 0.3);">
            <i class="fas fa-upload"></i> Process Database Upload
        </button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>