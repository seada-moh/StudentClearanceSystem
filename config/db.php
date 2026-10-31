<?php
// --- 1. STRICT COOKIE SECURITY ---
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

// --- 2. START SESSION ---
if (session_status() == PHP_SESSION_NONE) { 
    session_start(); 
}

// --- 3. ANTI-SESSION HIJACKING (FINGERPRINTING) ---
// We create a unique "Fingerprint" combining the user's IP Address and their Browser (User-Agent)
$current_fingerprint = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);

if (isset($_SESSION['user_id'])) {
    // If they are logged in, check if they have a fingerprint saved
    if (!isset($_SESSION['fingerprint'])) {
        // First time logging in: Save their fingerprint
        $_SESSION['fingerprint'] = $current_fingerprint;
    } else {
        // If they are moving around the site, check if their fingerprint changed!
        if ($_SESSION['fingerprint'] !== $current_fingerprint) {
            // HACKER DETECTED! (Someone copied the cookie to a different browser/computer)
            session_unset();
            session_destroy();
            
            // If they are in the 'pages' folder, go up one directory to login.php
            $redirect_path = (basename(dirname($_SERVER['PHP_SELF'])) == 'pages') ? '../login.php' : 'login.php';
            header("Location: " . $redirect_path);
            exit();
        }
    }
}

// --- 4. DATABASE CONNECTION ---
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'dtuscms';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>