<?php
// Start session at the beginning of the script
session_start();

// Check if user is logged in, redirect if not
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ideanest";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user ID from session
$session_id = $_SESSION['user_id'];

// Initialize variables
$user_name = "";

$about = "";
$user_image = "";
$project_count = 0;
$idea_count = 0;
$message = "";
$messageType = "";
$er_number = "";

// Fetch user details from register table using session_id
$sql_user = "SELECT name,  about, user_image , 	enrollment_number FROM register WHERE id = '$session_id'";
$result_user = $conn->query($sql_user);

if ($result_user && $result_user->num_rows > 0) {
    $user_data = $result_user->fetch_assoc();
    $user_name = $user_data['name'];
    $about = $user_data['about'];
    $user_image = $user_data['user_image'];
    $er_number = $user_data['enrollment_number'];

}

// Count projects by this user
$sql_projects = "SELECT COUNT(*) as project_count FROM projects WHERE user_id = '$session_id'";
$result_projects = $conn->query($sql_projects);

if ($result_projects && $result_projects->num_rows > 0) {
    $project_data = $result_projects->fetch_assoc();
    $project_count = $project_data['project_count'];
}


// Count blog posts (ideas) by this user
$sql_ideas = "SELECT COUNT(*) as idea_count FROM blog WHERE id = '$session_id'";
$result_ideas = $conn->query($sql_ideas);

if ($result_ideas && $result_ideas->num_rows > 0) {
    $idea_data = $result_ideas->fetch_assoc();
    $idea_count = $idea_data['idea_count'];
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle about update
    if (isset($_POST['update_about'])) {
        $new_about = $_POST['about'];
        
        $update_sql = "UPDATE register SET about = '$new_about' WHERE id = '$session_id'";
        
        if ($conn->query($update_sql) === TRUE) {
            $about = $new_about;
            $message = "About section updated successfully!";
            $messageType = "success";
        } else {
            $message = "Error updating about section: " . $conn->error;
            $messageType = "danger";
        }
    }
    
    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['size'] > 0) {
        $target_dir = "uploads/profile_images/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION);
        $new_filename = "user_" . $session_id . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Check if file is an actual image
        $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
        if($check !== false) {
            // Upload file
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                // Update database with new image path
                $update_img_sql = "UPDATE register SET user_image = '$target_file' WHERE id = '$session_id'";
                
                if ($conn->query($update_img_sql) === TRUE) {
                    $user_image = $target_file;
                    $message = "Profile image updated successfully!";
                    $messageType = "success";
                } else {
                    $message = "Error updating profile image in database: " . $conn->error;
                    $messageType = "danger";
                }
            } else {
                $message = "Sorry, there was an error uploading your file.";
                $messageType = "danger";
            }
        } else {
            $message = "File is not an image.";
            $messageType = "danger";
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    body {
        background-color: rgb(220, 222, 225);
    }

    .profile-container {
        max-width: 800px;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        margin-top: 40px;
        transition: 0.5s;
    }

    .profile-container:hover {
        box-shadow: 0px 1px 10px 1px rgba(0, 0, 0, 0.2);
    }

    .profile-header {
        display: flex;
        align-items: center;
        margin-bottom: 30px;
    }

    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background-color: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: #6c757d;
        margin-right: 30px;
        overflow: hidden;
        position: relative;
    }

    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-avatar .upload-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0, 0, 0, 0.5);
        color: white;
        text-align: center;
        padding: 5px 0;
        cursor: pointer;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .profile-avatar:hover .upload-overlay {
        opacity: 1;
    }

    .profile-stats {
        display: flex;
        justify-content: space-around;
        margin: 30px 0;
        padding: 20px 0;
        border-top: 1px solid #eee;
        border-bottom: 1px solid #eee;
    }

    .stat-box {
        text-align: center;
        padding: 15px;
        border-radius: 8px;
        background-color: #f8f9fa;
        min-width: 120px;
    }

    .stat-value {
        font-size: 1.8rem;
        font-weight: bold;
        color: #0d6efd;
    }

    .about-section {
        margin-top: 20px;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 8px;
    }

    .edit-button {
        cursor: pointer;
        color: #0d6efd;
    }

    #file-input {
        display: none;
    }
    </style>
</head>

<body>
    <div class="container profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <?php if (!empty($user_image)): ?>
                <img src="<?php echo htmlspecialchars($user_image); ?>" alt="Profile Image">
                <?php else: ?>
                <i class="fas fa-user"></i>
                <?php endif; ?>
                <label for="file-input" class="upload-overlay">
                    <i class="fas fa-camera"></i> Change
                </label>
                <form id="image-form" method="POST" enctype="multipart/form-data" style="display:none;">
                    <input type="file" id="file-input" name="profile_image" accept="image/*"
                        onchange="submitImageForm()">
                </form>
            </div>
            <div>
                <h2><?php echo htmlspecialchars($user_name); ?></h2>
                <p class="text-muted">ID: <?php echo htmlspecialchars($er_number); ?></p>
            </div>
        </div>

        <!-- About section with edit functionality -->
        <div class="about-section">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h4>About Me</h4>
                <span class="edit-button" onclick="toggleAboutEdit()"><i class="fas fa-edit"></i> Edit</span>
            </div>

            <div id="about-display" class="<?php echo empty($about) ? 'd-none' : ''; ?>">
                <p><?php echo !empty($about) ? htmlspecialchars($about) : 'No information available'; ?></p>
            </div>

            <div id="about-edit" class="<?php echo empty($about) ? '' : 'd-none'; ?>">
                <form method="POST" action="">
                    <div class="mb-3">
                        <textarea name="about" class="form-control" rows="4"
                            placeholder="Write something about yourself..."><?php echo htmlspecialchars($about); ?></textarea>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" onclick="toggleAboutEdit()">Cancel</button>
                        <button type="submit" name="update_about" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="profile-stats">
            <div class="stat-box">
                <div class="stat-value"><?php echo $project_count; ?></div>
                <div class="stat-label">Projects</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo $idea_count; ?></div>
                <div class="stat-label">Ideas</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">0</div>
                <div class="stat-label">Bookmarks</div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6 mb-3">
                <a href="../projects/my_projects.php" class="btn btn-outline-primary w-100">
                    <i class="fas fa-project-diagram me-2"></i> View My Projects
                </a>
            </div>
            <div class="col-md-6 mb-3">
                <a href="blog.php" class="btn btn-outline-primary w-100">
                    <i class="fas fa-lightbulb me-2"></i> View My Ideas
                </a>
            </div>
        </div>
    </div>

    <?php if (!empty($message)) { ?>
    <div class="modal fade show" style="display: block;" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-<?php echo $messageType; ?>">Message</h5>
                    <button type="button" class="btn-close" onclick="window.location.href='profile.php';"></button>
                </div>
                <div class="modal-body">
                    <p><?php echo $message; ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>

    <script>
    function toggleAboutEdit() {
        const displayElement = document.getElementById('about-display');
        const editElement = document.getElementById('about-edit');

        displayElement.classList.toggle('d-none');
        editElement.classList.toggle('d-none');
    }

    function submitImageForm() {
        document.getElementById('image-form').submit();
    }
    </script>
</body>

</html>