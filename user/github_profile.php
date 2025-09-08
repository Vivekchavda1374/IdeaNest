<?php
session_start();
include 'layout.php';
include '../Login/Login/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login/Login/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's GitHub data
$stmt = $conn->prepare("SELECT github_username, github_profile_url, github_repos_count, 
    github_followers, github_following, github_bio, github_location, github_company, github_last_sync 
    FROM register WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$github_data = $result->fetch_assoc();

// Get repositories
$repos = [];
if ($github_data['github_username']) {
    $stmt = $conn->prepare("SELECT * FROM user_github_repos WHERE user_id = ? ORDER BY stars_count DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $repos = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GitHub Profile - IdeaNest</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .main-content { padding: 2rem; max-width: 1200px; margin: 0 auto; }
        .github-header { text-align: center; margin-bottom: 2rem; }
        .github-profile { background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .github-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 2rem 0; }
        .stat-card { background: #f8f9fa; padding: 1.5rem; border-radius: 8px; text-align: center; }
        .stat-number { font-size: 2rem; font-weight: bold; color: #0366d6; }
        .repos-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1.5rem; margin-top: 1.5rem; }
        .repo-card { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 1.5rem; }
        .repo-title { font-size: 1.1rem; font-weight: 600; margin-bottom: 0.5rem; }
        .repo-title a { color: #0366d6; text-decoration: none; }
        .no-github { text-align: center; padding: 3rem; color: #586069; }
    </style>
</head>
<body>
<div class="main-content">
    <div class="github-header">
        <h1><i class="fab fa-github"></i> GitHub Profile</h1>
        <p>Your GitHub repositories and contributions</p>
    </div>

    <?php if (empty($github_data['github_username'])): ?>
        <div class="no-github">
            <i class="fab fa-github" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
            <h2>No GitHub Profile Connected</h2>
            <p>Connect your GitHub profile in <a href="user_profile_setting.php">Profile Settings</a> to showcase your repositories.</p>
        </div>
    <?php else: ?>
        <div class="github-profile">
            <h2><i class="fab fa-github"></i> <?php echo htmlspecialchars($github_data['github_username']); ?></h2>
            
            <?php if ($github_data['github_bio']): ?>
                <p style="color: #586069; margin-bottom: 1rem;"><?php echo htmlspecialchars($github_data['github_bio']); ?></p>
            <?php endif; ?>

            <div class="github-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $github_data['github_repos_count'] ?? 0; ?></div>
                    <div>Repositories</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $github_data['github_followers'] ?? 0; ?></div>
                    <div>Followers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $github_data['github_following'] ?? 0; ?></div>
                    <div>Following</div>
                </div>
            </div>

            <?php if ($github_data['github_profile_url']): ?>
                <div style="text-align: center;">
                    <a href="<?php echo htmlspecialchars($github_data['github_profile_url']); ?>" target="_blank" 
                       style="color: #0366d6; text-decoration: none; font-weight: 600;">
                        <i class="fas fa-external-link-alt"></i> View on GitHub
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($repos)): ?>
            <div class="github-profile">
                <h2><i class="fas fa-code-branch"></i> Repositories (<?php echo count($repos); ?>)</h2>
                <div class="repos-grid">
                    <?php foreach ($repos as $repo): ?>
                        <div class="repo-card">
                            <div class="repo-title">
                                <a href="<?php echo htmlspecialchars($repo['repo_url']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($repo['repo_name']); ?>
                                </a>
                            </div>
                            <div style="color: #586069; margin-bottom: 1rem;">
                                <?php echo htmlspecialchars($repo['repo_description'] ?: 'No description available'); ?>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.9rem; color: #586069;">
                                <div>
                                    <?php if ($repo['language']): ?>
                                        <span style="background: #0366d6; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">
                                            <?php echo htmlspecialchars($repo['language']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div style="display: flex; gap: 1rem;">
                                    <span><i class="fas fa-star"></i> <?php echo $repo['stars_count']; ?></span>
                                    <span><i class="fas fa-code-branch"></i> <?php echo $repo['forks_count']; ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>