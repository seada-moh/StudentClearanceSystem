<?php
require '../config/db.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'student' && $_SESSION['role'] != 'dormmate')) { header("Location: ../login.php"); exit(); }

$student_id = $conn->real_escape_string($_SESSION['user_id']); 

// ADDED: DELETE NOTIFICATION LOGIC
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    $conn->query("DELETE FROM notifications WHERE id = $del_id AND recipient_id = '$student_id'");
    header("Location: student_notifications.php"); exit();
}

$conn->query("UPDATE notifications SET is_read = 1 WHERE recipient_id = '$student_id'");
$notifs = $conn->query("SELECT * FROM notifications WHERE recipient_id = '$student_id' ORDER BY created_at DESC");

include '../includes/header.php';
?>
<h2><i class="fas fa-bell"></i> My Notifications</h2>

<div class="card" style="max-width: 800px; padding:0; overflow: hidden; border-radius: 8px;">
    <?php if($notifs && $notifs->num_rows > 0): ?>
        <ul style="list-style: none; padding: 0; margin: 0;">
            <?php while($n = $notifs->fetch_assoc()): 
                if($n['type'] == 'success') { $bg = '#d4edda'; $border = '#27ae60'; $icon = 'fa-check-circle'; $icon_color = '#27ae60'; } 
                elseif($n['type'] == 'danger') { $bg = '#f8d7da'; $border = '#c0392b'; $icon = 'fa-times-circle'; $icon_color = '#c0392b'; } 
                else { $bg = '#e8f4f8'; $border = '#3498db'; $icon = 'fa-info-circle'; $icon_color = '#3498db'; }
            ?>
                <li style="padding: 20px; border-bottom: 1px solid #ddd; background: <?= $bg ?>; border-left: 6px solid <?= $border ?>; position: relative;">
                    <!-- NEW DELETE BUTTON -->
                    <a href="?delete_id=<?= $n['id'] ?>" onclick="return confirm('Delete this notification?');" style="position: absolute; right: 20px; top: 20px; color: #e74c3c; font-size: 18px; text-decoration: none;" title="Delete Message"><i class="fas fa-trash-alt"></i></a>
                    
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px; padding-right: 30px;">
                        <strong style="color:<?= $icon_color ?>; font-size: 16px;"><i class="fas <?= $icon ?>"></i> Message From: <?= htmlspecialchars($n['sender_info']) ?></strong>
                        <small style="color:#555; font-weight: bold;"><i class="fas fa-clock"></i> <?= date('M j, Y - g:i A', strtotime($n['created_at'])) ?></small>
                    </div>
                    <div style="font-size: 16px; color: #2c3e50; line-height: 1.6; margin-top:5px; padding-left: 22px; padding-right: 30px;">
                        <?= $n['message'] ?>
                    </div>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <div style="padding: 50px; text-align: center; color: #7f8c8d; background: white;">
            <i class="fas fa-box-open" style="font-size: 60px; margin-bottom: 20px; color: #bdc3c7;"></i>
            <h3 style="margin-bottom: 10px; color: #34495e;">Your Inbox is Empty</h3>
            <p style="font-size: 16px;">Updates about your clearance process will appear here in real-time.</p>
        </div>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>