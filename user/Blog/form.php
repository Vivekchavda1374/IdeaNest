<?php
// Process form submission
session_start();
require_once '../../includes/csrf.php';
require_once '../../includes/validation.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireCSRF();
    // Set the timezone
    date_default_timezone_set('Asia/Kolkata');

    // Collect form data
    $erNumber       = trim($_POST['erNumber']);
    $projectName    = trim($_POST['projectName']);
    $projectType    = $_POST['projectType'];
    $classification = $_POST['classification'];
    $description    = trim($_POST['description']);
    $priority1      = isset($_POST['priority1']) ? $_POST['priority1'] : 'medium';
    $status         = isset($_POST['status']) ? $_POST['status'] : 'pending';
    $assignedTo     = !empty($_POST['assignedTo']) ? trim($_POST['assignedTo']) : null;
    $completionDate = !empty($_POST['completionDate']) ? $_POST['completionDate'] : null;

    // Get current datetime
    $submissionDateTime = date('Y-m-d H:i:s');

    // ✅ Get logged-in user_id from session (must be set during login)
    if (!isset($_SESSION['user_id'])) {
        $error_message = "Error: User not logged in.";
    } elseif (
        empty($erNumber) || empty($projectName) || empty($projectType) ||
            empty($classification) || empty($description)
    ) {
        $error_message = "Error: All required fields must be filled";
    } else {
        require_once '../../Login/Login/db.php';

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
       else {
            // ✅ Insert with user_id (foreign key to register table)
            $stmt = $conn->prepare("INSERT INTO blog 
                (er_number, project_name, project_type, classification, description, submission_datetime, priority1, status, assigned_to, completion_date, user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param(
                "ssssssssssi",
                $erNumber,
                $projectName,
                $projectType,
                $classification,
                $description,
                $submissionDateTime,
                $priority1,
                $status,
                $assignedTo,
                $completionDate,
                $_SESSION['user_id']   // ✅ logged-in user
            );

            if ($stmt->execute()) {
                $success_message = "Project submitted successfully on " . $submissionDateTime;
                $project_id = $conn->insert_id;

                // Clear form values
                $erNumber = $projectName = $projectType = $classification = $description = $assignedTo = $completionDate = "";
            } else {
                $error_message = "Error: " . $stmt->error;
            }

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

    <link rel="stylesheet" href="../../assets/css/index.css">
    <link rel="stylesheet" href="../../assets/css/layout_user.css">
    <style>
        /* Purple Theme CSS for Project Submission Portal - Compact & Centered */
        :root {
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --accent-color: #a855f7;
            --light-purple: #f3f4f6;
            --dark-purple: #4c1d95;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
            --shadow-light: 0 1px 3px rgba(99, 102, 241, 0.1);
            --shadow-medium: 0 4px 6px rgba(99, 102, 241, 0.1);
            --shadow-heavy: 0 10px 25px rgba(99, 102, 241, 0.15);
        }

        body {
            background: #fff;
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            /* Enhanced centering with flexbox */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem 0;
            margin-left: 280px; /* Account for sidebar width */
        }

        .form-container {
            max-width: 600px; /* Reduced from 800px */
            width: 100%;
            margin: 0 auto;
            padding: 0 1rem;
            /* Ensure the container takes full available space within constraints */
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: fit-content;
        }

        .card {
            border: none;
            border-radius: 12px; /* Reduced from 16px */
            box-shadow: var(--shadow-heavy);
            overflow: hidden;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            /* Center the card within its container */
            margin: 0 auto;
            width: 100%;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-bottom: none;
            padding: 1.5rem; /* Reduced from 2rem */
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.1) 25%, transparent 25%),
            linear-gradient(-45deg, rgba(255, 255, 255, 0.1) 25%, transparent 25%);
            background-size: 20px 20px;
            background-position: 0 0, 0 10px;
            opacity: 0.3;
        }

        .card-header h3 {
            position: relative;
            z-index: 2;
            margin: 0;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            font-size: 1.5rem; /* Slightly smaller */
        }

        .card-header p {
            position: relative;
            z-index: 2;
            color: rgba(255, 255, 255, 0.9);
            margin: 0;
            font-size: 0.9rem; /* Smaller subtitle */
        }

        .card-body {
            padding: 1.8rem; /* Reduced from 2.5rem */
        }

        /* Form Sections - More Compact */
        .form-section {
            margin-bottom: 2rem; /* Reduced from 3rem */
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s ease;
            border-radius: 10px; /* Reduced from 12px */
            border: 1px solid var(--border-color);
            padding: 1.5rem; /* Reduced from 2rem */
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.02), rgba(139, 92, 246, 0.02));
            position: relative;
        }

        .form-section:hover {
            transform: translateY(-1px); /* Reduced from -2px */
            box-shadow: var(--shadow-medium);
        }

        .form-section-title {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            margin-bottom: 1.2rem; /* Reduced from 1.5rem */
            font-size: 1.1rem; /* Reduced from 1.25rem */
            font-weight: 600;
            color: var(--text-primary);
            gap: 0.8rem; /* Reduced from 1rem */
        }

        .icon-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px; /* Reduced from 32px */
            height: 28px; /* Reduced from 32px */
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 50%;
            font-size: 0.8rem; /* Reduced from 0.875rem */
            font-weight: 700;
            box-shadow: var(--shadow-light);
        }

        /* Form Controls - More Compact */
        .form-label {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.4rem; /* Reduced from 0.5rem */
            display: flex;
            align-items: center;
            gap: 0.4rem; /* Reduced from 0.5rem */
            font-size: 0.9rem; /* Slightly smaller */
        }

        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 6px; /* Reduced from 8px */
            padding: 0.6rem 0.8rem; /* Reduced from 0.75rem 1rem */
            font-size: 0.9rem; /* Reduced from 0.95rem */
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.15rem rgba(99, 102, 241, 0.25); /* Reduced from 0.2rem */
            outline: none;
        }

        .form-control:hover, .form-select:hover {
            border-color: var(--secondary-color);
        }

        /* Icon Input - Adjusted for smaller size */
        .icon-input {
            position: relative;
        }

        .icon-input i {
            position: absolute;
            left: 0.8rem; /* Reduced from 1rem */
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            z-index: 5;
            font-size: 0.9rem; /* Reduced from 1rem */
        }

        .icon-input .form-control,
        .icon-input .form-select {
            padding-left: 2.5rem; /* Reduced from 3rem */
        }

        /* Priority Selector - More Compact */
        .priority1-selector {
            display: flex;
            gap: 0.8rem; /* Reduced from 1rem */
            flex-wrap: wrap;
            justify-content: center;
        }

        .priority1-selector input[type="radio"] {
            display: none;
        }

        .priority1-selector label {
            display: flex;
            align-items: center;
            padding: 0.6rem 1.2rem; /* Reduced from 0.75rem 1.5rem */
            border: 2px solid var(--border-color);
            border-radius: 6px; /* Reduced from 8px */
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            font-weight: 500;
            min-width: 100px; /* Reduced from 120px */
            justify-content: center;
            font-size: 0.85rem; /* Smaller text */
        }

        .priority1-selector input[type="radio"]:checked + label {
            transform: translateY(-1px); /* Reduced from -2px */
            box-shadow: var(--shadow-medium);
        }

        .priority1-low {
            color: var(--success-color);
            border-color: var(--success-color) !important;
        }

        .priority1-selector input[type="radio"]:checked + .priority1-low {
            background: var(--success-color);
            color: white;
        }

        .priority1-medium {
            color: var(--primary-color);
            border-color: var(--primary-color) !important;
        }

        .priority1-selector input[type="radio"]:checked + .priority1-medium {
            background: var(--primary-color);
            color: white;
        }

        .priority1-high {
            color: var(--danger-color);
            border-color: var(--danger-color) !important;
        }

        .priority1-selector input[type="radio"]:checked + .priority1-high {
            background: var(--danger-color);
            color: white;
        }

        /* Status Selector - More Compact */
        .status-selector {
            display: flex;
            gap: 0.8rem; /* Reduced from 1rem */
            flex-wrap: wrap;
            justify-content: center;
        }

        .status-selector input[type="radio"] {
            display: none;
        }

        .status-selector label {
            display: flex;
            align-items: center;
            padding: 0.6rem 1rem; /* Reduced from 0.75rem 1.25rem */
            border: 2px solid var(--border-color);
            border-radius: 6px; /* Reduced from 8px */
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            font-weight: 500;
            min-width: 110px; /* Reduced from 130px */
            justify-content: center;
            font-size: 0.85rem; /* Smaller text */
        }

        .status-selector input[type="radio"]:checked + label {
            transform: translateY(-1px); /* Reduced from -2px */
            box-shadow: var(--shadow-medium);
        }

        .status-pending {
            color: var(--warning-color);
            border-color: var(--warning-color) !important;
        }

        .status-selector input[type="radio"]:checked + .status-pending {
            background: var(--warning-color);
            color: white;
        }

        .status-in-progress {
            color: var(--primary-color);
            border-color: var(--primary-color) !important;
        }

        .status-selector input[type="radio"]:checked + .status-in-progress {
            background: var(--primary-color);
            color: white;
        }

        .status-completed {
            color: var(--success-color);
            border-color: var(--success-color) !important;
        }

        .status-selector input[type="radio"]:checked + .status-completed {
            background: var(--success-color);
            color: white;
        }

        .status-rejected {
            color: var(--danger-color);
            border-color: var(--danger-color) !important;
        }

        .status-selector input[type="radio"]:checked + .status-rejected {
            background: var(--danger-color);
            color: white;
        }

        /* Buttons - More Compact */
        .btn {
            border-radius: 6px; /* Reduced from 8px */
            padding: 0.6rem 1.2rem; /* Reduced from 0.75rem 1.5rem */
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem; /* Reduced from 0.5rem */
            text-decoration: none;
            font-size: 0.9rem; /* Smaller button text */
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            box-shadow: var(--shadow-light);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            transform: translateY(-1px); /* Reduced from -2px */
            box-shadow: var(--shadow-medium);
            color: white;
        }

        .btn-outline-secondary {
            background: transparent;
            color: var(--text-secondary);
            border: 2px solid var(--border-color);
        }

        .btn-outline-secondary:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-1px); /* Reduced from -2px */
        }

        /* Center button groups - More Compact */
        .d-grid.gap-2.d-md-flex.justify-content-md-end {
            justify-content: center !important;
            gap: 0.8rem; /* Reduced from 1rem */
            margin-top: 1.5rem; /* Reduced from 2rem */
        }

        /* Alerts - More Compact */
        .alert {
            border: none;
            border-radius: 10px; /* Reduced from 12px */
            padding: 0.8rem 1.2rem; /* Reduced from 1rem 1.5rem */
            margin-bottom: 1.5rem; /* Reduced from 2rem */
            font-weight: 500;
            font-size: 0.9rem; /* Smaller text */
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
            color: var(--danger-color);
            border-left: 3px solid var(--danger-color); /* Reduced from 4px */
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
            color: var(--success-color);
            border-left: 3px solid var(--success-color); /* Reduced from 4px */
        }

        /* Success Container - More Compact */
        .success-container {
            text-align: center;
            padding: 2rem 1.5rem; /* Reduced from 3rem 2rem */
        }

        .success-icon {
            font-size: 3rem; /* Reduced from 4rem */
            color: var(--success-color);
            margin-bottom: 1rem; /* Reduced from 1.5rem */
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); } /* Reduced from 1.1 */
            100% { transform: scale(1); }
        }

        .confirmation-number {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 0.8rem 1.5rem; /* Reduced from 1rem 2rem */
            border-radius: 40px; /* Reduced from 50px */
            font-weight: 700;
            font-size: 1rem; /* Reduced from 1.1rem */
            display: inline-block;
            margin: 0.8rem 0; /* Reduced from 1rem 0 */
            box-shadow: var(--shadow-medium);
        }

        /* Submission Info - More Compact */
        .submission-info {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 10px; /* Reduced from 12px */
            padding: 1rem; /* Reduced from 1.25rem */
            color: var(--primary-color);
            font-weight: 500;
            text-align: center;
            font-size: 0.9rem; /* Smaller text */
        }

        /* Textarea - More Compact */
        textarea.form-control {
            resize: vertical;
            min-height: 100px; /* Reduced from 120px */
        }

        /* Row spacing - More Compact */
        .row.mb-3 {
            margin-bottom: 1.5rem !important; /* Reduced spacing */
        }

        .mb-4 {
            margin-bottom: 1.5rem !important; /* Reduced from default */
        }

        .mb-3 {
            margin-bottom: 1rem !important; /* Reduced spacing */
        }

        /* Responsive Design for Mobile */
        @media (max-width: 1024px) {
            body {
                margin-left: 0; /* Remove sidebar margin on mobile */
                padding: 0.5rem 0;
                align-items: flex-start;
            }

            .form-container {
                max-width: 500px; /* Even smaller on mobile */
                padding: 0 0.5rem;
                margin-top: 4rem; /* Account for mobile menu toggle */
            }
        }

        @media (max-width: 768px) {
            .form-container {
                max-width: 450px; /* Smaller for tablets */
                padding: 0 0.5rem;
            }

            .card-body {
                padding: 1.2rem; /* Further reduced padding */
            }

            .form-section {
                padding: 1.2rem; /* Reduced padding */
                padding-left: 1.8rem; /* Account for left border */
            }

            .priority1-selector,
            .status-selector {
                flex-direction: column;
                align-items: center;
                gap: 0.6rem; /* Reduced gap */
            }

            .priority1-selector label,
            .status-selector label {
                min-width: auto;
                width: 100%;
                max-width: 200px; /* Reduced from 250px */
                padding: 0.5rem 1rem; /* Smaller padding */
            }

            .d-md-flex {
                flex-direction: column;
                align-items: center;
            }

            .d-md-flex .btn {
                margin-bottom: 0.5rem;
                width: 100%;
                max-width: 180px; /* Reduced from 200px */
            }
        }

        /* For very small screens */
        @media (max-width: 480px) {
            .form-container {
                max-width: 100%;
                padding: 0 0.75rem;
            }

            .card-header {
                padding: 1.2rem 0.8rem; /* Further reduced */
            }

            .card-body {
                padding: 1rem; /* Minimal padding */
            }

            .form-section {
                padding: 1rem; /* Minimal padding */
                padding-left: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .form-section-title {
                font-size: 1rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.4rem;
            }

            .icon-badge {
                align-self: flex-start;
                width: 24px; /* Even smaller */
                height: 24px;
                font-size: 0.75rem;
            }
        }

        /* Additional Purple Theme Enhancements - Adjusted */
        .form-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 3px; /* Reduced from 4px */
            height: 100%;
            background: linear-gradient(to bottom, var(--primary-color), var(--secondary-color));
            border-radius: 0 2px 2px 0;
        }

        .form-section {
            padding-left: 2rem; /* Adjusted for smaller border */
        }

        /* Hover effects for interactive elements */
        .form-control:hover,
        .form-select:hover {
            box-shadow: 0 2px 6px rgba(99, 102, 241, 0.08); /* Lighter shadow */
        }

        /* Focus states */
        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--primary-color) !important;
            box-shadow: 0 0 0 0.15rem rgba(99, 102, 241, 0.25) !important; /* Reduced from 0.2rem */
        }

        /* Custom scrollbar for textarea */
        textarea::-webkit-scrollbar {
            width: 5px; /* Reduced from 6px */
        }

        textarea::-webkit-scrollbar-track {
            background: var(--light-purple);
            border-radius: 3px;
        }

        textarea::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 3px;
        }

        textarea::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }

        /* Ensure proper centering on larger screens with compact size */
        @media (min-width: 1200px) {
            body {
                padding: 2rem 0; /* Reduced from 4rem */
            }

            .form-container {
                max-width: 650px; /* Slightly larger but still compact */
            }
        }
    </style>
</head>

<body>
<?php include '../layout.php'?>
    <div class="container form-container">
        <?php if (isset($error_message)) : ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <?php if (isset($success_message) && isset($project_id)) : ?>
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
        <?php else : ?>
        <div class="card">
            <div class="card-header text-white">
                <h3 class="mb-0"><i class="fas fa-project-diagram me-2"></i> Project Submission Portal</h3>
                <p class="mb-0 mt-2">Please fill out the form below to submit your project proposal</p>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <?php echo getCSRFField(); ?>
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
                                    <label for="projectName" class="form-label">Project Title</label>
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
                                placeholder="Provide detailed description for your project..."
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
<script src="../../assets/js/layout_user.js"></script>
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
        const projectTypeElement = document.getElementById('projectType');
        if (projectTypeElement && projectTypeElement.value) {
            updateClassifications();

            // If classification is already set, select it
            <?php if (isset($classification) && !empty($classification)) : ?>
            setTimeout(() => {
                const classificationElement = document.getElementById('classification');
                if (classificationElement) {
                    classificationElement.value = '<?php echo $classification; ?>';
                }
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
