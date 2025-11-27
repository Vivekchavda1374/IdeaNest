<?php
require_once __DIR__ . '/includes/security_init.php';
session_start();
require_once '../Login/Login/db.php';
require_once 'mentor_layout.php';

if (!isset($_SESSION['mentor_id'])) {
    header('Location: ../Login/Login/login.php');
    exit;
}

$mentor_id = $_SESSION['mentor_id'];

// Get active students from accepted requests
$students_query = "SELECT mr.id, mr.student_id, mr.project_id, mr.created_at as paired_at,
                   r.name, r.email, r.department, r.enrollment_number,
                   p.project_name, p.classification, p.description,
                   NULL as rating, NULL as feedback
                   FROM mentor_requests mr
                   JOIN register r ON mr.student_id = r.id 
                   LEFT JOIN projects p ON mr.project_id = p.id 
                   WHERE mr.mentor_id = ? AND mr.status = 'accepted'
                   ORDER BY mr.created_at DESC";
$stmt = $conn->prepare($students_query);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// If no students from mentor_requests, try mentor_student_pairs
if (empty($students)) {
    $students_query = "SELECT msp.id, msp.student_id, msp.project_id, msp.paired_at,
                       r.name, r.email, r.department, r.enrollment_number,
                       p.project_name, p.classification, p.description,
                       msp.rating, msp.feedback
                       FROM mentor_student_pairs msp
                       JOIN register r ON msp.student_id = r.id 
                       LEFT JOIN projects p ON msp.project_id = p.id 
                       WHERE msp.mentor_id = ? AND msp.status = 'active'
                       ORDER BY msp.paired_at DESC";
    $stmt = $conn->prepare($students_query);
    $stmt->bind_param("i", $mentor_id);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get completed students
$completed_query = "SELECT msp.*, r.name, r.email, r.department,
                    p.project_name, msp.completed_at, msp.rating, msp.feedback
                    FROM mentor_student_pairs msp 
                    JOIN register r ON msp.student_id = r.id 
                    LEFT JOIN projects p ON msp.project_id = p.id 
                    WHERE msp.mentor_id = ? AND msp.status = 'completed'
                    ORDER BY msp.completed_at DESC";
$stmt = $conn->prepare($completed_query);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$completed = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

ob_start();
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="glass-card p-4">
            <h2><i class="fas fa-users text-primary me-2"></i>My Students</h2>
            <p class="text-muted">Manage your active and completed mentorships</p>
        </div>
    </div>
</div>

<!-- Active Students -->
<div class="row mb-4">
    <div class="col-12">
        <div class="glass-card">
            <div class="card-header bg-transparent border-0 p-4">
                <h5 class="mb-0">Active Students (<?= count($students) ?>)</h5>
            </div>
            <div class="card-body p-4">
                <?php if (empty($students)) : ?>
                    <div class="text-center py-5">
                        <i class="fas fa-user-plus fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No active students</h6>
                        <p class="text-muted">Accept pairing suggestions from the dashboard</p>
                    </div>
                <?php else : ?>
                    <div class="row">
                        <?php foreach ($students as $student) : ?>
                            <div class="col-md-6 mb-3">
                                <div class="glass-card p-3">
                                    <div class="d-flex align-items-start">
                                        <div class="bg-primary rounded-circle p-2 me-3">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?= htmlspecialchars($student['name']) ?></h6>
                                            <small class="text-muted d-block"><?= htmlspecialchars($student['department']) ?></small>
                                            <small class="text-muted"><?= htmlspecialchars($student['email']) ?></small>
                                            
                                            <?php if ($student['project_name']) : ?>
                                                <div class="mt-2">
                                                    <span class="badge bg-info"><?= htmlspecialchars($student['project_name']) ?></span>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($student['classification']) ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <small class="text-muted d-block mt-2">
                                                Paired: <?= date('M j, Y', strtotime($student['paired_at'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3 d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-success" onclick="completePairing(<?= $student['id'] ?>, '<?= htmlspecialchars($student['name']) ?>')">
                                            <i class="fas fa-check me-1"></i>Complete
                                        </button>
                                        <button class="btn btn-sm btn-outline-info" onclick="viewProgress(<?= $student['id'] ?>)">
                                            <i class="fas fa-chart-line me-1"></i>Progress
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Completed Students -->
<div class="row">
    <div class="col-12">
        <div class="glass-card">
            <div class="card-header bg-transparent border-0 p-4">
                <h5 class="mb-0">Completed Mentorships (<?= count($completed) ?>)</h5>
            </div>
            <div class="card-body p-4">
                <?php if (empty($completed)) : ?>
                    <div class="text-center py-3">
                        <i class="fas fa-trophy fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No completed mentorships yet</p>
                    </div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Project</th>
                                    <th>Completed</th>
                                    <th>Rating</th>
                                    <th>Feedback</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($completed as $student) : ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($student['name']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($student['email']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($student['project_name'] ?? 'N/A') ?></td>
                                        <td><?= date('M j, Y', strtotime($student['completed_at'])) ?></td>
                                        <td>
                                            <?php for ($i = 1; $i <= 5; $i++) : ?>
                                                <i class="fas fa-star <?= $i <= ($student['rating'] ?? 0) ? 'text-warning' : 'text-muted' ?>"></i>
                                            <?php endfor; ?>
                                        </td>
                                        <td><?= htmlspecialchars(substr($student['feedback'] ?? '', 0, 50)) ?>...</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function completePairing(pairId, studentName) {
    const modal = `
        <div class="modal fade" id="completeModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content glass-card">
                    <div class="modal-header border-0">
                        <h5 class="modal-title">Complete Mentorship - ${studentName}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Rate Student (1-5) *</label>
                            <div class="rating-stars">
                                ${[1,2,3,4,5].map(i => `<i class="fas fa-star star-rating" data-rating="${i}"></i>`).join('')}
                            </div>
                            <input type="hidden" id="rating" value="5">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Feedback *</label>
                            <textarea class="form-control" id="feedback" rows="4" placeholder="Share your experience mentoring this student..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" onclick="submitCompletion(${pairId})">Complete Mentorship</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modal);
    const bootstrapModal = new bootstrap.Modal(document.getElementById('completeModal'));

    // Star rating functionality
    document.querySelectorAll('.star-rating').forEach(star => {
        star.addEventListener('click', function() {
            const rating = this.dataset.rating;
            document.getElementById('rating').value = rating;
            document.querySelectorAll('.star-rating').forEach((s, index) => {
                s.classList.toggle('text-warning', index < rating);
            });
        });
    });

    // Set default 5 stars
    document.querySelectorAll('.star-rating').forEach(star => {
        star.classList.add('text-warning');
    });

    bootstrapModal.show();

    // Clean up modal after hiding
    document.getElementById('completeModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

function submitCompletion(pairId) {
    const rating = document.getElementById('rating').value;
    const feedback = document.getElementById('feedback').value;

    if (!rating || rating < 1 || rating > 5) {
        alert('Please select a rating');
        return;
    }

    if (!feedback.trim()) {
        alert('Please provide feedback');
        return;
    }

    fetch('complete_pairing.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            pair_id: pairId,
            rating: parseInt(rating),
            feedback: feedback.trim()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Mentorship completed successfully!');
            bootstrap.Modal.getInstance(document.getElementById('completeModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            alert(data.error || 'Failed to complete mentorship');
        }
    })
    .catch(error => {
        alert('Network error occurred');
    });
}

function viewProgress(pairId) {
    window.location.href = `student_progress.php?pair_id=${pairId}`;
}

// Add CSS for star ratings
const style = document.createElement('style');
style.textContent = `
    .rating-stars {
        font-size: 1.5rem;
        margin: 10px 0;
    }
    .star-rating {
        color: #ddd;
        cursor: pointer;
        transition: color 0.2s;
        margin: 0 2px;
    }
    .star-rating:hover,
    .star-rating.text-warning {
        color: #ffc107 !important;
    }
`;
document.head.appendChild(style);
</script>

<?php
$content = ob_get_clean();
renderLayout('My Students', $content);
?>