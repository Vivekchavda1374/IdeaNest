<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login/Login/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error_message = null;
$mentors_result = null;
$projects_result = null;
$conn = null;

// Try to connect to database
try {
    require_once '../Login/Login/db.php';
} catch (Exception $e) {
    $error_message = "Database connection failed. Please ensure MySQL is running.";
}

// Load PHPMailer if available
$phpmailer_available = false;
if (file_exists('../vendor/autoload.php')) {
    try {
        require_once '../vendor/autoload.php';
        $phpmailer_available = class_exists('PHPMailer\\PHPMailer\\PHPMailer');
    } catch (Exception $e) {
        // PHPMailer not available, continue without it
    }
}

if (!$error_message && isset($conn)) {
    // Check if required tables exist
    $tables_exist = true;
    $required_tables = ['register', 'mentors', 'mentor_requests', 'projects'];
    
    foreach ($required_tables as $table) {
        $check_table = $conn->query("SHOW TABLES LIKE '$table'");
        if (!$check_table || $check_table->num_rows == 0) {
            $tables_exist = false;
            break;
        }
    }
    
    if (!$tables_exist) {
        $error_message = "Required database tables are missing. Please run the database setup.";
    } else {
        // Get available mentors
        $mentors_query = "SELECT r.id, r.name, r.email, 
                                COALESCE(r.department, 'Not specified') as department,
                                COALESCE(m.specialization, 'General') as specialization, 
                                COALESCE(m.experience_years, 0) as experience_years, 
                                COALESCE(m.bio, '') as bio, 
                                COALESCE(m.max_students, 5) as max_students, 
                                COALESCE(m.current_students, 0) as current_students 
                         FROM register r 
                         LEFT JOIN mentors m ON r.id = m.user_id 
                         WHERE r.role = 'mentor' 
                         ORDER BY r.name";
        $mentors_result = $conn->query($mentors_query);
        
        // Get user's projects for selection
        $projects_query = "SELECT id, project_name FROM projects WHERE user_id = ?";
        $projects_stmt = $conn->prepare($projects_query);
        if ($projects_stmt) {
            $projects_stmt->bind_param("i", $user_id);
            $projects_stmt->execute();
            $projects_result = $projects_stmt->get_result();
        }
    }
}

// Handle mentor request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_mentor']) && !$error_message) {
    $mentor_id = $_POST['mentor_id'];
    $project_id = $_POST['project_id'] ?? null;
    $message = $_POST['message'];

    // Check if request already exists
    $check_query = "SELECT id FROM mentor_requests WHERE student_id = ? AND mentor_id = ? AND status = 'pending'";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $user_id, $mentor_id);
    $check_stmt->execute();

    if ($check_stmt->get_result()->num_rows == 0) {
        try {
            $insert_query = "INSERT INTO mentor_requests (student_id, mentor_id, project_id, message) VALUES (?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("iiis", $user_id, $mentor_id, $project_id, $message);
            $insert_stmt->execute();

            $success_message = "Mentor request sent successfully!";
            
            // Send email if PHPMailer is available
            if ($phpmailer_available) {
                try {
                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'ideanest.ict@gmail.com';
                    $mail->Password = 'luou xlhs ojuw auvx';
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    // Get mentor details
                    $details_query = "SELECT r1.name as student_name, r2.name as mentor_name, r2.email as mentor_email
                                     FROM register r1, register r2
                                     WHERE r1.id = ? AND r2.id = ?";
                    $details_stmt = $conn->prepare($details_query);
                    $details_stmt->bind_param("ii", $user_id, $mentor_id);
                    $details_stmt->execute();
                    $details = $details_stmt->get_result()->fetch_assoc();

                    $mail->setFrom('ideanest.ict@gmail.com', 'IdeaNest');
                    $mail->addAddress($details['mentor_email'], $details['mentor_name']);
                    $mail->isHTML(true);
                    $mail->Subject = 'New Mentorship Request - IdeaNest';
                    $mail->Body = "
                    <h2>New Mentorship Request</h2>
                    <p>Dear {$details['mentor_name']},</p>
                    <p>You have received a new mentorship request from <strong>{$details['student_name']}</strong>.</p>
                    <p><strong>Message:</strong></p>
                    <p style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>{$message}</p>
                    <p>Best regards,<br>The IdeaNest Team</p>
                    ";
                    $mail->send();
                    $success_message .= " The mentor has been notified via email.";
                } catch (Exception $e) {
                    // Email failed, but request was saved
                }
            }

        } catch (Exception $e) {
            $error_message = "Failed to send request. Please try again.";
        }
    } else {
        $error_message = "You already have a pending request with this mentor.";
    }
}

include 'layout.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Mentor - IdeaNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: min-height: 100vh; }
        .main-content { margin-left: 280px; padding: 20px; }
        .mentor-card { background: rgba(255, 255, 255, 0.95); border-radius: 15px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease; }
        .mentor-card:hover { transform: translateY(-5px); }
        .mentor-avatar { width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; font-weight: bold; }
        .btn-request { background: linear-gradient(135deg, #667eea, #764ba2); border: none; border-radius: 25px; padding: 8px 20px; }
        .specialization-badge { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 15px; padding: 4px 12px; font-size: 0.8rem; }
        @media (max-width: 768px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="text-purple mb-0"><i class="fas fa-user-graduate me-2"></i>Select a Mentor</h2>
                    <p class="text-purple-50">Choose a mentor to guide you through your project journey</p>
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
                <?php if (!$error_message && $mentors_result && $mentors_result->num_rows > 0) : ?>
                    <?php while ($mentor = $mentors_result->fetch_assoc()) : ?>
                        <div class="col-lg-6 col-xl-4 mb-4">
                            <div class="mentor-card p-4 h-100">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="mentor-avatar me-3">
                                        <?= strtoupper(substr($mentor['name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <h5 class="mb-1"><?= htmlspecialchars($mentor['name']) ?></h5>
                                        <p class="text-muted mb-0"><?= htmlspecialchars($mentor['department']) ?></p>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <span class="specialization-badge"><?= htmlspecialchars($mentor['specialization']) ?></span>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i><?= $mentor['experience_years'] ?> years experience
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-users me-1"></i><?= $mentor['current_students'] ?>/<?= $mentor['max_students'] ?> students
                                    </small>
                                </div>

                                <?php if ($mentor['bio']) : ?>
                                    <p class="text-muted small mb-3"><?= htmlspecialchars(substr($mentor['bio'], 0, 100)) ?>...</p>
                                <?php endif; ?>

                                <button class="btn btn-request text-white w-100" data-bs-toggle="modal" data-bs-target="#requestModal" 
                                        data-mentor-id="<?= $mentor['id'] ?>" data-mentor-name="<?= htmlspecialchars($mentor['name']) ?>">
                                    <i class="fas fa-paper-plane me-2"></i>Send Request
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else : ?>
                    <div class="col-12">
                        <div class="mentor-card p-5 text-center">
                            <?php if ($error_message) : ?>
                                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                                <h4 class="text-warning">System Error</h4>
                                <p class="text-muted"><?= htmlspecialchars($error_message) ?></p>
                                <div class="mt-3">
                                    <p class="small text-muted">To fix this issue:</p>
                                    <ol class="text-start small text-muted">
                                        <li>Start MySQL: <code>sudo /opt/lampp/lampp startmysql</code></li>
                                        <li>Create database: <code>mysql -u root -e "CREATE DATABASE ideanest;"</code></li>
                                        <li>Import schema: <code>mysql -u root ideanest < db/ideanest.sql</code></li>
                                    </ol>
                                </div>
                            <?php else : ?>
                                <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">No Available Mentors</h4>
                                <p class="text-muted">There are currently no mentors available. Please check back later.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Request Modal -->
    <div class="modal fade" id="requestModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Send Mentor Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="mentor_id" id="mentorId">
                        
                        <div class="mb-3">
                            <label class="form-label">Mentor: <span id="mentorName" class="fw-bold"></span></label>
                        </div>

                        <div class="mb-3">
                            <label for="project_id" class="form-label">Select Project (Optional)</label>
                            <select class="form-select" name="project_id" id="project_id">
                                <option value="">No specific project</option>
                                <?php if ($projects_result) : ?>
                                    <?php while ($project = $projects_result->fetch_assoc()) : ?>
                                        <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['project_name']) ?></option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" name="message" id="message" rows="4" 
                                      placeholder="Tell the mentor why you'd like their guidance..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="request_mentor" class="btn btn-request text-white">
                            <i class="fas fa-paper-plane me-2"></i>Send Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const requestModal = document.getElementById('requestModal');
            requestModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const mentorId = button.getAttribute('data-mentor-id');
                const mentorName = button.getAttribute('data-mentor-name');
                
                document.getElementById('mentorId').value = mentorId;
                document.getElementById('mentorName').textContent = mentorName;
            });
        });
    </script>
</body>
</html>