<?php
session_start();
if (!isset($_SESSION['subadmin_logged_in']) || !$_SESSION['subadmin_logged_in']) {
    header("Location: ../../Login/Login/login.php");
    exit();
}
include_once "../../Login/Login/db.php";
$subadmin_id = $_SESSION['subadmin_id'];

// Fetch current profile
$stmt = $conn->prepare("SELECT email, name, domain, software_classification, hardware_classification FROM subadmins WHERE id = ?");
$stmt->bind_param("i", $subadmin_id);
$stmt->execute();
$stmt->bind_result($email, $name, $domain, $software_classification, $hardware_classification);
$stmt->fetch();
$stmt->close();

$success = $error = '';

// Classification options
$software_options = [
    'Web',
    'Mobile',
    'Artificial Intelligence & Machine Learning',
    'Desktop',
    'System Software',
    'Embedded/IoT Software',
    'Cybersecurity',
    'Game Development',
    'Data Science & Analytics',
    'Cloud-Based Applications'
];
$hardware_options = [
    'Embedded Systems',
    'Internet of Things (IoT)',
    'Robotics',
    'Automation',
    'Sensor-Based Systems',
    'Communication Systems',
    'Power Electronics',
    'Wearable Technology',
    'Mechatronics',
    'Renewable Energy Systems'
];

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = trim($_POST['name']);
    $new_domain = trim($_POST['domain']);
    $new_software = $_POST['software_classification'] ?? '';
    $new_hardware = $_POST['hardware_classification'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    if ($new_name === '' || $new_domain === '') {
        $error = "Name and domain are required.";
    } else if ($new_software === '' && $new_hardware === '') {
        $error = "Please select at least one classification (software or hardware).";
    } else if ($new_password !== '' || $confirm_password !== '') {
        if ($new_password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else if (strlen($new_password) < 6) {
            $error = "Password must be at least 6 characters.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE subadmins SET name=?, domain=?, software_classification=?, hardware_classification=?, password=?, profile_complete=1 WHERE id=?");
            $stmt->bind_param("sssssi", $new_name, $new_domain, $new_software, $new_hardware, $hashed_password, $subadmin_id);
            if ($stmt->execute()) {
                $success = "Profile and password updated successfully.";
                $name = $new_name;
                $domain = $new_domain;
                $software_classification = $new_software;
                $hardware_classification = $new_hardware;
            } else {
                $error = "Failed to update profile/password.";
            }
            $stmt->close();
        }
    } else {
        $stmt = $conn->prepare("UPDATE subadmins SET name=?, domain=?, software_classification=?, hardware_classification=?, profile_complete=1 WHERE id=?");
        $stmt->bind_param("ssssi", $new_name, $new_domain, $new_software, $new_hardware, $subadmin_id);
        if ($stmt->execute()) {
            $success = "Profile updated successfully.";
            $name = $new_name;
            $domain = $new_domain;
            $software_classification = $new_software;
            $hardware_classification = $new_hardware;
        } else {
            $error = "Failed to update profile.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subadmin Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        .sidebar { position: fixed; top: 0; left: 0; bottom: 0; width: 250px; background: rgba(255,255,255,0.95); box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.08); border-radius: 0 2rem 2rem 0; z-index: 1000; transition: all 0.3s; overflow-y: auto; padding: 1.5rem 1rem 1rem 1.5rem; }
        .sidebar-header { padding: 1rem 0; text-align: center; border-bottom: 1px solid #f1f1f1; margin-bottom: 1.5rem; }
        .sidebar-brand { font-size: 1.7rem; font-weight: 700; color: #4f46e5; text-decoration: none; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; letter-spacing: 1px; }
        .sidebar-brand i { margin-right: 0.6rem; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; }
        .sidebar-item { margin-bottom: 0.7rem; }
        .sidebar-link { display: flex; align-items: center; padding: 0.85rem 1.1rem; color: #6366f1; text-decoration: none; border-radius: 0.5rem; font-weight: 500; font-size: 1.08rem; transition: all 0.2s; background: transparent; }
        .sidebar-link i { margin-right: 0.85rem; font-size: 1.3rem; }
        .sidebar-link.active, .sidebar-link:focus { background: linear-gradient(90deg, #6366f1 0%, #a5b4fc 100%); color: #fff; box-shadow: 0 2px 8px rgba(99,102,241,0.08); }
        .sidebar-link:hover:not(.active) { background: #f1f5f9; color: #4f46e5; }
        .sidebar-divider { margin: 1.2rem 0; border-top: 1.5px solid #e5e7eb; }
        .sidebar-footer { padding: 1.2rem 0 0.5rem 0; border-top: 1px solid #f1f1f1; margin-top: 1.5rem; }
        .main-content { margin-left: 250px; padding: 2.5rem 2rem 2rem 2rem; transition: all 0.3s; max-width: 100vw; width: 100%; }
        .topbar { display: flex; align-items: center; justify-content: space-between; padding: 1.2rem 0 1.5rem 0; margin-bottom: 2.5rem; }
        .page-title { font-size: 2rem; font-weight: 700; margin: 0; color: #4f46e5; letter-spacing: 1px; }
        .topbar-actions { display: flex; align-items: center; }
        .user-avatar { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #6366f1 0%, #a5b4fc 100%); display: flex; align-items: center; justify-content: center; color: #fff; margin-left: 1.2rem; font-size: 1.5rem; box-shadow: 0 2px 8px rgba(99,102,241,0.08); }
        .glass-card { background: rgba(255,255,255,0.85); box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.10); backdrop-filter: blur(8px); border-radius: 1.5rem; border: 1px solid rgba(255,255,255,0.18); transition: transform 0.15s, box-shadow 0.15s; }
        .glass-card:hover { transform: translateY(-4px) scale(1.03); box-shadow: 0 16px 32px 0 rgba(99,102,241,0.13); z-index: 2; }
        .alert { border-radius: 0.75rem; }
        .card { border: none; }
        @media (max-width: 991.98px) { .sidebar { transform: translateX(-100%); border-radius: 0 0 2rem 2rem; } .sidebar.show { transform: translateX(0); } .main-content { margin-left: 0; padding: 1rem; } .main-content.pushed { margin-left: 250px; } }
        @media (max-width: 600px) { .profile-card { padding: 1.2rem 0.5rem; } }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="#" class="sidebar-brand">
                <i class="bi bi-lightbulb"></i>
                <span>IdeaNest Subadmin</span>
            </a>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item">
                <a href="dashboard.php" class="sidebar-link">
                    <i class="bi bi-grid-1x2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="profile.php" class="sidebar-link active">
                    <i class="bi bi-person-circle"></i>
                    <span>Profile</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="#projects" class="sidebar-link">
                    <i class="bi bi-kanban"></i>
                    <span>Assigned Projects</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="#notifications" class="sidebar-link">
                    <i class="bi bi-bell"></i>
                    <span>Notifications</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="#support" class="sidebar-link">
                    <i class="bi bi-envelope"></i>
                    <span>Support</span>
                </a>
            </li>
            <hr class="sidebar-divider">
        </ul>
        <div class="sidebar-footer">
            <a href="../../Login/Login/logout.php" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        </div>
    </div>
    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <button class="btn d-lg-none" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>
            <h1 class="page-title">Subadmin Profile</h1>
            <div class="topbar-actions">
                <div class="dropdown">
                    <a href="#" class="user-avatar" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../../Login/Login/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- Profile Card/Form -->
        <div class="profile-card card shadow-lg mx-auto">
            <div class="card-body">
                <h3 class="mb-4 text-center"><i class="bi bi-person-circle me-2"></i>Subadmin Profile</h3>
                <?php if($success): ?>
                    <div class="alert alert-success"> <?php echo $success; ?> </div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div class="alert alert-danger"> <?php echo $error; ?> </div>
                <?php endif; ?>
                <form method="post" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <input type="text" class="form-control" name="domain" value="<?php echo htmlspecialchars($domain); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Software Classification</label>
                        <select class="form-select" name="software_classification">
                            <option value="">Select Software Classification</option>
                            <?php foreach($software_options as $opt): ?>
                                <option value="<?php echo htmlspecialchars($opt); ?>" <?php if($software_classification == $opt) echo 'selected'; ?>><?php echo $opt; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hardware Classification</label>
                        <select class="form-select" name="hardware_classification">
                            <option value="">Select Hardware Classification</option>
                            <?php foreach($hardware_options as $opt): ?>
                                <option value="<?php echo htmlspecialchars($opt); ?>" <?php if($hardware_classification == $opt) echo 'selected'; ?>><?php echo $opt; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label">New Password <span class="text-muted small">(leave blank to keep current)</span></label>
                        <input type="password" class="form-control" name="new_password" minlength="6" autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" minlength="6" autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Update Profile</button>
                </form>
                
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
            document.querySelector('.main-content').classList.toggle('pushed');
        });
    </script>
</body>
</html> 