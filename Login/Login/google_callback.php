<?php
require_once __DIR__ . '/../../includes/security_init.php';
session_start();
include 'db.php';
require_once 'google_config.php';

// Handle OAuth code flow (from manual button)
if (isset($_GET['code'])) {
    // Exchange authorization code for access token
    $code = $_GET['code'];
    
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $postData = [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];
    
    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    $response = curl_exec($ch);
    curl_close($ch);
    
    $tokenData = json_decode($response, true);
    
    if (isset($tokenData['access_token'])) {
        // Get user info
        $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
        $ch = curl_init($userInfoUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $tokenData['access_token']
        ]);
        $userInfoResponse = curl_exec($ch);
        curl_close($ch);
        
        $userInfo = json_decode($userInfoResponse, true);
        
        if (isset($userInfo['email'])) {
            $email = $userInfo['email'];
            $name = $userInfo['name'] ?? $email;
            $google_id = $userInfo['id'];
            
            // Process login (same as JWT flow below)
            processGoogleLogin($conn, $email, $name, $google_id);
            exit;
        }
    }
    
    // If we get here, something failed
    header('Location: login.php?error=google_auth_failed');
    exit;
}

// Handle JWT credential flow (from Google One Tap)
// Set proper headers for AJAX
if (isset($_SERVER['HTTP_CONTENT_TYPE']) && $_SERVER['HTTP_CONTENT_TYPE'] === 'application/json') {
    header('Content-Type: application/json');
}

// Get JSON input for AJAX requests
$input = json_decode(file_get_contents('php://input'), true);
$credential = $input['credential'] ?? $_POST['credential'] ?? null;

if (!$credential) {
    if (isset($_SERVER['HTTP_CONTENT_TYPE']) && $_SERVER['HTTP_CONTENT_TYPE'] === 'application/json') {
        echo json_encode(['success' => false, 'message' => 'No credential provided']);
    } else {
        header('Location: login.php?error=no_credential');
    }
    exit;
}

function decodeJWT($jwt)
{
    $parts = explode('.', $jwt);
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
    return $payload;
}

$payload = decodeJWT($credential);

$email = $payload['email'];
$name = $payload['name'];
$google_id = $payload['sub'];

processGoogleLogin($conn, $email, $name, $google_id);

// Function to process Google login (used by both JWT and OAuth flows)
function processGoogleLogin($conn, $email, $name, $google_id) {
    $isAjax = isset($_SERVER['HTTP_CONTENT_TYPE']) && $_SERVER['HTTP_CONTENT_TYPE'] === 'application/json';
    
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

            if ($isAjax) {
                echo json_encode(['success' => true, 'redirect' => '../../user/index.php']);
            } else {
                header('Location: ../../user/index.php');
            }
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
                
                if ($isAjax) {
                    echo json_encode(['success' => true, 'redirect' => '../../user/index.php']);
                } else {
                    header('Location: ../../user/index.php');
                }
            } else {
                // Create new user with Google data
                $role = 'student';
                $enrollment_number = 'G' . substr($google_id, -8);
                $gr_number = $enrollment_number;
                $about = 'New student at IdeaNest';
                $passout_year = date('Y') + 4;
                
                $stmt = $conn->prepare("INSERT INTO register (name, email, enrollment_number, gr_number, password, about, passout_year, google_id, email_notifications, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)");
                $dummy_password = password_hash('google_auth_' . $google_id, PASSWORD_DEFAULT);
                $stmt->bind_param("sssssssss", $name, $email, $enrollment_number, $gr_number, $dummy_password, $about, $passout_year, $google_id, $role);

                if ($stmt->execute()) {
                    $user_id = $conn->insert_id;
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['er_number'] = $enrollment_number;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['is_admin'] = false;
                    $_SESSION['google_new_user'] = true;
                    $_SESSION['google_email'] = $email;

                    error_log("New Google user created: ID=$user_id, Email=$email");

                    if ($isAjax) {
                        echo json_encode(['success' => true, 'redirect' => '../../user/user_profile_setting.php?google_setup=1']);
                    } else {
                        header('Location: ../../user/user_profile_setting.php?google_setup=1');
                    }
                } else {
                    error_log('Failed to create Google user: ' . $stmt->error);
                    if ($isAjax) {
                        echo json_encode(['success' => false, 'message' => 'Failed to create user account']);
                    } else {
                        header('Location: login.php?error=create_failed');
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log('Google OAuth error: ' . $e->getMessage());
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => 'Authentication failed. Please try again.']);
        } else {
            header('Location: login.php?error=auth_failed');
        }
    }
}
