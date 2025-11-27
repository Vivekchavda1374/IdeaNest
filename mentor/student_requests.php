<?php
require_once __DIR__ . '/includes/security_init.php';
session_start();
require_once '../Login/Login/db.php';

try {
    require_once dirname(__DIR__) . "/includes/simple_smtp.php";
} catch (Exception $e) {
}


if (!isset($_SESSION['mentor_id'])) {
    header("Location: login.php");
    exit();
}

$mentor_id = $_SESSION['mentor_id'];

// Handle request actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['request_id'])) {
        $request_id = $_POST['request_id'];
        $action = $_POST['action'];

        if ($action === 'accept') {
            // Accept the request and grant project access
            $conn->begin_transaction();
            try {
                // Get request and user details before updating
                $details_query = "SELECT mr.student_id, mr.project_id, r.name, r.email, m.name as mentor_name, p.project_name
                                 FROM mentor_requests mr
                                 JOIN register r ON mr.student_id = r.id
                                 JOIN register m ON mr.mentor_id = m.id
                                 LEFT JOIN projects p ON mr.project_id = p.id
                                 WHERE mr.id = ?";
                $details_stmt = $conn->prepare($details_query);
                $details_stmt->bind_param("i", $request_id);
                $details_stmt->execute();
                $details = $details_stmt->get_result()->fetch_assoc();

                // Update request status
                $update_query = "UPDATE mentor_requests SET status = 'accepted' WHERE id = ? AND mentor_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("ii", $request_id, $mentor_id);
                $update_stmt->execute();

                // Grant access to specific project if selected
                if ($details['project_id']) {
                    $access_query = "INSERT IGNORE INTO mentor_project_access (mentor_id, student_id, project_id) VALUES (?, ?, ?)";
                    $access_stmt = $conn->prepare($access_query);
                    $access_stmt->bind_param("iii", $mentor_id, $details['student_id'], $details['project_id']);
                    $access_stmt->execute();
                } else {
                    // Grant access to all student's projects
                    $all_projects_query = "INSERT IGNORE INTO mentor_project_access (mentor_id, student_id, project_id) 
                                          SELECT ?, ?, id FROM projects WHERE user_id = ?";
                    $all_projects_stmt = $conn->prepare($all_projects_query);
                    $all_projects_stmt->bind_param("iii", $mentor_id, $details['student_id'], $details['student_id']);
                    $all_projects_stmt->execute();
                }

                // Send acceptance email
                $subject = 'Mentorship Request Accepted - IdeaNest';
                $project_text = $details['project_name'] ? "for your project '{$details['project_name']}'" : "for general mentorship";
                $body = "
                <h2>Great News! Your Mentorship Request Has Been Accepted</h2>
                <p>Dear {$details['name']},</p>
                <p>We're excited to inform you that <strong>{$details['mentor_name']}</strong> has accepted your mentorship request {$project_text}.</p>
                <p>You can now collaborate with your mentor and get guidance on your projects.</p>
                <p>Best regards,<br>The IdeaNest Team</p>
                ";

                sendSMTPEmail($details['email'], $subject, $body);

                $conn->commit();
                $success_message = "Request accepted successfully! Email sent to student.";
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "Failed to accept request. Please try again.";
            }
        } elseif ($action === 'reject') {
            try {
                // Get request and user details before updating
                $details_query = "SELECT mr.student_id, mr.project_id, r.name, r.email, m.name as mentor_name, p.project_name
                                 FROM mentor_requests mr
                                 JOIN register r ON mr.student_id = r.id
                                 JOIN register m ON mr.mentor_id = m.id
                                 LEFT JOIN projects p ON mr.project_id = p.id
                                 WHERE mr.id = ?";
                $details_stmt = $conn->prepare($details_query);
                $details_stmt->bind_param("i", $request_id);
                $details_stmt->execute();
                $details = $details_stmt->get_result()->fetch_assoc();

                $update_query = "UPDATE mentor_requests SET status = 'rejected' WHERE id = ? AND mentor_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("ii", $request_id, $mentor_id);
                $update_stmt->execute();

                // Send rejection email
                $subject = 'Mentorship Request Update - IdeaNest';
                $project_text = $details['project_name'] ? "for your project '{$details['project_name']}'" : "for general mentorship";
                $body = "
                <h2>Mentorship Request Update</h2>
                <p>Dear {$details['name']},</p>
                <p>Thank you for your interest in mentorship {$project_text}.</p>
                <p>Unfortunately, <strong>{$details['mentor_name']}</strong> is unable to take on your mentorship request at this time.</p>
                <p>Don't be discouraged! You can explore other available mentors who might be a better fit for your needs.</p>
                <p>Best regards,<br>The IdeaNest Team</p>
                ";

                sendSMTPEmail($details['email'], $subject, $body);

                $success_message = "Request rejected. Email sent to student.";
            } catch (Exception $e) {
                $error_message = "Failed to reject request. Please try again.";
            }
        }
    }
}

// Get pending requests with project details
$requests_query = "SELECT mr.id, mr.student_id, mr.project_id, mr.message, mr.created_at, mr.status,
                          r.name as student_name, r.email as student_email, r.department,
                          p.project_name, p.description, p.language, p.project_type, p.classification,
                          p.difficulty_level, p.team_size, p.development_time, p.github_repo, p.live_demo_url
                   FROM mentor_requests mr
                   JOIN register r ON mr.student_id = r.id
                   LEFT JOIN projects p ON mr.project_id = p.id
                   WHERE mr.mentor_id = ?
                   ORDER BY mr.created_at DESC";
$requests_stmt = $conn->prepare($requests_query);
$requests_stmt->bind_param("i", $mentor_id);
$requests_stmt->execute();
$requests_result = $requests_stmt->get_result();

require_once 'mentor_layout.php';

$title = "Student Requests";
$content = ob_get_clean();
ob_start();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="text-white mb-0"><i class="fas fa-inbox me-2"></i>Student Requests</h2>
            <p class="text-white-50">Manage requests from students seeking mentorship</p>
        </div>
    </div>

    <?php if (isset($success_message)) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= $success_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= $error_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="glass-card p-4">
                <?php if ($requests_result->num_rows > 0) : ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Project</th>
                                    <th>Message</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($request = $requests_result->fetch_assoc()) : ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary rounded-circle p-2 me-3 text-white" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                    <?= strtoupper(substr($request['student_name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0"><?= htmlspecialchars($request['student_name']) ?></h6>
                                                    <small class="text-muted"><?= htmlspecialchars($request['department']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($request['project_name']) : ?>
                                                <span class="badge bg-info"><?= htmlspecialchars($request['project_name']) ?></span>
                                            <?php else : ?>
                                                <span class="text-muted">General mentorship</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="message-preview" style="max-width: 200px;">
                                                <?= htmlspecialchars(substr($request['message'], 0, 100)) ?>
                                                <?php if (strlen($request['message']) > 100) :
                                                    ?>...<?php
                                                endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?= date('M j, Y', strtotime($request['created_at'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = [
                                                'pending' => 'warning',
                                                'accepted' => 'success',
                                                'rejected' => 'danger'
                                            ];
                                            ?>
                                            <span class="badge bg-<?= $status_class[$request['status']] ?>">
                                                <?= ucfirst($request['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($request['status'] === 'pending') : ?>
                                                <div class="btn-group" role="group">
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                                        <input type="hidden" name="action" value="accept">
                                                        <button type="submit" class="btn btn-success btn-sm" 
                                                                onclick="return confirm('Accept this mentorship request?')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                                        <input type="hidden" name="action" value="reject">
                                                        <button type="submit" class="btn btn-danger btn-sm" 
                                                                onclick="return confirm('Reject this mentorship request?')">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                                <button class="btn btn-info btn-sm ms-2" data-bs-toggle="modal" 
                                                        data-bs-target="#detailsModal" 
                                                        data-message="<?= htmlspecialchars($request['message']) ?>"
                                                        data-student="<?= htmlspecialchars($request['student_name']) ?>"
                                                        data-project="<?= htmlspecialchars($request['project_name'] ?? 'General Mentorship') ?>"
                                                        data-description="<?= htmlspecialchars($request['description'] ?? 'No specific project') ?>"
                                                        data-language="<?= htmlspecialchars($request['language'] ?? 'N/A') ?>"
                                                        data-type="<?= htmlspecialchars($request['project_type'] ?? 'N/A') ?>"
                                                        data-classification="<?= htmlspecialchars($request['classification'] ?? 'N/A') ?>"
                                                        data-difficulty="<?= htmlspecialchars($request['difficulty_level'] ?? 'N/A') ?>"
                                                        data-team="<?= htmlspecialchars($request['team_size'] ?? 'N/A') ?>"
                                                        data-time="<?= htmlspecialchars($request['development_time'] ?? 'N/A') ?>"
                                                        data-github="<?= htmlspecialchars($request['github_repo'] ?? '') ?>"
                                                        data-demo="<?= htmlspecialchars($request['live_demo_url'] ?? '') ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            <?php else : ?>
                                                <button class="btn btn-info btn-sm" data-bs-toggle="modal" 
                                                        data-bs-target="#detailsModal" 
                                                        data-message="<?= htmlspecialchars($request['message']) ?>"
                                                        data-student="<?= htmlspecialchars($request['student_name']) ?>"
                                                        data-project="<?= htmlspecialchars($request['project_name'] ?? 'General Mentorship') ?>"
                                                        data-description="<?= htmlspecialchars($request['description'] ?? 'No specific project') ?>"
                                                        data-language="<?= htmlspecialchars($request['language'] ?? 'N/A') ?>"
                                                        data-type="<?= htmlspecialchars($request['project_type'] ?? 'N/A') ?>"
                                                        data-classification="<?= htmlspecialchars($request['classification'] ?? 'N/A') ?>"
                                                        data-difficulty="<?= htmlspecialchars($request['difficulty_level'] ?? 'N/A') ?>"
                                                        data-team="<?= htmlspecialchars($request['team_size'] ?? 'N/A') ?>"
                                                        data-time="<?= htmlspecialchars($request['development_time'] ?? 'N/A') ?>"
                                                        data-github="<?= htmlspecialchars($request['github_repo'] ?? '') ?>"
                                                        data-demo="<?= htmlspecialchars($request['live_demo_url'] ?? '') ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else : ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No Requests Yet</h4>
                        <p class="text-muted">You haven't received any mentorship requests from students.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-user me-2"></i>Student Information</h6>
                        <p><strong>Name:</strong> <span id="studentName"></span></p>
                        
                        <h6 class="mt-4"><i class="fas fa-project-diagram me-2"></i>Project Information</h6>
                        <p><strong>Project:</strong> <span id="projectName"></span></p>
                        <p><strong>Type:</strong> <span id="projectType"></span></p>
                        <p><strong>Classification:</strong> <span id="projectClassification"></span></p>
                        <p><strong>Language:</strong> <span id="projectLanguage"></span></p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-info-circle me-2"></i>Project Details</h6>
                        <p><strong>Difficulty:</strong> <span id="projectDifficulty"></span></p>
                        <p><strong>Team Size:</strong> <span id="projectTeam"></span></p>
                        <p><strong>Development Time:</strong> <span id="projectTime"></span></p>
                        <div id="projectLinks"></div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <h6><i class="fas fa-align-left me-2"></i>Project Description</h6>
                        <p id="projectDescription" class="bg-light p-3 rounded"></p>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <h6><i class="fas fa-envelope me-2"></i>Student's Message</h6>
                        <p id="fullMessage" class="bg-light p-3 rounded"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const detailsModal = document.getElementById('detailsModal');
    detailsModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        
        document.getElementById('studentName').textContent = button.getAttribute('data-student');
        document.getElementById('projectName').textContent = button.getAttribute('data-project');
        document.getElementById('projectType').textContent = button.getAttribute('data-type');
        document.getElementById('projectClassification').textContent = button.getAttribute('data-classification');
        document.getElementById('projectLanguage').textContent = button.getAttribute('data-language');
        document.getElementById('projectDifficulty').textContent = button.getAttribute('data-difficulty');
        document.getElementById('projectTeam').textContent = button.getAttribute('data-team');
        document.getElementById('projectTime').textContent = button.getAttribute('data-time');
        document.getElementById('projectDescription').textContent = button.getAttribute('data-description');
        document.getElementById('fullMessage').textContent = button.getAttribute('data-message');
        
        // Handle project links
        const github = button.getAttribute('data-github');
        const demo = button.getAttribute('data-demo');
        let linksHtml = '';
        
        if (github) {
            linksHtml += `<a href="${github}" target="_blank" class="btn btn-sm btn-outline-dark me-2"><i class="fab fa-github me-1"></i>GitHub</a>`;
        }
        if (demo) {
            linksHtml += `<a href="${demo}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-external-link-alt me-1"></i>Demo</a>`;
        }
        if (!github && !demo) {
            linksHtml = '<span class="text-muted">No links provided</span>';
        }
        
        document.getElementById('projectLinks').innerHTML = linksHtml;
    });
});
</script>

<?php
$content = ob_get_contents();
ob_end_clean();
renderLayout($title, $content);
?>