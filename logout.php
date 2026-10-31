<?php
// Start the session to access the current session data
session_start();

// 1. Empty all session variables
$_SESSION = array();

// 2. Destroy the physical cookie stored in the user's web browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy the session memory on the server
session_destroy();

// 4. Send them back to the secure login page
header("Location: login.php");
exit();
?>