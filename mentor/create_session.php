<?php
session_start();
require_once '../Login/Login/db.php';
require_once 'mentor_layout.php';

if (!isset($_SESSION['mentor_id'])) {
    header('Location: ../Login/Login/login.php');
    exit;
}

$content = '<h2>Create Session</h2><p>Create new mentoring session page coming soon...</p>';
renderLayout('Create Session', $content);
?>