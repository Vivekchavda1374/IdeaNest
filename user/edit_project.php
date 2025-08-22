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
    $project_name = trim($_POST['project_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $classification = trim($_POST['classification'] ?? '');
    $project_type = trim($_POST['project_type'] ?? '');
    $language = trim($_POST['language'] ?? '');

    // Validate required fields
    if (empty($project_name) || empty($description) || empty($classification)) {
        $error_message = 'Project name, description, and classification are required.';
    } else {
        // Update project
        $update_sql = "UPDATE admin_approved_projects SET 
                       project_name = ?, 
                       description = ?, 
                       classification = ?, 
                       project_type = ?, 
                       language = ?
                       WHERE id = ?";

        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sssssi", $project_name, $description, $classification, $project_type, $language, $project_id);

        if ($update_stmt->execute()) {
            $success_message = 'Project updated successfully!';
        } else {
            $error_message = 'Error updating project: ' . $conn->error;
        }
        $update_stmt->close();
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

    <!-- Use same CSS variables and styles from all_projects.php -->
    <style>
        /* Import the same CSS variables and base styles from all_projects.php */
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
            background: var(--white);
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
            background: var(--white);
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

        .form-label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: var(--spacing-sm);
            font-size: var(--font-size-sm);
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
            outline: none;
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
                <p class="mb-0">Make changes to your project details</p>
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
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Edit Form -->
    <form method="post" class="edit-form">
        <div class="row g-4">
            <div class="col-12">
                <label for="project_name" class="form-label">
                    <i class="fas fa-project-diagram me-2"></i>Project Name *
                </label>
                <input type="text" class="form-control" id="project_name" name="project_name"
                       value="<?php echo htmlspecialchars($project['project_name']); ?>"
                       placeholder="Enter project name" required>
            </div>

            <div class="col-12">
                <label for="description" class="form-label">
                    <i class="fas fa-file-text me-2"></i>Description *
                </label>
                <textarea class="form-control" id="description" name="description"
                          rows="6" placeholder="Describe your project in detail" required><?php echo htmlspecialchars($project['description']); ?></textarea>
            </div>

            <div class="col-md-6">
                <label for="classification" class="form-label">
                    <i class="fas fa-tags me-2"></i>Classification *
                </label>
                <select class="form-select" id="classification" name="classification" required>
                    <option value="">Select Classification</option>
                    <option value="Web Development" <?php echo ($project['classification'] === 'Web Development') ? 'selected' : ''; ?>>Web Development</option>
                    <option value="Mobile App" <?php echo ($project['classification'] === 'Mobile App') ? 'selected' : ''; ?>>Mobile App</option>
                    <option value="Desktop Application" <?php echo ($project['classification'] === 'Desktop Application') ? 'selected' : ''; ?>>Desktop Application</option>
                    <option value="Game Development" <?php echo ($project['classification'] === 'Game Development') ? 'selected' : ''; ?>>Game Development</option>
                    <option value="Data Science" <?php echo ($project['classification'] === 'Data Science') ? 'selected' : ''; ?>>Data Science</option>
                    <option value="Machine Learning" <?php echo ($project['classification'] === 'Machine Learning') ? 'selected' : ''; ?>>Machine Learning</option>
                    <option value="AI" <?php echo ($project['classification'] === 'AI') ? 'selected' : ''; ?>>AI</option>
                    <option value="IoT" <?php echo ($project['classification'] === 'IoT') ? 'selected' : ''; ?>>IoT</option>
                    <option value="Other" <?php echo ($project['classification'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>

            <div class="col-md-6">
                <label for="project_type" class="form-label">
                    <i class="fas fa-layer-group me-2"></i>Project Type
                </label>
                <select class="form-select" id="project_type" name="project_type">
                    <option value="">Select Type</option>
                    <option value="Open Source" <?php echo ($project['project_type'] === 'Open Source') ? 'selected' : ''; ?>>Open Source</option>
                    <option value="Commercial" <?php echo ($project['project_type'] === 'Commercial') ? 'selected' : ''; ?>>Commercial</option>
                    <option value="Educational" <?php echo ($project['project_type'] === 'Educational') ? 'selected' : ''; ?>>Educational</option>
                    <option value="Personal" <?php echo ($project['project_type'] === 'Personal') ? 'selected' : ''; ?>>Personal</option>
                    <option value="Research" <?php echo ($project['project_type'] === 'Research') ? 'selected' : ''; ?>>Research</option>
                </select>
            </div>

            <div class="col-md-6">
                <label for="language" class="form-label">
                    <i class="fas fa-code me-2"></i>Programming Language
                </label>
                <input type="text" class="form-control" id="language" name="language"
                       value="<?php echo htmlspecialchars($project['language'] ?? ''); ?>"
                       placeholder="e.g., PHP, Python, JavaScript">
            </div>

            <div class="col-12">
                <div class="d-flex gap-3 justify-content-end">
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
        // Add form validation
        const form = document.querySelector('.edit-form');
        const requiredFields = form.querySelectorAll('[required]');

        form.addEventListener('submit', function(e) {
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = 'var(--danger-color)';
                    isValid = false;
                } else {
                    field.style.borderColor = 'var(--gray-200)';
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });

        // Add character counter for description
        const description = document.getElementById('description');
        const counter = document.createElement('div');
        counter.className = 'text-muted mt-2 small';
        counter.textContent = `${description.value.length} characters`;
        description.parentNode.appendChild(counter);

        description.addEventListener('input', function() {
            counter.textContent = `${this.value.length} characters`;
        });

        // Auto-save functionality (optional)
        let autoSaveTimeout;
        const formInputs = form.querySelectorAll('input, textarea, select');

        formInputs.forEach(input => {
            input.addEventListener('input', function() {
                clearTimeout(autoSaveTimeout);
                // Show "unsaved changes" indicator
                document.title = '• Edit Project - IdeaNest';
            });
        });
    });
</script>
</body>
</html>