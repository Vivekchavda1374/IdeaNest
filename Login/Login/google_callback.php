<?php

session_start();
include 'db.php';

if (!isset($_POST['credential'])) {
    header("Location: login.php?error=1");
    exit;
}

function decodeJWT($jwt)
{
    $parts = explode('.', $jwt);
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
    return $payload;
}

$credential = $_POST['credential'];
$payload = decodeJWT($credential);

$email = $payload['email'];
$name = $payload['name'];
$google_id = $payload['sub'];

try {
    // Check if user exists with Google ID
    $stmt = $conn->prepare("SELECT id, name, enrollment_number FROM register WHERE google_id = ?");
    $stmt->bind_param("s", $google_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User exists, log them in
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['er_number'] = $user['enrollment_number'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['is_admin'] = false;

        header("Location: ../../user/index.php");
    } else {
        // Create new user with only name and email
        $stmt = $conn->prepare("INSERT INTO register (name, email, password) VALUES (?, ?, ?)");
        $dummy_password = password_hash('google_auth_' . $google_id, PASSWORD_DEFAULT);
        $stmt->bind_param("sss", $name, $email, $dummy_password);

        if ($stmt->execute()) {
            $user_id = $conn->insert_id;
            $_SESSION['user_id'] = $user_id;
            $_SESSION['er_number'] = '';
            $_SESSION['user_name'] = $name;
            $_SESSION['is_admin'] = false;
            $_SESSION['google_new_user'] = true;

            header("Location: ../../user/user_profile_setting.php?google_setup=1");
        } else {
            header("Location: login.php?error=2");
        }
    }
} catch (Exception $e) {
    header("Location: login.php?error=3");
}
