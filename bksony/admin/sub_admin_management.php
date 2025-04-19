<?php
session_start();
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ideanest";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize messages
$success_message = "";
$error_message = "";

// Add new sub-admin
if (isset($_POST['add_sub_admin'])) {
    $name = trim($_POST['name']);
    $email_username = trim($_POST['email_username']);
    $email_domain = "@marwadiuniversity.edu.in";
    $email = $email_username . $email_domain;
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($name) || empty($email_username) || empty($password) || empty($confirm_password)) {
        $error_message = "All fields are required!";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match!";
    } else {
        // Check if email already exists
        $check_sql = "SELECT * FROM sub_admin WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "This email is already registered!";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new sub-admin
            $insert_sql = "INSERT INTO sub_admin (name, email, password) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sss", $name, $email, $hashed_password);
            
            if ($insert_stmt->execute()) {
                $success_message = "Sub-admin added successfully!";
            } else {
                $error_message = "Error: " . $insert_stmt->error;
            }
            
            $insert_stmt->close();
        }
        
        $check_stmt->close();
    }
}

// Remove sub-admin
if (isset($_POST['remove_sub_admin'])) {
    $sub_admin_id = $_POST['sub_admin_id'];
    
    $delete_sql = "DELETE FROM sub_admin WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $sub_admin_id);
    
    if ($delete_stmt->execute()) {
        $success_message = "Sub-admin removed successfully!";
    } else {
        $error_message = "Error removing sub-admin: " . $delete_stmt->error;
    }
    
    $delete_stmt->close();
}

// Fetch all sub-admins
$sub_admins = [];
$select_sql = "SELECT * FROM sub_admin ORDER BY id DESC";
$result = $conn->query($select_sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sub_admins[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management | Sub-Admin Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
        --primary-color: #00838f;
        --secondary-color: #005f6b;
        --light-color: #f8f9fa;
        --dark-color: #343a40;
        --danger-color: #dc3545;
        --success-color: #28a745;
    }

    body {
        background-color: #f5f7fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .admin-header {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        padding: 20px 0;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }

    .admin-title {
        font-size: 28px;
        font-weight: 600;
        margin: 0;
    }

    .admin-subtitle {
        opacity: 0.8;
        margin: 0;
    }

    .content-card {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        padding: 25px;
        margin-bottom: 30px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .content-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 25px rgba(0, 0, 0, 0.12);
    }

    .card-title {
        color: var(--primary-color);
        font-weight: 600;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
    }

    .card-title i {
        margin-right: 10px;
    }

    .form-label {
        font-weight: 500;
        color: var(--dark-color);
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(0, 131, 143, 0.25);
    }

    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background-color: var(--secondary-color);
        border-color: var(--secondary-color);
        transform: translateY(-2px);
    }

    .btn-danger {
        transition: all 0.3s ease;
    }

    .btn-danger:hover {
        transform: translateY(-2px);
    }

    .alert {
        border-radius: 5px;
        margin-bottom: 20px;
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 999;
    }

    .sub-admin-table {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
    }

    .sub-admin-table th {
        background-color: var(--primary-color);
        color: white;
        font-weight: 500;
        white-space: nowrap;
    }

    .sub-admin-table td {
        vertical-align: middle;
    }

    .table-row {
        transition: background-color 0.2s ease;
    }

    .table-row:hover {
        background-color: rgba(0, 131, 143, 0.05);
    }

    .email-input-group {
        display: flex;
    }

    .email-input-group input {
        border-radius: 5px 0 0 5px;
        border-right: none;
    }

    .email-input-group span {
        border-radius: 0 5px 5px 0;
        border-left: none;
        background-color: #e9ecef;
        color: #495057;
        font-weight: 500;
        display: flex;
        align-items: center;
        padding: 0 12px;
    }

    .password-strength-meter {
        height: 5px;
        width: 100%;
        background-color: #ddd;
        margin-top: 5px;
        border-radius: 3px;
        position: relative;
        overflow: hidden;
    }

    .password-strength-meter span {
        height: 100%;
        width: 0;
        position: absolute;
        transition: width 0.3s ease;
    }

    .strength-weak {
        background-color: #dc3545;
    }

    .strength-medium {
        background-color: #ffc107;
    }

    .strength-strong {
        background-color: #28a745;
    }

    .password-feedback {
        font-size: 12px;
        margin-top: 5px;
        color: #6c757d;
    }

    .dashboard-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background-color: white;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: var(--primary-color);
        margin: 10px 0;
    }

    .stat-label {
        color: #6c757d;
        font-size: 14px;
        font-weight: 500;
    }

    .stat-icon {
        font-size: 24px;
        color: var(--primary-color);
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 50px;
        margin-bottom: 15px;
        opacity: 0.5;
    }

    .confirmation-modal .modal-content {
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .confirmation-modal .modal-header {
        background-color: var(--danger-color);
        color: white;
        border-radius: 10px 10px 0 0;
    }
    </style>
</head>

<body>
    <!-- Toast container for notifications -->
    <div class="toast-container"></div>

    <!-- Admin Header -->
    <header class="admin-header">
        <div class="container">
            <h1 class="admin-title"><i class="fas fa-user-shield me-2"></i>Admin Management Portal</h1>
            <p class="admin-subtitle">Manage your sub-administrators efficiently</p>
        </div>
    </header>

    <div class="container">
        <!-- Success/Error Messages -->
        <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Stats Dashboard -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users-cog"></i>
                </div>
                <div class="stat-value"><?php echo count($sub_admins); ?></div>
                <div class="stat-label">Total Sub-Admins</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="stat-value" id="recent-additions">0</div>
                <div class="stat-label">Recent Additions</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-value">100%</div>
                <div class="stat-label">System Status</div>
            </div>
        </div>

        <div class="row">
            <!-- Add New Sub-Admin Form -->
            <div class="col-lg-5 mb-4">
                <div class="content-card">
                    <h3 class="card-title"><i class="fas fa-user-plus"></i> Add New Sub-Admin</h3>
                    <form id="addSubAdminForm" method="post" action="">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label for="email_username" class="form-label">Email</label>
                            <div class="email-input-group">
                                <input type="text" class="form-control" id="email_username" name="email_username"
                                    required>
                                <span>@marwadiuniversity.edu.in</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="password-strength-meter">
                                <span id="strength-meter" class=""></span>
                            </div>
                            <div class="password-feedback" id="password-feedback">Password strength: Enter a password
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                required>
                        </div>

                        <button type="submit" name="add_sub_admin" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add Sub-Admin
                        </button>
                    </form>
                </div>
            </div>

            <!-- Manage Sub-Admins -->
            <div class="col-lg-7">
                <div class="content-card">
                    <h3 class="card-title"><i class="fas fa-users-cog"></i> Manage Sub-Admins</h3>

                    <?php if (count($sub_admins) > 0): ?>
                    <div class="table-responsive sub-admin-table">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sub_admins as $admin): ?>
                                <tr class="table-row">
                                    <td><?php echo $admin['id']; ?></td>
                                    <td><?php echo htmlspecialchars($admin['name']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-danger remove-btn"
                                            data-id="<?php echo $admin['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($admin['name']); ?>">
                                            <i class="fas fa-trash-alt"></i> Remove
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-users-slash"></i>
                        <h4>No Sub-Admins Found</h4>
                        <p>Add your first sub-admin using the form.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal for Removing Sub-Admin -->
    <div class="modal fade confirmation-modal" id="removeSubAdminModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Removal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to remove <strong id="remove-admin-name"></strong> as a sub-admin?</p>
                    <p class="text-danger"><small>This action cannot be undone.</small></p>

                    <form id="removeSubAdminForm" method="post" action="">
                        <input type="hidden" name="sub_admin_id" id="remove_sub_admin_id">
                        <input type="hidden" name="remove_sub_admin" value="1">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmRemoveBtn">
                        <i class="fas fa-trash-alt me-2"></i>Remove
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap & jQuery Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
    $(document).ready(function() {
        // Update recent additions stat - assuming sub-admins added in the last 7 days
        const recentAdditions = Math.floor(Math.random() * 3); // Simulated data
        $('#recent-additions').text(recentAdditions);

        // Password strength meter
        $('#password').on('input', function() {
            const password = $(this).val();
            let strength = 0;
            let feedback = "";

            if (password.length > 0) {
                // Check length
                if (password.length >= 8) strength += 1;

                // Check for numbers
                if (/\d/.test(password)) strength += 1;

                // Check for special characters
                if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength += 1;

                // Check for uppercase and lowercase
                if (/[A-Z]/.test(password) && /[a-z]/.test(password)) strength += 1;

                // Update visual meter
                const $meter = $('#strength-meter');
                $meter.removeClass('strength-weak strength-medium strength-strong');

                if (strength <= 1) {
                    $meter.addClass('strength-weak').css('width', '33%');
                    feedback = "Password strength: Weak";
                } else if (strength <= 3) {
                    $meter.addClass('strength-medium').css('width', '66%');
                    feedback = "Password strength: Medium";
                } else {
                    $meter.addClass('strength-strong').css('width', '100%');
                    feedback = "Password strength: Strong";
                }
            } else {
                $('#strength-meter').css('width', '0%');
                feedback = "Password strength: Enter a password";
            }

            $('#password-feedback').text(feedback);
        });

        // Confirm password validation
        $('#confirm_password').on('input', function() {
            const password = $('#password').val();
            const confirmPassword = $(this).val();

            if (confirmPassword === password) {
                $(this).removeClass('is-invalid').addClass('is-valid');
            } else {
                $(this).removeClass('is-valid').addClass('is-invalid');
            }
        });

        // Handle remove button clicks
        $('.remove-btn').click(function() {
            const adminId = $(this).data('id');
            const adminName = $(this).data('name');

            $('#remove_sub_admin_id').val(adminId);
            $('#remove-admin-name').text(adminName);

            const removeModal = new bootstrap.Modal(document.getElementById('removeSubAdminModal'));
            removeModal.show();
        });

        // Handle confirm remove button
        $('#confirmRemoveBtn').click(function() {
            $('#removeSubAdminForm').submit();
        });

        // Form validation
        $('#addSubAdminForm').on('submit', function(e) {
            const password = $('#password').val();
            const confirmPassword = $('#confirm_password').val();

            if (password !== confirmPassword) {
                e.preventDefault();
                showToast('Passwords do not match!', 'danger');
            }
        });

        // Toast notification function
        function showToast(message, type) {
            const toastId = 'toast-' + Date.now();
            const toast = `
                    <div class="toast align-items-center text-white bg-${type} border-0" id="${toastId}" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="fas fa-${type === 'danger' ? 'exclamation-circle' : 'check-circle'} me-2"></i>
                                ${message}
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </div>
                `;

            $('.toast-container').append(toast);
            const toastElement = new bootstrap.Toast(document.getElementById(toastId), {
                delay: 5000
            });
            toastElement.show();
        }

        // Show toast for PHP messages if they exist
        <?php if (!empty($success_message)): ?>
        setTimeout(() => showToast('<?php echo $success_message; ?>', 'success'), 300);
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
        setTimeout(() => showToast('<?php echo $error_message; ?>', 'danger'), 300);
        <?php endif; ?>

        // Animation for table rows
        $('.table-row').each(function(index) {
            $(this).css({
                'opacity': 0,
                'transform': 'translateY(20px)'
            });

            setTimeout(() => {
                $(this).css({
                    'transition': 'all 0.3s ease',
                    'opacity': 1,
                    'transform': 'translateY(0)'
                });
            }, 100 * index);
        });
    });
    </script>
</body>

</html>