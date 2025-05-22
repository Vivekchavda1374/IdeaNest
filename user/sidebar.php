<?php
// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login/Login/login.php");
    exit();
}

// Get current page to highlight active menu item
$current_page = basename($_SERVER['PHP_SELF']);

// Fetch user information
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "User";
$user_initial = !empty($user_name) ? substr($user_name, 0, 1) : "U";

// Database connection to fetch additional user info if needed
include_once '../Login/Login/db.php';

$user_id = $_SESSION['user_id'];
$emailSql = "SELECT email FROM register WHERE id = ?";
$stmt = $conn->prepare($emailSql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$emailResult = $stmt->get_result();

if ($emailResult->num_rows > 0) {
    $emailRow = $emailResult->fetch_assoc();
    $user_email = $emailRow['email'];
} else {
    $user_email = "user@example.com";
}
$stmt->close();
?>

<!-- Sidebar -->
<style>
:root {
    --sidebar-bg: #ffffff;
    --sidebar-text: #333;
    --sidebar-active-bg: #4361ee;
    --sidebar-active-text: #ffffff;
    --sidebar-hover-bg: #f1f5f9;
}

.sidebar {
    width: 260px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    background-color: var(--sidebar-bg);
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    z-index: 1000;
    overflow-y: auto;
}

.sidebar-header {
    display: flex;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.sidebar-header .logo {
    margin-left: 15px;
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--sidebar-text);
}

.sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-menu li {
    margin: 0;
}

.sidebar-menu li a {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: var(--sidebar-text);
    text-decoration: none;
    transition: all 0.3s ease;
}

.sidebar-menu li a i {
    margin-right: 15px;
    width: 24px;
    text-align: center;
    color: #6b7280;
}

.sidebar-menu li a:hover {
    background-color: var(--sidebar-hover-bg);
}

.sidebar-menu li a.active {
    background-color: var(--sidebar-active-bg);
    color: var(--sidebar-active-text);
}

.sidebar-menu li a.active i {
    color: var(--sidebar-active-text);
}

.user-profile {
    padding: 20px;
    border-top: 1px solid #eee;
    text-align: center;
}

.user-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: #4361ee;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    font-size: 2rem;
    font-weight: 600;
}

.user-details h4 {
    margin: 0;
    font-size: 1.1rem;
    color: var(--sidebar-text);
}

.user-details p {
    margin: 5px 0 0;
    color: #6b7280;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .sidebar {
        width: 0;
        overflow: hidden;
    }

    .sidebar.active {
        width: 260px;
    }
}
</style>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-cubes"></i>
        <div class="logo">IdeaNest</div>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="user_project_search.php" class="<?php echo ($current_page == 'user_project_search.php') ? 'active' : ''; ?>">
                <i class="fas fa-project-diagram"></i>
                <span>Projects</span>
            </a>
        </li>
        <li>
            <a href="Blog/idea_dashboard.php" class="<?php echo (strpos($current_page, 'idea_dashboard.php') !== false) ? 'active' : ''; ?>">
                <i class="fas fa-lightbulb"></i>
                <span>Ideas</span>
            </a>
        </li>
        <li>
            <a href="#" class="<?php echo ($current_page == 'mentorship.php') ? 'active' : ''; ?>">
                <i class="fas fa-user-graduate"></i>
                <span>Mentorship</span>
            </a>
        </li>
        <li>
            <a href="bookmarks.php" class="<?php echo ($current_page == 'bookmarks.php') ? 'active' : ''; ?>">
                <i class="fas fa-bookmark"></i>
                <span>Project Bookmarks</span>
            </a>
        </li>
        <li>
            <a href="Blog/idea_bookmarks.php" class="<?php echo (strpos($current_page, 'idea_bookmarks.php') !== false) ? 'active' : ''; ?>">
                <i class="fas fa-lightbulb"></i>
                <span>Idea Bookmarks</span>
            </a>
        </li>
        <li>
            <a href="user_profile_setting.php" class="<?php echo ($current_page == 'user_profile_setting.php') ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </li>
    </ul>

    <div class="user-profile">
        <div class="user-avatar">
            <?php echo htmlspecialchars($user_initial); ?>
        </div>
        <div class="user-details">
            <h4><?php echo htmlspecialchars($user_name); ?></h4>
            <p><?php echo htmlspecialchars($user_email); ?></p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle for mobile
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const overlay = document.getElementById('overlay');

    function toggleSidebar() {
        sidebar.classList.toggle('active');
        mainContent.classList.toggle('sidebar-active');
        overlay.classList.toggle('active');
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    
    if (overlay) {
        overlay.addEventListener('click', toggleSidebar);
    }
});
</script> 