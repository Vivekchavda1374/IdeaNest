<?php
session_start();
require_once '../Login/Login/db.php';
require_once 'mentor_layout.php';

if (!isset($_SESSION['mentor_id'])) {
    header('Location: ../Login/Login/login.php');
    exit;
}

$student_id = $_GET['student_id'] ?? null;
if (!$student_id) {
    header('Location: dashboard.php');
    exit;
}

$content = '<h2>Student Details</h2><p>Student details for ID: ' . htmlspecialchars($student_id) . '</p>';
renderLayout('Student Details', $content);
?>