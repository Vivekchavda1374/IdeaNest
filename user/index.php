<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$basePath = './';
include $basePath . 'layout.php';
include '../Login/Login/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fetch project count
$sql = "SELECT COUNT(*) AS project_count FROM admin_approved_projects";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$projectCount = $row['project_count'];

$blogSql = "SELECT COUNT(*) AS blog_count FROM blog";
$blogResult = $conn->query($blogSql);
$blogRow = $blogResult->fetch_assoc();
$blogCount = $blogRow['blog_count'];

$bookMarkSql = "SELECT COUNT(*) AS bookmark_count FROM bookmark";
$bookMarkResult = $conn->query($bookMarkSql);
$bookMarkRow = $bookMarkResult->fetch_assoc();
$bookMarkCount = $bookMarkRow['bookmark_count'];

$user_id = $_SESSION['user_id'];
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "User";
$user_initial = !empty($user_name) ? substr($user_name, 0, 1) : "U";
$emailSql = "SELECT email FROM register WHERE id = ?";
$stmt = $conn->prepare($emailSql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$emailResult = $stmt->get_result();
if ($emailResult->num_rows > 0) {
    $emailRow = $emailResult->fetch_assoc();
    $user_email = $emailRow['email'];
    $_SESSION['email'] = $user_email;
} else {
    $user_email = "admin@ICT.com";
}
$stmt->close();
$conn->close();
?>

<style>
/* Glassmorphism and modern dashboard styles */
.dashboard-hero {
    background: linear-gradient(120deg, #3a86ff 0%, #8338ec 100%);
    border-radius: 2rem;
    padding: 3rem 2rem 2rem 2rem;
    margin-bottom: 2.5rem;
    box-shadow: 0 8px 32px 0 rgba(58,134,255,0.15);
    color: #fff;
    position: relative;
    overflow: hidden;
}
.dashboard-hero .avatar {
    width: 80px;
    height: 80px;
    background: rgba(255,255,255,0.15);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 1rem;
    box-shadow: 0 4px 24px rgba(67,97,238,0.10);
    border: 2px solid rgba(255,255,255,0.25);
}
.glass-card {
    background: rgba(255,255,255,0.7);
    border-radius: 1.5rem;
    box-shadow: 0 8px 32px 0 rgba(58,134,255,0.10);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.18);
    transition: transform 0.2s, box-shadow 0.2s;
}
.glass-card:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 16px 48px 0 rgba(58,134,255,0.18);
}
.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin-right: 1rem;
    box-shadow: 0 2px 8px rgba(58,134,255,0.10);
}
.stat-projects { background: linear-gradient(135deg, #3a86ff 60%, #4cc9f0 100%); color: #fff; }
.stat-ideas { background: linear-gradient(135deg, #ffbe0b 60%, #ff006e 100%); color: #fff; }
.stat-bookmarks { background: linear-gradient(135deg, #38b000 60%, #3a86ff 100%); color: #fff; }
.stat-label { font-size: 1.1rem; color: #6c757d; }
.stat-value { font-size: 2.2rem; font-weight: 700; }
@media (max-width: 767px) {
    .dashboard-hero { padding: 2rem 1rem 1.5rem 1rem; }
    .glass-card { border-radius: 1rem; }
}
</style>

<div class="container-fluid px-0" style="background: #f8f9fa; min-height: 100vh;">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <!-- Hero Section -->
            <div class="dashboard-hero text-center mb-5">
                <div class="avatar mx-auto mb-2">
                    <?php echo htmlspecialchars($user_initial); ?>
                </div>
                <h1 class="fw-bold mb-2">Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1>
                <p class="mb-1 fs-5">Your innovation dashboard at a glance</p>
                <p class="mb-0 text-white-50">Email: <?php echo htmlspecialchars($user_email); ?></p>
                <a href="./forms/new_project_add.php" class="btn btn-light btn-lg mt-4 shadow-sm px-4 fw-semibold">
                    <i class="fas fa-plus me-2"></i> New Project
                </a>
            </div>
            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="glass-card p-4 d-flex align-items-center h-100">
                        <div class="stat-icon stat-projects me-3">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                        <div>
                            <div class="stat-label">Projects</div>
                            <div class="stat-value"><?php echo $projectCount; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card p-4 d-flex align-items-center h-100">
                        <div class="stat-icon stat-ideas me-3">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <div>
                            <div class="stat-label">Ideas</div>
                            <div class="stat-value"><?php echo $blogCount; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card p-4 d-flex align-items-center h-100">
                        <div class="stat-icon stat-bookmarks me-3">
                            <i class="fas fa-bookmark"></i>
                        </div>
                        <div>
                            <div class="stat-label">Bookmarks</div>
                            <div class="stat-value"><?php echo $bookMarkCount; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Main Content Cards (Progress bars, etc.) -->
            <div class="row g-4 mb-4 align-items-stretch">
                <div class="col-12 col-md-6 d-flex">
                    <?php include './forms/progressbar.php'; ?>
                </div>
                <div class="col-12 col-md-6 d-flex">
                    <?php include './forms/progressbar_idea.php'; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $basePath . 'layout_footer.php'; ?>
