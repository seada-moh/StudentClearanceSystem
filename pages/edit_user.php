<?php
require '../config/db.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { header("Location: ../index.php"); exit(); }

$role = $_GET['role'] ?? '';
$id = $conn->real_escape_string($_GET['id'] ?? ''); // FIXED: IDs are now strings!

$tables_map =[
    'student' =>['table' => 'students', 'id_col' => 'student_id'],
    'dormmate' =>['table' => 'dorm_mates', 'id_col' => 'mate_id'],
    'proctor' =>['table' => 'proctors', 'id_col' => 'proctor_id'],
    'librarian' =>['table' => 'librarians', 'id_col' => 'librarian_id'],
    'cafeteria' =>['table' => 'cafeterias', 'id_col' => 'cafeteria_id'],
    'student_service' =>['table' => 'student_services', 'id_col' => 'student_service_id'],
    'dept_head' =>['table' => 'dept_heads', 'id_col' => 'dept_head_id'],
    'registrar' =>['table' => 'registrars', 'id_col' => 'registrar_id']
];

if (!isset($tables_map[$role]) || empty($id)) { die("Invalid request."); }

$table = $tables_map[$role]['table'];
$id_col = $tables_map[$role]['id_col'];
$msg = "";

// HANDLE DYNAMIC UPDATE WITH BUSINESS RULES
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    try {
        // --- RULE CHECKING ---
        if ($role == 'student' || $role == 'dormmate') {
            $new_block = $conn->real_escape_string($_POST['block_no']);
            $new_dorm = $conn->real_escape_string($_POST['dorm_no']);
            $new_sex = $conn->real_escape_string($_POST['sex'] ?? 'M');

            // 1. Check Dorm Capacity (Max 6)
            $cap_check = $conn->query("SELECT COUNT(*) as c FROM students WHERE block_no='$new_block' AND dorm_no='$new_dorm' AND student_id != '$id'");
            if($cap_check->fetch_assoc()['c'] >= 6) throw new Exception("This dorm room is full (Maximum 6 students).");

            // 2. Check Block Gender Segregation
            $gender_check = $conn->query("SELECT sex FROM students WHERE block_no='$new_block' AND student_id != '$id' LIMIT 1");
            if($gender_check->num_rows > 0) {
                if($gender_check->fetch_assoc()['sex'] != $new_sex) {
                    throw new Exception("Gender mismatch! This block is reserved for the opposite sex.");
                }
            }
        } elseif ($role == 'proctor') {
            $new_block = $conn->real_escape_string($_POST['block_no']);
            $new_sex = $conn->real_escape_string($_POST['sex']);

            // 1. Check One Proctor Per Block
            $proc_check = $conn->query("SELECT COUNT(*) as c FROM proctors WHERE block_no='$new_block' AND proctor_id != '$id'");
            if($proc_check->fetch_assoc()['c'] > 0) throw new Exception("Block $new_block already has a Proctor assigned.");

            // 2. Check Proctor Gender matches Student Block Gender
            $gender_check = $conn->query("SELECT sex FROM students WHERE block_no='$new_block' LIMIT 1");
            if($gender_check->num_rows > 0) {
                if($gender_check->fetch_assoc()['sex'] != $new_sex) {
                    throw new Exception("Proctor gender must match the students' gender in this block.");
                }
            }
        }
        // --- END RULES ---

        $updates =[];
        foreach ($_POST as $key => $val) {
            if ($key != 'update_user') {
                $safe_val = $conn->real_escape_string($val);
                $updates[] = "$key = '$safe_val'";
            }
        }
        
        if (count($updates) > 0) {
            $query_str = implode(", ", $updates);
            if ($conn->query("UPDATE $table SET $query_str WHERE $id_col = '$id'")) {
                header("Location: view_users.php?view=$role");
                exit();
            } else {
                throw new Exception("Database update failed.");
            }
        }
    } catch (Exception $e) {
        $msg = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> " . $e->getMessage() . "</div>";
    }
}

// Fetch current user details
$user = $conn->query("SELECT * FROM $table WHERE $id_col = '$id'")->fetch_assoc();
if (!$user) { die("User not found in database."); }

include '../includes/header.php';
?>

<h2><i class="fas fa-user-edit"></i> Edit <?= ucwords(str_replace('_',' ',$role)) ?> Details</h2>
<?= $msg ?>

<div class="card" style="max-width: 600px;">
    <form method="POST">
        <input type="hidden" name="update_user" value="1">
        
        <?php 
        $exclude_cols =['student_id', 'proctor_id', 'librarian_id', 'cafeteria_id', 'student_service_id', 'dept_head_id', 'registrar_id', 'mate_id', 'password', 'photo', 'request_date'];
        
        foreach ($user as $col => $val): 
            if (in_array($col, $exclude_cols)) continue; 
            
            $display_label = ucwords(str_replace('_', ' ', $col));
            if($col == 'sname') $display_label = 'Student Name';
            if($col == 'fname') $display_label = 'First Name';
            if($col == 'lname') $display_label = 'Last Name';
        ?>
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="font-weight:bold; margin-bottom:5px; display:block; color:#2c3e50;"><?= $display_label ?>:</label>
                
                <?php if ($col == 'sex'): ?>
                    <select name="sex" style="width:100%; padding:10px; border-radius:5px; border:1px solid #bdc3c7;">
                        <option value="M" <?= $val=='M'?'selected':'' ?>>Male</option>
                        <option value="F" <?= $val=='F'?'selected':'' ?>>Female</option>
                    </select>
                <?php else: ?>
                    <input type="text" name="<?= $col ?>" value="<?= htmlspecialchars($val ?? '') ?>" style="width:100%; padding:10px; border-radius:5px; border:1px solid #bdc3c7;" required>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <button type="submit" class="btn-primary" style="width:100%; margin-top:20px; background:#3498db; padding:12px; font-size:16px;">
            <i class="fas fa-sync-alt"></i> Update User Details
        </button>
        <a href="view_users.php?view=<?= $role ?>" style="display:block; text-align:center; margin-top:15px; color:#e74c3c; text-decoration:none;"><i class="fas fa-times-circle"></i> Cancel</a>
    </form>
</div>
<?php include '../includes/footer.php'; ?>