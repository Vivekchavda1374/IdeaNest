<?php
session_start();
require_once '../Login/Login/db.php';
require_once 'mentor_layout.php';

if (!isset($_SESSION['mentor_id'])) {
    header('Location: ../Login/Login/login.php');
    exit;
}

$mentor_id = $_SESSION['mentor_id'];
$success = '';
$error = '';

// Handle form submission
if ($_POST) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $specialization = $_POST['specialization'] ?? '';
    $max_students = $_POST['max_students'] ?? 10;
    $bio = $_POST['bio'] ?? '';
    $linkedin_url = $_POST['linkedin_url'] ?? '';
    $github_url = $_POST['github_url'] ?? '';

    try {
        // Update register table
        $stmt = $conn->prepare("UPDATE register SET name = ?, email = ?, about = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $email, $bio, $mentor_id);
        $stmt->execute();

        // Update or insert mentor record
        $check_mentor = $conn->prepare("SELECT id FROM mentors WHERE user_id = ?");
        $check_mentor->bind_param("i", $mentor_id);
        $check_mentor->execute();

        if ($check_mentor->get_result()->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE mentors SET specialization = ?, max_students = ?, bio = ?, linkedin_url = ?, github_url = ? WHERE user_id = ?");
            $stmt->bind_param("sisssi", $specialization, $max_students, $bio, $linkedin_url, $github_url, $mentor_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO mentors (user_id, specialization, max_students, bio, linkedin_url, github_url) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isisss", $mentor_id, $specialization, $max_students, $bio, $linkedin_url, $github_url);
        }
        $stmt->execute();

        $success = 'Profile updated successfully!';
    } catch (Exception $e) {
        $error = 'Failed to update profile: ' . $e->getMessage();
    }
}

// Get current profile data
$profile_query = "SELECT r.*, m.specialization, m.max_students, m.bio as mentor_bio, m.linkedin_url, m.github_url
                  FROM register r 
                  LEFT JOIN mentors m ON r.id = m.user_id 
                  WHERE r.id = ?";
$stmt = $conn->prepare($profile_query);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

ob_start();
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="glass-card p-4">
            <h2><i class="fas fa-user-cog text-primary me-2"></i>Profile Settings</h2>
            <p class="text-muted">Manage your mentor profile and preferences</p>
        </div>
    </div>
</div>

<?php if ($success) : ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error) : ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="glass-card">
            <div class="card-header bg-transparent border-0 p-4">
                <h5 class="mb-0">Personal Information</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($profile['name']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($profile['email']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Specialization</label>
                            <select class="form-select" name="specialization">
                                <option value="Web Development" <?= $profile['specialization'] == 'Web Development' ? 'selected' : '' ?>>Web Development</option>
                                <option value="Mobile Development" <?= $profile['specialization'] == 'Mobile Development' ? 'selected' : '' ?>>Mobile Development</option>
                                <option value="AI/ML" <?= $profile['specialization'] == 'AI/ML' ? 'selected' : '' ?>>AI/ML</option>
                                <option value="Data Science" <?= $profile['specialization'] == 'Data Science' ? 'selected' : '' ?>>Data Science</option>
                                <option value="IoT" <?= $profile['specialization'] == 'IoT' ? 'selected' : '' ?>>IoT</option>
                                <option value="Cybersecurity" <?= $profile['specialization'] == 'Cybersecurity' ? 'selected' : '' ?>>Cybersecurity</option>
                                <option value="General" <?= $profile['specialization'] == 'General' ? 'selected' : '' ?>>General</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Maximum Students</label>
                            <input type="number" class="form-control" name="max_students" value="<?= $profile['max_students'] ?? 10 ?>" min="1" max="20">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Bio</label>
                        <textarea class="form-control" name="bio" rows="4" placeholder="Tell students about yourself, your experience, and mentoring approach..."><?= htmlspecialchars($profile['mentor_bio'] ?? $profile['about']) ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">LinkedIn URL</label>
                            <input type="url" class="form-control" name="linkedin_url" value="<?= htmlspecialchars($profile['linkedin_url'] ?? '') ?>" placeholder="https://linkedin.com/in/yourprofile">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">GitHub URL</label>
                            <input type="url" class="form-control" name="github_url" value="<?= htmlspecialchars($profile['github_url'] ?? '') ?>" placeholder="https://github.com/yourusername">
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save Changes
                        </button>
                        <button type="reset" class="btn btn-outline-secondary">
                            <i class="fas fa-undo me-1"></i>Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Profile Summary -->
        <div class="glass-card mb-4">
            <div class="card-header bg-transparent border-0 p-4">
                <h5 class="mb-0">Profile Summary</h5>
            </div>
            <div class="card-body p-4 text-center">
                <div class="bg-primary rounded-circle p-3 d-inline-block mb-3">
                    <i class="fas fa-user-graduate text-white fa-2x"></i>
                </div>
                <h5><?= htmlspecialchars($profile['name']) ?></h5>
                <p class="text-muted"><?= htmlspecialchars($profile['specialization'] ?? 'General Mentor') ?></p>
                
                <?php
                // Get current stats
                $stats_query = "SELECT 
                                (SELECT COUNT(*) FROM mentor_student_pairs WHERE mentor_id = ? AND status = 'active') as active,
                                (SELECT COUNT(*) FROM mentor_student_pairs WHERE mentor_id = ? AND status = 'completed') as completed,
                                (SELECT AVG(rating) FROM mentor_student_pairs WHERE mentor_id = ? AND rating IS NOT NULL) as rating";
                $stmt = $conn->prepare($stats_query);
                $stmt->bind_param("iii", $mentor_id, $mentor_id, $mentor_id);
                $stmt->execute();
                $stats = $stmt->get_result()->fetch_assoc();
                ?>
                
                <div class="row text-center">
                    <div class="col-4">
                        <h6><?= $stats['active'] ?></h6>
                        <small class="text-muted">Active</small>
                    </div>
                    <div class="col-4">
                        <h6><?= $stats['completed'] ?></h6>
                        <small class="text-muted">Completed</small>
                    </div>
                    <div class="col-4">
                        <h6><?= number_format($stats['rating'] ?? 0, 1) ?></h6>
                        <small class="text-muted">Rating</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Account Settings -->
        <div class="glass-card">
            <div class="card-header bg-transparent border-0 p-4">
                <h5 class="mb-0">Account Settings</h5>
            </div>
            <div class="card-body p-4">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                        <i class="fas fa-key me-1"></i>Change Password
                    </button>
                    <button class="btn btn-outline-info btn-sm">
                        <i class="fas fa-bell me-1"></i>Notification Settings
                    </button>
                    <button class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-user-times me-1"></i>Deactivate Account
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content glass-card">
            <div class="modal-header border-0">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="passwordForm">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="changePassword()">Change Password</button>
            </div>
        </div>
    </div>
</div>

<script>
function changePassword() {
    const form = document.getElementById('passwordForm');
    const formData = new FormData(form);
    
    if (formData.get('new_password') !== formData.get('confirm_password')) {
        alert('New passwords do not match');
        return;
    }
    
    fetch('change_password.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Password changed successfully');
            bootstrap.Modal.getInstance(document.getElementById('changePasswordModal')).hide();
        } else {
            alert(data.error || 'Failed to change password');
        }
    });
}
</script>

<?php
$content = ob_get_clean();
renderLayout('Profile Settings', $content);
?>