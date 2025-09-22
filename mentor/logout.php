<?php
session_start();
session_destroy();
header('Location: ../Login/Login/login.php');
exit;
?>