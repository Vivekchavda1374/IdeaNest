<?php
session_start();
include 'db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['credential'])) {
    echo json_encode(['success' => false, 'message' => 'Missing credential']);
    exit;
}

function decodeJWT($jwt) {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return false;
    $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1]));
    return json_decode($payload, true);
}

$payload = decodeJWT($input['credential']);
if (!$payload || !isset($payload['email'], $payload['name'], $payload['sub'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}
$email = $payload['email'];
$name = $payload['name'];
$google_id = $payload['sub'];

try {
    // Check if user exists with this Google ID
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
        
        echo json_encode(['success' => true, 'redirect' => '../../user/index.php']);
    } else {
        // Check if user exists with this email
        $stmt = $conn->prepare("SELECT id FROM register WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing user with Google ID
            $user = $result->fetch_assoc();
            $stmt = $conn->prepare("UPDATE register SET google_id = ? WHERE id = ?");
            $stmt->bind_param("si", $google_id, $user['id']);
            $stmt->execute();
            
            // Log them in
            $stmt = $conn->prepare("SELECT id, name, enrollment_number FROM register WHERE id = ?");
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            $user_data = $stmt->get_result()->fetch_assoc();
            
            $_SESSION['user_id'] = $user_data['id'];
            $_SESSION['er_number'] = $user_data['enrollment_number'];
            $_SESSION['user_name'] = $user_data['name'];
            $_SESSION['is_admin'] = false;
            
            echo json_encode(['success' => true, 'redirect' => '../../user/index.php']);
        } else {
            // Create new user with Google ID
            $enrollment_number = 'G' . substr($google_id, -8); // Generate ER number from Google ID
            $stmt = $conn->prepare("INSERT INTO register (name, email, enrollment_number, password, google_id) VALUES (?, ?, ?, ?, ?)");
            $dummy_password = password_hash('google_auth_' . $google_id, PASSWORD_DEFAULT);
            $stmt->bind_param("sssss", $name, $email, $enrollment_number, $dummy_password, $google_id);
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                
                $_SESSION['user_id'] = $user_id;
                $_SESSION['er_number'] = $enrollment_number;
                $_SESSION['user_name'] = $name;
                $_SESSION['is_admin'] = false;
                $_SESSION['google_new_user'] = true;
                
                echo json_encode(['success' => true, 'redirect' => '../../user/user_profile_setting.php?google_setup=1']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create user account']);
            }
        }
    }
    $stmt->close();
} catch (Exception $e) {
    error_log('Google Auth Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Authentication failed']);
}

$conn->close();
?>