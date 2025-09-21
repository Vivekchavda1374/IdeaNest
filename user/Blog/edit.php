<?php
// Start session to check user authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    // Set the timezone for accurate datetime
    date_default_timezone_set('Asia/Kolkata');

    // Collect form data
    $id = intval($_POST['id']);
    $erNumber = trim($_POST['erNumber']);
    $projectName = trim($_POST['projectName']);
    $projectType = $_POST['projectType'];
    $classification = $_POST['classification'];
    $description = trim($_POST['description']);
    $priority1 = isset($_POST['priority1']) ? $_POST['priority1'] : 'medium';
    $status = isset($_POST['status']) ? $_POST['status'] : 'pending';
    $assignedTo = !empty($_POST['assignedTo']) ? trim($_POST['assignedTo']) : null;
    $completionDate = !empty($_POST['completionDate']) ? $_POST['completionDate'] : null;

    // Get current date and time for update tracking
    $updateDateTime = date('Y-m-d H:i:s');

    // Validate the data
    if (
        empty($erNumber) || empty($projectName) || empty($projectType) ||
            empty($classification) || empty($description)
    ) {
        $error_message = "Error: All required fields must be filled";
    } else {
        // Database connection parameters
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "ideanest";

        // Create database connection
        $conn = new mysqli($servername, $username, $password, $dbname);

        // Check connection
        if ($conn->connect_error) {
            $error_message = "Connection failed: " . $conn->connect_error;
        } else {
            // First, check if the project belongs to the current user
            $check_stmt = $conn->prepare("SELECT user_id FROM blog WHERE id = ?");
            $check_stmt->bind_param("i", $id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows === 0) {
                $error_message = "Project not found";
            } else {
                $project_owner = $check_result->fetch_assoc()['user_id'];

                if ($project_owner != $current_user_id) {
                    $error_message = "Access denied: You can only edit your own projects";
                } else {
                    // User owns the project, proceed with update
                    $stmt = $conn->prepare("UPDATE blog 
                        SET er_number=?, project_name=?, project_type=?, classification=?, description=?, 
                            priority1=?, status=?, assigned_to=?, completion_date=?, updated_at=? 
                        WHERE id=? AND user_id=?");

                    $stmt->bind_param(
                        "ssssssssssii",
                        $erNumber,
                        $projectName,
                        $projectType,
                        $classification,
                        $description,
                        $priority1,
                        $status,
                        $assignedTo,
                        $completionDate,
                        $updateDateTime,
                        $id,
                        $current_user_id
                    );

                    // Execute the statement
                    if ($stmt->execute()) {
                        $success_message = "Project updated successfully on " . $updateDateTime;
                    } else {
                        $error_message = "Error: " . $stmt->error;
                    }

                    // Close statement
                    $stmt->close();
                }
            }
            $check_stmt->close();
            $conn->close();
        }
    }
}

// Get project ID from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$projectData = null;

if ($id > 0) {
    // Database connection parameters
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "ideanest";

    // Create database connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        $error_message = "Connection failed: " . $conn->connect_error;
    } else {
        // Prepare and bind the SQL statement - only get project if it belongs to current user
        $stmt = $conn->prepare("SELECT * FROM blog WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $current_user_id);

        // Execute the statement
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $projectData = $result->fetch_assoc();

            // Assign fetched data to variables for form
            $erNumber = $projectData['er_number'];
            $projectName = $projectData['project_name'];
            $projectType = $projectData['project_type'];
            $classification = $projectData['classification'];
            $description = $projectData['description'];
            $priority1 = $projectData['priority1'];
            $status = $projectData['status'];
            $assignedTo = $projectData['assigned_to'];
            $completionDate = $projectData['completion_date'];
            $submissionDateTime = $projectData['submission_datetime'];
        } else {
            $error_message = "Project not found or you don't have permission to edit this project.";
        }

        // Close statement and connection
        $stmt->close();
        $conn->close();
    }
} else {
    $error_message = "Invalid project ID.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Project | Project Management Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/edit_idea.css">
</head>

<body>
<div class="container form-container">
    <?php if (isset($error_message)) : ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error_message); ?>
            <?php if (!$projectData) : ?>
                <div class="mt-3">
                    <a href="list-project.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Projects List
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($success_message)) : ?>
        <div class="alert alert-success" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if ($projectData) : ?>
        <div class="card">
            <div class="card-header text-white">
                <h3 class="mb-0"><i class="fas fa-edit me-2"></i> Edit Project</h3>
                <p class="mb-0 mt-2">Update the project details below</p>
            </div>
            <div class="card-body">
                <div class="project-metadata">
                    <div class="row">
                        <div class="col-md-6">
                            <p><span class="label"><i class="fas fa-id-card me-2"></i>Project ID:</span> <?php echo htmlspecialchars($projectData['id']); ?></p>
                            <p><span class="label"><i class="fas fa-calendar me-2"></i>Submitted:</span> <?php echo date('F j, Y, g:i a', strtotime($submissionDateTime)); ?></p>
                        </div>
                        <div class="col-md-6">
                            <?php if (!empty($projectData['updated_at'])) : ?>
                                <p><span class="label"><i class="fas fa-sync me-2"></i>Last Updated:</span> <?php echo date('F j, Y, g:i a', strtotime($projectData['updated_at'])); ?></p>
                            <?php endif; ?>
                            <p><span class="label"><i class="fas fa-user me-2"></i>Owner:</span> You</p>
                        </div>
                    </div>
                </div>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $id; ?>">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">

                    <!-- Basic Information Section -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <span class="icon-badge">1</span>Basic Information
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="icon-input mb-3">
                                    <i class="fas fa-id-badge"></i>
                                    <label for="erNumber" class="form-label">ER Number of User</label>
                                    <input type="text" class="form-control" id="erNumber" name="erNumber"
                                           value="<?php echo htmlspecialchars($erNumber); ?>"
                                           placeholder="Enter your ER number" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="icon-input mb-3">
                                    <i class="fas fa-clipboard"></i>
                                    <label for="projectName" class="form-label">Project Name</label>
                                    <input type="text" class="form-control" id="projectName" name="projectName"
                                           value="<?php echo htmlspecialchars($projectName); ?>"
                                           placeholder="Enter project name" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Project Details Section -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <span class="icon-badge">2</span>Project Details
                        </div>
                        <div class="mb-4">
                            <label for="projectType" class="form-label">Project Type</label>
                            <select class="form-select" id="projectType" name="projectType" required
                                    onchange="updateClassifications()">
                                <option value="">Select Project Type</option>
                                <option value="software" <?php echo ($projectType == 'software') ? 'selected' : ''; ?>>
                                    Software
                                </option>
                                <option value="hardware" <?php echo ($projectType == 'hardware') ? 'selected' : ''; ?>>
                                    Hardware
                                </option>
                            </select>
                        </div>

                        <div class="mb-4" id="classificationsContainer">
                            <label for="classification" class="form-label">Classification</label>
                            <select class="form-select" id="classification" name="classification" required>
                                <option value="">Select Project Type First</option>
                                <!-- Options will be loaded by JavaScript -->
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Priority Level</label>
                            <div class="priority1-selector">
                                <input type="radio" name="priority1" id="priority1-low" value="low"
                                        <?php echo ($priority1 == 'low') ? 'checked' : ''; ?>>
                                <label for="priority1-low" class="priority1-low">
                                    <i class="fas fa-angle-down me-2"></i>Low
                                </label>

                                <input type="radio" name="priority1" id="priority1-medium" value="medium"
                                        <?php echo ($priority1 == 'medium') ? 'checked' : ''; ?>>
                                <label for="priority1-medium" class="priority1-medium">
                                    <i class="fas fa-equals me-2"></i>Medium
                                </label>

                                <input type="radio" name="priority1" id="priority1-high" value="high"
                                        <?php echo ($priority1 == 'high') ? 'checked' : ''; ?>>
                                <label for="priority1-high" class="priority1-high">
                                    <i class="fas fa-angle-up me-2"></i>High
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Project Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5"
                                      placeholder="Provide a detailed description of your project..."
                                      required><?php echo htmlspecialchars($description); ?></textarea>
                        </div>
                    </div>

                    <!-- Project Status Section -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <span class="icon-badge">3</span>Project Status & Assignment
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Project Status</label>
                            <div class="status-selector">
                                <input type="radio" name="status" id="status-pending" value="pending"
                                        <?php echo ($status == 'pending') ? 'checked' : ''; ?>>
                                <label for="status-pending" class="status-pending">
                                    <i class="fas fa-clock me-2"></i>Pending
                                </label>

                                <input type="radio" name="status" id="status-in-progress" value="in_progress"
                                        <?php echo ($status == 'in_progress') ? 'checked' : ''; ?>>
                                <label for="status-in-progress" class="status-in-progress">
                                    <i class="fas fa-spinner me-2"></i>In Progress
                                </label>

                                <input type="radio" name="status" id="status-completed" value="completed"
                                        <?php echo ($status == 'completed') ? 'checked' : ''; ?>>
                                <label for="status-completed" class="status-completed">
                                    <i class="fas fa-check-circle me-2"></i>Completed
                                </label>

                                <input type="radio" name="status" id="status-rejected" value="rejected"
                                        <?php echo ($status == 'rejected') ? 'checked' : ''; ?>>
                                <label for="status-rejected" class="status-rejected">
                                    <i class="fas fa-times-circle me-2"></i>Rejected
                                </label>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="icon-input mb-3">
                                    <i class="fas fa-user-tie"></i>
                                    <label for="assignedTo" class="form-label">Assigned To (Optional)</label>
                                    <input type="text" class="form-control" id="assignedTo" name="assignedTo"
                                           value="<?php echo htmlspecialchars($assignedTo ?? ''); ?>"
                                           placeholder="Enter team member's name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="icon-input mb-3">
                                    <i class="fas fa-calendar-check"></i>
                                    <label for="completionDate" class="form-label">Expected Completion Date (Optional)</label>
                                    <input type="date" class="form-control" id="completionDate" name="completionDate"
                                           value="<?php echo htmlspecialchars($completionDate ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="submission-info mb-4">
                        <i class="fas fa-info-circle me-2"></i> The last update date and time will be automatically recorded when you save changes.
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="list-project.php" class="btn btn-outline-secondary me-md-2">
                            <i class="fas fa-arrow-left me-2"></i>Back to List
                        </a>
                        <button type="submit" name="update" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php else : ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> Project not found or you don't have permission to edit this project.
            <div class="mt-3">
                <a href="list-project.php" class="btn btn-outline-primary">
                    <i class="fas fa-list me-2"></i>View All Projects
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/edit_idea.js"></script>
</body>

</html>