<?php
session_start();
require_once '../Login/Login/db.php';
require_once 'mentor_layout.php';

if (!isset($_SESSION['mentor_id'])) {
    header('Location: ../Login/Login/login.php');
    exit;
}

$mentor_id = $_SESSION['mentor_id'];

// Get students for dropdown
$students_query = "SELECT r.id, r.name FROM register r 
                   JOIN mentor_requests mr ON r.id = mr.student_id 
                   WHERE mr.mentor_id = ? AND mr.status = 'accepted'";
$stmt = $conn->prepare($students_query);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="glass-card">
            <div class="card-header bg-transparent border-0 p-4">
                <h4 class="mb-0">
                    <i class="fas fa-calendar-plus text-primary me-2"></i>
                    Schedule Session
                </h4>
                <p class="text-muted mb-0">Create a new mentoring session with your student</p>
            </div>
            <div class="card-body p-4">
                <form id="scheduleForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Student <span class="text-danger">*</span></label>
                            <select class="form-select" name="student_id" required>
                                <option value="">Select Student</option>
                                <?php foreach ($students as $student) : ?>
                                    <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" name="session_date" required>
                            <div class="form-text">mm/dd/yyyy, --:----</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Duration</label>
                            <select class="form-select" name="duration">
                                <option value="30">30 minutes</option>
                                <option value="60" selected>1 hour</option>
                                <option value="90">1.5 hours</option>
                                <option value="120">2 hours</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Meeting Link</label>
                            <input type="url" class="form-control" name="meeting_link" 
                                   placeholder="https://meet.google.com/... or https://zoom.us/...">
                            <div class="form-text">Optional: Add Google Meet, Zoom, or other meeting link</div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="4" 
                                  placeholder="Session agenda, topics to discuss..."></textarea>
                    </div>
                    
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-calendar-check me-2"></i>Schedule Session
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('scheduleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const button = this.querySelector('button[type="submit"]');
    const originalText = button.innerHTML;
    
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Scheduling...';
    
    fetch('schedule_session.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Session scheduled successfully!', 'success');
            this.reset();
            setTimeout(() => window.location.href = 'dashboard.php', 1500);
        } else {
            showNotification(data.error || 'Failed to schedule session', 'error');
        }
    })
    .catch(error => {
        showNotification('Network error occurred', 'error');
    })
    .finally(() => {
        button.disabled = false;
        button.innerHTML = originalText;
    });
});

// Set minimum date to tomorrow
const tomorrow = new Date();
tomorrow.setDate(tomorrow.getDate() + 1);
document.querySelector('input[name="session_date"]').min = tomorrow.toISOString().slice(0, 16);
</script>

<?php
$content = ob_get_clean();
renderLayout('Schedule Session', $content);
?>