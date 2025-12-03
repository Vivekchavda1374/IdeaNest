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

// Get leaderboard
$leaderboard = $gamification->getLeaderboard(100);
$user_rank = $gamification->getUserRank($user_id);
$user_stats = $gamification->getUserStats($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - IdeaNest</title>
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
            max-width: 1200px;
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
        
        .podium {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 40px;
            align-items: end;
        }
        
        .podium-item {
            background: var(--bg-primary);
            border-radius: 1.5rem;
            padding: 2rem 1.5rem;
            text-align: center;
            position: relative;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }
        
        .podium-item:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
        
        .podium-item.first {
            order: 2;
            padding: 40px 20px;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: white;
            box-shadow: 0 20px 60px rgba(251, 191, 36, 0.4);
        }
        
        .podium-item.second {
            order: 1;
            background: linear-gradient(135deg, #e5e7eb 0%, #9ca3af 100%);
            color: white;
            box-shadow: 0 15px 40px rgba(156, 163, 175, 0.3);
        }
        
        .podium-item.third {
            order: 3;
            background: linear-gradient(135deg, #fcd34d 0%, #d97706 100%);
            color: white;
            box-shadow: 0 15px 40px rgba(252, 211, 77, 0.3);
        }
        
        .podium-rank {
            width: 60px;
            height: 60px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 900;
            background: rgba(255,255,255,0.3);
            backdrop-filter: blur(10px);
        }
        
        .podium-item.first .podium-rank {
            width: 75px;
            height: 75px;
            font-size: 2.5rem;
        }
        
        .podium-avatar {
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            border: 4px solid rgba(255,255,255,0.5);
        }
        
        .podium-item.first .podium-avatar {
            width: 90px;
            height: 90px;
            font-size: 2.25rem;
        }
        
        .podium-name {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        
        .podium-points {
            font-size: 1.5rem;
            font-weight: 900;
            opacity: 0.9;
        }
        
        .podium-item.first .podium-points {
            font-size: 1.75rem;
        }
        
        .podium-stats {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
            font-size: 0.95rem;
            opacity: 0.9;
        }
        
        .your-rank-card {
            background: var(--bg-primary);
            border-radius: 1.5rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }
        
        .your-rank-content {
            display: flex;
            align-items: center;
            gap: 30px;
        }
        
        .your-rank-badge {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            font-weight: 900;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .your-rank-badge small {
            font-size: 0.75rem;
            font-weight: 600;
            opacity: 0.9;
        }
        
        .your-rank-info {
            flex: 1;
        }
        
        .your-rank-info h3 {
            font-size: 1.75rem;
            color: #1e293b;
            margin-bottom: 0.75rem;
        }
        
        .your-rank-stats {
            display: flex;
            gap: 30px;
            margin-top: 15px;
        }
        
        .your-rank-stat {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #64748b;
        }
        
        .your-rank-stat i {
            font-size: 1.3rem;
            color: #667eea;
        }
        
        .leaderboard-table {
            background: var(--bg-primary);
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }
        
        .table-header {
            background: var(--gradient-primary);
            color: white;
            padding: 1.5rem 2rem;
            font-size: 1.4rem;
            font-weight: 700;
        }
        
        .table-content {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .table-content::-webkit-scrollbar {
            width: 8px;
        }
        
        .table-content::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        .table-content::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        
        .leaderboard-row {
            display: grid;
            grid-template-columns: 80px 1fr 150px 150px 150px;
            align-items: center;
            padding: 20px 30px;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s;
        }
        
        .leaderboard-row:hover {
            background: #f8fafc;
        }
        
        .leaderboard-row.current-user {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.1) 0%, transparent 100%);
            border-left: 4px solid #667eea;
        }
        
        .rank-number {
            font-size: 1.5rem;
            font-weight: 900;
            color: #64748b;
        }
        
        .rank-number.top3 {
            color: #f59e0b;
            font-size: 1.75rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .user-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 1.15rem;
        }
        
        .stat-value {
            font-weight: 700;
            color: #1e293b;
            font-size: 1.2rem;
        }
        
        .stat-label {
            font-size: 0.95rem;
            color: #64748b;
            margin-top: 0.25rem;
        }
        

        
        @media (max-width: 768px) {
            .podium {
                grid-template-columns: 1fr;
            }
            
            .podium-item.first,
            .podium-item.second,
            .podium-item.third {
                order: initial;
            }
            
            .leaderboard-row {
                grid-template-columns: 60px 1fr;
                gap: 15px;
            }
            
            .leaderboard-row > div:not(:first-child):not(:nth-child(2)) {
                display: none;
            }
        }
    </style>
    <link rel="stylesheet" href="../assets/css/loader.css">
</head>
<body>
    <?php include 'layout.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1>🏆 Leaderboard</h1>
                <p>See how you rank against other IdeaNest members</p>
            </div>
            
            <!-- Top 3 Podium -->
            <?php if (count($leaderboard) >= 3): ?>
            <div class="podium">
                <?php for ($i = 0; $i < 3; $i++): 
                    $user = $leaderboard[$i];
                    $class = $i === 0 ? 'first' : ($i === 1 ? 'second' : 'third');
                    $medal = $i === 0 ? '🥇' : ($i === 1 ? '🥈' : '🥉');
                ?>
                    <div class="podium-item <?php echo $class; ?>">
                        <div class="podium-rank"><?php echo $medal; ?></div>
                        <div class="podium-avatar">
                            <?php echo strtoupper(substr($user['user_name'], 0, 1)); ?>
                        </div>
                        <div class="podium-name"><?php echo htmlspecialchars($user['user_name']); ?></div>
                        <div class="podium-points"><?php echo number_format($user['total_points']); ?> pts</div>
                        <div class="podium-stats">
                            <span><i class="fas fa-layer-group"></i> Lvl <?php echo $user['level']; ?></span>
                            <span><i class="fas fa-trophy"></i> <?php echo $user['badges_count']; ?></span>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
            
            <!-- Your Rank -->
            <div class="your-rank-card">
                <div class="your-rank-content">
                    <div class="your-rank-badge">
                        #<?php echo $user_rank; ?>
                        <small>YOUR RANK</small>
                    </div>
                    <div class="your-rank-info">
                        <h3><?php echo htmlspecialchars($_SESSION['user_name']); ?></h3>
                        <div class="your-rank-stats">
                            <div class="your-rank-stat">
                                <i class="fas fa-star"></i>
                                <div>
                                    <div style="font-weight: 700; color: #1e293b; font-size: 1.3rem;">
                                        <?php echo number_format($user_stats['total_points'] ?? 0); ?>
                                    </div>
                                    <div style="font-size: 0.95rem;">Points</div>
                                </div>
                            </div>
                            <div class="your-rank-stat">
                                <i class="fas fa-layer-group"></i>
                                <div>
                                    <div style="font-weight: 700; color: #1e293b; font-size: 1.3rem;">
                                        Level <?php echo $user_stats['level'] ?? 1; ?>
                                    </div>
                                    <div style="font-size: 0.95rem;">Current Level</div>
                                </div>
                            </div>
                            <div class="your-rank-stat">
                                <i class="fas fa-trophy"></i>
                                <div>
                                    <div style="font-weight: 700; color: #1e293b; font-size: 1.3rem;">
                                        <?php echo $user_stats['badges_earned'] ?? 0; ?>
                                    </div>
                                    <div style="font-size: 0.95rem;">Badges</div>
                                </div>
                            </div>
                            <div class="your-rank-stat">
                                <i class="fas fa-fire"></i>
                                <div>
                                    <div style="font-weight: 700; color: #1e293b; font-size: 1.3rem;">
                                        <?php echo $user_stats['current_streak'] ?? 0; ?>
                                    </div>
                                    <div style="font-size: 0.95rem;">Day Streak</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Full Leaderboard -->
            <div class="leaderboard-table">
                <div class="table-header">
                    <i class="fas fa-list-ol"></i> Full Rankings
                </div>
                <div class="table-content">
                    <?php foreach ($leaderboard as $index => $user): 
                        $rank = $index + 1;
                        $is_current = $user['id'] == $user_id;
                    ?>
                        <div class="leaderboard-row <?php echo $is_current ? 'current-user' : ''; ?>">
                            <div class="rank-number <?php echo $rank <= 3 ? 'top3' : ''; ?>">
                                <?php 
                                if ($rank === 1) echo '🥇';
                                elseif ($rank === 2) echo '🥈';
                                elseif ($rank === 3) echo '🥉';
                                else echo '#' . $rank;
                                ?>
                            </div>
                            <div class="user-info">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($user['user_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="user-name">
                                        <?php echo htmlspecialchars($user['user_name']); ?>
                                        <?php if ($is_current): ?>
                                            <span style="color: #667eea; font-size: 0.9rem;">(You)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="stat-value"><?php echo number_format($user['total_points']); ?></div>
                                <div class="stat-label">Points</div>
                            </div>
                            <div>
                                <div class="stat-value">Level <?php echo $user['level']; ?></div>
                                <div class="stat-label">Current Level</div>
                            </div>
                            <div>
                                <div class="stat-value"><?php echo $user['badges_count']; ?> Badges</div>
                                <div class="stat-label"><?php echo $user['projects_count']; ?> Projects</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
<script src="../assets/js/loader.js"></script>
</body>
</html>
