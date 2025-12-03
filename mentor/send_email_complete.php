<?php
require_once __DIR__ . '/../includes/security_init.php';
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
        require_once '../includes/smtp_mailer.php';
        
        $action = $_POST['action'] ?? '';
        $student_id = intval($_POST['student_id'] ?? 0);
        $response = ['success' => false, 'message' => 'Invalid action'];

        if ($student_id <= 0) {
            $response = ['success' => false, 'message' => 'Please select a student'];
        } else {
            // Get student details
            $student_query = "SELECT r.name, r.email FROM register r WHERE r.id = ? AND r.role = 'student'";
            $stmt = $conn->prepare($student_query);
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc();
            
            if (!$student) {
                $response = ['success' => false, 'message' => 'Student not found'];
            } else {
                // Get mentor details
                $mentor_query = "SELECT r.name, r.email FROM register r WHERE r.id = ? AND r.role = 'mentor'";
                $stmt = $conn->prepare($mentor_query);
                $stmt->bind_param("i", $mentor_id);
                $stmt->execute();
                $mentor = $stmt->get_result()->fetch_assoc();
                
                $mailer = new SMTPMailer();
                
                switch ($action) {
                    case 'send_welcome':
                        $subject = 'Welcome to IdeaNest Mentorship Program!';
                        $body = generateWelcomeEmail($student['name'], $mentor['name']);
                        
                        if ($mailer->send($student['email'], $subject, $body)) {
                            // Log the email
                            logEmail($conn, $mentor_id, $student_id, 'welcome', 'sent');
                            $response = ['success' => true, 'message' => 'Welcome email sent successfully to ' . $student['name']];
                        } else {
                            logEmail($conn, $mentor_id, $student_id, 'welcome', 'failed');
                            $response = ['success' => false, 'message' => 'Failed to send welcome email'];
                        }
                        break;

                    case 'send_session_invitation':
                        $session_date = $_POST['session_date'] ?? '';
                        $topic = $_POST['topic'] ?? 'Mentorship Session';
                        $meeting_link = $_POST['meeting_link'] ?? '';
                        
                        if (empty($session_date)) {
                            $response = ['success' => false, 'message' => 'Please select a session date'];
                        } else {
                            $subject = 'Session Invitation: ' . $topic;
                            $body = generateSessionInvitationEmail($student['name'], $mentor['name'], $session_date, $topic, $meeting_link);
                            
                            if ($mailer->send($student['email'], $subject, $body)) {
                                // Save session to database
                                saveSession($conn, $mentor_id, $student_id, $session_date, $topic, $meeting_link);
                                logEmail($conn, $mentor_id, $student_id, 'session_invitation', 'sent');
                                $response = ['success' => true, 'message' => 'Session invitation sent successfully to ' . $student['name']];
                            } else {
                                logEmail($conn, $mentor_id, $student_id, 'session_invitation', 'failed');
                                $response = ['success' => false, 'message' => 'Failed to send session invitation'];
                            }
                        }
                        break;

                    case 'send_reminder':
                        $message = $_POST['message'] ?? '';
                        if (empty($message)) {
                            $response = ['success' => false, 'message' => 'Please enter a reminder message'];
                        } else {
                            $subject = 'Reminder from your Mentor';
                            $body = generateReminderEmail($student['name'], $mentor['name'], $message);
                            
                            if ($mailer->send($student['email'], $subject, $body)) {
                                logEmail($conn, $mentor_id, $student_id, 'reminder', 'sent');
                                $response = ['success' => true, 'message' => 'Reminder sent successfully to ' . $student['name']];
                            } else {
                                logEmail($conn, $mentor_id, $student_id, 'reminder', 'failed');
                                $response = ['success' => false, 'message' => 'Failed to send reminder'];
                            }
                        }
                        break;

                    case 'send_feedback':
                        $feedback = $_POST['feedback'] ?? '';
                        if (empty($feedback)) {
                            $response = ['success' => false, 'message' => 'Please enter feedback'];
                        } else {
                            $subject = 'Feedback from your Mentor';
                            $body = generateFeedbackEmail($student['name'], $mentor['name'], $feedback);
                            
                            if ($mailer->send($student['email'], $subject, $body)) {
                                logEmail($conn, $mentor_id, $student_id, 'feedback', 'sent');
                                $response = ['success' => true, 'message' => 'Feedback sent successfully to ' . $student['name']];
                            } else {
                                logEmail($conn, $mentor_id, $student_id, 'feedback', 'failed');
                                $response = ['success' => false, 'message' => 'Failed to send feedback'];
                            }
                        }
                        break;

                    default:
                        $response = ['success' => false, 'message' => 'Unknown action: ' . $action];
                        break;
                }
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

// Get mentor's students
$students = [];
try {
    $students_query = "
        SELECT DISTINCT r.id, r.name, r.email 
        FROM register r 
        INNER JOIN mentor_student_pairs msp ON r.id = msp.student_id 
        WHERE msp.mentor_id = ? AND msp.status = 'active'
        ORDER BY r.name
    ";
    $stmt = $conn->prepare($students_query);
    $stmt->bind_param("i", $mentor_id);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    // Fallback to all students if mentor_student_pairs table doesn't exist
    $students_query = "SELECT id, name, email FROM register WHERE role = 'student' ORDER BY name";
    $stmt = $conn->prepare($students_query);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get recent email history
$email_history = [];
try {
    $history_query = "
        SELECT el.*, r.name as student_name 
        FROM email_logs el 
        INNER JOIN register r ON el.student_id = r.id 
        WHERE el.mentor_id = ? 
        ORDER BY el.sent_at DESC 
        LIMIT 10
    ";
    $stmt = $conn->prepare($history_query);
    $stmt->bind_param("i", $mentor_id);
    $stmt->execute();
    $email_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $email_history = [];
}

// Helper functions
function generateWelcomeEmail($student_name, $mentor_name) {
    return "
    <html>
    <head>
    <link rel="icon" type="image/png" href="../assets/image/fevicon.png">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        </style>
        <link rel="stylesheet" href="../assets/css/loader.css">
</head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Welcome to IdeaNest!</h1>
                <p>Your Mentorship Journey Begins</p>
            </div>
            <div class='content'>
                <p>Dear {$student_name},</p>
                <p>Welcome to the IdeaNest Mentorship Program! I'm {$mentor_name}, and I'm excited to be your mentor on this journey.</p>
                <p>Together, we'll work on developing your skills, exploring new technologies, and bringing your innovative ideas to life.</p>
                <p>I'm here to guide you, provide feedback, and help you achieve your goals. Don't hesitate to reach out with any questions or ideas you'd like to discuss.</p>
                <p>Looking forward to our collaboration!</p>
                <p>Best regards,<br>{$mentor_name}</p>
            </div>
        </div>
    <script src="../assets/js/loader.js"></script>
</body>
    </html>
    ";
}

function generateSessionInvitationEmail($student_name, $mentor_name, $session_date, $topic, $meeting_link) {
    $formatted_date = date('F j, Y \a\t g:i A', strtotime($session_date));
    $meeting_section = $meeting_link ? "<p><strong>Meeting Link:</strong> <a href='{$meeting_link}'>{$meeting_link}</a></p>" : "";
    
    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .session-details { background: white; padding: 20px; border-left: 4px solid #667eea; margin: 20px 0; }
        </style>
        <link rel="stylesheet" href="../assets/css/loader.css">
</head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>📅 Session Invitation</h1>
                <p>You're invited to a mentorship session</p>
            </div>
            <div class='content'>
                <p>Dear {$student_name},</p>
                <p>I'd like to schedule a mentorship session with you. Here are the details:</p>
                <div class='session-details'>
                    <h3>Session Details:</h3>
                    <p><strong>Topic:</strong> {$topic}</p>
                    <p><strong>Date & Time:</strong> {$formatted_date}</p>
                    {$meeting_section}
                </div>
                <p>Please confirm your attendance and let me know if you have any questions or if you need to reschedule.</p>
                <p>Looking forward to our session!</p>
                <p>Best regards,<br>{$mentor_name}</p>
            </div>
        </div>
    <script src="../assets/js/loader.js"></script>
</body>
    </html>
    ";
}

function generateReminderEmail($student_name, $mentor_name, $message) {
    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .message { background: white; padding: 20px; border-left: 4px solid #f59e0b; margin: 20px 0; }
        </style>
        <link rel="stylesheet" href="../assets/css/loader.css">
</head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔔 Reminder</h1>
                <p>A message from your mentor</p>
            </div>
            <div class='content'>
                <p>Dear {$student_name},</p>
                <div class='message'>
                    <p>{$message}</p>
                </div>
                <p>Best regards,<br>{$mentor_name}</p>
            </div>
        </div>
    <script src="../assets/js/loader.js"></script>
</body>
    </html>
    ";
}

function generateFeedbackEmail($student_name, $mentor_name, $feedback) {
    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .feedback { background: white; padding: 20px; border-left: 4px solid #10b981; margin: 20px 0; }
        </style>
        <link rel="stylesheet" href="../assets/css/loader.css">
</head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>💬 Feedback</h1>
                <p>Feedback from your mentor</p>
            </div>
            <div class='content'>
                <p>Dear {$student_name},</p>
                <p>I wanted to share some feedback with you:</p>
                <div class='feedback'>
                    <p>{$feedback}</p>
                </div>
                <p>Keep up the great work!</p>
                <p>Best regards,<br>{$mentor_name}</p>
            </div>
        </div>
    <script src="../assets/js/loader.js"></script>
</body>
    </html>
    ";
}

function logEmail($conn, $mentor_id, $student_id, $type, $status) {
    try {
        $log_query = "INSERT INTO email_logs (mentor_id, student_id, email_type, status, sent_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($log_query);
        $stmt->bind_param("iiss", $mentor_id, $student_id, $type, $status);
        $stmt->execute();
    } catch (Exception $e) {
        error_log('Email logging error: ' . $e->getMessage());
    }
}

function saveSession($conn, $mentor_id, $student_id, $session_date, $topic, $meeting_link) {
    try {
        $session_query = "INSERT INTO mentor_sessions (mentor_id, student_id, session_date, topic, meeting_link, status, created_at) VALUES (?, ?, ?, ?, ?, 'scheduled', NOW())";
        $stmt = $conn->prepare($session_query);
        $stmt->bind_param("iisss", $mentor_id, $student_id, $session_date, $topic, $meeting_link);
        $stmt->execute();
    } catch (Exception $e) {
        error_log('Session saving error: ' . $e->getMessage());
    }
}

ob_start();
?>
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="fas fa-envelope"></i> Email Management</h2>
            <p class="text-muted">Send emails to your students for various purposes</p>
        </div>
    </div>
    
    <div class="row">
        <!-- Welcome Email -->
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card email-form">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-hand-wave"></i> Send Welcome Email</h5>
                </div>
                <div class="card-body">
                    <form id="welcomeForm">
                        <div class="mb-3">
                            <label class="form-label">Select Student</label>
                            <select class="form-select" name="student_id" required>
                                <option value="">Choose a student...</option>
                                <?php foreach ($students as $student) : ?>
                                    <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['email']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Send Welcome Email
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Session Invitation -->
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card email-form">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-plus"></i> Send Session Invitation</h5>
                </div>
                <div class="card-body">
                    <form id="sessionForm">
                        <div class="mb-3">
                            <label class="form-label">Select Student</label>
                            <select class="form-select" name="student_id" required>
                                <option value="">Choose a student...</option>
                                <?php foreach ($students as $student) : ?>
                                    <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Session Date & Time</label>
                            <input type="datetime-local" class="form-control" name="session_date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Session Topic</label>
                            <input type="text" class="form-control" name="topic" placeholder="e.g., Project Review, Code Discussion" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Meeting Link (Optional)</label>
                            <input type="url" class="form-control" name="meeting_link" placeholder="https://meet.google.com/...">
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-calendar-check"></i> Send Invitation
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Send Reminder -->
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card email-form">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-bell"></i> Send Reminder</h5>
                </div>
                <div class="card-body">
                    <form id="reminderForm">
                        <div class="mb-3">
                            <label class="form-label">Select Student</label>
                            <select class="form-select" name="student_id" required>
                                <option value="">Choose a student...</option>
                                <?php foreach ($students as $student) : ?>
                                    <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reminder Message</label>
                            <textarea class="form-control" name="message" rows="4" placeholder="Enter your reminder message..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-exclamation-triangle"></i> Send Reminder
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Send Feedback -->
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card email-form">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-comments"></i> Send Feedback</h5>
                </div>
                <div class="card-body">
                    <form id="feedbackForm">
                        <div class="mb-3">
                            <label class="form-label">Select Student</label>
                            <select class="form-select" name="student_id" required>
                                <option value="">Choose a student...</option>
                                <?php foreach ($students as $student) : ?>
                                    <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Feedback</label>
                            <textarea class="form-control" name="feedback" rows="4" placeholder="Provide constructive feedback..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-comment-dots"></i> Send Feedback
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Email History -->
    <?php if (!empty($email_history)) : ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Recent Email History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Email Type</th>
                                    <th>Status</th>
                                    <th>Sent At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($email_history as $email) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($email['student_name']) ?></td>
                                    <td>
                                        <span class="badge bg-secondary"><?= ucfirst(str_replace('_', ' ', $email['email_type'])) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $email['status'] === 'sent' ? 'bg-success' : 'bg-danger' ?>">
                                            <?= ucfirst($email['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M j, Y g:i A', strtotime($email['sent_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();

$additionalCSS = '
    .email-form { 
        transition: all 0.3s ease;
        border: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .email-form:hover { 
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }
    .alert { 
        padding: 1rem; 
        margin-bottom: 1rem; 
        border-radius: 0.5rem; 
        border: 1px solid; 
        animation: slideIn 0.3s ease;
    }
    .alert-success { 
        background-color: #d4edda; 
        border-color: #c3e6cb; 
        color: #155724; 
    }
    .alert-danger { 
        background-color: #f8d7da; 
        border-color: #f5c6cb; 
        color: #721c24; 
    }
    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .card-header {
        border-bottom: none;
        font-weight: 600;
    }
    .btn {
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    .btn:hover {
        transform: translateY(-1px);
    }
';

$additionalJS = '
document.getElementById("welcomeForm").addEventListener("submit", function(e) {
    e.preventDefault();
    sendEmail(this, "send_welcome");
});

document.getElementById("sessionForm").addEventListener("submit", function(e) {
    e.preventDefault();
    sendEmail(this, "send_session_invitation");
});

document.getElementById("reminderForm").addEventListener("submit", function(e) {
    e.preventDefault();
    sendEmail(this, "send_reminder");
});

document.getElementById("feedbackForm").addEventListener("submit", function(e) {
    e.preventDefault();
    sendEmail(this, "send_feedback");
});

function sendEmail(form, action) {
    const submitBtn = form.querySelector("button[type=submit]");
    const originalText = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.innerHTML = "<i class=\"fas fa-spinner fa-spin\"></i> Sending...";
    submitBtn.disabled = true;
    
    const formData = new FormData(form);
    formData.append("action", action);
    
    fetch("send_email.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Remove any existing alerts
        const existingAlert = form.querySelector(".alert");
        if (existingAlert) existingAlert.remove();
        
        // Create new alert
        const alertDiv = document.createElement("div");
        alertDiv.className = "alert " + (data.success ? "alert-success" : "alert-danger");
        alertDiv.innerHTML = "<i class=\"fas " + (data.success ? "fa-check-circle" : "fa-exclamation-circle") + "\"></i> " + data.message;
        form.insertBefore(alertDiv, form.firstChild);
        
        // Auto-remove alert after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.style.opacity = "0";
                setTimeout(() => alertDiv.remove(), 300);
            }
        }, 5000);
        
        if (data.success) {
            form.reset();
        }
    })
    .catch(error => {
        console.error("Error:", error);
        const alertDiv = document.createElement("div");
        alertDiv.className = "alert alert-danger";
        alertDiv.innerHTML = "<i class=\"fas fa-exclamation-circle\"></i> Network error occurred. Please try again.";
        form.insertBefore(alertDiv, form.firstChild);
    })
    .finally(() => {
        // Restore button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Set minimum date for session scheduling to current date/time
document.addEventListener("DOMContentLoaded", function() {
    const sessionDateInput = document.querySelector("input[name=session_date]");
    if (sessionDateInput) {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        sessionDateInput.min = now.toISOString().slice(0, 16);
    }
});
';

renderLayout('Email Management', $content, $additionalCSS, $additionalJS);
?>