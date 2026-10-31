<?php
require '../config/db.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }

$my_id = $conn->real_escape_string($_SESSION['user_id']);
$my_name = $conn->real_escape_string($_SESSION['name']);
$my_role = $_SESSION['role'];
$msg = "";

// SEND MESSAGE LOGIC
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $message = $conn->real_escape_string(trim($_POST['message']));
    $target_ids = [];

    // ROUTING LOGIC based on sender's role
    if ($my_role == 'student' || $my_role == 'dormmate') {
        $target_role = $_POST['target_staff_role'];
        
        // Smart Routing: Send to specific staff related to student, or all staff of that role
        if ($target_role == 'proctor') {
            $block = (int)$_SESSION['block_no'];
            $q = $conn->query("SELECT proctor_id AS id FROM proctors WHERE block_no = $block AND is_deleted=0");
            while($row = $q->fetch_assoc()) $target_ids[] = $row['id'];
        } elseif ($target_role == 'dept_head') {
            $dept = $conn->real_escape_string($_SESSION['department']);
            $q = $conn->query("SELECT dept_head_id AS id FROM dept_heads WHERE department = '$dept' AND is_deleted=0");
            while($row = $q->fetch_assoc()) $target_ids[] = $row['id'];
        } else {
            // For Librarian, Cafeteria, etc., send to all in that department
            $tbl = ($target_role == 'student_service') ? 'student_services' : $target_role . 's';
            $col = $target_role . "_id";
            $q = $conn->query("SELECT $col AS id FROM $tbl WHERE is_deleted=0");
            while($row = $q->fetch_assoc()) $target_ids[] = $row['id'];
        }
    } else {
        // SENDER IS STAFF
        $send_type = $_POST['staff_send_type'];
        if ($send_type == 'to_student') {
            $target_ids[] = $conn->real_escape_string(trim($_POST['student_id']));
        } else {
            $target_role = $_POST['target_staff_role'];
            $tbl = ($target_role == 'student_service') ? 'student_services' : $target_role . 's';
            $col = $target_role . "_id";
            $q = $conn->query("SELECT $col AS id FROM $tbl WHERE is_deleted=0");
            while($row = $q->fetch_assoc()) $target_ids[] = $row['id'];
        }
    }

    if (count($target_ids) > 0) {
        $success_count = 0;
        foreach ($target_ids as $t_id) {
            $stmt = $conn->prepare("INSERT INTO chat_messages (sender_id, sender_name, recipient_id, message) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $my_id, $my_name, $t_id, $message);
            if($stmt->execute()) $success_count++;
        }
        if($success_count > 0) $msg = "<div class='alert success' style='background:#d4edda; color:#155724; padding:15px; border-radius:5px; margin-bottom:20px;'><i class='fas fa-check-circle'></i> Message successfully delivered!</div>";
    } else {
        $msg = "<div class='alert error' style='background:#f8d7da; color:#721c24; padding:15px; border-radius:5px; margin-bottom:20px;'><i class='fas fa-exclamation-triangle'></i> Error: Recipient not found or unavailable.</div>";
    }
}

// DELETE MESSAGE LOGIC
if (isset($_GET['delete_msg'])) {
    $del_id = (int)$_GET['delete_msg'];
    $conn->query("DELETE FROM chat_messages WHERE id = $del_id AND recipient_id = '$my_id'");
    header("Location: chat.php"); exit();
}

$conn->query("UPDATE chat_messages SET is_read = 1 WHERE recipient_id = '$my_id'");
$inbox = $conn->query("SELECT * FROM chat_messages WHERE recipient_id = '$my_id' ORDER BY sent_at DESC");

include '../includes/header.php';
?>

<h2><i class="fas fa-envelope"></i> Internal Messaging System</h2>
<?= $msg ?>

<div class="card" style="margin-bottom: 30px; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border-top: 4px solid #3498db;">
    <h3 style="margin-top:0; color:#2c3e50;"><i class="fas fa-paper-plane"></i> Send a Direct Message</h3>
    
    <form method="POST" style="display: flex; flex-direction: column; gap: 15px;">
        <input type="hidden" name="send_message" value="1">
        
        <?php if ($my_role == 'student' || $my_role == 'dormmate'): ?>
            <div>
                <label style="font-weight:bold; color:#34495e;">Select Office to Contact:</label>
                <select name="target_staff_role" required style="width:100%; padding:12px; border:1px solid #bdc3c7; border-radius:5px; margin-top:5px; font-size:15px; outline:none;">
                    <option value="proctor">My Block Proctor</option>
                    <option value="librarian">Library Office</option>
                    <option value="cafeteria">Cafeteria Office</option>
                    <option value="student_service">Student Services</option>
                    <option value="dept_head">My Department Head</option>
                    <option value="registrar">Registrar Office</option>
                </select>
            </div>
        <?php else: ?>
            <div>
                <label style="font-weight:bold; color:#34495e;">Send Message To:</label>
                <select name="staff_send_type" id="staffSendType" onchange="toggleStaffChat()" required style="width:100%; padding:12px; border:1px solid #bdc3c7; border-radius:5px; margin-top:5px; font-size:15px; outline:none;">
                    <option value="to_student">A Specific Student (By ID)</option>
                    <option value="to_staff">Another Staff Office</option>
                </select>
            </div>
            
            <div id="studentIdField">
                <label style="font-weight:bold; color:#34495e;">Student ID Number:</label>
                <input type="text" name="student_id" placeholder="E.g., DTU1234" style="width:100%; padding:12px; border:1px solid #bdc3c7; border-radius:5px; margin-top:5px; font-size:15px;">
            </div>
            
            <div id="staffRoleField" style="display:none;">
                <label style="font-weight:bold; color:#34495e;">Select Staff Office:</label>
                <select name="target_staff_role" style="width:100%; padding:12px; border:1px solid #bdc3c7; border-radius:5px; margin-top:5px; font-size:15px; outline:none;">
                    <option value="proctor">Proctors</option>
                    <option value="librarian">Librarians</option>
                    <option value="cafeteria">Cafeteria Staff</option>
                    <option value="student_service">Student Services</option>
                    <option value="dept_head">Department Heads</option>
                    <option value="registrar">Registrars</option>
                </select>
            </div>
            
            <script>
                function toggleStaffChat() {
                    let type = document.getElementById('staffSendType').value;
                    if(type === 'to_student') {
                        document.getElementById('studentIdField').style.display = 'block';
                        document.getElementById('staffRoleField').style.display = 'none';
                    } else {
                        document.getElementById('studentIdField').style.display = 'none';
                        document.getElementById('staffRoleField').style.display = 'block';
                    }
                }
            </script>
        <?php endif; ?>

        <div>
            <label style="font-weight:bold; color:#34495e;">Your Message:</label>
            <textarea name="message" rows="4" required placeholder="Type your message..." style="width:100%; padding:12px; border:1px solid #bdc3c7; border-radius:5px; margin-top:5px; font-family:sans-serif; font-size:15px; resize:vertical;"></textarea>
        </div>
        
        <button type="submit" class="btn-primary" style="background:#3498db; color:white; padding:12px 20px; border:none; border-radius:5px; font-weight:bold; cursor:pointer; font-size:16px; align-self: flex-start;">
            <i class="fas fa-paper-plane"></i> Send
        </button>
    </form>
</div>

<!-- MY INBOX -->
<div class="card" style="padding: 0; overflow:hidden;">
    <h3 style="margin:0; background:#f4f7f6; padding:20px; color:#2c3e50; border-bottom:1px solid #ddd;"><i class="fas fa-inbox"></i> My Inbox</h3>
    
    <?php if($inbox && $inbox->num_rows > 0): ?>
        <ul style="list-style:none; padding:0; margin:0;">
            <?php while($m = $inbox->fetch_assoc()): ?>
                <li style="padding: 20px; border-bottom: 1px solid #eee; position: relative; background: #fff; transition:0.3s;" onmouseover="this.style.background='#fdfdfd'" onmouseout="this.style.background='#fff'">
                    <a href="?delete_msg=<?= $m['id'] ?>" onclick="return confirm('Delete this message permanently?');" style="position: absolute; right: 20px; top: 20px; color: #e74c3c; font-size: 18px; text-decoration: none;" title="Delete Message"><i class="fas fa-trash-alt"></i></a>
                    <div style="margin-bottom:8px; padding-right: 30px;">
                        <strong style="color:#2c3e50; font-size: 16px;">
                            <i class="fas fa-user-circle" style="color:#3498db; margin-right:5px;"></i> <?= htmlspecialchars($m['sender_name']) ?> <span style="color:#7f8c8d; font-size:14px; font-weight:normal;">(ID: <?= htmlspecialchars($m['sender_id']) ?>)</span>
                        </strong>
                        <div style="color:#95a5a6; font-size:13px; margin-top:5px;"><i class="fas fa-clock"></i> <?= date('M j, Y - g:i A', strtotime($m['sent_at'])) ?></div>
                    </div>
                    <div style="font-size: 16px; color: #34495e; line-height: 1.6; margin-top:10px; padding: 15px; background: #f8f9fa; border-radius: 5px; border-left: 4px solid #3498db;">
                        <?= nl2br(htmlspecialchars($m['message'])) ?>
                    </div>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <div style="padding: 50px; text-align: center; color: #7f8c8d;">
            <i class="far fa-comments" style="font-size: 60px; margin-bottom: 20px; color: #bdc3c7;"></i>
            <h3 style="margin-bottom: 10px; color: #34495e;">No Messages Received</h3>
        </div>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>