<?php
require '../config/db.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) { header("Location: ../index.php"); exit(); }

$role = $_SESSION['role'];
$user_id_string = $_SESSION['user_id'];
$msg = "";

$tables_map = [
    'admin' =>['table' => 'admins', 'id_col' => 'username'],
    'student' =>['table' => 'students', 'id_col' => 'student_id'],
    'proctor' =>['table' => 'proctors', 'id_col' => 'proctor_id'],
    'librarian' =>['table' => 'librarians', 'id_col' => 'librarian_id'],
    'cafeteria' =>['table' => 'cafeterias', 'id_col' => 'cafeteria_id'],
    'student_service' => ['table' => 'student_services', 'id_col' => 'student_service_id'],
    'dept_head' =>['table' => 'dept_heads', 'id_col' => 'dept_head_id'],
    'registrar' =>['table' => 'registrars', 'id_col' => 'registrar_id']
];

$tbl = $tables_map[$role]['table'];
$col = $tables_map[$role]['id_col'];

$user = $conn->query("SELECT * FROM $tbl WHERE $col = '$user_id_string'")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $email = trim($conn->real_escape_string($_POST['email']));
    $password_query = "";
    
    try {
        // STRICT EMAIL CHECKS FOR OWN PROFILE UPDATE
        if ($role == 'admin') {
            if ($email !== 'admin@dtu.edu.et') throw new Exception("Admin email must be exactly admin@dtu.edu.et");
        } elseif ($role == 'student') {
            $expected = strtolower(str_replace(' ', '', $user['sname'])) . "@dtu.edu.et";
            if ($email !== $expected) throw new Exception("Your email must match your first name: $expected");
        } else {
            $initials = ['proctor'=>'p', 'librarian'=>'l', 'cafeteria'=>'c', 'student_service'=>'ss', 'dept_head'=>'d', 'registrar'=>'r'];
            $expected = strtolower(str_replace(' ', '', $user['fname'])) . $initials[$role] . "@dtu.edu.et";
            if ($email !== $expected) throw new Exception("Staff email must be format: $expected");
        }

        if (!empty($_POST['new_password'])) {
            $hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $password_query = ", password = '$hashed'";
        }

        $photo_query = "";
        if (!empty($_FILES['photo']['name'])) {
            $photo = $user_id_string . "_upd_" . time() . "." . pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            move_uploaded_file($_FILES['photo']['tmp_name'], "../uploads/" . $photo);
            $photo_query = ", photo = '$photo'";
            $_SESSION['photo'] = $photo; 
        }

        $query = "UPDATE $tbl SET email = '$email' $password_query $photo_query WHERE $col = '$user_id_string'";
        if ($conn->query($query)) {
            $msg = "<div class='alert success' style='background:#d4edda; color:#155724; padding:15px; border-radius:5px; margin-bottom:15px;'><i class='fas fa-check-circle'></i> Profile updated successfully!</div>";
            $user['email'] = $email; // Update local array for display
        }
    } catch(Exception $e) {
        $msg = "<div class='alert error' style='background:#f8d7da; color:#721c24; padding:15px; border-radius:5px; margin-bottom:15px;'><i class='fas fa-exclamation-triangle'></i> " . $e->getMessage() . "</div>";
    }
}

include '../includes/header.php';
?>
<h2><i class="fas fa-user-edit"></i> Account Settings</h2>
<?= $msg ?>

<div class="card" style="max-width: 600px; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="../uploads/<?= htmlspecialchars($_SESSION['photo']) ?>" alt="Profile Photo" style="width: 140px; height: 140px; border-radius: 50%; object-fit: cover; border: 4px solid #3498db;">
    </div>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="update_profile" value="1">
        
        <div style="margin-bottom: 15px;">
            <label style="font-weight:bold; color:#34495e;">ID / Username (Unchangeable):</label>
            <input type="text" value="<?= htmlspecialchars($user_id_string) ?>" readonly style="width:100%; padding:12px; border-radius:5px; border:1px solid #ccc; background: #ecf0f1; margin-top:5px; box-sizing:border-box;">
        </div>

        <div style="margin-bottom: 15px; position: relative;">
            <label style="font-weight:bold; color:#34495e;">Verified Email Address:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required style="width:100%; padding:12px; border-radius:5px; border:1px solid #3498db; background:#e8f4f8; font-weight:bold; margin-top:5px; box-sizing:border-box;">
            <small style="color:#7f8c8d;">Email format must follow University standard conventions.</small>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="font-weight:bold; color:#34495e;">Change Password <small style="color:#7f8c8d;">(Leave blank to keep current)</small>:</label>
            <input type="password" name="new_password" placeholder="Enter new secure password" style="width:100%; padding:12px; border-radius:5px; border:1px solid #ccc; margin-top:5px; box-sizing:border-box;">
        </div>

        <div style="margin-bottom: 25px;">
            <label style="font-weight:bold; color:#34495e;">Update Profile Photo:</label>
            <input type="file" name="photo" accept="image/*" style="width:100%; padding:10px; border-radius:5px; border:1px solid #ccc; margin-top:5px; box-sizing:border-box; background:#f9f9f9;">
        </div>

        <button type="submit" class="btn-primary" style="width:100%; padding:15px; font-size:16px; background:#3498db; border:none; border-radius:5px; color:white; font-weight:bold; cursor:pointer;"><i class="fas fa-save"></i> Save Changes</button>
    </form>
</div>
<?php include '../includes/footer.php'; ?>