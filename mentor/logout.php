<?php
require_once __DIR__ . '/../includes/security_init.php';
session_start();
$_SESSION = array();
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}
session_destroy();
header('Location: ../Login/Login/login.php?logout=success');
exit();