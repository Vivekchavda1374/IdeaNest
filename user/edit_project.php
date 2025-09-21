<?php
// user/edit_project.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$basePath = './';
include '../Login/Login/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get project ID from URL
$project_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($project_id <= 0) {
    header('Location: all_projects.php?error=invalid_id');
    exit;
}

$session_id = session_id();
$success_message = '';
$error_message = '';

// Check if user owns this project
$ownership_sql = "SELECT COUNT(*) as owns FROM temp_project_ownership WHERE project_id = ? AND user_session = ?";
$ownership_stmt = $conn->prepare($ownership_sql);
$ownership_stmt->bind_param("is", $project_id, $session_id);
$ownership_stmt->execute();
$ownership_result = $ownership_stmt->get_result();
$owns_project = $ownership_result->fetch_assoc()['owns'] > 0;
$ownership_stmt->close();

if (!$owns_project) {
    header('Location: all_projects.php?error=not_owner');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get all form data
    $project_name = trim($_POST['project_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $classification = trim($_POST['classification'] ?? '');
    $project_type = trim($_POST['project_type'] ?? '');
    $project_category = trim($_POST['project_category'] ?? '');
    $difficulty_level = trim($_POST['difficulty_level'] ?? '');
    $development_time = trim($_POST['development_time'] ?? '');
    $team_size = trim($_POST['team_size'] ?? '');
    $target_audience = trim($_POST['target_audience'] ?? '');
    $project_goals = trim($_POST['project_goals'] ?? '');
    $challenges_faced = trim($_POST['challenges_faced'] ?? '');
    $future_enhancements = trim($_POST['future_enhancements'] ?? '');
    $github_repo = trim($_POST['github_repo'] ?? '');
    $live_demo_url = trim($_POST['live_demo_url'] ?? '');
    $project_license = trim($_POST['project_license'] ?? '');
    $keywords = trim($_POST['keywords'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $social_links = trim($_POST['social_links'] ?? '');
    $language = trim($_POST['language'] ?? '');

    // Validate required fields
    if (empty($project_name) || empty($description) || empty($classification)) {
        $error_message = 'Project name, description, and classification are required.';
    } else {
        // Validate email format if provided
        if (!empty($contact_email) && !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Please enter a valid email address.';
        }
        // Validate URLs if provided
        elseif (
            (!empty($github_repo) && !filter_var($github_repo, FILTER_VALIDATE_URL)) ||
                (!empty($live_demo_url) && !filter_var($live_demo_url, FILTER_VALIDATE_URL))
        ) {
            $error_message = 'Please enter valid URLs for GitHub repository and live demo.';
        } else {
            // Update project with all fields
            $update_sql = "UPDATE admin_approved_projects SET 
                           project_name = ?, 
                           description = ?, 
                           classification = ?, 
                           project_type = ?, 
                           project_category = ?,
                           difficulty_level = ?,
                           development_time = ?,
                           team_size = ?,
                           target_audience = ?,
                           project_goals = ?,
                           challenges_faced = ?,
                           future_enhancements = ?,
                           github_repo = ?,
                           live_demo_url = ?,
                           project_license = ?,
                           keywords = ?,
                           contact_email = ?,
                           social_links = ?,
                           language = ?
                           WHERE id = ?";

            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param(
                "sssssssssssssssssssi",
                $project_name,
                $description,
                $classification,
                $project_type,
                $project_category,
                $difficulty_level,
                $development_time,
                $team_size,
                $target_audience,
                $project_goals,
                $challenges_faced,
                $future_enhancements,
                $github_repo,
                $live_demo_url,
                $project_license,
                $keywords,
                $contact_email,
                $social_links,
                $language,
                $project_id
            );

            if ($update_stmt->execute()) {
                $success_message = 'Project updated successfully!';
            } else {
                $error_message = 'Error updating project: ' . $conn->error;
            }
            $update_stmt->close();
        }
    }
}

// Fetch project data
$project_sql = "SELECT * FROM admin_approved_projects WHERE id = ?";
$project_stmt = $conn->prepare($project_sql);
$project_stmt->bind_param("i", $project_id);
$project_stmt->execute();
$project_result = $project_stmt->get_result();

if ($project_result->num_rows === 0) {
    header('Location: all_projects.php?error=project_not_found');
    exit;
}

$project = $project_result->fetch_assoc();
$project_stmt->close();
$conn->close();

// Get user info from session
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "vivek";
$user_initial = !empty($user_name) ? strtoupper(substr($user_name, 0, 1)) : "V";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Project - <?php echo htmlspecialchars($project['project_name']); ?> - IdeaNest</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #6366f1;
            --primary-hover: #5855eb;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --dark: #1e293b;
            --light: #f8fafc;
            --white: #ffffff;
            --gray-50: #f9fafb;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --border-radius: 12px;
            --border-radius-lg: 16px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --sidebar-width: 280px;
            --gradient-primary: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            --font-size-xs: 0.75rem;
            --font-size-sm: 0.875rem;
            --font-size-base: 1rem;
            --font-size-lg: 1.125rem;
            --font-size-xl: 1.25rem;
            --font-size-2xl: 1.5rem;
            --font-size-3xl: 1.875rem;
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: var(--border-radius);
            --radius-xl: var(--border-radius-lg);
            --radius-2xl: 24px;
            --radius-full: 9999px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: var(--gray-50);
            color: var(--gray-700);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            padding: var(--spacing-xl);
            transition: all 0.3s ease;
            background: var(--gray-50);
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 80px var(--spacing-md) var(--spacing-xl);
            }
        }

        .edit-header {
            background: var(--white);
            border-radius: var(--radius-2xl);
            padding: var(--spacing-2xl);
            margin-bottom: var(--spacing-xl);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--gray-200);
            position: relative;
            overflow: hidden;
        }

        .edit-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .edit-form {
            background: var(--white);
            border-radius: var(--radius-2xl);
            padding: var(--spacing-2xl);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--gray-200);
        }

        .form-section {
            margin-bottom: var(--spacing-2xl);
            padding-bottom: var(--spacing-2xl);
            border-bottom: 2px solid var(--gray-100);
        }

        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .section-title {
            font-size: var(--font-size-xl);
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: var(--spacing-lg);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .section-title i {
            color: var(--primary-color);
            font-size: 1.2em;
        }

        .form-label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: var(--spacing-sm);
            font-size: var(--font-size-sm);
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }

        .form-label.required::after {
            content: '*';
            color: var(--danger-color);
            margin-left: var(--spacing-xs);
        }

        .form-control,
        .form-select {
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 12px 16px;
            font-size: var(--font-size-base);
            transition: all 0.3s ease;
            background: var(--white);
            color: var(--gray-700);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            outline: none;
            background: var(--white);
        }

        .form-text {
            font-size: var(--font-size-xs);
            color: var(--gray-500);
            margin-top: var(--spacing-xs);
        }

        .btn {
            font-weight: 600;
            border-radius: var(--radius-md);
            padding: 12px 24px;
            font-size: var(--font-size-base);
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: var(--white);
            box-shadow: var(--shadow-md);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            color: var(--white);
        }

        .btn-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
            border: 1px solid var(--gray-200);
        }

        .btn-secondary:hover {
            background: var(--gray-200);
            color: var(--gray-800);
        }

        .alert {
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
            border: none;
            font-weight: 500;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        .project-info {
            background: var(--gray-50);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            margin-bottom: var(--spacing-lg);
            border-left: 4px solid var(--primary-color);
        }

        .character-counter {
            font-size: var(--font-size-xs);
            color: var(--gray-400);
            text-align: right;
            margin-top: var(--spacing-xs);
        }

        .form-floating {
            position: relative;
        }

        .tags-input {
            min-height: 45px;
            padding: 8px 12px;
        }

        .progress-bar {
            height: 4px;
            background: var(--gray-200);
            border-radius: var(--radius-full);
            overflow: hidden;
            margin-bottom: var(--spacing-lg);
        }

        .progress-fill {
            height: 100%;
            background: var(--gradient-primary);
            transition: width 0.3s ease;
            border-radius: var(--radius-full);
        }

        .sticky-actions {
            position: sticky;
            bottom: 0;
            background: var(--white);
            padding: var(--spacing-lg);
            border-top: 1px solid var(--gray-200);
            border-radius: 0 0 var(--radius-2xl) var(--radius-2xl);
            margin: calc(var(--spacing-2xl) * -1) calc(var(--spacing-2xl) * -1) 0;
            box-shadow: 0 -4px 6px -1px rgb(0 0 0 / 0.1);
        }
    </style>
</head>
<body>
<!-- Sidebar -->
<?php include "layout.php"; ?>

<!-- Main Content -->
<main class="main-content">
    <!-- Edit Header -->
    <div class="edit-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-edit me-3"></i>Edit Project</h2>
                <p class="mb-0">Comprehensive project editing with all details</p>
            </div>
            <a href="all_projects.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Projects
            </a>
        </div>

        <div class="project-info mt-4">
            <strong>Project ID:</strong> #<?php echo $project['id']; ?> |
            <strong>Status:</strong> <span class="text-success">Approved</span> |
            <strong>Created:</strong> <?php echo htmlspecialchars($project['submission_date'] ?? $project['created_at'] ?? 'N/A'); ?>
        </div>

        <!-- Progress Bar -->
        <div class="progress-bar mt-3">
            <div class="progress-fill" id="progressFill" style="width: 0%"></div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($success_message)) : ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)) : ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Edit Form -->
    <form method="post" class="edit-form" id="editForm">
        <!-- Basic Information Section -->
        <div class="form-section">
            <h3 class="section-title">
                <i class="fas fa-info-circle"></i>Basic Information
            </h3>
            <div class="row g-4">
                <div class="col-12">
                    <label for="project_name" class="form-label required">
                        <i class="fas fa-project-diagram"></i>Project Name
                    </label>
                    <input type="text" class="form-control" id="project_name" name="project_name"
                           value="<?php echo htmlspecialchars($project['project_name']); ?>"
                           placeholder="Enter project name" required maxlength="255">
                    <div class="character-counter">
                        <span id="project_name_count">0</span>/255 characters
                    </div>
                </div>

                <div class="col-12">
                    <label for="description" class="form-label required">
                        <i class="fas fa-file-text"></i>Project Description
                    </label>
                    <textarea class="form-control" id="description" name="description"
                              rows="6" placeholder="Provide a detailed description of your project" required><?php echo htmlspecialchars($project['description']); ?></textarea>
                    <div class="form-text">Describe what your project does, its purpose, and key features.</div>
                    <div class="character-counter">
                        <span id="description_count">0</span> characters
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="classification" class="form-label required">
                        <i class="fas fa-tags"></i>Project Classification
                    </label>
                    <select class="form-select" id="classification" name="classification" required>
                        <option value="">Select Classification</option>
                        <option value="Web Development" <?php echo ($project['classification'] === 'Web Development') ? 'selected' : ''; ?>>Web Development</option>
                        <option value="Mobile App" <?php echo ($project['classification'] === 'Mobile App') ? 'selected' : ''; ?>>Mobile App</option>
                        <option value="Desktop Application" <?php echo ($project['classification'] === 'Desktop Application') ? 'selected' : ''; ?>>Desktop Application</option>
                        <option value="Game Development" <?php echo ($project['classification'] === 'Game Development') ? 'selected' : ''; ?>>Game Development</option>
                        <option value="Data Science" <?php echo ($project['classification'] === 'Data Science') ? 'selected' : ''; ?>>Data Science</option>
                        <option value="Machine Learning" <?php echo ($project['classification'] === 'Machine Learning') ? 'selected' : ''; ?>>Machine Learning</option>
                        <option value="AI" <?php echo ($project['classification'] === 'AI') ? 'selected' : ''; ?>>Artificial Intelligence</option>
                        <option value="IoT" <?php echo ($project['classification'] === 'IoT') ? 'selected' : ''; ?>>Internet of Things</option>
                        <option value="Blockchain" <?php echo ($project['classification'] === 'Blockchain') ? 'selected' : ''; ?>>Blockchain</option>
                        <option value="Cloud Computing" <?php echo ($project['classification'] === 'Cloud Computing') ? 'selected' : ''; ?>>Cloud Computing</option>
                        <option value="DevOps" <?php echo ($project['classification'] === 'DevOps') ? 'selected' : ''; ?>>DevOps</option>
                        <option value="Cybersecurity" <?php echo ($project['classification'] === 'Cybersecurity') ? 'selected' : ''; ?>>Cybersecurity</option>
                        <option value="Other" <?php echo ($project['classification'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="project_category" class="form-label">
                        <i class="fas fa-folder"></i>Project Category
                    </label>
                    <select class="form-select" id="project_category" name="project_category">
                        <option value="">Select Category</option>
                        <option value="Frontend" <?php echo ($project['project_category'] === 'Frontend') ? 'selected' : ''; ?>>Frontend</option>
                        <option value="Backend" <?php echo ($project['project_category'] === 'Backend') ? 'selected' : ''; ?>>Backend</option>
                        <option value="Full Stack" <?php echo ($project['project_category'] === 'Full Stack') ? 'selected' : ''; ?>>Full Stack</option>
                        <option value="API" <?php echo ($project['project_category'] === 'API') ? 'selected' : ''; ?>>API</option>
                        <option value="Library" <?php echo ($project['project_category'] === 'Library') ? 'selected' : ''; ?>>Library</option>
                        <option value="Framework" <?php echo ($project['project_category'] === 'Framework') ? 'selected' : ''; ?>>Framework</option>
                        <option value="Tool" <?php echo ($project['project_category'] === 'Tool') ? 'selected' : ''; ?>>Tool</option>
                        <option value="Plugin" <?php echo ($project['project_category'] === 'Plugin') ? 'selected' : ''; ?>>Plugin</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="project_type" class="form-label">
                        <i class="fas fa-layer-group"></i>Project Type
                    </label>
                    <select class="form-select" id="project_type" name="project_type">
                        <option value="">Select Type</option>
                        <option value="Open Source" <?php echo ($project['project_type'] === 'Open Source') ? 'selected' : ''; ?>>Open Source</option>
                        <option value="Commercial" <?php echo ($project['project_type'] === 'Commercial') ? 'selected' : ''; ?>>Commercial</option>
                        <option value="Educational" <?php echo ($project['project_type'] === 'Educational') ? 'selected' : ''; ?>>Educational</option>
                        <option value="Personal" <?php echo ($project['project_type'] === 'Personal') ? 'selected' : ''; ?>>Personal</option>
                        <option value="Research" <?php echo ($project['project_type'] === 'Research') ? 'selected' : ''; ?>>Research</option>
                        <option value="Prototype" <?php echo ($project['project_type'] === 'Prototype') ? 'selected' : ''; ?>>Prototype</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="language" class="form-label">
                        <i class="fas fa-code"></i>Primary Programming Language
                    </label>
                    <input type="text" class="form-control" id="language" name="language"
                           value="<?php echo htmlspecialchars($project['language'] ?? ''); ?>"
                           placeholder="e.g., PHP, Python, JavaScript, Java" maxlength="100">
                    <div class="form-text">Main programming language used in the project</div>
                </div>
            </div>
        </div>

        <!-- Project Details Section -->
        <div class="form-section">
            <h3 class="section-title">
                <i class="fas fa-cogs"></i>Project Details
            </h3>
            <div class="row g-4">
                <div class="col-md-6">
                    <label for="difficulty_level" class="form-label">
                        <i class="fas fa-signal"></i>Difficulty Level
                    </label>
                    <select class="form-select" id="difficulty_level" name="difficulty_level">
                        <option value="">Select Difficulty</option>
                        <option value="beginner" <?php echo ($project['difficulty_level'] === 'beginner') ? 'selected' : ''; ?>>Beginner</option>
                        <option value="intermediate" <?php echo ($project['difficulty_level'] === 'intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                        <option value="advanced" <?php echo ($project['difficulty_level'] === 'advanced') ? 'selected' : ''; ?>>Advanced</option>
                        <option value="expert" <?php echo ($project['difficulty_level'] === 'expert') ? 'selected' : ''; ?>>Expert</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="development_time" class="form-label">
                        <i class="fas fa-clock"></i>Development Time
                    </label>
                    <select class="form-select" id="development_time" name="development_time">
                        <option value="">Select Duration</option>
                        <option value="1-2 weeks" <?php echo ($project['development_time'] === '1-2 weeks') ? 'selected' : ''; ?>>1-2 weeks</option>
                        <option value="3-4 weeks" <?php echo ($project['development_time'] === '3-4 weeks') ? 'selected' : ''; ?>>3-4 weeks</option>
                        <option value="1-2 months" <?php echo ($project['development_time'] === '1-2 months') ? 'selected' : ''; ?>>1-2 months</option>
                        <option value="3-6 months" <?php echo ($project['development_time'] === '3-6 months') ? 'selected' : ''; ?>>3-6 months</option>
                        <option value="6+ months" <?php echo ($project['development_time'] === '6+ months') ? 'selected' : ''; ?>>6+ months</option>
                        <option value="Ongoing" <?php echo ($project['development_time'] === 'Ongoing') ? 'selected' : ''; ?>>Ongoing</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="team_size" class="form-label">
                        <i class="fas fa-users"></i>Team Size
                    </label>
                    <select class="form-select" id="team_size" name="team_size">
                        <option value="">Select Team Size</option>
                        <option value="Solo" <?php echo ($project['team_size'] === 'Solo') ? 'selected' : ''; ?>>Solo (1 person)</option>
                        <option value="2-3 people" <?php echo ($project['team_size'] === '2-3 people') ? 'selected' : ''; ?>>2-3 people</option>
                        <option value="4-5 people" <?php echo ($project['team_size'] === '4-5 people') ? 'selected' : ''; ?>>4-5 people</option>
                        <option value="6-10 people" <?php echo ($project['team_size'] === '6-10 people') ? 'selected' : ''; ?>>6-10 people</option>
                        <option value="10+ people" <?php echo ($project['team_size'] === '10+ people') ? 'selected' : ''; ?>>10+ people</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="project_license" class="form-label">
                        <i class="fas fa-certificate"></i>Project License
                    </label>
                    <select class="form-select" id="project_license" name="project_license">
                        <option value="">Select License</option>
                        <option value="MIT" <?php echo ($project['project_license'] === 'MIT') ? 'selected' : ''; ?>>MIT License</option>
                        <option value="Apache 2.0" <?php echo ($project['project_license'] === 'Apache 2.0') ? 'selected' : ''; ?>>Apache License 2.0</option>
                        <option value="GPL v3" <?php echo ($project['project_license'] === 'GPL v3') ? 'selected' : ''; ?>>GNU GPL v3</option>
                        <option value="BSD" <?php echo ($project['project_license'] === 'BSD') ? 'selected' : ''; ?>>BSD License</option>
                        <option value="Creative Commons" <?php echo ($project['project_license'] === 'Creative Commons') ? 'selected' : ''; ?>>Creative Commons</option>
                        <option value="Proprietary" <?php echo ($project['project_license'] === 'Proprietary') ? 'selected' : ''; ?>>Proprietary</option>
                        <option value="Other" <?php echo ($project['project_license'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="col-12">
                    <label for="target_audience" class="form-label">
                        <i class="fas fa-bullseye"></i>Target Audience
                    </label>
                    <textarea class="form-control" id="target_audience" name="target_audience"
                              rows="3" placeholder="Who is this project intended for? (e.g., developers, students, businesses)"><?php echo htmlspecialchars($project['target_audience'] ?? ''); ?></textarea>
                    <div class="form-text">Describe the primary users or beneficiaries of your project</div>
                </div>

                <div class="col-12">
                    <label for="keywords" class="form-label">
                        <i class="fas fa-tags"></i>Keywords & Tags
                    </label>
                    <input type="text" class="form-control tags-input" id="keywords" name="keywords"
                           value="<?php echo htmlspecialchars($project['keywords'] ?? ''); ?>"
                           placeholder="web development, php, mysql, bootstrap, javascript" maxlength="500">
                    <div class="form-text">Separate keywords with commas. These help others find your project.</div>
                    <div class="character-counter">
                        <span id="keywords_count">0</span>/500 characters
                    </div>
                </div>
            </div>
        </div>

        <!-- Project Goals & Challenges Section -->
        <div class="form-section">
            <h3 class="section-title">
                <i class="fas fa-target"></i>Goals & Challenges
            </h3>
            <div class="row g-4">
                <div class="col-12">
                    <label for="project_goals" class="form-label">
                        <i class="fas fa-flag"></i>Project Goals
                    </label>
                    <textarea class="form-control" id="project_goals" name="project_goals"
                              rows="4" placeholder="What are the main objectives and goals of this project?"><?php echo htmlspecialchars($project['project_goals'] ?? ''); ?></textarea>
                    <div class="form-text">Describe what you aim to achieve with this project</div>
                </div>

                <div class="col-12">
                    <label for="challenges_faced" class="form-label">
                        <i class="fas fa-exclamation-triangle"></i>Challenges Faced
                    </label>
                    <textarea class="form-control" id="challenges_faced" name="challenges_faced"
                              rows="4" placeholder="What challenges did you encounter during development?"><?php echo htmlspecialchars($project['challenges_faced'] ?? ''); ?></textarea>
                    <div class="form-text">Share the difficulties and how you overcame them</div>
                </div>

                <div class="col-12">
                    <label for="future_enhancements" class="form-label">
                        <i class="fas fa-rocket"></i>Future Enhancements
                    </label>
                    <textarea class="form-control" id="future_enhancements" name="future_enhancements"
                              rows="4" placeholder="What features or improvements do you plan to add?"><?php echo htmlspecialchars($project['future_enhancements'] ?? ''); ?></textarea>
                    <div class="form-text">Describe planned updates, new features, or improvements</div>
                </div>
            </div>
        </div>

        <!-- Links & Contact Section -->
        <div class="form-section">
            <h3 class="section-title">
                <i class="fas fa-link"></i>Links & Contact
            </h3>
            <div class="row g-4">
                <div class="col-md-6">
                    <label for="github_repo" class="form-label">
                        <i class="fab fa-github"></i>GitHub Repository
                    </label>
                    <input type="url" class="form-control" id="github_repo" name="github_repo"
                           value="<?php echo htmlspecialchars($project['github_repo'] ?? ''); ?>"
                           placeholder="https://github.com/username/repository">
                    <div class="form-text">Link to your project's source code</div>
                </div>

                <div class="col-md-6">
                    <label for="live_demo_url" class="form-label">
                        <i class="fas fa-external-link-alt"></i>Live Demo URL
                    </label>
                    <input type="url" class="form-control" id="live_demo_url" name="live_demo_url"
                           value="<?php echo htmlspecialchars($project['live_demo_url'] ?? ''); ?>"
                           placeholder="https://your-project-demo.com">
                    <div class="form-text">Link to live version or demo of your project</div>
                </div>

                <div class="col-md-6">
                    <label for="contact_email" class="form-label">
                        <i class="fas fa-envelope"></i>Contact Email
                    </label>
                    <input type="email" class="form-control" id="contact_email" name="contact_email"
                           value="<?php echo htmlspecialchars($project['contact_email'] ?? ''); ?>"
                           placeholder="your.email@example.com">
                    <div class="form-text">Email for project-related inquiries</div>
                </div>

                <div class="col-md-6">
                    <label for="social_links" class="form-label">
                        <i class="fas fa-share-alt"></i>Social Links
                    </label>
                    <textarea class="form-control" id="social_links" name="social_links"
                              rows="3" placeholder="LinkedIn: https://linkedin.com/in/yourprofile&#10;Twitter: https://twitter.com/yourhandle&#10;Portfolio: https://yourwebsite.com"><?php echo htmlspecialchars($project['social_links'] ?? ''); ?></textarea>
                    <div class="form-text">Add your social media profiles, portfolio, or other relevant links (one per line)</div>
                </div>
            </div>
        </div>

        <!-- Sticky Action Buttons -->
        <div class="sticky-actions">
            <div class="d-flex gap-3 justify-content-between align-items-center">
                <div class="text-muted">
                    <small><i class="fas fa-save me-1"></i>Auto-save enabled</small>
                </div>
                <div class="d-flex gap-3">
                    <a href="all_projects.php" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Project
                    </button>
                </div>
            </div>
        </div>
    </form>
</main>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/layout_user.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('editForm');
        const progressFill = document.getElementById('progressFill');
        const requiredFields = form.querySelectorAll('[required]');

        // Character counters
        const counters = {
            'project_name': { element: document.getElementById('project_name_count'), max: 255 },
            'description': { element: document.getElementById('description_count'), max: null },
            'keywords': { element: document.getElementById('keywords_count'), max: 500 }
        };

        // Initialize character counters
        Object.keys(counters).forEach(fieldId => {
            const field = document.getElementById(fieldId);
            const counter = counters[fieldId];

            if (field && counter.element) {
                // Set initial count
                counter.element.textContent = field.value.length;

                // Update on input
                field.addEventListener('input', function() {
                    const length = this.value.length;
                    counter.element.textContent = length;

                    // Color coding for character limits
                    if (counter.max) {
                        if (length > counter.max * 0.9) {
                            counter.element.style.color = 'var(--danger-color)';
                        } else if (length > counter.max * 0.7) {
                            counter.element.style.color = 'var(--warning-color)';
                        } else {
                            counter.element.style.color = 'var(--gray-400)';
                        }
                    }
                });
            }
        });

        // Form progress calculation
        function updateProgress() {
            const allInputs = form.querySelectorAll('input, textarea, select');
            let filledInputs = 0;

            allInputs.forEach(input => {
                if (input.value.trim() !== '') {
                    filledInputs++;
                }
            });

            const progress = (filledInputs / allInputs.length) * 100;
            progressFill.style.width = progress + '%';
        }

        // Update progress on input changes
        form.addEventListener('input', updateProgress);
        form.addEventListener('change', updateProgress);

        // Initial progress calculation
        updateProgress();

        // Form validation
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const errors = [];

            // Check required fields
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = 'var(--danger-color)';
                    isValid = false;
                    errors.push(`${field.previousElementSibling.textContent.replace('*', '').trim()} is required`);
                } else {
                    field.style.borderColor = 'var(--gray-200)';
                }
            });

            // Validate email
            const email = document.getElementById('contact_email');
            if (email.value && !email.checkValidity()) {
                email.style.borderColor = 'var(--danger-color)';
                isValid = false;
                errors.push('Please enter a valid email address');
            }

            // Validate URLs
            const urls = ['github_repo', 'live_demo_url'];
            urls.forEach(urlId => {
                const urlField = document.getElementById(urlId);
                if (urlField.value && !urlField.checkValidity()) {
                    urlField.style.borderColor = 'var(--danger-color)';
                    isValid = false;
                    errors.push(`Please enter a valid URL for ${urlField.previousElementSibling.textContent}`);
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fix the following errors:\n\n' + errors.join('\n'));

                // Scroll to first error
                const firstError = form.querySelector('[style*="danger-color"]');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
            }
        });

        // Auto-save functionality (localStorage simulation)
        let autoSaveTimeout;
        const formInputs = form.querySelectorAll('input, textarea, select');
        let hasUnsavedChanges = false;

        formInputs.forEach(input => {
            input.addEventListener('input', function() {
                hasUnsavedChanges = true;
                document.title = '• ' + document.title.replace('• ', '');

                clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(() => {
                    // Simulate auto-save (you can implement actual auto-save here)
                    console.log('Auto-saving...');
                }, 3000);
            });
        });

        // Warn before leaving with unsaved changes
        window.addEventListener('beforeunload', function(e) {
            if (hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Mark as saved when form is submitted
        form.addEventListener('submit', function() {
            hasUnsavedChanges = false;
        });

        // Enhanced UX: Real-time field validation
        const emailField = document.getElementById('contact_email');
        const urlFields = [document.getElementById('github_repo'), document.getElementById('live_demo_url')];

        emailField.addEventListener('blur', function() {
            if (this.value && !this.checkValidity()) {
                this.style.borderColor = 'var(--danger-color)';
                this.nextElementSibling.textContent = 'Please enter a valid email address';
                this.nextElementSibling.style.color = 'var(--danger-color)';
            } else {
                this.style.borderColor = 'var(--gray-200)';
                this.nextElementSibling.style.color = 'var(--gray-500)';
            }
        });

        urlFields.forEach(urlField => {
            if (urlField) {
                urlField.addEventListener('blur', function() {
                    if (this.value && !this.checkValidity()) {
                        this.style.borderColor = 'var(--danger-color)';
                        const helpText = this.nextElementSibling;
                        helpText.textContent = 'Please enter a valid URL (e.g., https://example.com)';
                        helpText.style.color = 'var(--danger-color)';
                    } else {
                        this.style.borderColor = 'var(--gray-200)';
                        this.nextElementSibling.style.color = 'var(--gray-500)';
                    }
                });
            }
        });

        // Smooth scroll for section navigation
        const sectionTitles = document.querySelectorAll('.section-title');
        sectionTitles.forEach((title, index) => {
            title.style.cursor = 'pointer';
            title.addEventListener('click', function() {
                this.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });

        // Tags input enhancement for keywords
        const keywordsField = document.getElementById('keywords');
        keywordsField.addEventListener('input', function() {
            // Auto-format tags with proper spacing
            let value = this.value;
            value = value.replace(/,\s*,/g, ','); // Remove double commas
            value = value.replace(/,(?!\s)/g, ', '); // Add space after comma if not present
            if (value !== this.value) {
                this.value = value;
            }
        });

        // Enhanced form sections collapsing (optional)
        sectionTitles.forEach(title => {
            const icon = title.querySelector('i');
            const section = title.closest('.form-section');
            const content = section.querySelector('.row');

            // Add collapse functionality on double-click
            title.addEventListener('dblclick', function() {
                content.style.display = content.style.display === 'none' ? 'flex' : 'none';
                icon.style.transform = content.style.display === 'none' ? 'rotate(-90deg)' : 'rotate(0deg)';
            });
        });
    });
</script>
</body>
</html>