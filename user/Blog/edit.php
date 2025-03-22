<?php
// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    // Set the timezone for accurate datetime
    date_default_timezone_set('Asia/Kolkata'); // Change to your timezone

    // Collect form data
    $id= $_POST['id'];
    $erNumber = trim($_POST['erNumber']);
    $projectName = trim($_POST['projectName']);
    $projectType = $_POST['projectType'];
    $classification = $_POST['classification'];
    $description = trim($_POST['description']);
    $priority1 = isset($_POST['priority1']) ? $_POST['priority1'] : 'medium';
    $status = isset($_POST['status']) ? $_POST['status'] : 'pending';
    $assignedTo = trim(isset($_POST['assignedTo']) ? $_POST['assignedTo'] : null);
    $completionDate = !empty($_POST['completionDate']) ? $_POST['completionDate'] : null;

    // Get current date and time
    $updateDateTime = date('Y-m-d H:i:s');

    // Validate the data
    if (empty($erNumber) || empty($projectName) || empty($projectType) ||
        empty($classification) || empty($description)) {
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
            // Prepare and bind the SQL statement to prevent SQL injection
            $stmt = $conn->prepare("UPDATE blog SET er_number=?, project_name=?, project_type=?, classification=?, description=?, submission_datetime=?, priority1=?, status=?, assigned_to=?, completion_date=? WHERE id=?"); $stmt->bind_param("ssssssssssi", $erNumber, $projectName, $projectType, $classification, $description, $updateDateTime, $priority1, $status, $assignedTo, $completionDate, $id);

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
}

// Get project ID from URL
$id= isset($_GET['id']) ? intval($_GET['id']) : 0;
$projectData = null;

if ($id> 0) {
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
        // Prepare and bind the SQL statement
        $stmt = $conn->prepare("SELECT * FROM blog WHERE id = ?");
        $stmt->bind_param("i", $id);

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
            $error_message = "Project not found.";
        }

        // Close statement
        $stmt->close();
    }

    // Close connection
    $conn->close();
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
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }

        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .form-container {
            max-width: 900px;
            margin: 40px auto;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 20px;
            border-bottom: none;
        }

        .card-header h3 {
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .card-body {
            padding: 30px;
        }

        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #dee2e6;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.25);
            border-color: var(--primary-color);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 12px 20px;
            font-weight: 500;
            letter-spacing: 0.5px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }

        .btn-outline-secondary {
            border-color: #6c757d;
            color: #6c757d;
            background-color: transparent;
            padding: 12px 20px;
            font-weight: 500;
            letter-spacing: 0.5px;
            border-radius: 8px;
        }

        .btn-outline-secondary:hover {
            background-color: #6c757d;
            color: white;
        }

        .submission-info {
            font-size: 0.9rem;
            color: #6c757d;
            padding: 10px;
            background-color: rgba(108, 117, 125, 0.1);
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .alert-success {
            background-color: rgba(25, 135, 84, 0.15);
            border-color: rgba(25, 135, 84, 0.3);
            color: #198754;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.15);
            border-color: rgba(220, 53, 69, 0.3);
            color: #dc3545;
        }

        .priority1-selector {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .priority1-selector label {
            flex: 1;
            text-align: center;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
            border: 2px solid #dee2e6;
        }

        .priority1-selector input[type="radio"] {
            display: none;
        }

        .priority1-selector input[type="radio"]:checked + label.priority1-low {
            background-color: rgba(25, 135, 84, 0.2);
            border-color: #198754;
            color: #198754;
        }

        .priority1-selector input[type="radio"]:checked + label.priority1-medium {
            background-color: rgba(255, 193, 7, 0.2);
            border-color: #ffc107;
            color: #664d03;
        }

        .priority1-selector input[type="radio"]:checked + label.priority1-high {
            background-color: rgba(220, 53, 69, 0.2);
            border-color: #dc3545;
            color: #dc3545;
        }

        .status-selector {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .status-selector label {
            flex: 1;
            min-width: 120px;
            text-align: center;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
            border: 2px solid #dee2e6;
        }

        .status-selector input[type="radio"] {
            display: none;
        }

        .status-selector input[type="radio"]:checked + label.status-pending {
            background-color: rgba(255, 193, 7, 0.2);
            border-color: #ffc107;
            color: #664d03;
        }

        .status-selector input[type="radio"]:checked + label.status-in-progress {
            background-color: rgba(13, 110, 253, 0.2);
            border-color: #0d6efd;
            color: #0d6efd;
        }

        .status-selector input[type="radio"]:checked + label.status-completed {
            background-color: rgba(25, 135, 84, 0.2);
            border-color: #198754;
            color: #198754;
        }

        .status-selector input[type="radio"]:checked + label.status-rejected {
            background-color: rgba(220, 53, 69, 0.2);
            border-color: #dc3545;
            color: #dc3545;
        }

        .icon-input {
            position: relative;
        }

        .icon-input i {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            left: 12px;
            color: #6c757d;
        }

        .icon-input input {
            padding-left: 40px;
        }

        .form-floating label {
            padding-left: 40px;
        }

        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .form-section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--dark-color);
        }

        .icon-badge {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            margin-right: 12px;
        }

        .project-metadata {
            background-color: rgba(67, 97, 238, 0.1);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
        }

        .project-metadata p {
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .project-metadata .label {
            font-weight: 600;
            color: var(--primary-color);
        }
    </style>
</head>

<body>
<div class="container form-container">
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if ($projectData): ?>
        <div class="card">
            <div class="card-header text-white">
                <h3 class="mb-0"><i class="fas fa-edit me-2"></i> Edit Project</h3>
                <p class="mb-0 mt-2">Update the project details below</p>
            </div>
            <div class="card-body">
                <div class="project-metadata">
                    <div class="row">
                        <div class="col-md-6">
                            <p><span class="label"><i class="fas fa-id-card me-2"></i>Project ID:</span> <?php echo $projectData['id']; ?></p>
                            <p><span class="label"><i class="fas fa-calendar me-2"></i>Submitted:</span> <?php echo date('F j, Y, g:i a', strtotime($submissionDateTime)); ?></p>
                        </div>
                        <div class="col-md-6">
                            <?php if (!empty($projectData['last_updated'])): ?>
                                <p><span class="label"><i class="fas fa-sync me-2"></i>Last Updated:</span> <?php echo date('F j, Y, g:i a', strtotime($projectData['last_updated'])); ?></p>
                            <?php endif; ?>
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

                    <!-- idea Details Section -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <span class="icon-badge">2</span>idea Details
                        </div>
                        <div class="mb-4">
                            <label for="projectType" class="form-label">idea Type</label>
                            <select class="form-select" id="projectType" name="projectType" required
                                    onchange="updateClassifications()">
                                <option value="">Select idea Type</option>
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
                                <option value="">Select idea Type First</option>
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
                            <label for="description" class="form-label">idea Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5"
                                      placeholder="Provide a detailed description of your idea..."
                                      required><?php echo htmlspecialchars($description); ?></textarea>
                        </div>
                    </div>

                    <!-- Project Status Section -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <span class="icon-badge">3</span>Idea Status & Assignment
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
                                           value="<?php echo htmlspecialchars(isset($assignedTo) ? $assignedTo : ''); ?>"
                                           placeholder="Enter team member's name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="icon-input mb-3">
                                    <i class="fas fa-calendar-check"></i>
                                    <label for="completionDate" class="form-label">Expected Completion Date (Optional)</label>
                                    <input type="date" class="form-control" id="completionDate" name="completionDate"
                                           value="<?php echo htmlspecialchars(isset($completionDate) ? $completionDate : ''); ?>">
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
    <?php else: ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> idea not found or invalid idea ID.
            <div class="mt-3">
                <a href="index.php" class="btn btn-outline-primary">
                    <i class="fas fa-list me-2"></i>View All ideas
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function updateClassifications() {
        const projectType = document.getElementById('projectType').value;
        const classificationSelect = document.getElementById('classification');
        const currentClassification = '<?php echo $classification; ?>';

        // Reset classification options
        classificationSelect.innerHTML = '';

        if (projectType === 'software') {
            const softwareOptions = [{
                value: '',
                text: 'Select Classification',
                icon: ''
            },
                {
                    value: 'webapp',
                    text: 'Web Application',
                    icon: 'fa-globe'
                },
                {
                    value: 'mobileapp',
                    text: 'Mobile Application',
                    icon: 'fa-mobile-alt'
                },
                {
                    value: 'desktopapp',
                    text: 'Desktop Application',
                    icon: 'fa-desktop'
                },
                {
                    value: 'embeddedsystem',
                    text: 'Embedded System',
                    icon: 'fa-microchip'
                }
            ];

            softwareOptions.forEach(option => {
                const optionElement = document.createElement('option');
                optionElement.value = option.value;
                optionElement.textContent = option.text;
                if (option.value === currentClassification) {
                    optionElement.selected = true;
                }
                classificationSelect.appendChild(optionElement);
            });

        } else if (projectType === 'hardware') {
            const hardwareOptions = [{
                value: '',
                text: 'Select Classification',
                icon: ''
            },
                {
                    value: 'iotdevice',
                    text: 'IoT Device',
                    icon: 'fa-wifi'
                },
                {
                    value: 'robotics',
                    text: 'Robotics',
                    icon: 'fa-robot'
                },
                {
                    value: 'electroniccircuit',
                    text: 'Electronic Circuit',
                    icon: 'fa-microchip'
                }
            ];

            hardwareOptions.forEach(option => {
                const optionElement = document.createElement('option');
                optionElement.value = option.value;
                optionElement.textContent = option.text;
                if (option.value === currentClassification) {
                    optionElement.selected = true;
                }
                classificationSelect.appendChild(optionElement);
            });

        } else {
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Select Project Type First';
            classificationSelect.appendChild(defaultOption);
        }
    }

    // Run this on page load to ensure correct classification options are displayed
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('projectType').value) {
            updateClassifications();
        }

        // Add animation to form sections
        const formSections = document.querySelectorAll('.form-section');
        formSections.forEach((section, index) => {
            section.style.opacity = '0';
            section.style.transform = 'translateY(20px)';
            section.style.transition = 'opacity 0.5s ease, transform 0.5s ease';

            setTimeout(() => {
                section.style.opacity = '1';
                section.style.transform = 'translateY(0)';
            }, 300 + (index * 200));
        });
    });
</script>
</body>

</html>