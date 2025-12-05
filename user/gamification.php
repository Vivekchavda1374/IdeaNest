<?php
require_once __DIR__ . '/../includes/security_init.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Login/Login/login.php');
    exit;
}

require_once '../Login/Login/db.php';
require_once '../includes/gamification.php';

$gamification = new Gamification($conn);
$user_id = $_SESSION['user_id'];

// Initialize user if needed
$gamification->initializeUser($user_id);

// Get user stats
$stats = $gamification->getUserStats($user_id);
$earned_badges = $gamification->getUserBadges($user_id);
$available_badges = $gamification->getAvailableBadges($user_id);
$rank = $gamification->getUserRank($user_id);
$recent_activities = $gamification->getRecentActivities($user_id, 15);

// Calculate progress to next level
$current_points = $stats['total_points'] ?? 0;
$current_level = $stats['level'] ?? 1;
$points_for_current_level = ($current_level - 1) * 100;
$points_for_next_level = $current_level * 100;
$progress_in_level = $current_points - $points_for_current_level;
$progress_percentage = ($progress_in_level / 100) * 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Anti-injection script - MUST be first -->
    <script src="../assets/js/anti_injection.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Achievements & Rewards - IdeaNest</title>
    <link rel="icon" type="image/png" href="../assets/image/fevicon.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --accent-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #06b6d4;
            --success-color: #10b981;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --border-color: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #f1f5f9;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --gradient-primary: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            --gradient-accent: linear-gradient(135deg, var(--accent-color), #34d399);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-secondary);
            color: var(--text-primary);
            line-height: 1.6;
        }
        
        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .main-content { 
                margin-left: 0;
                padding: 1rem;
            }
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .page-header {
            background: var(--gradient-primary);
            color: white;
            padding: 3rem;
            border-radius: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .page-header p {
            font-size: 1.1rem;
            opacity: 0.95;
            position: relative;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: var(--bg-primary);
            border-radius: 1.5rem;
            padding: 2rem;
            text-align: center;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
        
        .stat-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 900;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.95rem;
            color: #64748b;
            font-weight: 500;
        }
        
        .level-card {
            background: var(--bg-primary);
            border-radius: 1.5rem;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }
        
        .level-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .level-info h2 {
            font-size: 1.75rem;
            color: #1e293b;
            margin-bottom: 0.75rem;
        }
        
        .level-info p {
            color: #64748b;
            font-size: 1rem;
        }
        
        .rank-badge {
            background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: 700;
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);
        }
        
        .progress-bar-container {
            background: #f1f5f9;
            height: 30px;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
            margin-bottom: 15px;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 1s ease;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 15px;
            color: white;
            font-weight: 700;
            font-size: 1rem;
        }
        
        .progress-text {
            text-align: center;
            color: #64748b;
            font-size: 1rem;
        }
        
        .section-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .section-title i {
            color: var(--primary-color);
            font-size: 1.75rem;
        }
        
        .badges-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 50px;
        }
        
        .badge-card {
            background: var(--bg-primary);
            border-radius: 1rem;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }
        
        .badge-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
        
        .badge-card.earned {
            border: 3px solid #10b981;
        }
        
        .badge-card.locked {
            opacity: 0.6;
            filter: grayscale(0.5);
        }
        
        .badge-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            position: relative;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        
        .badge-card.locked .badge-icon {
            background: #94a3b8 !important;
        }
        
        .badge-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .badge-description {
            font-size: 1rem;
            color: #64748b;
            margin-bottom: 0.75rem;
            line-height: 1.5;
        }
        
        .badge-progress {
            background: #f1f5f9;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        
        .badge-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
            transition: width 0.5s;
        }
        
        .badge-progress-text {
            font-size: 0.95rem;
            color: #64748b;
            font-weight: 600;
        }
        
        .badge-rarity {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .rarity-common { background: #10b981; color: white; }
        .rarity-rare { background: #3b82f6; color: white; }
        .rarity-epic { background: #8b5cf6; color: white; }
        .rarity-legendary { background: #f59e0b; color: white; }
        
        .earned-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #10b981;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }
        
        .activities-card {
            background: var(--bg-primary);
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 10px;
            transition: background 0.2s;
        }
        
        .activity-item:hover {
            background: #f8fafc;
        }
        
        .activity-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
            flex-shrink: 0;
        }
        
        .activity-icon.positive {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .activity-details {
            flex: 1;
        }
        
        .activity-description {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.25rem;
            font-size: 1.1rem;
        }
        
        .activity-time {
            font-size: 0.95rem;
            color: #64748b;
        }
        
        .activity-points {
            font-size: 1.4rem;
            font-weight: 800;
            color: #10b981;
        }
        

        
        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            background: var(--bg-primary);
            padding: 0.5rem;
            border-radius: 1rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }
        
        .tab {
            flex: 1;
            padding: 0.75rem 1.5rem;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            font-weight: 600;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .tab.active {
            background: var(--gradient-primary);
            color: white;
            box-shadow: var(--shadow-sm);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
    <link rel="stylesheet" href="../assets/css/loader.css">
</head>
<body>
    <?php include 'layout.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1>🏆 Achievements & Rewards</h1>
                <p>Track your progress, earn badges, and climb the leaderboard!</p>
                <div style="margin-top: 20px;">
                    <a href="achievements_guide.php" style="display: inline-flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.2); color: white; padding: 12px 24px; border-radius: 12px; text-decoration: none; font-weight: 600; transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                        <i class="fas fa-book-open"></i>
                        How to Earn Achievements
                    </a>
                </div>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-star"></i></div>
                    <div class="stat-value"><?php echo number_format($stats['total_points'] ?? 0); ?></div>
                    <div class="stat-label">Total Points</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
                    <div class="stat-value"><?php echo $stats['level'] ?? 1; ?></div>
                    <div class="stat-label">Level</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-trophy"></i></div>
                    <div class="stat-value"><?php echo $stats['badges_earned'] ?? 0; ?>/<?php echo $stats['total_badges'] ?? 0; ?></div>
                    <div class="stat-label">Badges Earned</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-fire"></i></div>
                    <div class="stat-value"><?php echo $stats['current_streak'] ?? 0; ?></div>
                    <div class="stat-label">Day Streak</div>
                </div>
            </div>
            
            <!-- Level Progress -->
            <div class="level-card">
                <div class="level-header">
                    <div class="level-info">
                        <h2>Level <?php echo $current_level; ?></h2>
                        <p><?php echo $progress_in_level; ?> / 100 points to next level</p>
                    </div>
                    <div class="rank-badge">
                        <i class="fas fa-medal"></i> Rank #<?php echo $rank; ?>
                    </div>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: <?php echo min($progress_percentage, 100); ?>%">
                        <?php echo round($progress_percentage); ?>%
                    </div>
                </div>
                <div class="progress-text">
                    <?php echo 100 - $progress_in_level; ?> points needed for Level <?php echo $current_level + 1; ?>
                </div>
            </div>
            
            <!-- Tabs -->
            <div class="tabs">
                <button class="tab active" onclick="switchTab('earned')">
                    <i class="fas fa-award"></i> Earned (<?php echo count($earned_badges); ?>)
                </button>
                <button class="tab" onclick="switchTab('available')">
                    <i class="fas fa-lock"></i> Available (<?php echo count($available_badges); ?>)
                </button>
                <button class="tab" onclick="switchTab('activity')">
                    <i class="fas fa-history"></i> Recent Activity
                </button>
            </div>
            
            <!-- Earned Badges Tab -->
            <div id="earned-tab" class="tab-content active">
                <h2 class="section-title"><i class="fas fa-award"></i> Earned Badges</h2>
                <div class="badges-grid">
                    <?php if (empty($earned_badges)): ?>
                        <div style="grid-column: 1/-1; text-align: center; padding: 60px; color: white;">
                            <i class="fas fa-trophy" style="font-size: 4rem; opacity: 0.5; margin-bottom: 20px;"></i>
                            <h3>No badges earned yet</h3>
                            <p>Start completing activities to earn your first badge!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($earned_badges as $badge): ?>
                            <div class="badge-card earned">
                                <div class="earned-badge"><i class="fas fa-check"></i></div>
                                <div class="badge-rarity rarity-<?php echo $badge['rarity']; ?>">
                                    <?php echo strtoupper($badge['rarity']); ?>
                                </div>
                                <div class="badge-icon" style="background: <?php echo $badge['color']; ?>">
                                    <i class="fas <?php echo $badge['icon']; ?>"></i>
                                </div>
                                <div class="badge-name"><?php echo htmlspecialchars($badge['name']); ?></div>
                                <div class="badge-description"><?php echo htmlspecialchars($badge['description']); ?></div>
                                <div class="badge-progress-text" style="color: #10b981;">
                                    <i class="fas fa-check-circle"></i> Earned <?php echo date('M j, Y', strtotime($badge['earned_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Available Badges Tab -->
            <div id="available-tab" class="tab-content">
                <h2 class="section-title"><i class="fas fa-lock"></i> Available Badges</h2>
                <div class="badges-grid">
                    <?php foreach ($available_badges as $badge): 
                        $progress = min(($badge['current_progress'] / $badge['condition_value']) * 100, 100);
                    ?>
                        <div class="badge-card locked">
                            <div class="badge-rarity rarity-<?php echo $badge['rarity']; ?>">
                                <?php echo strtoupper($badge['rarity']); ?>
                            </div>
                            <div class="badge-icon" style="background: <?php echo $badge['color']; ?>">
                                <i class="fas <?php echo $badge['icon']; ?>"></i>
                            </div>
                            <div class="badge-name"><?php echo htmlspecialchars($badge['name']); ?></div>
                            <div class="badge-description"><?php echo htmlspecialchars($badge['description']); ?></div>
                            <?php if ($badge['condition_type'] !== 'special'): ?>
                                <div class="badge-progress">
                                    <div class="badge-progress-bar" style="width: <?php echo $progress; ?>%"></div>
                                </div>
                                <div class="badge-progress-text">
                                    <?php echo $badge['current_progress']; ?> / <?php echo $badge['condition_value']; ?>
                                </div>
                            <?php else: ?>
                                <div class="badge-progress-text" style="color: #f59e0b;">
                                    <i class="fas fa-star"></i> Special Award
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Activity Tab -->
            <div id="activity-tab" class="tab-content">
                <h2 class="section-title"><i class="fas fa-history"></i> Recent Activity</h2>
                <div class="activities-card">
                    <?php if (empty($recent_activities)): ?>
                        <div style="text-align: center; padding: 40px; color: #64748b;">
                            <i class="fas fa-inbox" style="font-size: 3rem; opacity: 0.3; margin-bottom: 15px;"></i>
                            <p>No recent activity</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon positive">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <div class="activity-details">
                                    <div class="activity-description">
                                        <?php echo htmlspecialchars($activity['description'] ?: ucwords(str_replace('_', ' ', $activity['action_type']))); ?>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="activity-points">
                                    +<?php echo $activity['points']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(tab) {
            // Update tab buttons
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            event.target.closest('.tab').classList.add('active');
            
            // Update tab content
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.getElementById(tab + '-tab').classList.add('active');
        }
    </script>
<script src="../assets/js/loader.js"></script>
</body>
</html>
