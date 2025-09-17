<?php
session_start();
require_once '../Login/Login/db.php';

if (!isset($_SESSION['mentor_id'])) {
    header('Location: ../Login/Login/login.php');
    exit;
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="mentor_data.csv"');

echo "Export functionality coming soon...";
?>