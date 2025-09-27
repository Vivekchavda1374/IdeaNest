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
    $students_query = "SELECT r.id, r.name FROM register r WHERE r.role = 'student' ORDER BY r.name";
    $stmt = $conn->prepare($students_query);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $students = [];
}

ob_start();
?>
<div class="container mt-4">
    <h2><i class="fas fa-envelope"></i> Email Management</h2>
    
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
</div>

<?php
$content = ob_get_clean();

$additionalCSS = '
    .email-form { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
    .alert { padding: 1rem; margin-bottom: 1rem; border-radius: 0.5rem; border: 1px solid; }
    .alert-success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
    .alert-danger { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
';

$additionalJS = '
<script>
document.getElementById("welcomeForm").addEventListener("submit", function(e) {
    e.preventDefault();
    sendEmail(this, "send_welcome");
});

document.getElementById("sessionForm").addEventListener("submit", function(e) {
    e.preventDefault();
    sendEmail(this, "send_session_invitation");
});

function sendEmail(form, action) {
    const formData = new FormData(form);
    formData.append("action", action);
    
    fetch("send_email.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const alertDiv = document.createElement("div");
        alertDiv.className = "alert " + (data.success ? "alert-success" : "alert-danger") + " mt-3";
        alertDiv.innerHTML = data.message;
        form.insertBefore(alertDiv, form.firstChild);
        
        setTimeout(() => {
            if (alertDiv.parentNode) alertDiv.remove();
        }, 5000);
        
        if (data.success) {
            form.reset();
        }
    })
    .catch(error => {
        console.error("Error:", error);
    });
}
</script>
';

renderLayout($content, 'Email Management', $additionalCSS, $additionalJS);
?>