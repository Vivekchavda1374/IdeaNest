<?php
require_once __DIR__ . '/includes/security_init.php';
/**
 * GitHub Profile Display Component
 */

require_once '../Login/Login/db.php';
require_once 'github_service.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login/Login/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's GitHub information
$query = "SELECT github_username, github_profile_url, github_repos_count, github_followers, github_following, github_last_sync FROM register WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$github_data = $result->fetch_assoc();

$github_username = $github_data['github_username'] ?? '';
$github_profile = null;
$github_repos = [];

if (!empty($github_username)) {
    $last_sync = $github_data['github_last_sync'] ?? null;
    $cache_duration = 3600; // 1 hour cache
    
    // Check if cache is still valid and has follower data
    if ($last_sync && (time() - strtotime($last_sync)) < $cache_duration && 
        isset($github_data['github_followers']) && isset($github_data['github_following'])) {
        // Use cached data from database
        $github_profile = [
            'login' => $github_username,
            'html_url' => $github_data['github_profile_url'] ?? "https://github.com/{$github_username}",
            'public_repos' => $github_data['github_repos_count'] ?? 0,
            'followers' => $github_data['github_followers'] ?? 0,
            'following' => $github_data['github_following'] ?? 0,
            'avatar_url' => "https://github.com/{$github_username}.png",
            'name' => $github_username
        ];
        $github_repos = []; // Skip repos for cached data
    } else {
        // Fetch fresh GitHub data
        $github_profile = fetchGitHubProfile($github_username);
        $github_repos = fetchGitHubRepos($github_username);
        
        if ($github_profile) {
            // Update cache with all data
            $stmt = $conn->prepare("UPDATE register SET github_profile_url = ?, github_repos_count = ?, github_followers = ?, github_following = ?, github_last_sync = NOW() WHERE id = ?");
            $stmt->bind_param("siiii", $github_profile['html_url'], $github_profile['public_repos'], $github_profile['followers'], $github_profile['following'], $user_id);
            $stmt->execute();
        }
        
        // Limit to top 6 repositories
        $github_repos = array_slice($github_repos, 0, 6);
    }
}
?>

<?php if (!empty($github_username) && $github_profile) : ?>
<div class="github-section">
    <div class="section-header">
        <h3><i class="fab fa-github"></i> GitHub Profile</h3>
        <div class="github-actions">
            <button id="refreshGitHub" class="btn btn-outline-secondary btn-sm" onclick="refreshGitHubProfile()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <a href="<?php echo htmlspecialchars($github_profile['html_url']); ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-external-link-alt"></i> View on GitHub
            </a>
        </div>
    </div>
    
    <div class="github-profile-card">
        <div class="profile-info">
            <img src="<?php echo htmlspecialchars($github_profile['avatar_url']); ?>" alt="GitHub Avatar" class="github-avatar">
            <div class="profile-details">
                <h4><?php echo htmlspecialchars($github_profile['name'] ?? $github_profile['login']); ?></h4>
                <p class="username">@<?php echo htmlspecialchars($github_profile['login']); ?></p>
                <?php if (!empty($github_profile['bio'])) : ?>
                    <p class="bio"><?php echo htmlspecialchars($github_profile['bio']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="github-stats">
            <div class="stat-item">
                <span class="stat-number"><?php echo $github_profile['public_repos']; ?></span>
                <span class="stat-label">Repositories</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo $github_profile['followers']; ?></span>
                <span class="stat-label">Followers</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo $github_profile['following']; ?></span>
                <span class="stat-label">Following</span>
            </div>
        </div>
    </div>
    
    <?php if (!empty($github_repos)) : ?>
    <div class="github-repos">
        <h4>Recent Repositories</h4>
        <div class="repos-grid">
            <?php foreach ($github_repos as $repo) : ?>
            <div class="repo-card">
                <div class="repo-header">
                    <h5><a href="<?php echo htmlspecialchars($repo['html_url']); ?>" target="_blank"><?php echo htmlspecialchars($repo['name']); ?></a></h5>
                    <?php if (!$repo['private']) : ?>
                        <span class="badge badge-public">Public</span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($repo['description'])) : ?>
                    <p class="repo-description" loading="lazy"><?php echo htmlspecialchars($repo['description']); ?></p>
                <?php endif; ?>
                <div class="repo-meta">
                    <?php if (!empty($repo['language'])) : ?>
                        <span class="language">
                            <span class="language-dot" style="background-color: <?php
                                $languageColors = [
                                    'JavaScript' => '#f1e05a',
                                    'Python' => '#3572A5',
                                    'Java' => '#b07219',
                                    'TypeScript' => '#2b7489',
                                    'C++' => '#f34b7d',
                                    'C' => '#555555',
                                    'C#' => '#239120',
                                    'PHP' => '#4F5D95',
                                    'Ruby' => '#701516',
                                    'Go' => '#00ADD8',
                                    'Rust' => '#dea584',
                                    'Swift' => '#ffac45',
                                    'Kotlin' => '#F18E33',
                                    'Dart' => '#00B4AB',
                                    'HTML' => '#e34c26',
                                    'CSS' => '#1572B6',
                                    'Shell' => '#89e051',
                                    'Jupyter Notebook' => '#DA5B0B',
                                    'Vue' => '#2c3e50',
                                    'React' => '#61DAFB'
                                ];
                                echo $languageColors[$repo['language']] ?? '#' . substr(md5($repo['language']), 0, 6);
                                ?>;"></span>
                            <?php echo htmlspecialchars($repo['language']); ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($repo['stargazers_count'] > 0) : ?>
                        <span class="stars">
                            <i class="fas fa-star"></i> <?php echo $repo['stargazers_count']; ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($repo['forks_count'] > 0) : ?>
                        <span class="forks">
                            <i class="fas fa-code-branch"></i> <?php echo $repo['forks_count']; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.github-section {
    background: white;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.section-header h3 {
    margin: 0;
    color: #333;
}

.github-profile-card {
    margin-bottom: 20px;
}

.profile-info {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.github-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: 2px solid #e1e4e8;
}

.profile-details h4 {
    margin: 0 0 5px 0;
    color: #24292e;
}

.username {
    color: #586069;
    margin: 0 0 5px 0;
    font-size: 14px;
}

.bio {
    color: #586069;
    margin: 0;
    font-size: 14px;
}

.github-stats {
    display: flex;
    gap: 20px;
    padding: 15px;
    background: #f6f8fa;
    border-radius: 8px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 18px;
    font-weight: bold;
    color: #24292e;
}

.stat-label {
    font-size: 12px;
    color: #586069;
}

.repos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.repo-card {
    border: 1px solid #e1e4e8;
    border-radius: 8px;
    padding: 15px;
    background: white;
    transition: all 0.3s ease;
    animation: fadeInUp 0.6s ease forwards;
}

.repo-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: #0366d6;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.repo-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.repo-header h5 {
    margin: 0;
    font-size: 16px;
}

.repo-header a {
    color: #0366d6;
    text-decoration: none;
}

.repo-header a:hover {
    text-decoration: underline;
}

.badge-public {
    background: #28a745;
    color: white;
    padding: 2px 6px;
    border-radius: 12px;
    font-size: 10px;
    text-transform: uppercase;
}

.repo-description {
    color: #586069;
    font-size: 14px;
    margin-bottom: 10px;
    content-visibility: auto;
    contain-intrinsic-size: 0 40px;
}

.repo-meta {
    display: flex;
    gap: 15px;
    font-size: 12px;
    color: #586069;
}

.language {
    display: flex;
    align-items: center;
    gap: 5px;
}

.language-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.stars, .forks {
    display: flex;
    align-items: center;
    gap: 3px;
}

.github-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.github-loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    z-index: 10;
}

.loading-spinner {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 16px;
    color: #666;
}

.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 8px;
    color: white;
    display: flex;
    align-items: center;
    gap: 10px;
    z-index: 1000;
    animation: slideIn 0.3s ease;
}

.notification-info {
    background: #3b82f6;
}

.notification-error {
    background: #ef4444;
}

.notification-close {
    background: none;
    border: none;
    color: white;
    font-size: 18px;
    cursor: pointer;
    margin-left: 10px;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.github-section {
    position: relative;
}
</style>

<script>
// Animate repo cards on load
document.addEventListener('DOMContentLoaded', function() {
    const repoCards = document.querySelectorAll('.repo-card');
    repoCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
});

// AJAX function to refresh GitHub profile
function refreshGitHubProfile() {
    const refreshBtn = document.getElementById('refreshGitHub');
    const githubSection = document.querySelector('.github-section');
    
    // Show loading state
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
    refreshBtn.disabled = true;
    
    // Add loading overlay
    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'github-loading-overlay';
    loadingOverlay.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Fetching latest data...</div>';
    githubSection.appendChild(loadingOverlay);
    
    fetch('github_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'refresh_profile'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload the page to show updated data
            location.reload();
        } else {
            showNotification('Error refreshing GitHub profile: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Failed to refresh GitHub profile', 'error');
    })
    .finally(() => {
        // Remove loading state
        refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
        refreshBtn.disabled = false;
        if (loadingOverlay) {
            loadingOverlay.remove();
        }
    });
}

// Show notification function
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" class="notification-close">&times;</button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}
</script>

<?php else : ?>
<div class="github-section">
    <div class="no-github">
        <div class="text-center">
            <i class="fab fa-github" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
            <h4>Connect Your GitHub</h4>
            <p>Link your GitHub profile to showcase your repositories and contributions</p>
            <div class="github-connect-actions">
                <a href="user_profile_setting.php" class="btn btn-primary">
                    <i class="fab fa-github"></i> Connect GitHub
                </a>
                <button id="quickConnectGitHub" class="btn btn-outline-primary" onclick="quickConnectGitHub()">
                    <i class="fas fa-link"></i> Quick Connect
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Quick connect GitHub function
function quickConnectGitHub() {
    const username = prompt('Enter your GitHub username:');
    if (!username) return;
    
    const btn = document.getElementById('quickConnectGitHub');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connecting...';
    btn.disabled = true;
    
    fetch('github_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'connect_github',
            username: username
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('GitHub profile connected successfully!', 'info');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Failed to connect GitHub profile', 'error');
    })
    .finally(() => {
        btn.innerHTML = '<i class="fas fa-link"></i> Quick Connect';
        btn.disabled = false;
    });
}
</script>

<style>
.no-github {
    padding: 40px;
    text-align: center;
    color: #666;
}

.github-connect-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-top: 20px;
}

@media (max-width: 768px) {
    .github-connect-actions {
        flex-direction: column;
        align-items: center;
    }
}
</style>
<?php endif; ?>