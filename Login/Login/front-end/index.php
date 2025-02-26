<!DOCTYPE html>
<html lang="en">
<head>
    <title>User Authentication</title>
</head>
<body>
<h2>Login</h2>
<form action="../back-end/Login.php" method="POST">
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>
</form>

<h2>Register</h2>
<form action="../back-end/register.php" method="POST">
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Register</button>
</form>
</body>
</html>
