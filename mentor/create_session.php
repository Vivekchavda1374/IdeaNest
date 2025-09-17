<?php
session_start();
require_once '../Login/Login/db.php';
require_once 'mentor_layout.php';

if (!isset($_SESSION['mentor_id'])) {
    header('Location: ../Login/Login/login.php');
    exit;
}

$mentor_id = $_SESSION['mentor_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pair_id = $_POST['pair_id'] ?? null;
    $session_date = $_POST['session_date'] ?? null;
    $duration = $_POST['duration'] ?? 60;
    $notes = $_POST['notes'] ?? '';
    $meeting_link = $_POST['meeting_link'] ?? null;
    
    if ($pair_id && $session_date) {
        try {
            $stmt = $conn->prepare("INSERT INTO mentoring_sessions (pair_id, session_date, duration_minutes, notes, meeting_link, status) VALUES (?, ?, ?, ?, ?, 'scheduled')");
            $stmt->bind_param("isiss", $pair_id, $session_date, $duration, $notes, $meeting_link);
            $stmt->execute();
            
            header('Location: sessions.php?success=1');
            exit;
        } catch (Exception $e) {
            $error = 'Failed to create session';
        }
    } else {
        $error = 'Missing required fields';
    }
}

// Get mentor's students from existing database
$students = [];
try {
    // Check if current user is a mentor
    $mentor_check = "SELECT id FROM register WHERE id = ? AND role = 'mentor'";
    $stmt = $conn->prepare($mentor_check);
    $stmt->bind_param("i", $mentor_id);
    $stmt->execute();
    $is_mentor = $stmt->get_result()->num_rows > 0;
    
    if ($is_mentor) {
        $students_query = "SELECT msp.id, r.name FROM mentor_student_pairs msp 
                         JOIN register r ON msp.student_id = r.id 
                         WHERE msp.mentor_id = ? AND msp.status = 'active'";
        $stmt = $conn->prepare($students_query);
        $stmt->bind_param("i", $mentor_id);
        $stmt->execute();
        $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    $students = [];
}

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="glass-card p-4">
            <h2><i class="fas fa-calendar-plus text-primary me-2"></i>Create Session</h2>
            <p class="text-muted mb-4">Schedule a new mentoring session with your students</p>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if (empty($students)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-1"></i>
                    No students assigned yet. Please contact admin to assign students to your mentorship.
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Student *</label>
                    <select class="form-select" name="pair_id" required>
                        <option value="">Select Student</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Date & Time *</label>
                    <input type="datetime-local" class="form-control" name="session_date" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Duration</label>
                    <select class="form-select" name="duration">
                        <option value="30">30 minutes</option>
                        <option value="60" selected>1 hour</option>
                        <option value="90">1.5 hours</option>
                        <option value="120">2 hours</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" name="notes" rows="3" placeholder="Session agenda, topics to discuss..."></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Meeting Link</label>
                    <input type="url" class="form-control" name="meeting_link" placeholder="https://meet.google.com/... or https://zoom.us/...">
                    <small class="text-muted">Optional: Add Google Meet, Zoom, or other meeting link</small>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calendar-plus me-1"></i>Create Session
                    </button>
                    <a href="sessions.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to Sessions
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
renderLayout('Create Session', $content);
?>