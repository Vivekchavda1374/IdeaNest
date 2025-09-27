<?php

session_start();
include 'db.php';
require_once 'google_config.php';

// Set proper headers for production
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://ictmu.in');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get JSON input for AJAX requests
$input = json_decode(file_get_contents('php://input'), true);
$credential = $input['credential'] ?? $_POST['credential'] ?? null;

if (!$credential) {
    echo json_encode(['success' => false, 'message' => 'No credential provided']);
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

        echo json_encode([
            'success' => true,
            'redirect' => '../../user/index.php'
        ]);
    } else {
        // Check if user exists with email
        $stmt = $conn->prepare("SELECT id, name, enrollment_number FROM register WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing user with Google ID
            $user = $result->fetch_assoc();
            $update_stmt = $conn->prepare("UPDATE register SET google_id = ? WHERE id = ?");
            $update_stmt->bind_param("si", $google_id, $user['id']);
            $update_stmt->execute();
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['er_number'] = $user['enrollment_number'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['is_admin'] = false;
            
            echo json_encode([
                'success' => true,
                'redirect' => '../../user/index.php'
            ]);
        } else {
            // Create new user with Google data
            $stmt = $conn->prepare("INSERT INTO register (name, email, password, google_id) VALUES (?, ?, ?, ?)");
            $dummy_password = password_hash('google_auth_' . $google_id, PASSWORD_DEFAULT);
            $stmt->bind_param("ssss", $name, $email, $dummy_password, $google_id);

            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                $_SESSION['user_id'] = $user_id;
                $_SESSION['er_number'] = '';
                $_SESSION['user_name'] = $name;
                $_SESSION['is_admin'] = false;
                $_SESSION['google_new_user'] = true;

                echo json_encode([
                    'success' => true,
                    'redirect' => '../../user/user_profile_setting.php?google_setup=1'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to create user account'
                ]);
            }
        }
    }
} catch (Exception $e) {
    error_log('Google OAuth error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Authentication failed. Please try again.'
    ]);
}
