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

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "User";
$user_initial = !empty($user_name) ? substr($user_name, 0, 1) : "U";
if ($user_id) {
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
} else {
    $user_email = "admin@ICT.com";
}
$conn->close();
?>

<style>
body {
    background: linear-gradient(120deg, #f8f9fa 60%, #e0c3fc 100%);
    min-height: 100vh;
}
.hero-section {
    background: linear-gradient(120deg, #3a86ff 0%, #8338ec 100%);
    border-radius: 2rem;
    padding: 2.5rem 2rem 2rem 2rem;
    margin-bottom: 2.5rem;
    box-shadow: 0 8px 32px 0 rgba(58,134,255,0.15);
    color: #fff;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    gap: 2rem;
}
.hero-avatar {
    width: 90px;
    height: 90px;
    background: rgba(255,255,255,0.18);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.7rem;
    font-weight: bold;
    box-shadow: 0 4px 24px rgba(67,97,238,0.10);
    border: 2px solid rgba(255,255,255,0.25);
}
.hero-content h1 {
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}
.hero-content p {
    font-size: 1.1rem;
    margin-bottom: 0.2rem;
}
.stats-row {
    margin-bottom: 2.5rem;
}
.stat-card {
    background: rgba(255,255,255,0.7);
    border-radius: 1.5rem;
    box-shadow: 0 8px 32px 0 rgba(58,134,255,0.10);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.18);
    padding: 2rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.2rem;
    transition: transform 0.2s, box-shadow 0.2s;
    height: 100%;
}
.stat-card:hover {
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
    box-shadow: 0 2px 8px rgba(58,134,255,0.10);
}
.stat-projects { background: linear-gradient(135deg, #3a86ff 60%, #4cc9f0 100%); color: #fff; }
.stat-ideas { background: linear-gradient(135deg, #ffbe0b 60%, #ff006e 100%); color: #fff; }
.stat-bookmarks { background: linear-gradient(135deg, #38b000 60%, #3a86ff 100%); color: #fff; }
.stat-label { font-size: 1.1rem; color: #6c757d; }
.stat-value { font-size: 2.2rem; font-weight: 700; }
.progress-section {
    margin-bottom: 2rem;
}
@media (max-width: 991px) {
    .hero-section { flex-direction: column; text-align: center; gap: 1.5rem; }
    .hero-avatar { margin: 0 auto; }
    .hero-content { width: 100%; }
}
</style>

<div class="container-fluid px-0" style="min-height: 100vh;">
    <!-- Hero Section -->
    <div class="hero-section mb-4">
        <div class="hero-avatar">
            <?php echo htmlspecialchars($user_initial); ?>
        </div>
        <div class="hero-content">
            <h1>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1>
            <p>Your innovation dashboard at a glance</p>
            <p class="mb-0 text-white-50">Email: <?php echo htmlspecialchars($user_email); ?></p>
        </div>
        <a href="./forms/new_project_add.php" class="btn btn-light btn-lg shadow-sm px-4 fw-semibold ms-auto">
            <i class="fas fa-plus me-2"></i> New Project
        </a>
    </div>
    <!-- Stats Cards -->
    <div class="row stats-row g-4 mb-4">
        <div class="col-12 col-md-4 d-flex">
            <div class="stat-card stat-projects w-100">
                <div class="stat-icon stat-projects"><i class="fas fa-project-diagram"></i></div>
                <div>
                    <div class="stat-label">Projects</div>
                    <div class="stat-value"><?php echo $projectCount; ?></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 d-flex">
            <div class="stat-card stat-ideas w-100">
                <div class="stat-icon stat-ideas"><i class="fas fa-lightbulb"></i></div>
                <div>
                    <div class="stat-label">Ideas</div>
                    <div class="stat-value"><?php echo $blogCount; ?></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 d-flex">
            <div class="stat-card stat-bookmarks w-100">
                <div class="stat-icon stat-bookmarks"><i class="fas fa-bookmark"></i></div>
                <div>
                    <div class="stat-label">Bookmarks</div>
                    <div class="stat-value"><?php echo $bookMarkCount; ?></div>
                </div>
            </div>
        </div>
    </div>
    <!-- Progress Bar Cards -->
    <div class="row g-4 mb-4 align-items-stretch" style="width:100%; margin:0;">
        <div class="col-12 col-md-6 d-flex" style="padding:0 1rem;">
            <?php include './forms/progressbar.php'; ?>
        </div>
        <div class="col-12 col-md-6 d-flex" style="width: 100%;padding:0 1rem;">
            <?php include './forms/progressbar_idea.php'; ?>
        </div>
    </div>
</div>

<?php include $basePath . 'layout_footer.php'; ?>
