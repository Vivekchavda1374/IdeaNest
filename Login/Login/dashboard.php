<?php
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
</head>
<body>
<h2>Welcome, <?php echo $_SESSION['user']; ?>!</h2>
<a href="front-end/logout.php">Logout</a>

</body>
</html>
