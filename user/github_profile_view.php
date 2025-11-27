<?php
require_once __DIR__ . '/includes/security_init.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login/Login/login.php");
    exit();
}

require_once '../Login/Login/db.php';
require_once 'github_service.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// Get GitHub username
$stmt = $conn->prepare("SELECT github_username FROM register WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$github_username = $user_data['github_username'] ?? '';
$stmt->close();

if (empty($github_username)) {
    header("Location: user_profile_setting.php?error=no_github");
    exit();
}

// Fetch GitHub data
$github_profile = fetchGitHubProfile($github_username);
$github_repos = fetchGitHubRepos($github_username);

if (!$github_profile) {
    $error_message = "Unable to fetch GitHub profile. Please check your username.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GitHub Profile - <?php echo htmlspecialchars($github_username); ?></title>
    <link rel="icon" type="image/png" href="../assets/image/fevicon.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/index.css">
    <style>
        .github-profile-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 2rem 0;
        }
        .profile-header {
            background: white;
            border-radius: 16px;
            padding: 3rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
        }
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid #6366f1;
            margin-bottom: 1.5rem;
        }
        .profile-name {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        .profile-username {
            font-size: 1.25rem;
            color: #64748b;
            margin-bottom: 1rem;
        }
        .profile-bio {
            font-size: 1rem;
            color: #475569;
            max-width: 600px;
            margin: 0 auto 2rem;
        }
        .profile-stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-top: 2rem;
        }
        .stat-box {
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #6366f1;
            display: block;
        }
        .stat-label {
            font-size: 0.9rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .repos-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .repos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        .repo-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            background: white;
            transition: all 0.3s ease;
        }
        .repo-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border-color: #6366f1;
        }
        .repo-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #6366f1;
            margin-bottom: 0.75rem;
            text-decoration: none;
            display: block;
        }
        .repo-name:hover {
            text-decoration: underline;
        }
        .repo-description {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        .repo-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.85rem;
            color: #64748b;
            flex-wrap: wrap;
        }
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        .language-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        .back-btn {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99,102,241,0.3);
            color: white;
        }
        .error-message {
            background: #fee;
            color: #c33;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
    </style>
    <link rel="stylesheet" href="assets/css/loader.css">
    <link rel="stylesheet" href="assets/css/loading.css">
</head>
<body>
    <div class="github-profile-page">
        <div class="container">
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>

            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($github_profile): ?>
                <div class="profile-header">
                    <img src="<?php echo htmlspecialchars($github_profile['avatar_url']); ?>" alt="GitHub Avatar" class="profile-avatar">
                    <h1 class="profile-name"><?php echo htmlspecialchars($github_profile['name'] ?? $github_profile['login']); ?></h1>
                    <p class="profile-username">@<?php echo htmlspecialchars($github_profile['login']); ?></p>
                    <?php if (!empty($github_profile['bio'])): ?>
                        <p class="profile-bio"><?php echo htmlspecialchars($github_profile['bio']); ?></p>
                    <?php endif; ?>
                    
                    <div class="profile-stats">
                        <div class="stat-box">
                            <span class="stat-number"><?php echo $github_profile['public_repos']; ?></span>
                            <span class="stat-label">Repositories</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-number"><?php echo $github_profile['followers']; ?></span>
                            <span class="stat-label">Followers</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-number"><?php echo $github_profile['following']; ?></span>
                            <span class="stat-label">Following</span>
                        </div>
                    </div>
                    
                    <div style="margin-top: 2rem;">
                        <a href="<?php echo htmlspecialchars($github_profile['html_url']); ?>" target="_blank" class="btn btn-dark btn-lg">
                            <i class="fab fa-github"></i> View on GitHub
                        </a>
                    </div>
                </div>

                <?php if (!empty($github_repos)): ?>
                    <div class="repos-section">
                        <h2 class="section-title">
                            <i class="fas fa-code-branch"></i> Repositories (<?php echo count($github_repos); ?>)
                        </h2>
                        <div class="repos-grid">
                            <?php foreach ($github_repos as $repo): ?>
                                <div class="repo-card">
                                    <a href="<?php echo htmlspecialchars($repo['html_url']); ?>" target="_blank" class="repo-name">
                                        <i class="fas fa-book"></i> <?php echo htmlspecialchars($repo['name']); ?>
                                    </a>
                                    <?php if (!empty($repo['description'])): ?>
                                        <p class="repo-description"><?php echo htmlspecialchars($repo['description']); ?></p>
                                    <?php endif; ?>
                                    <div class="repo-meta">
                                        <?php if (!empty($repo['language'])): ?>
                                            <span class="meta-item">
                                                <span class="language-dot" style="background: <?php
                                                    $colors = ['JavaScript' => '#f1e05a', 'Python' => '#3572A5', 'Java' => '#b07219', 
                                                               'TypeScript' => '#2b7489', 'PHP' => '#4F5D95', 'C++' => '#f34b7d'];
                                                    echo $colors[$repo['language']] ?? '#666';
                                                ?>;"></span>
                                                <?php echo htmlspecialchars($repo['language']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($repo['stargazers_count'] > 0): ?>
                                            <span class="meta-item">
                                                <i class="fas fa-star"></i> <?php echo $repo['stargazers_count']; ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($repo['forks_count'] > 0): ?>
                                            <span class="meta-item">
                                                <i class="fas fa-code-branch"></i> <?php echo $repo['forks_count']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

<!-- Universal Loader -->
<div id="universalLoader" class="loader-overlay">
    <div class="loader">
        <div class="loader-spinner"></div>
        <div class="loader-text" id="loaderText">Loading...</div>
    </div>
</div>

<script src="assets/js/loader.js"></script>
<script src="assets/js/loading.js"></script>
</body>
</html>
