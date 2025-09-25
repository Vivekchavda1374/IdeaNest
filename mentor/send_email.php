<?php
session_start();
require_once '../Login/Login/db.php';
require_once 'mentor_layout.php';

if (!isset($_SESSION['mentor_id'])) {
    header('Location: ../Login/Login/login.php');
    exit;
}

$mentor_id = $_SESSION['mentor_id'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        require_once 'email_system.php';
        $email_system = new MentorEmailSystem($conn, $mentor_id);

        $action = $_POST['action'] ?? '';
        $student_id = intval($_POST['student_id'] ?? 0);

        $response = ['success' => false, 'message' => 'Invalid action'];

        if ($student_id <= 0) {
            $response = ['success' => false, 'message' => 'Please select a student'];
        } else {
            switch ($action) {
                case 'send_welcome':
                    if ($email_system->sendWelcomeMessage($student_id)) {
                        // Log activity
                        try {
                            $student_query = "SELECT name FROM register WHERE id = ?";
                            $stmt = $conn->prepare($student_query);
                            $stmt->bind_param("i", $student_id);
                            $stmt->execute();
                            $student_name = $stmt->get_result()->fetch_assoc()['name'] ?? 'Student';

                            // Check if activity logs table exists
                            $table_check = $conn->query("SHOW TABLES LIKE 'mentor_activity_logs'");
                            if ($table_check && $table_check->num_rows > 0) {
                                $log_stmt = $conn->prepare("INSERT INTO mentor_activity_logs (mentor_id, activity_type, description, student_id, created_at) VALUES (?, 'email_sent', ?, ?, NOW())");
                                $activity_desc = "Sent welcome email to " . $student_name;
                                $log_stmt->bind_param("isi", $mentor_id, $activity_desc, $student_id);
                                $log_stmt->execute();
                            }
                        } catch (Exception $e) {
                            error_log('Activity logging error: ' . $e->getMessage());
                        }

                        $response = ['success' => true, 'message' => 'Welcome email sent successfully'];
                    } else {
                        $response = ['success' => false, 'message' => 'Failed to send welcome email'];
                    }
                    break;

                case 'send_session_invitation':
                    $session_data = [
                        'session_date' => $_POST['session_date'] ?? '',
                        'topic' => $_POST['topic'] ?? '',
                        'meeting_link' => $_POST['meeting_link'] ?? ''
                    ];

                    if (empty($session_data['session_date'])) {
                        $response = ['success' => false, 'message' => 'Please select a session date'];
                    } elseif ($email_system->sendSessionInvitation($student_id, $session_data)) {
                        $response = ['success' => true, 'message' => 'Session invitation sent successfully'];
                    } else {
                        $response = ['success' => false, 'message' => 'Failed to send session invitation'];
                    }
                    break;

                case 'send_project_feedback':
                    $feedback_data = [
                        'project_id' => intval($_POST['project_id'] ?? 0),
                        'feedback_message' => trim($_POST['feedback_message'] ?? ''),
                        'rating' => intval($_POST['rating'] ?? 0)
                    ];

                    if (empty($feedback_data['feedback_message'])) {
                        $response = ['success' => false, 'message' => 'Please enter feedback message'];
                    } elseif ($email_system->sendProjectFeedback($student_id, $feedback_data)) {
                        $response = ['success' => true, 'message' => 'Project feedback sent successfully'];
                    } else {
                        $response = ['success' => false, 'message' => 'Failed to send project feedback'];
                    }
                    break;

                case 'send_progress_update':
                    $progress_data = [
                        'completion_percentage' => intval($_POST['completion_percentage'] ?? 0),
                        'achievements' => trim($_POST['achievements'] ?? ''),
                        'next_steps' => trim($_POST['next_steps'] ?? '')
                    ];

                    if ($email_system->sendProgressUpdate($student_id, $progress_data)) {
                        $response = ['success' => true, 'message' => 'Progress update sent successfully'];
                    } else {
                        $response = ['success' => false, 'message' => 'Failed to send progress update'];
                    }
                    break;

                default:
                    $response = ['success' => false, 'message' => 'Unknown action: ' . $action];
                    break;
            }
        }
    } catch (Exception $e) {
        error_log('Send email error: ' . $e->getMessage());
        $response = ['success' => false, 'message' => 'Email system error: ' . $e->getMessage()];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}


// Get students for forms
$students = [];
try {
    $students_query = "SELECT r.id, r.name FROM register r 
                       WHERE r.role = 'student' 
                       ORDER BY r.name";
    $stmt = $conn->prepare($students_query);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $students = [];
}

// Get email logs if table exists
$email_logs = [];
$table_check = $conn->query("SHOW TABLES LIKE 'mentor_email_logs'");
if ($table_check && $table_check->num_rows > 0) {
    try {
        $logs_query = "SELECT mel.*, r.name as recipient_name, r.email as recipient_email 
                       FROM mentor_email_logs mel 
                       JOIN register r ON mel.recipient_id = r.id 
                       WHERE mel.mentor_id = ? 
                       ORDER BY mel.sent_at DESC 
                       LIMIT 10";
        $stmt = $conn->prepare($logs_query);
        $stmt->bind_param("i", $mentor_id);
        $stmt->execute();
        $email_logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        $email_logs = [];
    }
}

ob_start();
?>
<div class="container mt-4">
    <h2><i class="fas fa-envelope"></i> Email Management</h2>
    
    <!-- Setup Notice -->
    <?php if (empty($email_logs)) : ?>
        <div class="alert alert-warning">
            <h5><i class="fas fa-exclamation-triangle"></i> Email System Setup Required</h5>
            <p>To use the email system, please complete the setup:</p>
            <ol>
                <li>Run: <code>php setup_mentor_email_system.php</code></li>
                <li>Configure SMTP settings in admin panel</li>
                <li>Install PHPMailer: <code>composer require phpmailer/phpmailer</code></li>
            </ol>
        </div>
    <?php endif; ?>
    
    <!-- Quick Email Forms -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="email-form">
                <h5>📧 Send Welcome Email</h5>
                <form id="welcomeForm">
                    <div class="mb-3">
                        <select class="form-select" name="student_id" required>
                            <option value="">Select Student</option>
                            <?php foreach ($students as $student) : ?>
                                <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Welcome Email</button>
                </form>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="email-form">
                <h5>📅 Send Session Invitation</h5>
                <form id="sessionForm">
                    <div class="mb-3">
                        <select class="form-select" name="student_id" required>
                            <option value="">Select Student</option>
                            <?php foreach ($students as $student) : ?>
                                <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <input type="datetime-local" class="form-control" name="session_date" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" class="form-control" name="topic" placeholder="Session Topic">
                    </div>
                    <div class="mb-3">
                        <input type="url" class="form-control" name="meeting_link" placeholder="Meeting Link (optional)">
                    </div>
                    <button type="submit" class="btn btn-success">Send Invitation</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="email-form">
                <h5>📝 Send Project Feedback</h5>
                <form id="feedbackForm">
                    <div class="mb-3">
                        <select class="form-select" name="student_id" required>
                            <option value="">Select Student</option>
                            <?php foreach ($students as $student) : ?>
                                <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <select class="form-select" name="project_id" required>
                            <option value="">Select Project</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <textarea class="form-control" name="feedback_message" rows="3" placeholder="Feedback message" required></textarea>
                    </div>
                    <div class="mb-3">
                        <select class="form-select" name="rating">
                            <option value="">Rating (optional)</option>
                            <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                            <option value="4">⭐⭐⭐⭐ Good</option>
                            <option value="3">⭐⭐⭐ Average</option>
                            <option value="2">⭐⭐ Needs Improvement</option>
                            <option value="1">⭐ Poor</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-info">Send Feedback</button>
                </form>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="email-form">
                <h5>📊 Send Progress Update</h5>
                <form id="progressForm">
                    <div class="mb-3">
                        <select class="form-select" name="student_id" required>
                            <option value="">Select Student</option>
                            <?php foreach ($students as $student) : ?>
                                <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Completion Percentage</label>
                        <input type="range" class="form-range" name="completion_percentage" min="0" max="100" value="50">
                        <span id="progressValue">50%</span>
                    </div>
                    <div class="mb-3">
                        <textarea class="form-control" name="achievements" rows="2" placeholder="Achievements (one per line)"></textarea>
                    </div>
                    <div class="mb-3">
                        <textarea class="form-control" name="next_steps" rows="2" placeholder="Next steps (one per line)"></textarea>
                    </div>
                    <button type="submit" class="btn btn-warning">Send Update</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Email History -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-history"></i> Email History</h5>
        </div>
        <div class="card-body">
            <?php if (empty($email_logs)) : ?>
                <p class="text-muted">No emails sent yet. Complete setup to start sending emails.</p>
            <?php else : ?>
                <?php foreach ($email_logs as $log) : ?>
                    <div class="card email-card <?= $log['status'] === 'sent' ? 'email-success' : 'email-failed' ?> mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title">
                                        <?= ucfirst(str_replace('_', ' ', $log['email_type'])) ?>
                                        <span class="badge bg-<?= $log['status'] === 'sent' ? 'success' : 'danger' ?>">
                                            <?= ucfirst($log['status']) ?>
                                        </span>
                                    </h6>
                                    <p class="card-text">
                                        <strong>To:</strong> <?= htmlspecialchars($log['recipient_name']) ?> 
                                        (<?= htmlspecialchars($log['recipient_email']) ?>)
                                    </p>
                                    <?php if ($log['error_message']) : ?>
                                        <p class="text-danger small">Error: <?= htmlspecialchars($log['error_message']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted"><?= date('M j, Y g:i A', strtotime($log['sent_at'])) ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$additionalCSS = '
    <link rel="stylesheet" href="../assets/css/loading.css">
    .email-card { border-left: 4px solid #007bff; }
    .email-success { border-left-color: #28a745; }
    .email-failed { border-left-color: #dc3545; }
    .email-form { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
    .alert { padding: 1rem; margin-bottom: 1rem; border-radius: 0.5rem; border: 1px solid; }
    .alert-success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
    .alert-danger { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
';

$additionalJS = '
    <script src="../assets/js/loading.js"></script>
    <script>
    // Progress slider update
    document.querySelector("input[name=\"completion_percentage\"]").addEventListener("input", function() {
        document.getElementById("progressValue").textContent = this.value + "%";
    });
    
    // Form submissions
    document.getElementById("welcomeForm").addEventListener("submit", function(e) {
        e.preventDefault();
        sendEmail(this, "send_welcome");
    });
    
    document.getElementById("sessionForm").addEventListener("submit", function(e) {
        e.preventDefault();
        sendEmail(this, "send_session_invitation");
    });
    
    document.getElementById("feedbackForm").addEventListener("submit", function(e) {
        e.preventDefault();
        sendEmail(this, "send_project_feedback");
    });
    
    document.getElementById("progressForm").addEventListener("submit", function(e) {
        e.preventDefault();
        sendEmail(this, "send_progress_update");
    });
    
    function sendEmail(form, action) {
        const formData = new FormData(form);
        formData.append("action", action);
        
        const button = form.querySelector("button[type=\"submit\"]");
        const originalText = button.textContent;
        
        // Show loading with email-specific styling
        showEmailLoading('Sending email...');
        setButtonLoading(button, true, 'Sending...');
        
        fetch("send_email.php", {
            method: "POST",
            body: formData,
            noLoading: true // Prevent double loading
        })
        .then(response => response.json())
        .then(data => {
            hideEmailLoading();
            
            // Show success/error message
            const alertClass = data.success ? 'alert-success' : 'alert-danger';
            const alertIcon = data.success ? 'fa-check-circle' : 'fa-exclamation-triangle';
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert ${alertClass} mt-3`;
            alertDiv.innerHTML = `
                <i class="fas ${alertIcon} me-2"></i>
                ${data.message}
            `;
            
            form.insertBefore(alertDiv, form.firstChild);
            
            // Auto-remove alert after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
            
            if (data.success) {
                form.reset();
                // Reload page to show updated email history
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        })
        .catch(error => {
            hideEmailLoading();
            
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger mt-3';
            alertDiv.innerHTML = `
                <i class="fas fa-exclamation-triangle me-2"></i>
                Error: ${error.message}
            `;
            
            form.insertBefore(alertDiv, form.firstChild);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        })
        .finally(() => {
            setButtonLoading(button, false);
        });
    }
    </script>
';

renderLayout($content, 'Email Management', $additionalCSS, $additionalJS);
?>