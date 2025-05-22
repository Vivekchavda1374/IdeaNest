<?php
include '../Login/Login/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../Login/Login/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "User";
$user_initial = !empty($user_name) ? substr($user_name, 0, 1) : "U";

// Initialize message variables
$alert = "";
$alertType = "";

// Check if viewing or editing a project
$view_project_id = isset($_GET['view_id']) ? $_GET['view_id'] : null;
$edit_project_id = isset($_GET['edit_id']) ? $_GET['edit_id'] : null;

// Initialize project data variables
$project_data = null;
$project_view_mode = false;
$project_edit_mode = false;

// Fetch single project if view_id or edit_id is set
if ($view_project_id) {
    $project_sql = "SELECT * FROM projects WHERE id = ? AND user_id = ?";
    $project_stmt = $conn->prepare($project_sql);
    $project_stmt->bind_param("ii", $view_project_id, $user_id);
    $project_stmt->execute();
    $project_result = $project_stmt->get_result();

    if ($project_result->num_rows > 0) {
        $project_data = $project_result->fetch_assoc();
        $project_view_mode = true;
    } else {
        $alert = "Project not found or you don't have permission to view it.";
        $alertType = "danger";
    }
    $project_stmt->close();
} elseif ($edit_project_id) {
    $project_sql = "SELECT * FROM projects WHERE id = ? AND user_id = ?";
    $project_stmt = $conn->prepare($project_sql);
    $project_stmt->bind_param("ii", $edit_project_id, $user_id);
    $project_stmt->execute();
    $project_result = $project_stmt->get_result();

    if ($project_result->num_rows > 0) {
        $project_data = $project_result->fetch_assoc();
        $project_edit_mode = true;
    } else {
        $alert = "Project not found or you don't have permission to edit it.";
        $alertType = "danger";
    }
    $project_stmt->close();
}

// Fetch current user data
$sql = "SELECT * FROM register WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

// Fetch user projects
$projectSql = "SELECT * FROM projects WHERE user_id = ?";
$projectStmt = $conn->prepare($projectSql);
$projectStmt->bind_param("i", $user_id);
$projectStmt->execute();
$projectResult = $projectStmt->get_result();
$userProjects = [];
while ($row = $projectResult->fetch_assoc()) {
    $userProjects[] = $row;
}
$projectStmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        // Get form data
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $enrollment_number = trim($_POST['enrollment_number']);
        $gr_number = trim($_POST['gr_number']);
        $about = trim($_POST['about']);

        // Validate required fields
        if (empty($name) || empty($email)) {
            $alert = "Name and email are required fields.";
            $alertType = "danger";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $alert = "Please enter a valid email address.";
            $alertType = "danger";
        } else {
            // Update user data
            $updateSql = "UPDATE register SET name = ?, email = ?, enrollment_number = ?, gr_number = ?, about = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("sssssi", $name, $email, $enrollment_number, $gr_number, $about, $user_id);

            if ($updateStmt->execute()) {
                // Update session data
                $_SESSION['user_name'] = $name;
                $_SESSION['email'] = $email;

                $alert = "Profile updated successfully!";
                $alertType = "success";

                // Refresh user data
                $stmt->execute();
                $result = $stmt->get_result();
                $userData = $result->fetch_assoc();
            } else {
                $alert = "Error updating profile: " . $conn->error;
                $alertType = "danger";
            }
            $updateStmt->close();
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Verify current password
        if (password_verify($current_password, $userData['password'])) {
            // Check if new passwords match
            if ($new_password === $confirm_password) {
                // Check password strength
                if (strlen($new_password) < 8) {
                    $alert = "Password must be at least 8 characters long.";
                    $alertType = "danger";
                } else {
                    // Hash the new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    // Update password
                    $passwordSql = "UPDATE register SET password = ? WHERE id = ?";
                    $passwordStmt = $conn->prepare($passwordSql);
                    $passwordStmt->bind_param("si", $hashed_password, $user_id);

                    if ($passwordStmt->execute()) {
                        $alert = "Password changed successfully!";
                        $alertType = "success";
                    } else {
                        $alert = "Error changing password: " . $conn->error;
                        $alertType = "danger";
                    }
                    $passwordStmt->close();
                }
            } else {
                $alert = "New password and confirm password do not match.";
                $alertType = "danger";
            }
        } else {
            $alert = "Current password is incorrect.";
            $alertType = "danger";
        }
    } elseif (isset($_POST['update_project'])) {
        // Get form data for project update
        $project_id = $_POST['project_id'];
        $project_name = trim($_POST['project_name']);
        $project_type = trim($_POST['project_type']);
        $classification = trim($_POST['classification']);
        $language = trim($_POST['language']);
        $description = trim($_POST['description']);

        $status = trim($_POST['status']);

        // Handle file uploads if included
        $image_path = isset($project_data['image_path']) ? $project_data['image_path'] : null;
        $video_path = isset($project_data['video_path']) ? $project_data['video_path'] : null;
        $code_file_path = isset($project_data['code_file_path']) ? $project_data['code_file_path'] : null;
        $instruction_file_path = isset($project_data['instruction_file_path']) ? $project_data['instruction_file_path'] : null;

        // Process image upload if present
        if(isset($_FILES['project_image']) && $_FILES['project_image']['size'] > 0) {
            $target_dir = "uploads/images/";
            if(!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $image_file = $target_dir . basename($_FILES["project_image"]["name"]);
            if(move_uploaded_file($_FILES["project_image"]["tmp_name"], $image_file)) {
                $image_path = $image_file;
            }
        }

        // Process video upload if present
        if(isset($_FILES['project_video']) && $_FILES['project_video']['size'] > 0) {
            $target_dir = "uploads/videos/";
            if(!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $video_file = $target_dir . basename($_FILES["project_video"]["name"]);
            if(move_uploaded_file($_FILES["project_video"]["tmp_name"], $video_file)) {
                $video_path = $video_file;
            }
        }

        // Process code file upload if present
        if(isset($_FILES['code_file']) && $_FILES['code_file']['size'] > 0) {
            $target_dir = "uploads/code/";
            if(!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $code_file = $target_dir . basename($_FILES["code_file"]["name"]);
            if(move_uploaded_file($_FILES["code_file"]["tmp_name"], $code_file)) {
                $code_file_path = $code_file;
            }
        }

        // Process instruction file upload if present
        if(isset($_FILES['instruction_file']) && $_FILES['instruction_file']['size'] > 0) {
            $target_dir = "uploads/instructions/";
            if(!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $instruction_file = $target_dir . basename($_FILES["instruction_file"]["name"]);
            if(move_uploaded_file($_FILES["instruction_file"]["tmp_name"], $instruction_file)) {
                $instruction_file_path = $instruction_file;
            }
        }

        // Validate required fields
        if (empty($project_name) || empty($project_type) || empty($language)) {
            $alert = "Project name, type, and language are required fields.";
            $alertType = "danger";
        } else {
            // Update project data
            $updateProjectSql = "UPDATE projects SET 
                project_name = ?, 
                project_type = ?, 
                classification = ?,
                language = ?, 
                description = ?, 
                
                image_path = ?,
                video_path = ?,
                code_file_path = ?,
                instruction_file_path = ?,
                status = ? 
                WHERE id = ? AND user_id = ?";

            $updateProjectStmt = $conn->prepare($updateProjectSql);
            $updateProjectStmt->bind_param(
                "ssssssssssssii",
                $project_name,
                $project_type,
                $classification,
                $language,
                $description,

                $image_path,
                $video_path,
                $code_file_path,
                $instruction_file_path,
                $status,
                $project_id,
                $user_id
            );

            if ($updateProjectStmt->execute()) {
                $alert = "Project updated successfully!";
                $alertType = "success";

                // Redirect to projects tab
                header("Location: user_profile_setting.php?tab=projects");
                exit();
            } else {
                $alert = "Error updating project: " . $conn->error;
                $alertType = "danger";
            }
            $updateProjectStmt->close();
        }
    } elseif (isset($_POST['delete_project'])) {
        $project_id = $_POST['project_id'];

        // Delete project
        $deleteProjectSql = "DELETE FROM projects WHERE id = ? AND user_id = ?";
        $deleteProjectStmt = $conn->prepare($deleteProjectSql);
        $deleteProjectStmt->bind_param("ii", $project_id, $user_id);

        if ($deleteProjectStmt->execute()) {
            $alert = "Project deleted successfully!";
            $alertType = "success";

            // Redirect to projects tab
            header("Location: user_profile_setting.php?tab=projects");
            exit();
        } else {
            $alert = "Error deleting project: " . $conn->error;
            $alertType = "danger";
        }
        $deleteProjectStmt->close();
    }
}

// Close statement
$stmt->close();

// Get active tab from URL parameter
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .settings-card {
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s;
        }
        .settings-card:hover {
            transform: translateY(-5px);
        }
        .nav-pills .nav-link.active {
            background-color: #4361ee;
        }
        .nav-pills .nav-link {
            color: #333;
            border-radius: 8px;
            padding: 10px 15px;
            margin-bottom: 8px;
        }
        .btn-primary {
            background-color: #4361ee;
            border-color: #4361ee;
        }
        .btn-outline-primary {
            color: #4361ee;
            border-color: #4361ee;
        }
        .btn-outline-primary:hover {
            background-color: #4361ee;
            border-color: #4361ee;
        }
        .avatar-upload {
            position: relative;
            width: 120px;
            margin: 0 auto 20px;
        }
        .avatar-upload img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #4361ee;
        }
        .avatar-upload .avatar-edit {
            position: absolute;
            right: 5px;
            bottom: 5px;
            z-index: 1;
            width: 34px;
            height: 34px;
        }
        .avatar-upload .avatar-edit input {
            display: none;
        }
        .avatar-upload .avatar-edit label {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #4361ee;
            border: 1px solid transparent;
            color: white;
            cursor: pointer;
        }
        .project-card {
            transition: transform 0.2s;
            border-left: 4px solid #4361ee;
        }
        .project-card:hover {
            transform: translateY(-3px);
        }
        .project-badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
        }
        .project-details dt {
            font-weight: 600;
            color: #495057;
        }
        .project-details dd {
            margin-bottom: 1rem;
        }
        .tech-badge {
            background-color: #e9ecef;
            color: #495057;
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            display: inline-block;
        }
        .project-actions {
            position: absolute;
            top: 15px;
            right: 15px;
        }
        .back-button {
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
<div class="overlay" id="overlay"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-cubes"></i>
        <div class="logo">Dashboard</div>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="index.php">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="user_project_search.php">
                <i class="fas fa-project-diagram"></i>
                <span>Projects</span>
            </a>
        </li>
        <li>
            <a href="Blog/idea_dashboard.php">
                <i class="fas fa-file-alt"></i>
                <span>Idea Posts</span>
            </a>
        </li>
        <li>
            <a href="#">
                <i class="fas fa-user-graduate"></i>
                <span>Mentorship</span>
            </a>
        </li>

        <li>
            <a href="#">
                <i class="fas fa-chart-bar"></i>
                <span>Analytics</span>
            </a>
        </li>
        <li>
            <a href="user_profile_setting.php" class="active">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content" id="mainContent">
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
        <div class="container-fluid">
            <!-- Sidebar Toggle Button -->
            <button id="sidebarToggle" class="btn btn-light d-lg-none me-3">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Page Title -->
            <h5 class="mb-0 text-primary fw-bold">Account Settings</h5>

            <!-- Right-side menu items -->
            <ul class="navbar-nav ms-auto align-items-center">
                <!-- User Profile Dropdown -->
                <li class="nav-item dropdown ms-2">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar rounded-circle bg-primary d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                            <span class="text-white fw-medium"><?php echo htmlspecialchars($user_initial); ?></span>
                        </div>
                        <span class="d-none d-lg-inline"><?php echo htmlspecialchars($user_name); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                        <li>
                            <div class="dropdown-item text-center">
                                <div class="user-avatar rounded-circle bg-primary d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 60px; height: 60px;">
                                    <span class="text-white fw-bold"><?php echo htmlspecialchars($user_initial); ?></span>
                                </div>
                                <h6 class="mb-0"><?php echo htmlspecialchars($user_name); ?></h6>
                                <p class="text-muted small mb-0"><?php echo htmlspecialchars($userData['email']); ?></p>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> My Profile</a></li>
                        <li><a class="dropdown-item active" href="user_profile_setting.php"><i class="fas fa-cog me-2"></i> Account Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../Login/Login/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid px-4">
        <!-- Alert for messages -->
        <?php if (!empty($alert)): ?>
            <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
                <?php echo $alert; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($project_view_mode): ?>
            <!-- View Project Section -->
            <div class="row">
                <div class="col-12">
                    <a href="user_profile_setting.php?tab=projects" class="btn btn-outline-primary back-button">
                        <i class="fas fa-arrow-left me-2"></i> Back to Projects
                    </a>
                    <div class="card settings-card">
                        <div class="card-body p-4">
                            <div class="project-actions">
                                <a href="user_profile_setting.php?edit_id=<?php echo $project_data['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit me-1"></i> Edit
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger ms-2" data-bs-toggle="modal" data-bs-target="#deleteProjectModal">
                                    <i class="fas fa-trash me-1"></i> Delete
                                </button>
                            </div>
                            <h4 class="card-title mb-1"><?php echo htmlspecialchars($project_data['project_name']); ?></h4>
                            <div class="mb-4">
                        <span class="badge bg-<?php echo $project_data['status'] == 'completed' ? 'success' : ($project_data['status'] == 'in progress' ? 'warning' : 'info'); ?>">
                            <?php echo ucfirst(htmlspecialchars($project_data['status'])); ?>
                        </span>
                                <span class="text-muted small ms-3">
                            <i class="far fa-calendar-alt me-1"></i>
                            Submitted: <?php echo date('F d, Y', strtotime($project_data['submission_date'])); ?>
                        </span>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-8">
                                    <div class="mb-4">
                                        <h5 class="mb-3">Description</h5>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($project_data['description'])); ?></p>
                                    </div>

                                    <div class="mb-4">
                                        <h5 class="mb-3">Project Details</h5>
                                        <dl class="row project-details">
                                            <dt class="col-sm-3">Project Type</dt>
                                            <dd class="col-sm-9"><?php echo htmlspecialchars($project_data['project_type']); ?></dd>

                                            <dt class="col-sm-3">Language/Framework</dt>
                                            <dd class="col-sm-9"><?php echo htmlspecialchars($project_data['language']); ?></dd>

                                            <dt class="col-sm-3">Classification</dt>
                                            <dd class="col-sm-9"><?php echo htmlspecialchars($project_data['classification']); ?></dd>
                                        </dl>
                                    </div>

                                    <!-- Project Files Section -->
                                    <div class="mb-4">
                                        <h5 class="mb-3">Project Files</h5>
                                        <div class="row">
                                            <?php
                                            // Define file types and their properties
                                            $file_types = [
                                                'image_path' => ['icon' => 'fas fa-image', 'title' => 'Project Image', 'color' => 'primary'],
                                                'video_path' => ['icon' => 'fas fa-video', 'title' => 'Project Video', 'color' => 'danger'],
                                                'code_file_path' => ['icon' => 'fas fa-code', 'title' => 'Code File', 'color' => 'success'],
                                                'instruction_file_path' => ['icon' => 'fas fa-file-alt', 'title' => 'Instructions', 'color' => 'warning']
                                            ];

                                            $has_files = false;

                                            foreach($file_types as $field => $props):
                                                if(!empty($project_data[$field])):
                                                    $has_files = true;
                                                    $file_name = basename($project_data[$field]);
                                                    ?>
                                                    <div class="col-md-6 mb-3">
                                                        <div class="card h-100">
                                                            <div class="card-body d-flex flex-column">
                                                                <div class="d-flex align-items-center mb-3">
                                                                    <div class="rounded-circle bg-<?php echo $props['color']; ?>-light p-3 me-3">
                                                                        <i class="<?php echo $props['icon']; ?> text-<?php echo $props['color']; ?>"></i>
                                                                    </div>
                                                                    <div>
                                                                        <h6 class="mb-0"><?php echo $props['title']; ?></h6>
                                                                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($file_name); ?></p>
                                                                    </div>
                                                                </div>
                                                                <div class="mt-auto text-end">
                                                                    <a href="<?php echo htmlspecialchars($project_data[$field]); ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                                        <i class="fas fa-external-link-alt me-1"></i> View
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php
                                                endif;
                                            endforeach;

                                            if(!$has_files):
                                                ?>
                                                <div class="col-12">
                                                    <div class="alert alert-info mb-0">
                                                        <i class="fas fa-info-circle me-2"></i> No project files have been uploaded.
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <hr>
                                            <h6 class="mb-2">Tags</h6>
                                            <div>
                                                <span class="tech-badge">Programming</span>
                                                <span class="tech-badge"><?php echo htmlspecialchars($project_data['language']); ?></span>
                                                <span class="tech-badge"><?php echo htmlspecialchars($project_data['project_type']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Project Modal -->
            <div class="modal fade" id="deleteProjectModal" tabindex="-1" aria-labelledby="deleteProjectModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteProjectModalLabel">Confirm Delete</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete the project <strong><?php echo htmlspecialchars($project_data['project_name']); ?></strong>?</p>
                            <p class="text-danger">This action cannot be undone.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <form method="POST" action="">
                                <input type="hidden" name="project_id" value="<?php echo $project_data['id']; ?>">
                                <button type="submit" name="delete_project" class="btn btn-danger">Delete Project</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($project_edit_mode): ?>
            <div class="row">
                <div class="col-12">
                    <a href="user_profile_setting.php?tab=projects" class="btn btn-outline-primary back-button">
                        <i class="fas fa-arrow-left me-2"></i> Back to Projects
                    </a>
                    <div class="card settings-card">
                        <div class="card-body p-4">
                            <h4 class="card-title mb-4">Edit Project</h4>
                            <form method="POST" action="" enctype="multipart/form-data">
                                <input type="hidden" name="project_id" value="<?php echo $project_data['id']; ?>">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="project_name" class="form-label">Project Name</label>
                                        <input type="text" class="form-control" id="project_name" name="project_name" value="<?php echo htmlspecialchars($project_data['project_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="planning" <?php echo ($project_data['status'] == 'planning') ? 'selected' : ''; ?>>Planning</option>
                                            <option value="in progress" <?php echo ($project_data['status'] == 'in progress') ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="completed" <?php echo ($project_data['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="project_type" class="form-label">Project Type</label>
                                        <select class="form-select" id="project_type" name="project_type" required>
                                            <option value="Web Application" <?php echo ($project_data['project_type'] == 'Web Application') ? 'selected' : ''; ?>>Web Application</option>
                                            <option value="Mobile App" <?php echo ($project_data['project_type'] == 'Mobile App') ? 'selected' : ''; ?>>Mobile App</option>
                                            <option value="API" <?php echo ($project_data['project_type'] == 'API') ? 'selected' : ''; ?>>API</option>
                                            <option value="Desktop Application" <?php echo ($project_data['project_type'] == 'Desktop Application') ? 'selected' : ''; ?>>Desktop Application</option>
                                            <option value="Library/Framework" <?php echo ($project_data['project_type'] == 'Library/Framework') ? 'selected' : ''; ?>>Library/Framework</option>
                                            <option value="Game" <?php echo ($project_data['project_type'] == 'Game') ? 'selected' : ''; ?>>Game</option>
                                            <option value="Other" <?php echo ($project_data['project_type'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="language" class="form-label">Language/Framework</label>
                                        <input type="text" class="form-control" id="language" name="language" value="<?php echo htmlspecialchars($project_data['language']); ?>" required>
                                        <div class="form-text">Main language or framework used (e.g., PHP, React, Python)</div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="classification" class="form-label">Classification</label>
                                        <select class="form-select" id="classification" name="classification">
                                            <option value="Beginner" <?php echo ($project_data['classification'] == 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                                            <option value="Intermediate" <?php echo ($project_data['classification'] == 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                            <option value="Advanced" <?php echo ($project_data['classification'] == 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Project Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($project_data['description']); ?></textarea>
                                </div>

                                <!-- File Upload Section -->
                                <h5 class="mb-3 mt-4">Project Files</h5>
                                <div class="row">
                                    <!-- Project Image -->
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h6 class="card-title">Project Image</h6>
                                                <?php if(!empty($project_data['image_path'])): ?>
                                                    <div class="mb-3">
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-image text-primary me-2"></i>
                                                            <span class="small text-muted"><?php echo basename($project_data['image_path']); ?></span>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="input-group">
                                                    <input type="file" class="form-control" id="project_image" name="project_image" accept="image/*">
                                                </div>
                                                <div class="form-text">Upload a new image to replace the existing one.</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Project Video -->
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h6 class="card-title">Project Video</h6>
                                                <?php if(!empty($project_data['video_path'])): ?>
                                                    <div class="mb-3">
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-video text-danger me-2"></i>
                                                            <span class="small text-muted"><?php echo basename($project_data['video_path']); ?></span>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="input-group">
                                                    <input type="file" class="form-control" id="project_video" name="project_video" accept="video/*">
                                                </div>
                                                <div class="form-text">Upload a new video to replace the existing one.</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Project Code File -->
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h6 class="card-title">Code File</h6>
                                                <?php if(!empty($project_data['code_file_path'])): ?>
                                                    <div class="mb-3">
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-code text-success me-2"></i>
                                                            <span class="small text-muted"><?php echo basename($project_data['code_file_path']); ?></span>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="input-group">
                                                    <input type="file" class="form-control" id="code_file" name="code_file">
                                                </div>
                                                <div class="form-text">Upload a new code file to replace the existing one.</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Project Instruction File -->
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h6 class="card-title">Instruction File</h6>
                                                <?php if(!empty($project_data['instruction_file_path'])): ?>
                                                    <div class="mb-3">
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-file-alt text-warning me-2"></i>
                                                            <span class="small text-muted"><?php echo basename($project_data['instruction_file_path']); ?></span>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="input-group">
                                                    <input type="file" class="form-control" id="instruction_file" name="instruction_file">
                                                </div>
                                                <div class="form-text">Upload a new instruction file to replace the existing one.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="user_profile_setting.php?tab=projects" class="btn btn-secondary me-md-2">Cancel</a>
                                    <button type="submit" name="update_project" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Settings Layout -->
            <div class="row">
                <!-- Left Navigation Menu -->
                <div class="col-md-3 mb-4">
                    <div class="card settings-card">
                        <div class="card-body p-0">
                            <div class="d-flex flex-column align-items-center p-3 pb-3 border-bottom">
                                <div class="avatar-upload mb-3">
                                    <div class="avatar-preview">
                                        <div class="user-avatar rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 120px; height: 120px;">
                                            <span class="text-white display-6"><?php echo htmlspecialchars($user_initial); ?></span>
                                        </div>
                                    </div>
                                    <div class="avatar-edit">
                                        <label for="imageUpload" title="Change Profile Picture"><i class="fas fa-camera"></i></label>
                                    </div>
                                </div>
                                <h5 class="mb-1"><?php echo htmlspecialchars($user_name); ?></h5>
                                <p class="text-muted mb-3 small"><?php echo htmlspecialchars($userData['email']); ?></p>
                            </div>

                            <div class="nav flex-column nav-pills p-3" role="tablist" aria-orientation="vertical">
                                <a class="nav-link <?php echo ($active_tab == 'profile') ? 'active' : ''; ?>" href="?tab=profile">
                                    <i class="fas fa-user me-2"></i> Profile Settings
                                </a>
                                <a class="nav-link <?php echo ($active_tab == 'security') ? 'active' : ''; ?>" href="?tab=security">
                                    <i class="fas fa-shield-alt me-2"></i> Security
                                </a>
                                <a class="nav-link <?php echo ($active_tab == 'projects') ? 'active' : ''; ?>" href="?tab=projects">
                                    <i class="fas fa-project-diagram me-2"></i> Projects
                                </a>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Settings Content -->
                <div class="col-md-9">
                    <div class="card settings-card">
                        <div class="card-body p-4">
                            <?php if ($active_tab == 'profile'): ?>
                                <h4 class="card-title mb-4">Profile Settings</h4>
                                <form method="POST" action="">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="name" class="form-label">Full Name</label>
                                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($userData['name']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email Address</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="enrollment_number" class="form-label">Enrollment Number</label>
                                            <input type="text" class="form-control" id="enrollment_number" name="enrollment_number" value="<?php echo isset($userData['enrollment_number']) ? htmlspecialchars($userData['enrollment_number']) : ''; ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="gr_number" class="form-label">GR Number</label>
                                            <input type="text" class="form-control" id="gr_number" name="gr_number" value="<?php echo isset($userData['gr_number']) ? htmlspecialchars($userData['gr_number']) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="about" class="form-label">About Me</label>
                                        <textarea class="form-control" id="about" name="about" rows="4"><?php echo isset($userData['about']) ? htmlspecialchars($userData['about']) : ''; ?></textarea>
                                        <div class="form-text">Tell us a bit about yourself, your skills, and interests.</div>
                                    </div>
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            <?php elseif ($active_tab == 'security'): ?>
                                <h4 class="card-title mb-4">Security Settings</h4>
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <div class="form-text">Password must be at least 8 characters long.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                                    </div>
                                </form>

                                <hr class="my-4">

                                <h5 class="mb-3">Two-Factor Authentication</h5>
                                <p class="text-muted mb-4">Enhance your account security by enabling two-factor authentication.</p>
                                <button class="btn btn-outline-primary" disabled>Enable 2FA (Coming Soon)</button>

                                <hr class="my-4">

                                <h5 class="mb-3">Login Sessions</h5>
                                <p class="text-muted mb-4">Manage your active login sessions across devices.</p>
                                <div class="list-group mb-4">
                                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">Current Session</h6>
                                            <p class="text-muted mb-0 small">Started <?php echo date('M d, Y H:i'); ?></p>
                                        </div>
                                        <span class="badge bg-success">Active</span>
                                    </div>
                                </div>
                                <a href="../Login/Login/logout.php" class="btn btn-outline-danger">
                                    Log Out
                                </a>
                            <?php elseif ($active_tab == 'projects'): ?>
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h4 class="card-title mb-0">My Projects</h4>
                                    <a href="./forms/new_project_add.php" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i> Add New Project
                                    </a>
                                </div>

                                <?php if (count($userProjects) > 0): ?>
                                    <div class="row">
                                        <?php foreach ($userProjects as $project): ?>
                                            <div class="col-md-6 mb-4">
                                                <div class="card project-card h-100">
                                                    <div class="card-body">
                                                        <h5 class="card-title mb-1">
                                                            <?php echo htmlspecialchars($project['project_name']); ?>
                                                        </h5>
                                                        <div class="mb-2">
                                                            <span class="badge bg-<?php echo $project['status'] == 'completed' ? 'success' : ($project['status'] == 'in progress' ? 'warning' : 'info'); ?> project-badge">
                                                                <?php echo ucfirst(htmlspecialchars($project['status'])); ?>
                                                            </span>
                                                            <span class="text-muted small ms-2">
                                                                Added: <?php echo date('M d, Y', strtotime($project['submission_date'])); ?>
                                                            </span>
                                                        </div>
                                                        <p class="card-text small text-muted mb-3">
                                                            <?php echo strlen($project['description']) > 100 ? htmlspecialchars(substr($project['description'], 0, 100)) . '...' : htmlspecialchars($project['description']); ?>
                                                        </p>
                                                        <div class="d-flex mb-3">
                                                            <span class="tech-badge me-2"><?php echo htmlspecialchars($project['project_type']); ?></span>
                                                            <span class="tech-badge"><?php echo htmlspecialchars($project['language']); ?></span>
                                                        </div>
                                                        <div class="d-flex justify-content-end">
                                                            <a href="user_profile_setting.php?view_id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-primary me-2">
                                                                <i class="fas fa-eye me-1"></i> View
                                                            </a>
                                                            <a href="user_profile_setting.php?edit_id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                                <i class="fas fa-edit me-1"></i> Edit
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-project-diagram display-1 text-muted mb-3"></i>
                                        <h5>No projects yet</h5>
                                        <p class="text-muted">Start showcasing your work by adding your first project.</p>
                                        <a href="./forms/new_project_add.php" class="btn btn-primary mt-2">
                                            <i class="fas fa-plus me-2"></i> Add Your First Project
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
    // Sidebar toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const overlay = document.getElementById('overlay');

        // Function to toggle sidebar
        function toggleSidebar() {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        // Event listeners
        sidebarToggle.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', function() {
            if (sidebar.classList.contains('active')) {
                toggleSidebar();
            }
        });

        // On window resize, if larger than mobile view, remove active classes
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                sidebar.classList.remove('active');
                mainContent.classList.remove('active');
                overlay.classList.remove('active');
            }
        });
    });
</script>
</body>
</html>