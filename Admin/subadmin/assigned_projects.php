<?php
// Configure session settings
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_path', '/');
session_start();

if (!isset($_SESSION['subadmin_logged_in']) || !$_SESSION['subadmin_logged_in']) {
    header("Location: ../../Login/Login/login.php");
    exit();
}

include_once "../../Login/Login/db.php";
require_once "sidebar_subadmin.php"; // Include the layout file
require_once "../../includes/notification_helper.php"; // Notification system

$subadmin_id = $_SESSION['subadmin_id'];

// Fetch subadmin's classifications
$stmt = $conn->prepare("SELECT domains FROM subadmins WHERE id = ?");
$stmt->bind_param("i", $subadmin_id);
$stmt->execute();
$stmt->bind_result($domains);
$stmt->fetch();
$stmt->close();

// Build classification array from domains with proper mapping
$classifications = [];
if (!empty($domains)) {
    $domain_list = array_map('trim', explode(',', $domains));
    
    // Map domain names to classification values
    $domain_mapping = [
        // Web domains
        'Web Development' => 'Web',
        'Web Application' => 'Web',
        'Web' => 'Web',
        
        // Mobile domains
        'Mobile Development' => 'Mobile',
        'Mobile Application' => 'Mobile',
        'Mobile' => 'Mobile',
        
        // AI/ML domains
        'AI/ML' => 'Artificial Intelligence & Machine Learning',
        'AI & Machine Learning' => 'Artificial Intelligence & Machine Learning',
        'Artificial Intelligence & Machine Learning' => 'Artificial Intelligence & Machine Learning',
        'Data Science' => 'Data Science & Analytics',
        'Data Science & Analytics' => 'Data Science & Analytics',
        
        // Desktop domains
        'Desktop Application' => 'Desktop',
        'Desktop' => 'Desktop',
        
        // System Software
        'System Software' => 'System Software',
        'Cybersecurity' => 'Cybersecurity',
        'Game Development' => 'Game Development',
        
        // IoT and Embedded
        'IoT' => 'Embedded/IoT Software',
        'IoT Projects' => 'Embedded/IoT Software',
        'Internet of Things (IoT)' => 'Embedded/IoT Software',
        'Embedded Systems' => 'Embedded/IoT Software',
        'Embedded/IoT Software' => 'Embedded/IoT Software',
        'Embedded' => 'Embedded/IoT Software',
        'Sensor-Based Projects' => 'Embedded/IoT Software',
        
        // Hardware domains
        'Robotics' => 'Embedded/IoT Software',
        'Automation' => 'Embedded/IoT Software',
        'Communication Systems' => 'Embedded/IoT Software',
        'Power Electronics' => 'Embedded/IoT Software',
        'Wearable Technology' => 'Embedded/IoT Software',
        'Mechatronics' => 'Embedded/IoT Software',
        'Renewable Energy' => 'Embedded/IoT Software',
        
        // Cloud
        'Cloud-Based Applications' => 'Cloud-Based Applications'
    ];
    
    foreach ($domain_list as $domain) {
        if (isset($domain_mapping[$domain])) {
            $classifications[] = $domain_mapping[$domain];
        }
    }
    $classifications = array_unique($classifications);
}

// Map subadmin domains to project classification values (as stored in DB)
$classification_map = [
    'web' => ['web'],
    'web development' => ['web'],
    'web application' => ['web'],
    'mobile' => ['mobile'],
    'mobile development' => ['mobile'],
    'mobile application' => ['mobile'],
    'ai/ml' => ['ai_ml'],
    'ai & machine learning' => ['ai_ml'],
    'ai ml' => ['ai_ml'],
    'desktop' => ['desktop'],
    'desktop application' => ['desktop'],
    'system software' => ['system'],
    'system' => ['system'],
    'embedded systems' => ['embedded', 'embedded_iot'],
    'embedded' => ['embedded', 'embedded_iot'],
    'iot' => ['iot', 'embedded_iot'],
    'iot projects' => ['iot', 'embedded_iot'],
    'robotics' => ['robotics'],
    'automation' => ['automation'],
    'sensor-based projects' => ['sensor'],
    'sensor' => ['sensor'],
    'communication systems' => ['communication'],
    'communication' => ['communication'],
    'power electronics' => ['power'],
    'power' => ['power'],
    'wearable technology' => ['wearable'],
    'wearable' => ['wearable'],
    'mechatronics' => ['mechatronics'],
    'renewable energy' => ['renewable'],
    'renewable' => ['renewable'],
    'cybersecurity' => ['cybersecurity'],
    'game development' => ['game'],
    'game' => ['game'],
    'data science' => ['data_science'],
    'data science & analytics' => ['data_science']
];

$matched_classifications = [];
if (!empty($domains)) {
    $domain_list = array_map('trim', explode(',', $domains));
    foreach ($domain_list as $domain) {
        $domain_lower = strtolower($domain);
        if (isset($classification_map[$domain_lower])) {
            $matched_classifications = array_merge($matched_classifications, $classification_map[$domain_lower]);
        }
    }
    $matched_classifications = array_unique($matched_classifications);
}

// Fetch projects matching subadmin's domain (only pending projects)
if (!empty($classifications)) {
    // Build WHERE clause for domain matching
    $where_conditions = [];
    foreach ($classifications as $classification) {
        $where_conditions[] = "classification LIKE ?";
    }
    $where_clause = implode(' OR ', $where_conditions);
    
    $sql = "SELECT * FROM projects WHERE status = 'pending' AND ($where_clause) ORDER BY submission_date DESC";
    $stmt = $conn->prepare($sql);
    
    // Bind parameters
    $types = str_repeat('s', count($classifications));
    $params = array_map(function($c) { return "%$c%"; }, $classifications);
    $stmt->bind_param($types, ...$params);
    
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    // No domains assigned - show empty result
    $result = $conn->query("SELECT * FROM projects WHERE 1=0");
}



// Email functions removed - no emails sent on approval/rejection for faster processing

// Handle approve/reject actions
$action_message = '';
if (isset($_POST['action']) && isset($_POST['project_id'])) {
    $project_id = intval($_POST['project_id']);
    $action = $_POST['action'];
    $rejection_reason = $action === 'reject' ? trim($_POST['rejection_reason'] ?? '') : '';

    try {
        $conn->begin_transaction();
        
        // Get project and user data
        $stmt = $conn->prepare("SELECT p.*, r.email, r.name FROM projects p JOIN register r ON p.user_id = r.id WHERE p.id=?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $project_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$project_data) {
            throw new Exception("Project not found");
        }
        
        if ($action === 'approve') {
            // Move to admin_approved_projects
            $stmt = $conn->prepare("INSERT INTO admin_approved_projects (user_id, project_name, project_type, classification, project_category, difficulty_level, development_time, team_size, target_audience, project_goals, challenges_faced, future_enhancements, github_repo, live_demo_url, project_license, keywords, contact_email, social_links, description, language, image_path, video_path, code_file_path, instruction_file_path, presentation_file_path, additional_files_path, submission_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')");
            $stmt->bind_param("issssssssssssssssssssssssss", 
                $project_data['user_id'], $project_data['project_name'], $project_data['project_type'], 
                $project_data['classification'], $project_data['project_category'], $project_data['difficulty_level'],
                $project_data['development_time'], $project_data['team_size'], $project_data['target_audience'],
                $project_data['project_goals'], $project_data['challenges_faced'], $project_data['future_enhancements'],
                $project_data['github_repo'], $project_data['live_demo_url'], $project_data['project_license'],
                $project_data['keywords'], $project_data['contact_email'], $project_data['social_links'],
                $project_data['description'], $project_data['language'], $project_data['image_path'],
                $project_data['video_path'], $project_data['code_file_path'], $project_data['instruction_file_path'],
                $project_data['presentation_file_path'], $project_data['additional_files_path'], 
                $project_data['submission_date']
            );
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert into admin_approved_projects: " . $stmt->error);
            }
            $stmt->close();
            
            // Update status in projects table
            $stmt = $conn->prepare("UPDATE projects SET status='approved' WHERE id=?");
            $stmt->bind_param("i", $project_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update project status: " . $stmt->error);
            }
            $stmt->close();
            
            // Create notification for user
            $notifier = new NotificationHelper($conn);
            $notifier->notifyProjectApproved($project_data['user_id'], $project_id, $project_data['project_name']);
            
            $action_message = "Project approved successfully!";
        } else {
            // Move to denial_projects
            $stmt = $conn->prepare("INSERT INTO denial_projects (user_id, project_name, project_type, classification, project_category, difficulty_level, development_time, team_size, target_audience, project_goals, challenges_faced, future_enhancements, github_repo, live_demo_url, project_license, keywords, contact_email, social_links, description, language, image_path, video_path, code_file_path, instruction_file_path, presentation_file_path, additional_files_path, submission_date, status, rejection_date, rejection_reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'rejected', NOW(), ?)");
            $stmt->bind_param("isssssssssssssssssssssssssss", 
                $project_data['user_id'], $project_data['project_name'], $project_data['project_type'], 
                $project_data['classification'], $project_data['project_category'], $project_data['difficulty_level'],
                $project_data['development_time'], $project_data['team_size'], $project_data['target_audience'],
                $project_data['project_goals'], $project_data['challenges_faced'], $project_data['future_enhancements'],
                $project_data['github_repo'], $project_data['live_demo_url'], $project_data['project_license'],
                $project_data['keywords'], $project_data['contact_email'], $project_data['social_links'],
                $project_data['description'], $project_data['language'], $project_data['image_path'],
                $project_data['video_path'], $project_data['code_file_path'], $project_data['instruction_file_path'],
                $project_data['presentation_file_path'], $project_data['additional_files_path'], 
                $project_data['submission_date'], $rejection_reason
            );
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert into denial_projects: " . $stmt->error);
            }
            $stmt->close();
            
            // Update status in projects table
            $stmt = $conn->prepare("UPDATE projects SET status='rejected' WHERE id=?");
            $stmt->bind_param("i", $project_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update project status: " . $stmt->error);
            }
            $stmt->close();
            
            // Create notification for user
            $notifier = new NotificationHelper($conn);
            $notifier->notifyProjectRejected($project_data['user_id'], $project_id, $project_data['project_name'], $rejection_reason);
            
            $action_message = "Project rejected successfully!";
        }
        
        $conn->commit();
        
    } catch (Exception $e) {
        if ($conn->connect_errno === 0) {
            $conn->rollback();
        }
        $action_message = "Error: " . $e->getMessage();
        error_log("Error in assigned_projects.php: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    }

    header("Location: assigned_projects.php?msg=" . urlencode($action_message));
    exit();
}

if (isset($_GET['msg'])) {
    $action_message = htmlspecialchars($_GET['msg']);
}

// Start output buffering to capture the content
ob_start();
?>

    <!-- Page specific styles -->
    <link rel="stylesheet" href="../../assets/css/assigned_projects.css">



    <!-- Action Message Alert -->
<?php if ($action_message) : ?>
    <?php 
    $isError = strpos($action_message, 'Error') !== false;
    $alertClass = $isError ? 'alert-danger' : 'alert-success';
    $icon = $isError ? 'bi-exclamation-triangle' : 'bi-check-circle';
    ?>
    <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show" role="alert">
        <i class="<?php echo $icon; ?> me-2"></i>
        <?php echo $action_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

    <!-- Projects Statistics -->
<?php
$total_projects = $result->num_rows;
$approved_count = 0;
$pending_count = 0;
$rejected_count = 0;

// Count projects by status
$projects_data = [];
while ($row = $result->fetch_assoc()) {
    $projects_data[] = $row;
    switch ($row['status']) {
        case 'approved':
            $approved_count++;
            break;
        case 'pending':
            $pending_count++;
            break;
        case 'rejected':
            $rejected_count++;
            break;
    }
}
?>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="glass-card stats-card text-center">
                <span class="stats-number"><?php echo $total_projects; ?></span>
                <span class="stats-label">Total Projects</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card p-3 text-center" style="background: linear-gradient(135deg, var(--success-color) 0%, #10b981 100%); color: white;">
                <span class="stats-number"><?php echo $approved_count; ?></span>
                <span class="stats-label">Approved</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card p-3 text-center" style="background: linear-gradient(135deg, var(--warning-color) 0%, #f59e0b 100%); color: white;">
                <span class="stats-number"><?php echo $pending_count; ?></span>
                <span class="stats-label">Pending</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card p-3 text-center" style="background: linear-gradient(135deg, var(--danger-color) 0%, #ef4444 100%); color: white;">
                <span class="stats-number"><?php echo $rejected_count; ?></span>
                <span class="stats-label">Rejected</span>
            </div>
        </div>
    </div>

    <!-- Projects Header -->
    <div class="glass-card mb-4">
        <div class="p-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <div>
                    <h5 class="mb-1 fw-bold">
                        <i class="bi bi-kanban-fill me-2 text-primary"></i>
                        Assigned Projects
                    </h5>
                    <p class="text-muted mb-0">Projects assigned based on your classification</p>
                </div>
                <div class="d-flex align-items-center gap-2 text-muted">
                    <small>
                        <i class="bi bi-tags-fill me-1"></i>
                        Your Domains: <?php echo htmlspecialchars($domains ?: 'None'); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Projects Grid -->
    <?php if (count($projects_data) > 0) : ?>
        <div class="row g-4">
            <?php foreach ($projects_data as $row) : ?>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-card h-100">
                        <div class="p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="fw-bold text-primary mb-0"><?php echo htmlspecialchars($row['project_name']); ?></h5>
                                <span class="status-badge status-<?php echo $row['status']; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <span class="badge bg-secondary me-2">
                                    <i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($row['project_type']); ?>
                                </span>
                                <span class="badge bg-info">
                                    <i class="bi bi-bookmark me-1"></i><?php echo htmlspecialchars($row['classification']); ?>
                                </span>
                            </div>
                            
                            <p class="text-muted mb-4" style="min-height: 60px;">
                                <?php echo htmlspecialchars(substr($row['description'], 0, 120)) . (strlen($row['description']) > 120 ? '...' : ''); ?>
                            </p>
                            
                            <?php if ($row['status'] == 'pending') : ?>
                                <div class="d-flex gap-2 flex-wrap">
                                    <button class="btn btn-sm btn-info flex-fill" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $row['id']; ?>">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                    <form method="POST" action="assigned_projects.php" class="flex-fill" onsubmit="return confirm('Are you sure you want to approve this project?');">
                                        <input type="hidden" name="project_id" value="<?php echo $row['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-sm btn-success w-100">
                                            <i class="bi bi-check-lg"></i> Approve
                                        </button>
                                    </form>
                                    <button class="btn btn-sm btn-danger flex-fill" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $row['id']; ?>">
                                        <i class="bi bi-x-lg"></i> Reject
                                    </button>
                                </div>
                            <?php else : ?>
                                <div class="text-center text-muted fst-italic">
                                    <i class="bi bi-check-circle me-1"></i>
                                    No actions available
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- View Modal -->
                <div class="modal fade" id="viewModal<?php echo $row['id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="bi bi-eye me-2"></i><?php echo htmlspecialchars($row['project_name']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-primary"><i class="bi bi-info-circle me-2"></i>Basic Information</h6>
                                        <hr>
                                        <p><strong>Project Name:</strong> <?php echo htmlspecialchars($row['project_name']); ?></p>
                                        <p><strong>Type:</strong> <span class="badge bg-secondary"><?php echo htmlspecialchars($row['project_type']); ?></span></p>
                                        <p><strong>Classification:</strong> <span class="badge bg-info"><?php echo htmlspecialchars($row['classification']); ?></span></p>
                                        <p><strong>Category:</strong> <?php echo htmlspecialchars($row['project_category'] ?? 'N/A'); ?></p>
                                        <p><strong>Language:</strong> <?php echo htmlspecialchars($row['language']); ?></p>
                                        <p><strong>Difficulty:</strong> <span class="badge bg-warning"><?php echo htmlspecialchars($row['difficulty_level'] ?? 'N/A'); ?></span></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-primary"><i class="bi bi-people me-2"></i>Project Details</h6>
                                        <hr>
                                        <p><strong>Team Size:</strong> <?php echo htmlspecialchars($row['team_size'] ?? 'N/A'); ?></p>
                                        <p><strong>Development Time:</strong> <?php echo htmlspecialchars($row['development_time'] ?? 'N/A'); ?></p>
                                        <p><strong>License:</strong> <?php echo htmlspecialchars($row['project_license'] ?? 'N/A'); ?></p>
                                        <p><strong>Submission Date:</strong> <?php echo date('M d, Y', strtotime($row['submission_date'])); ?></p>
                                        <p><strong>Status:</strong> <span class="status-badge status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></p>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <h6 class="text-primary"><i class="bi bi-file-text me-2"></i>Description</h6>
                                    <hr>
                                    <p><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                                </div>
                                
                                <?php if (!empty($row['target_audience'])): ?>
                                <div class="mb-3">
                                    <h6 class="text-primary"><i class="bi bi-bullseye me-2"></i>Target Audience</h6>
                                    <hr>
                                    <p><?php echo nl2br(htmlspecialchars($row['target_audience'])); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($row['project_goals'])): ?>
                                <div class="mb-3">
                                    <h6 class="text-primary"><i class="bi bi-flag me-2"></i>Project Goals</h6>
                                    <hr>
                                    <p><?php echo nl2br(htmlspecialchars($row['project_goals'])); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($row['challenges_faced'])): ?>
                                <div class="mb-3">
                                    <h6 class="text-primary"><i class="bi bi-exclamation-triangle me-2"></i>Challenges Faced</h6>
                                    <hr>
                                    <p><?php echo nl2br(htmlspecialchars($row['challenges_faced'])); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($row['future_enhancements'])): ?>
                                <div class="mb-3">
                                    <h6 class="text-primary"><i class="bi bi-rocket me-2"></i>Future Enhancements</h6>
                                    <hr>
                                    <p><?php echo nl2br(htmlspecialchars($row['future_enhancements'])); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($row['keywords'])): ?>
                                <div class="mb-3">
                                    <h6 class="text-primary"><i class="bi bi-tags me-2"></i>Keywords</h6>
                                    <hr>
                                    <p><?php echo htmlspecialchars($row['keywords']); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <h6 class="text-primary"><i class="bi bi-link-45deg me-2"></i>Links & Resources</h6>
                                    <hr>
                                    <?php if (!empty($row['github_repo'])): ?>
                                        <p><strong>GitHub:</strong> <a href="<?php echo htmlspecialchars($row['github_repo']); ?>" target="_blank"><?php echo htmlspecialchars($row['github_repo']); ?></a></p>
                                    <?php endif; ?>
                                    <?php if (!empty($row['live_demo_url'])): ?>
                                        <p><strong>Live Demo:</strong> <a href="<?php echo htmlspecialchars($row['live_demo_url']); ?>" target="_blank"><?php echo htmlspecialchars($row['live_demo_url']); ?></a></p>
                                    <?php endif; ?>
                                    <?php if (!empty($row['contact_email'])): ?>
                                        <p><strong>Contact:</strong> <?php echo htmlspecialchars($row['contact_email']); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <h6 class="text-primary"><i class="bi bi-paperclip me-2"></i>Attached Files</h6>
                                    <hr>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php if (!empty($row['image_path'])): ?>
                                            <span class="badge bg-success"><i class="bi bi-image me-1"></i>Image</span>
                                        <?php endif; ?>
                                        <?php if (!empty($row['video_path'])): ?>
                                            <span class="badge bg-success"><i class="bi bi-camera-video me-1"></i>Video</span>
                                        <?php endif; ?>
                                        <?php if (!empty($row['code_file_path'])): ?>
                                            <span class="badge bg-success"><i class="bi bi-file-code me-1"></i>Code</span>
                                        <?php endif; ?>
                                        <?php if (!empty($row['instruction_file_path'])): ?>
                                            <span class="badge bg-success"><i class="bi bi-file-text me-1"></i>Instructions</span>
                                        <?php endif; ?>
                                        <?php if (!empty($row['presentation_file_path'])): ?>
                                            <span class="badge bg-success"><i class="bi bi-file-slides me-1"></i>Presentation</span>
                                        <?php endif; ?>
                                        <?php if (!empty($row['additional_files_path'])): ?>
                                            <span class="badge bg-success"><i class="bi bi-files me-1"></i>Additional Files</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reject Modal -->
                <div class="modal fade" id="rejectModal<?php echo $row['id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                                    Reject Project
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST" action="assigned_projects.php">
                                <div class="modal-body">
                                    <div class="alert alert-warning">
                                        <i class="bi bi-info-circle me-2"></i>
                                        You are about to reject "<strong><?php echo htmlspecialchars($row['project_name']); ?></strong>". Please provide a reason.
                                    </div>
                                    <input type="hidden" name="project_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <div class="mb-3">
                                        <label for="rejection_reason<?php echo $row['id']; ?>" class="form-label">
                                            <i class="bi bi-chat-square-text me-1"></i>
                                            Reason for Rejection <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form-control" name="rejection_reason" id="rejection_reason<?php echo $row['id']; ?>" rows="4" placeholder="Please provide a detailed reason..." required></textarea>
                                        <div class="form-text">This will be recorded for future reference.</div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                        <i class="bi bi-x-circle me-1"></i> Cancel
                                    </button>
                                    <button type="submit" class="btn btn-danger">
                                        <i class="bi bi-exclamation-triangle me-1"></i> Reject Project
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div class="glass-card">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-inbox"></i>
                </div>
                <h3 class="empty-state-title">No Projects Found</h3>
                <p class="empty-state-desc">
                    There are currently no projects assigned to your domains.<br>
                    <small class="text-muted">Your domains: <?php echo htmlspecialchars($domains ?: 'None assigned'); ?></small>
                </p>
            </div>
        </div>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Clean up any leftover modal artifacts
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
            document.body.style.removeProperty('overflow');
        });
    </script>

<?php
// Capture the content
$content = ob_get_clean();

// Render the page using the layout
renderLayout('Assigned Projects', $content, 'projects');
?>