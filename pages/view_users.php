<?php
require '../config/db.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { header("Location: ../index.php"); exit(); }

$msg = "";
$tables_map =[
    'student'=>'students', 'proctor'=>'proctors', 
    'librarian'=>'librarians', 'cafeteria'=>'cafeterias', 'student_service'=>'student_services', 
    'dept_head'=>'dept_heads', 'registrar'=>'registrars'
];

$id_columns =[
    'student'=>'student_id', 'proctor'=>'proctor_id', 
    'librarian'=>'librarian_id', 'cafeteria'=>'cafeteria_id', 'student_service'=>'student_service_id', 
    'dept_head'=>'dept_head_id', 'registrar'=>'registrar_id'
];

$view_role = $_GET['view'] ?? 'student';
$tab = $_GET['tab'] ?? 'active';

// Fallback in case an invalid role is passed
if (!array_key_exists($view_role, $tables_map)) {
    $view_role = 'student';
}

$current_table = $tables_map[$view_role];
$current_id_col = $id_columns[$view_role];

// --- ACTIONS LOGIC ---
if (isset($_GET['action']) && isset($_GET['target_id'])) {
    $target_id = $conn->real_escape_string($_GET['target_id']);
    $action = $_GET['action'];
    
    if ($action == 'soft_del') {
        // Move to Recycle Bin
        $conn->query("UPDATE $current_table SET is_deleted = 1 WHERE $current_id_col = '$target_id'");
        $conn->query("INSERT INTO activity_logs (action, role) VALUES ('deleted', '$view_role')");
        $msg = "<div class='alert success' style='background:#d4edda; color:#155724; padding:15px; border-radius:5px; margin-bottom:15px;'><i class='fas fa-trash'></i> User moved to Recycle Bin!</div>";
    
    } elseif ($action == 'recover') {
        // Recover from Recycle Bin
        $conn->query("UPDATE $current_table SET is_deleted = 0 WHERE $current_id_col = '$target_id'");
        $conn->query("INSERT INTO activity_logs (action, role) VALUES ('added', '$view_role')");
        $msg = "<div class='alert success' style='background:#d4edda; color:#155724; padding:15px; border-radius:5px; margin-bottom:15px;'><i class='fas fa-undo'></i> User Recovered Successfully!</div>";
    
    } elseif ($action == 'hard_del') {
        // Permanently Delete
        $conn->query("DELETE FROM $current_table WHERE $current_id_col = '$target_id'");
        $msg = "<div class='alert error' style='background:#f8d7da; color:#721c24; padding:15px; border-radius:5px; margin-bottom:15px;'><i class='fas fa-fire'></i> User Permanently Erased from Database!</div>";
    }
}

// --- FETCH DATA LOGIC ---
// This is the fix! It correctly sets the flag to 0 (Active) or 1 (Deleted)
$is_deleted_status = ($tab == 'deleted') ? 1 : 0;

$query = "SELECT * FROM $current_table WHERE is_deleted = $is_deleted_status ORDER BY $current_id_col ASC";
$users_list = $conn->query($query);

include '../includes/header.php';
?>
<style>
    .tab-btn { padding: 12px 25px; text-decoration: none; font-weight: bold; border-radius: 8px 8px 0 0; display: inline-block; transition: 0.3s; font-size: 15px; margin-right: 5px; }
    .tab-active { background: #34495e; color: white; border: 1px solid #34495e; border-bottom: none; }
    .tab-inactive { background: #ecf0f1; color: #7f8c8d; border: 1px solid #bdc3c7; border-bottom: none; }
    .tab-inactive:hover { background: #dfe6e9; color: #2c3e50; }
</style>

<h2><i class="fas fa-users-cog"></i> Manage System Users</h2>
<?= $msg ?>

<div class="card" style="padding-top: 15px;">
    
    <!-- TABS -->
    <div style="border-bottom: 4px solid #34495e; margin-bottom: 25px; padding-left: 10px;">
        <a href="?view=<?= $view_role ?>&tab=active" class="tab-btn <?= ($tab=='active')?'tab-active':'tab-inactive' ?>">
            <i class="fas fa-user-check"></i> Active Users
        </a>
        <a href="?view=<?= $view_role ?>&tab=deleted" class="tab-btn <?= ($tab=='deleted')?'tab-active':'tab-inactive' ?>">
            <i class="fas fa-trash-alt"></i> Recycle Bin (Deleted)
        </a>
    </div>

    <form method="GET" style="margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
        <label style="font-weight: bold; color: #2c3e50; font-size: 16px;">Select Department: </label>
        <select name="view" onchange="this.form.submit()" style="padding:10px 15px; width:250px; border-radius:5px; border:2px solid #bdc3c7; font-weight:bold; font-size:15px; outline:none; background:#f9f9f9;">
            <?php foreach($tables_map as $r => $t): ?>
                <option value="<?= $r ?>" <?= ($view_role == $r) ? 'selected' : '' ?>><?= ucwords(str_replace('_',' ',$r)) ?>s</option>
            <?php endforeach; ?>
        </select>
    </form>

    <div class="table-responsive">
        <table class="table">
            <tr style="background: #2c3e50; color: white;">
                <th>ID</th>
                <th>Name</th>
                <th>Sex</th>
                <th>Details / Contact</th>
                <th>Action</th>
            </tr>
            
            <?php if($users_list && $users_list->num_rows > 0): while($u = $users_list->fetch_assoc()): 
                $disp_id = $u[$current_id_col]; 
            ?>
            <tr style="<?= ($tab == 'deleted') ? 'background: #fdf5f6;' : 'transition: 0.3s;' ?>" onmouseover="this.style.background='#f1f4f8'" onmouseout="this.style.background='<?= ($tab == 'deleted') ? '#fdf5f6' : 'white' ?>'">
                
                <td style="font-family: monospace; font-size: 15px;"><b><?= htmlspecialchars($disp_id) ?></b></td>
                
                <td style="font-weight: bold; color: #34495e;">
                    <?= htmlspecialchars($u['fname'] ?? $u['sname'] . " " . ($u['lname'] ?? $u['fathername'])) ?>
                </td>
                
                <td><span style="background:#ecf0f1; padding:3px 8px; border-radius:3px; font-weight:bold;"><?= $u['sex'] ?></span></td>
                
                <td style="color: #7f8c8d;">
                    <?php if ($view_role == 'student' || $view_role == 'proctor'): ?>
                        <i class="fas fa-cubes"></i> Block: <b><?= htmlspecialchars($u['block_no']) ?></b> 
                    <?php endif; ?>
                    
                    <?php if ($view_role == 'student'): ?>
                        | <i class="fas fa-door-closed"></i> Dorm: <b><?= htmlspecialchars($u['dorm_no']) ?></b> 
                    <?php endif; ?>
                    
                    <br><i class="fas fa-envelope" style="color:#bdc3c7;"></i> <?= htmlspecialchars($u['email']) ?>
                </td>
                
                <td style="white-space: nowrap;">
                    <?php if($tab == 'active'): ?>
                        <!-- ACTIVE TAB ACTIONS -->
                        <a href="edit_user.php?role=<?= urlencode($view_role) ?>&id=<?= urlencode($disp_id) ?>" style="background:#3498db; color:white; text-decoration:none; padding:8px 12px; border-radius:4px; margin-right:5px; font-weight:bold; font-size:13px; display:inline-block;"><i class="fas fa-sync-alt"></i> Update</a>
                        
                        <a href="?view=<?= urlencode($view_role) ?>&tab=active&action=soft_del&target_id=<?= urlencode($disp_id) ?>" style="background:#e74c3c; color:white; text-decoration:none; padding:8px 12px; border-radius:4px; font-weight:bold; font-size:13px; display:inline-block;" onclick="return confirm('Move this user to the Recycle Bin?');"><i class="fas fa-trash"></i> Delete</a>
                    
                    <?php else: ?>
                        <!-- DELETED TAB ACTIONS -->
                        <a href="?view=<?= urlencode($view_role) ?>&tab=deleted&action=recover&target_id=<?= urlencode($disp_id) ?>" style="background:#27ae60; color:white; text-decoration:none; padding:8px 12px; border-radius:4px; margin-right:5px; font-weight:bold; font-size:13px; display:inline-block;"><i class="fas fa-undo"></i> Recover</a>
                        
                        <a href="?view=<?= urlencode($view_role) ?>&tab=deleted&action=hard_del&target_id=<?= urlencode($disp_id) ?>" style="background:#c0392b; color:white; text-decoration:none; padding:8px 12px; border-radius:4px; font-weight:bold; font-size:13px; display:inline-block;" onclick="return confirm('WARNING: This will permanently erase the user from the database. This cannot be undone. Are you sure?');"><i class="fas fa-fire"></i> Perm Delete</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; else: ?>
                <tr>
                    <td colspan='5' style='text-align:center; padding:50px; color:#bdc3c7;'>
                        <i class='fas fa-folder-open' style='font-size:50px; margin-bottom:15px; display:block;'></i> 
                        <span style="font-size: 18px; color: #7f8c8d;">Nothing found here.</span>
                    </td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>