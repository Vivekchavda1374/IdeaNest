<?php
session_start();
require_once '../Login/Login/db.php';
require_once 'mentor_layout.php';

if (!isset($_SESSION['mentor_id'])) {
    header('Location: ../Login/Login/login.php');
    exit;
}

$pair_id = $_GET['pair_id'] ?? null;
if (!$pair_id) {
    header('Location: dashboard.php');
    exit;
}

$content = '<h2>Student Progress</h2><p>Student progress tracking for pair ID: ' . htmlspecialchars($pair_id) . '</p>';
renderLayout('Student Progress', $content);
?>