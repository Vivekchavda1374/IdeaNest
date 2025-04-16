<?php
// Process form submission
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Set the timezone for accurate datetime
    date_default_timezone_set('Asia/Kolkata'); // Change to your timezone

    // Collect form data
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
    $submissionDateTime = date('Y-m-d H:i:s');

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
            $stmt = $conn->prepare("INSERT INTO blog (er_number, project_name, project_type, classification, description, submission_datetime, priority1, status, assigned_to, completion_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssss", $erNumber, $projectName, $projectType, $classification, $description, $submissionDateTime, $priority1, $status, $assignedTo, $completionDate);

            // Execute the statement
            if ($stmt->execute()) {
                $success_message = "Project submitted successfully on " . $submissionDateTime;
                $project_id = $conn->insert_id;
                // Clear form data after successful submission
                $erNumber = $projectName = $projectType = $classification = $description = $assignedTo = $completionDate = "";
            } else {
                $error_message = "Error: " . $stmt->error;
            }

            // Close statement and connection
            $stmt->close();
            $conn->close();
            
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Submission Portal</title>
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
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        min-height: 100vh;
    }

    .form-container {
        max-width: 900px;
        margin: 40px auto;
    }

    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .card-header {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        padding: 25px;
        border-bottom: none;
        position: relative;
    }

    .card-header::after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-color), transparent);
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

    .form-control,
    .form-select {
        border-radius: 8px;
        padding: 12px 15px;
        border: 1px solid #dee2e6;
        font-size: 16px;
        transition: all 0.3s;
    }

    .form-control:focus,
    .form-select:focus {
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
        padding: 15px;
        background-color: rgba(108, 117, 125, 0.1);
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid var(--accent-color);
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

    .priority1-selector input[type="radio"]:checked+label.priority1-low {
        background-color: rgba(25, 135, 84, 0.2);
        border-color: #198754;
        color: #198754;
    }

    .priority1-selector input[type="radio"]:checked+label.priority1-medium {
        background-color: rgba(255, 193, 7, 0.2);
        border-color: #ffc107;
        color: #664d03;
    }

    .priority1-selector input[type="radio"]:checked+label.priority1-high {
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

    .status-selector input[type="radio"]:checked+label.status-pending {
        background-color: rgba(255, 193, 7, 0.2);
        border-color: #ffc107;
        color: #664d03;
    }

    .status-selector input[type="radio"]:checked+label.status-in-progress {
        background-color: rgba(13, 110, 253, 0.2);
        border-color: #0d6efd;
        color: #0d6efd;
    }

    .status-selector input[type="radio"]:checked+label.status-completed {
        background-color: rgba(25, 135, 84, 0.2);
        border-color: #198754;
        color: #198754;
    }

    .status-selector input[type="radio"]:checked+label.status-rejected {
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
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.5s ease, transform 0.5s ease;
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

    .success-container {
        text-align: center;
        padding: 40px 20px;
    }

    .success-icon {
        font-size: 60px;
        color: #198754;
        margin-bottom: 20px;
    }

    .confirmation-number {
        background-color: rgba(25, 135, 84, 0.15);
        border-radius: 8px;
        padding: 10px 15px;
        font-size: 24px;
        font-weight: 600;
        color: #198754;
        display: inline-block;
        margin: 20px 0;
    }

    .form-select {
        background-position: right 15px center;
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

        <?php if (isset($success_message) && isset($project_id)): ?>
        <div class="card">
            <div class="card-body success-container">
                <i class="fas fa-check-circle success-icon"></i>
                <h3 class="mb-4">Project Submitted Successfully!</h3>
                <p>Your project has been received and will be reviewed shortly.</p>
                <div class="confirmation-number">
                    Project ID: <?php echo $project_id; ?>
                </div>
                <p><?php echo $success_message; ?></p>
                <div class="mt-4">
                    <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i> Submit Another Project
                    </a>
                </div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                    <a href="list-project.php" class="btn btn-outline-secondary">
                        <i class="fas fa-list me-2"></i>View All Projects
                    </a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-header text-white">
                <h3 class="mb-0"><i class="fas fa-project-diagram me-2"></i> Project Submission Portal</h3>
                <p class="mb-0 mt-2">Please fill out the form below to submit your project proposal</p>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
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
                                        value="<?php echo isset($erNumber) ? htmlspecialchars($erNumber) : ''; ?>"
                                        placeholder="Enter your ER number" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="icon-input mb-3">
                                    <i class="fas fa-clipboard"></i>
                                    <label for="projectName" class="form-label">Project Name</label>
                                    <input type="text" class="form-control" id="projectName" name="projectName"
                                        value="<?php echo isset($projectName) ? htmlspecialchars($projectName) : ''; ?>"
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
                                <option value="software"
                                    <?php echo (isset($projectType) && $projectType == 'software') ? 'selected' : ''; ?>>
                                    Software
                                </option>
                                <option value="hardware"
                                    <?php echo (isset($projectType) && $projectType == 'hardware') ? 'selected' : ''; ?>>
                                    Hardware
                                </option>
                            </select>
                        </div>

                        <div class="mb-4" id="classificationsContainer">
                            <label for="classification" class="form-label">Classification</label>
                            <select class="form-select" id="classification" name="classification" required>
                                <option value="">Select Project Type First</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Priority Level</label>
                            <div class="priority1-selector">
                                <input type="radio" name="priority1" id="priority1-low" value="low"
                                    <?php echo (isset($priority1) && $priority1 == 'low') ? 'checked' : ''; ?>>
                                <label for="priority1-low" class="priority1-low">
                                    <i class="fas fa-angle-down me-2"></i>Low
                                </label>

                                <input type="radio" name="priority1" id="priority1-medium" value="medium"
                                    <?php echo (!isset($priority1) || $priority1 == 'medium') ? 'checked' : ''; ?>>
                                <label for="priority1-medium" class="priority1-medium">
                                    <i class="fas fa-equals me-2"></i>Medium
                                </label>

                                <input type="radio" name="priority1" id="priority1-high" value="high"
                                    <?php echo (isset($priority1) && $priority1 == 'high') ? 'checked' : ''; ?>>
                                <label for="priority1-high" class="priority1-high">
                                    <i class="fas fa-angle-up me-2"></i>High
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Project Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5"
                                placeholder="Provide a detailed description of your project..."
                                required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
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
                                    <?php echo (!isset($status) || $status == 'pending') ? 'checked' : ''; ?>>
                                <label for="status-pending" class="status-pending">
                                    <i class="fas fa-clock me-2"></i>Pending
                                </label>

                                <input type="radio" name="status" id="status-in-progress" value="in_progress"
                                    <?php echo (isset($status) && $status == 'in_progress') ? 'checked' : ''; ?>>
                                <label for="status-in-progress" class="status-in-progress">
                                    <i class="fas fa-spinner me-2"></i>In Progress
                                </label>

                                <input type="radio" name="status" id="status-completed" value="completed"
                                    <?php echo (isset($status) && $status == 'completed') ? 'checked' : ''; ?>>
                                <label for="status-completed" class="status-completed">
                                    <i class="fas fa-check-circle me-2"></i>Completed
                                </label>

                                <input type="radio" name="status" id="status-rejected" value="rejected"
                                    <?php echo (isset($status) && $status == 'rejected') ? 'checked' : ''; ?>>
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
                                        value="<?php echo isset($assignedTo) ? htmlspecialchars($assignedTo) : ''; ?>"
                                        placeholder="Enter team member's name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="icon-input mb-3">
                                    <i class="fas fa-calendar-check"></i>
                                    <label for="completionDate" class="form-label">Expected Completion Date
                                        (Optional)</label>
                                    <input type="date" class="form-control" id="completionDate" name="completionDate"
                                        value="<?php echo isset($completionDate) ? htmlspecialchars($completionDate) : ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="submission-info mb-4">
                        <i class="fas fa-info-circle me-2"></i> Submission Date and Time will be automatically recorded.
                        You'll receive a confirmation number after successful submission.
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="list-project.php" class="btn btn-outline-secondary me-md-2">
                            <i class="fas fa-list me-2"></i>Back to List
                        </a>
                        <button type="reset" class="btn btn-outline-secondary me-md-2">
                            <i class="fas fa-undo me-2"></i>Reset Form
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Submit Project
                        </button>
                    </div>
                </form>
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

        // Reset classification options
        classificationSelect.innerHTML = '';

        if (projectType === 'software') {
            const softwareOptions = [{
                    value: '',
                    text: 'Select Classification',
                    icon: ''
                },
                {
                    value: 'web',
                    text: 'Web Application (Web App)',
                    icon: 'fa-globe'
                },
                {
                    value: 'mobile',
                    text: 'Mobile Application (Mobile App)',
                    icon: 'fa-mobile-alt'
                },
                {
                    value: 'ai_ml',
                    text: 'Artificial Intelligence & Machine Learning (AI/ML)',
                    icon: 'fa-brain'
                },
                {
                    value: 'desktop',
                    text: 'Desktop Application',
                    icon: 'fa-desktop'
                },
                {
                    value: 'system',
                    text: 'System Software',
                    icon: 'fa-cogs'
                },
                {
                    value: 'embedded_iot',
                    text: 'Embedded Systems / IoT Software',
                    icon: 'fa-microchip'
                },
                {
                    value: 'cybersecurity',
                    text: 'Cybersecurity Software',
                    icon: 'fa-shield-alt'
                },
                {
                    value: 'game',
                    text: 'Game Development',
                    icon: 'fa-gamepad'
                },
                {
                    value: 'data_science',
                    text: 'Data Science & Analytics',
                    icon: 'fa-chart-bar'
                },
                {
                    value: 'cloud',
                    text: 'Cloud-Based Applications',
                    icon: 'fa-cloud'
                }
            ];

            softwareOptions.forEach(option => {
                const optionElement = document.createElement('option');
                optionElement.value = option.value;

                // Add icon in text if available
                if (option.icon && option.value !== '') {
                    optionElement.textContent = `${option.text}`;
                } else {
                    optionElement.textContent = option.text;
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
                    value: 'embedded',
                    text: 'Embedded Systems Projects',
                    icon: 'fa-microchip'
                },
                {
                    value: 'iot',
                    text: 'IoT (Internet of Things) Projects',
                    icon: 'fa-wifi'
                },
                {
                    value: 'robotics',
                    text: 'Robotics Projects',
                    icon: 'fa-robot'
                },
                {
                    value: 'automation',
                    text: 'Automation Projects',
                    icon: 'fa-cogs'
                },
                {
                    value: 'sensor',
                    text: 'Sensor-Based Projects',
                    icon: 'fa-broadcast-tower'
                },
                {
                    value: 'communication',
                    text: 'Communication Systems Projects',
                    icon: 'fa-satellite-dish'
                },
                {
                    value: 'power',
                    text: 'Power Electronics Projects',
                    icon: 'fa-bolt'
                },
                {
                    value: 'wearable',
                    text: 'Wearable Technology Projects',
                    icon: 'fa-watch'
                },
                {
                    value: 'mechatronics',
                    text: 'Mechatronics Projects',
                    icon: 'fa-cog'
                },
                {
                    value: 'renewable',
                    text: 'Renewable Energy Projects',
                    icon: 'fa-solar-panel'
                }
            ];

            hardwareOptions.forEach(option => {
                const optionElement = document.createElement('option');
                optionElement.value = option.value;

                // Add icon in text if available
                if (option.icon && option.value !== '') {
                    optionElement.textContent = `${option.text}`;
                } else {
                    optionElement.textContent = option.text;
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

            // If classification is already set, select it
            <?php if(isset($classification) && !empty($classification)): ?>
            setTimeout(() => {
                document.getElementById('classification').value = '<?php echo $classification; ?>';
            }, 100);
            <?php endif; ?>
        }

        // Add animation to form sections
        const formSections = document.querySelectorAll('.form-section');
        formSections.forEach((section, index) => {
            setTimeout(() => {
                section.style.opacity = '1';
                section.style.transform = 'translateY(0)';
            }, 300 + (index * 200));
        });
    });
    </script>
</body>

</html>