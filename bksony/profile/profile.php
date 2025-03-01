<?php
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ideanest";

// Start session to maintain user login status
session_start();

// Check if user is logged in, redirect to login page if not
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables for user data
$name = $email = $er_number = $gr_number = "";
$update_message = "";

// Fetch user data from database
$sql = "SELECT * FROM register WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $name = $row["name"];
    $email = $row["email"];
    $er_number = $row["er_number"];
    $gr_number = $row["gr_number"];
}

// Process form submission for profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input data
    $new_name = trim($_POST["name"]);
    $new_email = trim($_POST["email"]);
    $new_er_number = trim($_POST["er_number"]);
    $new_gr_number = trim($_POST["gr_number"]);
    $new_password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    
    // Simple validation
    $valid = true;
    
    if (empty($new_name)) {
        $valid = false;
        $update_message = "Name cannot be empty.";
    } elseif (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $valid = false;
        $update_message = "Please enter a valid email address.";
    }
    
    // Check if email already exists (if email changed)
    if ($valid && $new_email != $email) {
        $check_email = "SELECT id FROM register WHERE email = ? AND id != ?";
        $stmt_check = $conn->prepare($check_email);
        $stmt_check->bind_param("si", $new_email, $user_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $valid = false;
            $update_message = "Email already in use. Please choose another email.";
        }
    }
    
    // If password provided, validate and check matching
    $password_update = false;
    if (!empty($new_password) || !empty($confirm_password)) {
        if (strlen($new_password) < 8) {
            $valid = false;
            $update_message = "Password must be at least 8 characters long.";
        } elseif ($new_password != $confirm_password) {
            $valid = false;
            $update_message = "Passwords do not match.";
        } else {
            $password_update = true;
        }
    }
    
    // If validation passes, update profile
    if ($valid) {
        if ($password_update) {
            // Update profile with new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE register SET name = ?, email = ?, er_number = ?, gr_number = ?, password = ? WHERE id = ?";
            $stmt_update = $conn->prepare($update_sql);
            $stmt_update->bind_param("sssssi", $new_name, $new_email, $new_er_number, $new_gr_number, $hashed_password, $user_id);
        } else {
            // Update profile without changing password
            $update_sql = "UPDATE register SET name = ?, email = ?, er_number = ?, gr_number = ? WHERE id = ?";
            $stmt_update = $conn->prepare($update_sql);
            $stmt_update->bind_param("ssssi", $new_name, $new_email, $new_er_number, $new_gr_number, $user_id);
        }
        
        if ($stmt_update->execute()) {
            $update_message = "Profile updated successfully!";
            // Update local variables with new values
            $name = $new_name;
            $email = $new_email;
            $er_number = $new_er_number;
            $gr_number = $new_gr_number;
        } else {
            $update_message = "Error updating profile: " . $conn->error;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    .profile-section {
        background-color: #f8f9fa;
        border-radius: 10px;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .profile-header {
        background-color: #0d6efd;
        color: white;
        padding: 20px;
        border-radius: 10px 10px 0 0;
    }

    .btn-primary {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }

    .btn-primary:hover {
        background-color: #0b5ed7;
        border-color: #0a58ca;
    }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="profile-section">
                    <div class="profile-header">
                        <div class="text-center">
                            <i class="fas fa-user-circle fa-5x mb-3"></i>
                            <h2><?php echo htmlspecialchars($name); ?></h2>
                            <p class="mb-0"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($email); ?>
                            </p>
                        </div>
                    </div>

                    <div class="p-4">
                        <?php if (!empty($update_message)): ?>
                        <div
                            class="alert <?php echo strpos($update_message, 'successfully') !== false ? 'alert-success' : 'alert-danger'; ?>">
                            <?php echo $update_message; ?>
                        </div>
                        <?php endif; ?>

                        <h3 class="mb-4 border-bottom pb-2">Edit Profile</h3>

                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="<?php echo htmlspecialchars($name); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="er_number" class="form-label">ER Number</label>
                                    <input type="text" class="form-control" id="er_number" name="er_number"
                                        value="<?php echo htmlspecialchars($er_number); ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="gr_number" class="form-label">GR Number</label>
                                    <input type="text" class="form-control" id="gr_number" name="gr_number"
                                        value="<?php echo htmlspecialchars($gr_number); ?>">
                                </div>
                            </div>

                            <h4 class="mt-4 mb-3">Change Password</h4>
                            <p class="text-muted small">Leave blank if you don't want to change your password</p>

                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="password" name="password">
                                <div class="form-text">Password must be at least 8 characters long</div>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password"
                                    name="confirm_password">
                            </div>

                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">Update Profile</button>
                                <a href="../dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>