<?php
include '../Login/Login/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login/Login/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$project_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($project_id <= 0) {
    header("Location: user_project_search.php");
    exit;
}

$sql = "SELECT * FROM admin_approved_projects WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: user_project_search.php");
    exit;
}

$project = $result->fetch_assoc();

// Check if the user has bookmarked this project
$bookmarkSql = "SELECT id FROM user_bookmarks WHERE user_id = ? AND project_id = ?";
$bookmarkStmt = $conn->prepare($bookmarkSql);
$bookmarkStmt->bind_param("ii", $user_id, $project_id);
$bookmarkStmt->execute();
$bookmarkResult = $bookmarkStmt->get_result();
$isBookmarked = $bookmarkResult->num_rows > 0;
$bookmarkStmt->close();

$related_sql = "SELECT id, project_name, project_type, language FROM admin_approved_projects
                WHERE project_type = ? AND id != ? 
                ORDER BY submission_date DESC LIMIT 3";
$related_stmt = $conn->prepare($related_sql);
$related_stmt->bind_param("si", $project['project_type'], $project_id);
$related_stmt->execute();
$related_result = $related_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['project_name']); ?> | IdeaNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #ffffff;
            --primary-hover: #2414ff;
            --primary-light: rgba(99, 102, 241, 0.1);
            --primary-dark: #3e2eff;
            --secondary-color: #f43f5e;
            --secondary-light: rgba(244, 63, 94, 0.1);
            --success-color: #10b981;
            --success-light: rgba(16, 185, 129, 0.1);
            --success-hover: #059669;
            --warning-color: #f59e0b;
            --warning-light: rgba(245, 158, 11, 0.1);
            --danger-color: #ef4444;
            --danger-light: rgba(239, 68, 68, 0.1);
            --info-color: #0ea5e9;
            --info-light: rgba(14, 165, 233, 0.1);
            --light-bg: #f9fafb;
            --dark-text: #1e293b;
            --light-text: #64748b;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --card-shadow: 0 2px 20px rgba(0, 0, 0, 0.03);
            --card-hover-shadow: 0 12px 28px rgba(0, 0, 0, 0.07);
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            --border-radius: 1rem;
            --border-radius-sm: 0.75rem;
            --font-primary: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--light-bg);
            font-family: var(--font-primary);
            color: var(--dark-text);
            line-height: 1.6;
        }

        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: var(--gray-100);
            border-radius: 8px;
        }
        ::-webkit-scrollbar-thumb {
            background: var(--gray-300);
            border-radius: 8px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--gray-400);
        }

        /* Hero section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 3.5rem 0 3rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.15);
            margin-bottom: 2.5rem;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Cpath fill='%23ffffff' fill-opacity='0.05' d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z'%3E%3C/path%3E%3C/svg%3E");
            opacity: 0.1;
        }

        .hero-shape {
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 60px;
        }

        .hero-container {
            position: relative;
            z-index: 2;
        }

        .btn-back {
            background-color: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: var(--border-radius-sm);
            padding: 0.6rem 1.2rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-back:hover {
            background-color: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.3);
            color: white;
            transform: translateY(-2px);
        }

        .project-title {
            font-weight: 700;
            margin: 1.5rem 0 1rem;
            font-size: 2.5rem;
            line-height: 1.2;
            letter-spacing: -0.025em;
        }

        .badge {
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            margin-right: 0.5rem;
            font-size: 0.875rem;
            letter-spacing: 0.025em;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
        }

        .badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .badge-project-type {
            background-color: var(--primary-light);
            color: var(--primary-color);
            border: 1px solid rgba(99, 102, 241, 0.3);
        }

        .badge-language {
            background-color: var(--info-light);
            color: var(--info-color);
            border: 1px solid rgba(14, 165, 233, 0.3);
        }

        .badge-classification {
            background-color: var(--warning-light);
            color: var(--warning-color);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        /* Content cards */
        .content-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            transition: var(--transition);
            border: 1px solid rgba(226, 232, 240, 0.6);
            overflow: hidden;
        }

        .content-card:hover {
            box-shadow: var(--card-hover-shadow);
            transform: translateY(-3px);
        }

        .card-header {
            padding: 1.5rem 1.75rem;
            border-bottom: 1px solid var(--gray-200);
            background-color: white;
        }

        .card-body {
            padding: 1.75rem;
        }

        /* Section titles */
        .section-title {
            color: var(--dark-text);
            font-weight: 600;
            font-size: 1.25rem;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            position: relative;
        }

        .section-title i {
            color: var(--primary-color);
            font-size: 1.15rem;
        }

        /* Description content */
        .description-content {
            line-height: 1.8;
            white-space: pre-line;
            color: var(--dark-text);
        }

        /* Video container */
        .video-container {
            border-radius: var(--border-radius-sm);
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        /* Buttons */
        .btn-action {
            border-radius: var(--border-radius-sm);
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            transition: var(--transition);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-download {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .btn-download:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            color: white;
        }

        .btn-code {
            background-color: var(--success-color);
            border-color: var(--success-color);
            color: white;
        }

        .btn-code:hover {
            background-color: var(--success-hover);
            border-color: var(--success-hover);
            color: white;
        }

        /* Project details */
        .project-detail {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: flex-start;
            transition: var(--transition);
        }

        .project-detail:hover {
            background-color: var(--gray-100);
        }

        .project-detail:last-child {
            border-bottom: none;
        }

        .detail-icon {
            color: var(--primary-color);
            font-size: 1.25rem;
            margin-right: 1rem;
            min-width: 1.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 40px;
            width: 40px;
            background-color: var(--primary-light);
            border-radius: 50%;
        }

        .detail-content {
            flex: 1;
            padding-top: 0.25rem;
        }

        .detail-label {
            color: var(--light-text);
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 500;
        }

        .detail-value {
            font-weight: 600;
            color: var(--dark-text);
            font-size: 1.05rem;
        }

        /* Related projects */
        .related-card {
            background-color: white;
            border-radius: var(--border-radius-sm);
            padding: 1.25rem;
            margin-bottom: 1.25rem;
            transition: var(--transition);
            border: 1px solid var(--gray-200);
            display: block;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.04);
        }

        .related-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-hover-shadow);
            border-color: var(--primary-light);
        }

        .related-card h5 {
            color: var(--dark-text);
            font-size: 1.15rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            transition: var(--transition);
        }

        .related-card:hover h5 {
            color: var(--primary-color);
        }

        /* Breadcrumb */
        .breadcrumb {
            margin-bottom: 0;
            padding: 0;
            background: transparent;
        }

        .breadcrumb-item {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .breadcrumb-item.active {
            color: white;
        }

        .breadcrumb-item a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb-item a:hover {
            color: white;
            text-decoration: underline;
        }

        /* Submission info */
        .submission-info {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            margin-top: 0.75rem;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }

        .btn-sm {
            padding: 0.4rem 0.75rem;
            font-size: 0.85rem;
            border-radius: 0.5rem;
        }

        /* Download button in project details */
        .btn-sm.btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
            transition: var(--transition);
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
        }

        .btn-sm.btn-success:hover {
            background-color: var(--success-hover);
            border-color: var(--success-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
        }

        /* Responsive adjustments */
        @media (max-width: 991.98px) {
            .project-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 767.98px) {
            .hero-section {
                padding: 2.5rem 0 2rem;
            }

            .project-title {
                font-size: 1.8rem;
                line-height: 1.3;
            }

            .related-section {
                margin-top: 1.5rem;
            }

            .card-header {
                padding: 1.25rem;
            }

            .card-body {
                padding: 1.25rem;
            }

            .badge {
                padding: 0.4rem 0.8rem;
                font-size: 0.8rem;
            }

            .project-detail {
                padding: 0.75rem 1rem;
            }
        }

        @media (max-width: 575.98px) {
            .project-title {
                font-size: 1.6rem;
            }

            .btn-action {
                width: 100%;
                margin-bottom: 0.75rem;
                justify-content: center;
            }
        }

        /* Additional styles for bookmark button */
        .btn-back.active {
            background-color: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.4);
        }
        .btn-back.active i {
            color: #f7e018;
        }
    </style>
</head>

<body>
<div class="hero-section">
    <div class="hero-container container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="user_project_search.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Back to Projects
            </a>
            <div>
                <button id="bookmark-btn" class="btn btn-back <?php echo $isBookmarked ? 'active' : ''; ?>" 
                        data-project-id="<?php echo $project_id; ?>" data-user-id="<?php echo $user_id; ?>" 
                        data-status="<?php echo $isBookmarked ? 'bookmarked' : 'not-bookmarked'; ?>">
                    <i class="<?php echo $isBookmarked ? 'fas' : 'far'; ?> fa-bookmark"></i> 
                    <span id="bookmark-text"><?php echo $isBookmarked ? 'Bookmarked' : 'Bookmark'; ?></span>
                </button>
            </div>
        </div>

        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="user_project_search.php">Projects</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($project['project_name']); ?></li>
            </ol>
        </nav>

        <h1 class="project-title"><?php echo htmlspecialchars($project['project_name']); ?></h1>

        <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="badge badge-project-type">
                <i class="fas fa-folder me-1"></i> <?php echo htmlspecialchars($project['project_type']); ?>
            </span>
            <span class="badge badge-language">
                <i class="fas fa-code me-1"></i> <?php echo htmlspecialchars($project['language']); ?>
            </span>
            <?php if (!empty($project['classification'])): ?>
                <span class="badge badge-classification">
                    <i class="fas fa-tag me-1"></i> <?php echo htmlspecialchars($project['classification']); ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="submission-info">
            <i class="far fa-calendar-alt"></i>
            Submitted on <?php echo date('F d, Y', strtotime($project['submission_date'])); ?>
        </div>
    </div>

    <svg class="hero-shape" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 60">
        <path fill="#f9fafb" fill-opacity="1" d="M0,32L80,37.3C160,43,320,53,480,53.3C640,53,800,43,960,37.3C1120,32,1280,32,1360,32L1440,32L1440,60L1360,60C1280,60,1120,60,960,60C800,60,640,60,480,60C320,60,160,60,80,60L0,60Z"></path>
    </svg>
</div>

<div class="container mb-5">
    <div class="row">
        <div class="col-lg-8">
            <!-- Project Description -->
            <div class="content-card">
                <div class="card-header">
                    <h2 class="section-title">
                        <i class="fas fa-info-circle"></i> Project Description
                    </h2>
                </div>
                <div class="card-body">
                    <div class="description-content">
                        <?php echo nl2br(htmlspecialchars($project['description'])); ?>
                    </div>
                </div>
            </div>

            <!-- Project Demo -->
            <?php if (!empty($project['video_path']) && file_exists('uploads/' . $project['video_path'])): ?>
                <div class="content-card">
                    <div class="card-header">
                        <h2 class="section-title">
                            <i class="fas fa-play-circle"></i> Project Demo
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="video-container">
                            <video width="100%" controls>
                                <source src="uploads/<?php echo htmlspecialchars($project['video_path']); ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Additional Information -->
            <?php if (!empty($project['additional_info'])): ?>
                <div class="content-card">
                    <div class="card-header">
                        <h2 class="section-title">
                            <i class="fas fa-plus-circle"></i> Additional Information
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="description-content">
                            <?php echo nl2br(htmlspecialchars($project['additional_info'])); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Download Buttons -->
            <div class="d-flex flex-wrap gap-3 mt-4">
                <?php if (!empty($project['instruction_file_path']) && file_exists('uploads/' . $project['instruction_file_path'])): ?>
                    <a href="uploads/<?php echo htmlspecialchars($project['instruction_file_path']); ?>"
                       class="btn btn-action btn-download" download>
                        <i class="fas fa-file-alt"></i> Download Instructions
                    </a>
                <?php endif; ?>

                <?php if (!empty($project['code_file_path']) && file_exists('uploads/' . $project['code_file_path'])): ?>
                    <a href="uploads/<?php echo htmlspecialchars($project['code_file_path']); ?>"
                       class="btn btn-action btn-code" download>
                        <i class="fas fa-code"></i> Download Code
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Project Details Card -->
            <div class="content-card">
                <div class="card-header">
                    <h2 class="section-title">
                        <i class="fas fa-clipboard-list"></i> Project Details
                    </h2>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($project['code_file_path'])): ?>
                        <div class="project-detail">
                            <div class="detail-icon">
                                <i class="fas fa-file-code"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">Code File</div>
                                <div class="detail-value">
                                    <a href="uploads/<?php echo htmlspecialchars($project['code_file_path']); ?>"
                                       class="btn btn-sm btn-success" download>
                                        <i class="fas fa-download me-1"></i> Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="project-detail">
                        <div class="detail-icon">
                            <i class="fas fa-code"></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Language</div>
                            <div class="detail-value"><?php echo htmlspecialchars($project['language']); ?></div>
                        </div>
                    </div>

                    <?php if (!empty($project['framework'])): ?>
                        <div class="project-detail">
                            <div class="detail-icon">
                                <i class="fas fa-layer-group"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">Framework</div>
                                <div class="detail-value"><?php echo htmlspecialchars($project['framework']); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($project['version'])): ?>
                        <div class="project-detail">
                            <div class="detail-icon">
                                <i class="fas fa-code-branch"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">Version</div>
                                <div class="detail-value"><?php echo htmlspecialchars($project['version']); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($project['requirements'])): ?>
                        <div class="project-detail">
                            <div class="detail-icon">
                                <i class="fas fa-list-check"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">Requirements</div>
                                <div class="detail-value"><?php echo htmlspecialchars($project['requirements']); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($project['github_link'])): ?>
                        <div class="project-detail">
                            <div class="detail-icon">
                                <i class="fab fa-github"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">GitHub</div>
                                <div class="detail-value">
                                    <a href="<?php echo htmlspecialchars($project['github_link']); ?>"
                                       class="text-primary" target="_blank">View Repository</a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Related Projects -->
            <?php if ($related_result->num_rows > 0): ?>
            <div class="content-card related-section">
                <div class="card-header">
                    <h2 class="section-title">
                        <i class="fas fa-project-diagram"></i> Related Projects
                    </h2>
                </div>
                <div class="card-body">
                    <?php while ($related = $related_result->fetch_assoc()): ?>
                    <a href="project_details.php?id=<?php echo $related['id']; ?>" class="text-decoration-none">
                        <div class="related-card">
                            <h5><?php echo htmlspecialchars($related['project_name']); ?></h5>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge badge-project-type"><?php echo htmlspecialchars($related['project_type']); ?></span>
                                <span class="badge badge-language"><?php echo htmlspecialchars($related['language']); ?></span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="bg-dark text-white py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6 mb-4 mb-md-0">
                <h5 class="mb-3">IdeaNest</h5>
                <p class="mb-1">A platform for sharing innovative project ideas and solutions.</p>
                <p class="mb-0 text-muted">© <?php echo date('Y'); ?> IdeaNest. All rights reserved.</p>
            </div>
            <div class="col-md-6">
                <div class="row">
                    <div class="col-6">
                        <h5 class="mb-3">Quick Links</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2"><a href="../index.php" class="text-white-50">Home</a></li>
                            <li class="mb-2"><a href="user_project_search.php" class="text-white-50">Projects</a></li>
                            <li class="mb-2"><a href="faq.php" class="text-white-50">FAQ</a></li>
                            <li class="mb-2"><a href="contact.php" class="text-white-50">Contact Us</a></li>
                        </ul>
                    </div>
                    <div class="col-6">
                        <h5 class="mb-3">Follow Us</h5>
                        <div class="d-flex gap-3">
                            <a href="#" class="text-white-50"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="text-white-50"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="text-white-50"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="text-white-50"><i class="fab fa-github"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Bookmark functionality
        const bookmarkBtn = document.getElementById('bookmark-btn');
        if (bookmarkBtn) {
            bookmarkBtn.addEventListener('click', function() {
                const projectId = this.getAttribute('data-project-id');
                const status = this.getAttribute('data-status');
                const endpoint = status === 'bookmarked' ? 'forms/remove_bookmark.php' : 'forms/add_bookmark.php';
                
                $.ajax({
                    url: endpoint,
                    type: 'POST',
                    data: { project_id: projectId },
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                // Toggle bookmark status
                                if (status === 'bookmarked') {
                                    bookmarkBtn.setAttribute('data-status', 'not-bookmarked');
                                    bookmarkBtn.querySelector('i').classList.replace('fas', 'far');
                                    document.getElementById('bookmark-text').textContent = 'Bookmark';
                                    bookmarkBtn.classList.remove('active');
                                } else {
                                    bookmarkBtn.setAttribute('data-status', 'bookmarked');
                                    bookmarkBtn.querySelector('i').classList.replace('far', 'fas');
                                    document.getElementById('bookmark-text').textContent = 'Bookmarked';
                                    bookmarkBtn.classList.add('active');
                                }
                            } else {
                                alert(result.message);
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                        }
                    },
                    error: function() {
                        alert('Error communicating with the server');
                    }
                });
            });
        }
        
        // Add smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    });
</script>
</body>
</html>

<?php
// Close connections
$stmt->close();
$related_stmt->close();
$conn->close();
?>