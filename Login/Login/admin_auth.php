<?php
// admin_auth.php - Include this at the top of all admin pages
session_start();


function verifyAdminAccess() {
// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
// Not logged in as admin, redirect to login page
header("Location: ../Login/Login/login.php");
exit();
}

// Check for session expiration (2 hours)
$session_duration = 7200; // 2 hours in seconds
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $session_duration)) {
// Session expired
session_unset();
session_destroy();
header("Location: ../Login/Login/login.php?expired=1");
exit();
}

// Verify admin token exists
if (!isset($_SESSION['admin_token'])) {
session_unset();
session_destroy();
header("Location: ../Login/Login/login.php?error=security");
exit();
}
}

// Run verification
verifyAdminAccess();
?>