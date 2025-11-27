<?php
require_once __DIR__ . '/../includes/security_init.php';
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dashboard</title>
    <link rel="icon" type="image/png" href="../../assets/image/fevicon.png">
    <link rel="stylesheet" href="../assets/css/loader.css">
    <link rel="stylesheet" href="../assets/css/loading.css">
</head>
<body>
<h2>Welcome, <?php echo $_SESSION['user']; ?>!</h2>
<a href="front-end/logout.php">Logout</a>


<!-- Universal Loader -->
<div id="universalLoader" class="loader-overlay">
    <div class="loader">
        <div class="loader-spinner"></div>
        <div class="loader-text" id="loaderText">Loading...</div>
    </div>
</div>

<script src="../assets/js/loader.js"></script>
<script src="../assets/js/loading.js"></script>
</body>
</html>
