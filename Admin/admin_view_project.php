<?php
// Production-safe error reporting
if (($_ENV['APP_ENV'] ?? 'development') !== 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
}

require_once dirname(__FILE__) . '/../includes/autoload_simple.php';
include "../Login/Login/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../Login/Login/login.php");
    exit();
}

$user_name = $_SESSION['user_name'] ?? "Admin";

// Handle project actions
if(isset($_POST['reject_submit'])) {
    $$project_id = post_param('project_id');
    $$rejection_reason = post_param('rejection_reason');
    rejectProject($project_id, $rejection_reason, $conn);
}

if(isset($_GET['action']) && isset($_GET['id'])) {
    $$project_id = get_param('id');
    $$action = get_param('action');
    
    if($action == 'approve') {
        approveProject($project_id, $conn);
    }
}

// Functions
function approveProject($project_id, $conn) {
    $query = "SELECT p.*, r.email, r.name FROM projects p JOIN register r ON p.user_id = r.id WHERE p.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        $project = $result->fetch_assoc();
        
        $approve_query = "INSERT INTO admin_approved_projects (
            user_id, project_name, project_type, classification, project_category,
            difficulty_level, development_time, team_size, target_audience, project_goals,
            challenges_faced, future_enhancements, github_repo, live_demo_url, project_license,
            keywords, contact_email, social_links, description, language, image_path, video_path,
            code_file_path, instruction_file_path, presentation_file_path, additional_files_path,
            submission_date, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')";

        $approve_stmt = $conn->prepare($approve_query);
        $approve_stmt->bind_param("sssssssssssssssssssssssssss",
            $project['user_id'], $project['project_name'], $project['project_type'],
            $project['classification'], $project['project_category'], $project['difficulty_level'],
            $project['development_time'], $project['team_size'], $project['target_audience'],
            $project['project_goals'], $project['challenges_faced'], $project['future_enhancements'],
            $project['github_repo'], $project['live_demo_url'], $project['project_license'],
            $project['keywords'], $project['contact_email'], $project['social_links'],
            $project['description'], $project['language'], $project['image_path'],
            $project['video_path'], $project['code_file_path'], $project['instruction_file_path'],
            $project['presentation_file_path'], $project['additional_files_path'], $project['submission_date']
        );
        $approve_stmt->execute();

        $update_query = "UPDATE projects SET status = 'approved' WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $project_id);
        $update_stmt->execute();

        sendApprovalEmail($project['email'], $project['name'], $project['project_name'], $conn);

        header("Location: admin_view_project.php?message=Project approved successfully");
        exit;
    }
}

function rejectProject($project_id, $rejection_reason, $conn) {
    $query = "SELECT p.*, r.email, r.name FROM projects p JOIN register r ON p.user_id = r.id WHERE p.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        $project = $result->fetch_assoc();
        
        $reject_query = "INSERT INTO denial_projects (
            user_id, project_name, project_type, classification, project_category,
            difficulty_level, development_time, team_size, target_audience, project_goals,
            challenges_faced, future_enhancements, github_repo, live_demo_url, project_license,
            keywords, contact_email, social_links, description, language, image_path, video_path,
            code_file_path, instruction_file_path, presentation_file_path, additional_files_path,
            submission_date, status, rejection_date, rejection_reason
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'rejected', NOW(), ?)";

        $reject_stmt = $conn->prepare($reject_query);
        $reject_stmt->bind_param("ssssssssssssssssssssssssssss",
            $project['user_id'], $project['project_name'], $project['project_type'],
            $project['classification'], $project['project_category'], $project['difficulty_level'],
            $project['development_time'], $project['team_size'], $project['target_audience'],
            $project['project_goals'], $project['challenges_faced'], $project['future_enhancements'],
            $project['github_repo'], $project['live_demo_url'], $project['project_license'],
            $project['keywords'], $project['contact_email'], $project['social_links'],
            $project['description'], $project['language'], $project['image_path'],
            $project['video_path'], $project['code_file_path'], $project['instruction_file_path'],
            $project['presentation_file_path'], $project['additional_files_path'], $project['submission_date'],
            $rejection_reason
        );
        $reject_stmt->execute();

        $update_query = "UPDATE projects SET status = 'rejected' WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $project_id);
        $update_stmt->execute();

        sendRejectionEmail($project['email'], $project['name'], $project['project_name'], $rejection_reason, $conn);

        header("Location: admin_view_project.php?message=Project rejected successfully");
        exit;
    }
}

function sendApprovalEmail($email, $name, $project_name, $conn) {
    try {
        // Try EmailHelper first
        if (class_exists('EmailHelper')) {
            $emailHelper = new EmailHelper();
            $emailHelper->sendProjectApprovalEmail($email, $project_name, 'approved');
        } elseif (class_exists('SMTPMailer')) {
            // Fallback to SMTPMailer
            $mailer = new SMTPMailer();
            $subject = 'Project Approved - ' . $project_name;
            $body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #10b981;'>Project Approved! 🎉</h2>
                <p>Dear {$name},</p>
                <p>Congratulations! Your project <strong>{$project_name}</strong> has been approved by IdeaNest.</p>
                <p>You can now view your approved project on your dashboard.</p>
                <p>Best regards,<br>IdeaNest Team</p>
            </div>";
            $mailer->send($email, $subject, $body);
        } else {
            error_log('No email class available for approval email');
        }
    } catch (Exception $e) {
        error_log('Approval email failed: ' . $e->getMessage());
    }
}

function sendRejectionEmail($email, $name, $project_name, $reason, $conn) {
    try {
        // Try SMTPMailer first
        if (class_exists('SMTPMailer')) {
            $mailer = new SMTPMailer();
            $subject = 'Project Review - ' . $project_name;
            $body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #ef4444;'>Project Review Update</h2>
                <p>Dear {$name},</p>
                <p>Thank you for submitting your project <strong>{$project_name}</strong> to IdeaNest.</p>
                <p>After careful review, we need you to make some improvements before we can approve it.</p>
                <h3>Feedback:</h3>
                <p style='background: #f3f4f6; padding: 15px; border-left: 4px solid #ef4444;'>{$reason}</p>
                <p>Please review the feedback and resubmit your project with the necessary improvements.</p>
                <p>Best regards,<br>IdeaNest Team</p>
            </div>";
            $mailer->send($email, $subject, $body);
        } else {
            // No email class available - just log
            error_log('No email class available for rejection email to: ' . $email);
        }
    } catch (Exception $e) {
        error_log('Rejection email failed: ' . $e->getMessage());
    }
}

// Get projects
$projects_query = "SELECT * FROM projects ORDER BY submission_date DESC";
$projects_result = $conn->query($projects_query);

$message = $_GET['message'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Projects Management | IdeaNest</title>
    <link rel="icon" type="image/png" href="../../assets/image/fevicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/loading.css">
    <style>
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .table th {
            background-color: #f8f9fa;
            color: #495057;
            border: none;
            font-weight: 600;
        }
        .badge {
            font-size: 0.75em;
            padding: 0.5em 0.75em;
        }
        .btn {
            border-radius: 0.375rem;
            font-weight: 500;
        }
        .modal-header {
            background-color: #4361ee;
            color: white;
            border-radius: 0.5rem 0.5rem 0 0;
        }
        .project-detail-card {
            border-left: 4px solid #4361ee;
            margin-bottom: 1rem;
        }
        .file-link {
            display: inline-block;
            margin: 0.25rem;
            padding: 0.5rem 1rem;
            background: #f8f9fa;
            border-radius: 0.375rem;
            text-decoration: none;
            border: 1px solid #dee2e6;
            transition: all 0.2s ease;
        }
        .file-link:hover {
            background: #e9ecef;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .stats-card {
            background-color: #4361ee;
            color: white;
            border-radius: 0.5rem;
        }
        .enhanced-table {
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            border-radius: 0.5rem;
            overflow: hidden;
        }
        .enhanced-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
            border: none;
            padding: 12px 15px;
        }
        .enhanced-table td {
            padding: 12px 15px;
            vertical-align: middle;
            border-top: 1px solid #f1f1f1;
        }
        .enhanced-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .bg-primary-light {
            background-color: rgba(67, 97, 238, 0.1);
        }
        .bg-success-light {
            background-color: rgba(16, 185, 129, 0.1);
        }
        .bg-warning-light {
            background-color: rgba(245, 158, 11, 0.1);
        }
        .bg-danger-light {
            background-color: rgba(239, 68, 68, 0.1);
        }
    </style>
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>
    
    <div class="main-content" style="margin-left: 250px; padding: 20px;">
        <div class="container-fluid">
            <!-- Mobile Toggle Button -->
            <button class="btn d-lg-none mb-3" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>
            
            <!-- Header Section -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0"><i class="fas fa-project-diagram me-2"></i>Projects Management</h2>
                    <p class="text-muted mb-0">Review and manage project submissions</p>
                </div>
                <div class="stats-card p-3">
                    <div class="row text-center">
                        <div class="col">
                            <h5 class="mb-0"><?php echo $projects_result->num_rows; ?></h5>
                            <small>Total Projects</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-hashtag me-1"></i>ID</th>
                                    <th><i class="fas fa-project-diagram me-1"></i>Project Name</th>
                                    <th><i class="fas fa-tag me-1"></i>Type</th>
                                    <th><i class="fas fa-info-circle me-1"></i>Status</th>
                                    <th><i class="fas fa-calendar me-1"></i>Submitted</th>
                                    <th><i class="fas fa-cogs me-1"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($project = $projects_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $project['id']; ?></td>
                                    <td><?php echo htmlspecialchars($project['project_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($project['project_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $project['status'] == 'pending' ? 'warning' : ($project['status'] == 'approved' ? 'success' : 'danger'); ?>">
                                            <?php echo ucfirst($project['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($project['submission_date'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $project['id']; ?>">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                        <?php if($project['status'] == 'pending'): ?>
                                            <a href="?action=approve&id=<?php echo $project['id']; ?>" class="btn btn-sm btn-success approve-btn" onclick="return handleApprove(event, <?php echo $project['id']; ?>)">
                                                <i class="bi bi-check"></i> Approve
                                            </a>
                                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $project['id']; ?>">
                                                <i class="bi bi-x"></i> Reject
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <!-- View Modal -->
                                <div class="modal fade" id="viewModal<?php echo $project['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-xl">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><i class="fas fa-eye me-2"></i>Project Details - <?php echo htmlspecialchars($project['project_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-8">
                                                        <div class="project-detail-card card p-3 mb-3">
                                                            <h6><i class="fas fa-info-circle text-primary me-2"></i>Basic Information</h6>
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <p><strong>Type:</strong> <?php echo htmlspecialchars($project['project_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                                                    <p><strong>Classification:</strong> <?php echo htmlspecialchars($project['classification'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                                                    <p><strong>Category:</strong> <?php echo htmlspecialchars($project['project_category'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                                                    <p><strong>Language:</strong> <?php echo htmlspecialchars($project['language'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <p><strong>Difficulty:</strong> <span class="badge bg-info"><?php echo htmlspecialchars($project['difficulty_level'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span></p>
                                                                    <p><strong>Team Size:</strong> <?php echo htmlspecialchars($project['team_size'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                                                    <p><strong>Development Time:</strong> <?php echo htmlspecialchars($project['development_time'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                                                    <p><strong>Target Audience:</strong> <?php echo htmlspecialchars($project['target_audience'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="project-detail-card card p-3 mb-3">
                                                            <h6><i class="fas fa-align-left text-success me-2"></i>Description</h6>
                                                            <p><?php echo nl2br(htmlspecialchars($project['description'] ?? '', ENT_QUOTES, 'UTF-8')); ?></p>
                                                        </div>
                                                        
                                                        <?php if($project['project_goals']): ?>
                                                        <div class="project-detail-card card p-3 mb-3">
                                                            <h6><i class="fas fa-bullseye text-warning me-2"></i>Project Goals</h6>
                                                            <p><?php echo nl2br(htmlspecialchars($project['project_goals'] ?? '', ENT_QUOTES, 'UTF-8')); ?></p>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if($project['challenges_faced']): ?>
                                                        <div class="project-detail-card card p-3 mb-3">
                                                            <h6><i class="fas fa-exclamation-triangle text-danger me-2"></i>Challenges Faced</h6>
                                                            <p><?php echo nl2br(htmlspecialchars($project['challenges_faced'] ?? '', ENT_QUOTES, 'UTF-8')); ?></p>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if($project['future_enhancements']): ?>
                                                        <div class="project-detail-card card p-3 mb-3">
                                                            <h6><i class="fas fa-rocket text-info me-2"></i>Future Enhancements</h6>
                                                            <p><?php echo nl2br(htmlspecialchars($project['future_enhancements'] ?? '', ENT_QUOTES, 'UTF-8')); ?></p>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <div class="col-md-4">
                                                        <div class="card p-3 mb-3">
                                                            <h6><i class="fas fa-link text-primary me-2"></i>Links & Resources</h6>
                                                            <?php if($project['github_repo']): ?>
                                                                <a href="<?php echo htmlspecialchars($project['github_repo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="file-link">
                                                                    <i class="fab fa-github me-2"></i>GitHub Repository
                                                                </a>
                                                            <?php endif; ?>
                                                            <?php if($project['live_demo_url']): ?>
                                                                <a href="<?php echo htmlspecialchars($project['live_demo_url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="file-link">
                                                                    <i class="fas fa-external-link-alt me-2"></i>Live Demo
                                                                </a>
                                                            <?php endif; ?>
                                                            <?php if($project['contact_email']): ?>
                                                                <a href="mailto:<?php echo htmlspecialchars($project['contact_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="file-link">
                                                                    <i class="fas fa-envelope me-2"></i>Contact Email
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                        
                                                        <div class="card p-3 mb-3">
                                                            <h6><i class="fas fa-file text-secondary me-2"></i>Project Files</h6>
                                                            <?php if($project['image_path']): ?>
                                                                <a href="../user/<?php echo htmlspecialchars($project['image_path'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="file-link">
                                                                    <i class="fas fa-image me-2"></i>View Image
                                                                </a>
                                                            <?php endif; ?>
                                                            <?php if(!empty($project['video_path'])): ?>
                                                                <a href="../user/<?php echo htmlspecialchars($project['video_path'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="file-link">
                                                                    <i class="fas fa-video me-2"></i>Video
                                                                </a>
                                                            <?php endif; ?>
                                                            <?php if($project['code_file_path']): ?>
                                                                <a href="../user/<?php echo htmlspecialchars($project['code_file_path'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="file-link">
                                                                    <i class="fas fa-code me-2"></i>Source Code
                                                                </a>
                                                            <?php endif; ?>
                                                            <?php if($project['presentation_file_path']): ?>
                                                                <a href="../user/<?php echo htmlspecialchars($project['presentation_file_path'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="file-link">
                                                                    <i class="fas fa-presentation me-2"></i>Presentation
                                                                </a>
                                                            <?php endif; ?>
                                                            <?php if($project['instruction_file_path']): ?>
                                                                <a href="../user/<?php echo htmlspecialchars($project['instruction_file_path']); ?>" target="_blank" class="file-link">
                                                                    <i class="fas fa-file-alt me-2"></i>Instructions
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                        
                                                        <?php if($project['keywords']): ?>
                                                        <div class="card p-3">
                                                            <h6><i class="fas fa-tags text-info me-2"></i>Keywords</h6>
                                                            <?php 
                                                            $keywords = explode(',', $project['keywords']);
                                                            foreach($keywords as $keyword): 
                                                            ?>
                                                                <span class="badge bg-light text-dark me-1 mb-1"><?php echo trim(htmlspecialchars($keyword)); ?></span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    <i class="fas fa-times me-2"></i>Close
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Reject Modal -->
                                <?php if($project['status'] == 'pending'): ?>
                                <div class="modal fade" id="rejectModal<?php echo $project['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title"><i class="fas fa-times-circle me-2"></i>Reject Project</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="post">
                                                <div class="modal-body">
                                                    <div class="alert alert-warning">
                                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                                        <strong>Warning:</strong> This action will reject the project "<?php echo htmlspecialchars($project['project_name']); ?>". Please provide a clear reason for rejection.
                                                    </div>
                                                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label"><i class="fas fa-comment me-2"></i>Rejection Reason</label>
                                                        <textarea class="form-control" name="rejection_reason" rows="4" placeholder="Please provide detailed feedback on why this project is being rejected..." required></textarea>
                                                        <div class="form-text">This feedback will help the student improve their project submission.</div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                        <i class="fas fa-times me-2"></i>Cancel
                                                    </button>
                                                    <button type="submit" name="reject_submit" class="btn btn-danger reject-btn">
                                                        <i class="fas fa-ban me-2"></i>Reject Project
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/loading.js"></script>
    <script>
        function handleApprove(event, projectId) {
            if (confirm('Approve this project? This will send an approval email to the student.')) {
                const button = event.target.closest('a');
                setButtonLoading(button, true, 'Approving...');
                window.loadingManager.show('Approving project and sending notification email...', 'email');
                return true;
            }
            return false;
        }
        
        // Handle reject form submissions
        document.addEventListener('DOMContentLoaded', function() {
            const rejectForms = document.querySelectorAll('form[method="post"]');
            rejectForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('.reject-btn');
                    if (submitBtn) {
                        setButtonLoading(submitBtn, true, 'Rejecting...');
                        window.loadingManager.show('Rejecting project and sending notification email...', 'email');
                    }
                });
            });
        });
    </script>
</body>
</html>