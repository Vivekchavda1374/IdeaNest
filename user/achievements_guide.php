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

// Get user stats
$stats = $gamification->getUserStats($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>How to Earn Achievements - IdeaNest</title>
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
        
        .guide-card {
            background: var(--bg-primary);
            border-radius: 1.5rem;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }
        
        .guide-card h2 {
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.75rem;
            font-weight: 700;
        }
        
        .guide-card h2 i {
            color: var(--primary-color);
            font-size: 1.75rem;
        }
        
        .points-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .points-table th {
            background: var(--gradient-primary);
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 1.15rem;
        }
        
        .points-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            font-size: 1rem;
        }
        
        .points-table tr:hover {
            background: var(--bg-tertiary);
        }
        
        .points-value {
            font-weight: 700;
            color: #10b981;
            font-size: 1.3rem;
        }
        
        .badge-category {
            margin-bottom: 40px;
        }
        
        .badge-category h3 {
            color: #1e293b;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .badges-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .badge-item {
            background: var(--bg-tertiary);
            border-radius: 1rem;
            padding: 1.5rem;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }
        
        .badge-item:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }
        
        .badge-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .badge-icon {
            width: 50px;
            height: 50px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .badge-info {
            flex: 1;
        }
        
        .badge-name {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.25rem;
            font-size: 1.15rem;
        }
        
        .badge-rarity {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: 0.25rem 0.65rem;
            border-radius: 0.5rem;
            display: inline-block;
        }
        
        .rarity-common { background: #10b981; color: white; }
        .rarity-rare { background: #3b82f6; color: white; }
        .rarity-epic { background: #8b5cf6; color: white; }
        .rarity-legendary { background: #f59e0b; color: white; }
        
        .badge-description {
            color: #64748b;
            font-size: 1rem;
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        
        .badge-requirement {
            background: white;
            padding: 0.75rem;
            border-radius: 0.75rem;
            font-size: 1rem;
            color: #1e293b;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .badge-requirement i {
            color: #667eea;
        }
        
        .tip-box {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.1));
            border-left: 4px solid var(--accent-color);
            padding: 1.5rem;
            border-radius: 1rem;
            margin-top: 2rem;
        }
        
        .tip-box h4 {
            color: #065f46;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.2rem;
        }
        
        .tip-box p {
            color: #047857;
            line-height: 1.6;
            font-size: 1.05rem;
        }
        
        .level-info {
            background: var(--bg-tertiary);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }
        
        .level-info h3 {
            color: #1e293b;
            margin-bottom: 1rem;
            font-size: 1.4rem;
        }
        
        .level-info p {
            color: #64748b;
            line-height: 1.7;
            margin-bottom: 0.75rem;
            font-size: 1.05rem;
        }
        
        .level-formula {
            background: white;
            padding: 1rem;
            border-radius: 0.75rem;
            font-family: 'Courier New', monospace;
            color: #667eea;
            font-weight: 700;
            margin-top: 1rem;
            font-size: 1.1rem;
        }
        

        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--bg-primary);
            border: 2px solid var(--border-color);
            border-radius: 0.75rem;
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 500;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }
        
        .back-link:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
    </style>
    <link rel="stylesheet" href="../assets/css/loader.css">
</head>
<body>
    <?php include 'layout.php'; ?>
    
    <div class="main-content">
        <div class="container">
            
            
            <div class="page-header">
                <h1>🎓 How to Earn Achievements</h1>
                <p>Complete guide to earning points, badges, and climbing the leaderboard</p>
            </div>
            
            <!-- Points System -->
            <div class="guide-card">
                <h2><i class="fas fa-star"></i> Points System</h2>
                <p style="color: #64748b; margin-bottom: 1.5rem; font-size: 1.05rem;">Earn points by completing various activities on IdeaNest. Points help you level up and unlock badges!</p>
                
                <table class="points-table">
                    <thead>
                        <tr>
                            <th>Activity</th>
                            <th>Points</th>
                            <th>How to Do It</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><i class="fas fa-sign-in-alt" style="color: #667eea; margin-right: 8px;"></i> Daily Login</td>
                            <td><span class="points-value">+5</span></td>
                            <td>Login to IdeaNest once per day to maintain your streak</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-folder-plus" style="color: #667eea; margin-right: 8px;"></i> Submit Project</td>
                            <td><span class="points-value">+20</span></td>
                            <td>Go to "New Project" and submit your project</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-check-circle" style="color: #667eea; margin-right: 8px;"></i> Project Approved</td>
                            <td><span class="points-value">+50</span></td>
                            <td>Wait for admin to approve your submitted project</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-lightbulb" style="color: #667eea; margin-right: 8px;"></i> Post Idea</td>
                            <td><span class="points-value">+10</span></td>
                            <td>Share a new idea in the "Ideas" section</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-heart" style="color: #667eea; margin-right: 8px;"></i> Receive Like</td>
                            <td><span class="points-value">+2</span></td>
                            <td>Get likes on your ideas or projects</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-comment" style="color: #667eea; margin-right: 8px;"></i> Make Comment</td>
                            <td><span class="points-value">+3</span></td>
                            <td>Comment on other users' ideas or projects</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-chalkboard-teacher" style="color: #667eea; margin-right: 8px;"></i> Mentor Session</td>
                            <td><span class="points-value">+25</span></td>
                            <td>Complete a mentoring session with your mentor</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-trophy" style="color: #667eea; margin-right: 8px;"></i> Earn Badge</td>
                            <td><span class="points-value">Varies</span></td>
                            <td>Automatically awarded when you unlock a badge</td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="tip-box">
                    <h4><i class="fas fa-lightbulb"></i> Pro Tip</h4>
                    <p>Focus on submitting quality projects and engaging with the community. The more active you are, the faster you'll earn points and unlock badges!</p>
                </div>
            </div>
            
            <!-- Level System -->
            <div class="guide-card">
                <h2><i class="fas fa-layer-group"></i> Level System</h2>
                
                <div class="level-info">
                    <h3>How Leveling Works</h3>
                    <p>Your level increases automatically as you earn points. Each level requires 100 points.</p>
                    <p><strong>Current Level:</strong> <?php echo $stats['level'] ?? 1; ?></p>
                    <p><strong>Current Points:</strong> <?php echo number_format($stats['total_points'] ?? 0); ?></p>
                    <p><strong>Points to Next Level:</strong> <?php 
                        $current_level = $stats['level'] ?? 1;
                        $current_points = $stats['total_points'] ?? 0;
                        $points_for_next = ($current_level * 100) - $current_points;
                        echo $points_for_next > 0 ? $points_for_next : 0;
                    ?></p>
                    
                    <div class="level-formula">
                        Level = (Total Points ÷ 100) + 1
                    </div>
                </div>
                
                <h3 style="color: #1e293b; margin-bottom: 1.5rem; font-size: 1.4rem;">Level Examples</h3>
                <table class="points-table">
                    <thead>
                        <tr>
                            <th>Level</th>
                            <th>Points Required</th>
                            <th>Example Achievement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Level 1</strong></td>
                            <td>0 - 99 points</td>
                            <td>Beginner - Just getting started!</td>
                        </tr>
                        <tr>
                            <td><strong>Level 2</strong></td>
                            <td>100 - 199 points</td>
                            <td>Active Member - Submitted a few projects</td>
                        </tr>
                        <tr>
                            <td><strong>Level 5</strong></td>
                            <td>400 - 499 points</td>
                            <td>Regular Contributor - Multiple projects & ideas</td>
                        </tr>
                        <tr>
                            <td><strong>Level 10</strong></td>
                            <td>900 - 999 points</td>
                            <td>Power User - Very active in community</td>
                        </tr>
                        <tr>
                            <td><strong>Level 20</strong></td>
                            <td>1900 - 1999 points</td>
                            <td>Expert - Top contributor</td>
                        </tr>
                        <tr>
                            <td><strong>Level 50+</strong></td>
                            <td>4900+ points</td>
                            <td>Legend - Elite member of IdeaNest!</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Badges Guide -->
            <div class="guide-card">
                <h2><i class="fas fa-award"></i> Achievement Badges</h2>
                <p style="color: #64748b; margin-bottom: 1.5rem; font-size: 1.05rem;">Unlock badges by completing specific milestones. Each badge awards bonus points!</p>
                
                <!-- Project Badges -->
                <div class="badge-category">
                    <h3><i class="fas fa-folder-open"></i> Project Badges</h3>
                    <div class="badges-list">
                        <div class="badge-item">
                            <div class="badge-header">
                                <div class="badge-icon" style="background: #10b981;">
                                    <i class="fas fa-rocket"></i>
                                </div>
                                <div class="badge-info">
                                    <div class="badge-name">First Steps</div>
                                    <span class="badge-rarity rarity-common">Common</span>
                                </div>
                            </div>
                            <div class="badge-description">Submit your first project</div>
                            <div class="badge-requirement">
                                <i class="fas fa-check-circle"></i>
                                Submit 1 project
                            </div>
                        </div>
                        
                        <div class="badge-item">
                            <div class="badge-header">
                                <div class="badge-icon" style="background: #3b82f6;">
                                    <i class="fas fa-folder-open"></i>
                                </div>
                                <div class="badge-info">
                                    <div class="badge-name">Project Enthusiast</div>
                                    <span class="badge-rarity rarity-common">Common</span>
                                </div>
                            </div>
                            <div class="badge-description">Submit 5 projects</div>
                            <div class="badge-requirement">
                                <i class="fas fa-check-circle"></i>
                                Submit 5 projects (+50 pts)
                            </div>
                        </div>
                        
                        <div class="badge-item">
                            <div class="badge-header">
                                <div class="badge-icon" style="background: #8b5cf6;">
                                    <i class="fas fa-trophy"></i>
                                </div>
                                <div class="badge-info">
                                    <div class="badge-name">Project Master</div>
                                    <span class="badge-rarity rarity-rare">Rare</span>
                                </div>
                            </div>
                            <div class="badge-description">Submit 10 projects</div>
                            <div class="badge-requirement">
                                <i class="fas fa-check-circle"></i>
                                Submit 10 projects (+150 pts)
                            </div>
                        </div>
                        
                        <div class="badge-item">
                            <div class="badge-header">
                                <div class="badge-icon" style="background: #f59e0b;">
                                    <i class="fas fa-crown"></i>
                                </div>
                                <div class="badge-info">
                                    <div class="badge-name">Project Legend</div>
                                    <span class="badge-rarity rarity-epic">Epic</span>
                                </div>
                            </div>
                            <div class="badge-description">Submit 25 projects</div>
                            <div class="badge-requirement">
                                <i class="fas fa-check-circle"></i>
                                Submit 25 projects (+500 pts)
                            </div>
                        </div>
                        
                        <div class="badge-item">
                            <div class="badge-header">
                                <div class="badge-icon" style="background: #ef4444;">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div class="badge-info">
                                    <div class="badge-name">Project God</div>
                                    <span class="badge-rarity rarity-legendary">Legendary</span>
                                </div>
                            </div>
                            <div class="badge-description">Submit 50 projects</div>
                            <div class="badge-requirement">
                                <i class="fas fa-check-circle"></i>
                                Submit 50 projects (+1000 pts)
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Social Badges -->
                <div class="badge-category">
                    <h3><i class="fas fa-users"></i> Social Badges</h3>
                    <div class="badges-list">
                        <div class="badge-item">
                            <div class="badge-header">
                                <div class="badge-icon" style="background: #ec4899;">
                                    <i class="fas fa-heart"></i>
                                </div>
                                <div class="badge-info">
                                    <div class="badge-name">Popular</div>
                                    <span class="badge-rarity rarity-common">Common</span>
                                </div>
                            </div>
                            <div class="badge-description">Receive 10 likes on your content</div>
                            <div class="badge-requirement">
                                <i class="fas fa-check-circle"></i>
                                Get 10 likes (+20 pts)
                            </div>
                        </div>
                        
                        <div class="badge-item">
                            <div class="badge-header">
                                <div class="badge-icon" style="background: #06b6d4;">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <div class="badge-info">
                                    <div class="badge-name">Conversationalist</div>
                                    <span class="badge-rarity rarity-common">Common</span>
                                </div>
                            </div>
                            <div class="badge-description">Make 25 helpful comments</div>
                            <div class="badge-requirement">
                                <i class="fas fa-check-circle"></i>
                                Comment 25 times (+50 pts)
                            </div>
                        </div>
                        
                        <div class="badge-item">
                            <div class="badge-header">
                                <div class="badge-icon" style="background: #fbbf24;">
                                    <i class="fas fa-lightbulb"></i>
                                </div>
                                <div class="badge-info">
                                    <div class="badge-name">Idea Starter</div>
                                    <span class="badge-rarity rarity-common">Common</span>
                                </div>
                            </div>
                            <div class="badge-description">Share 5 innovative ideas</div>
                            <div class="badge-requirement">
                                <i class="fas fa-check-circle"></i>
                                Post 5 ideas (+30 pts)
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Streak Badges -->
                <div class="badge-category">
                    <h3><i class="fas fa-fire"></i> Streak Badges</h3>
                    <div class="badges-list">
                        <div class="badge-item">
                            <div class="badge-header">
                                <div class="badge-icon" style="background: #10b981;">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="badge-info">
                                    <div class="badge-name">Consistent</div>
                                    <span class="badge-rarity rarity-common">Common</span>
                                </div>
                            </div>
                            <div class="badge-description">Login for 7 consecutive days</div>
                            <div class="badge-requirement">
                                <i class="fas fa-check-circle"></i>
                                7 day streak (+50 pts)
                            </div>
                        </div>
                        
                        <div class="badge-item">
                            <div class="badge-header">
                                <div class="badge-icon" style="background: #3b82f6;">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="badge-info">
                                    <div class="badge-name">Dedicated</div>
                                    <span class="badge-rarity rarity-rare">Rare</span>
                                </div>
                            </div>
                            <div class="badge-description">Login for 30 consecutive days</div>
                            <div class="badge-requirement">
                                <i class="fas fa-check-circle"></i>
                                30 day streak (+200 pts)
                            </div>
                        </div>
                        
                        <div class="badge-item">
                            <div class="badge-header">
                                <div class="badge-icon" style="background: #f59e0b;">
                                    <i class="fas fa-fire-alt"></i>
                                </div>
                                <div class="badge-info">
                                    <div class="badge-name">Unstoppable</div>
                                    <span class="badge-rarity rarity-epic">Epic</span>
                                </div>
                            </div>
                            <div class="badge-description">Login for 90 consecutive days</div>
                            <div class="badge-requirement">
                                <i class="fas fa-check-circle"></i>
                                90 day streak (+600 pts)
                            </div>
                        </div>
                        
                        <div class="badge-item">
                            <div class="badge-header">
                                <div class="badge-icon" style="background: #ef4444;">
                                    <i class="fas fa-infinity"></i>
                                </div>
                                <div class="badge-info">
                                    <div class="badge-name">Legendary Streak</div>
                                    <span class="badge-rarity rarity-legendary">Legendary</span>
                                </div>
                            </div>
                            <div class="badge-description">Login for 365 consecutive days</div>
                            <div class="badge-requirement">
                                <i class="fas fa-check-circle"></i>
                                365 day streak (+2000 pts)
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="tip-box">
                    <h4><i class="fas fa-fire"></i> Streak Tips</h4>
                    <p><strong>Don't break your streak!</strong> Login every day to maintain your streak and earn bonus points. Even a quick login counts. Set a daily reminder to keep your streak alive!</p>
                </div>
            </div>
            
            <!-- Quick Start Guide -->
            <div class="guide-card">
                <h2><i class="fas fa-rocket"></i> Quick Start Guide</h2>
                
                <h3 style="color: #1e293b; margin-bottom: 1.5rem; font-size: 1.4rem;">Fastest Way to Earn Points</h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 1.75rem; border-radius: 1rem;">
                        <h4 style="margin-bottom: 1rem; font-size: 1.4rem;">
                            <i class="fas fa-1"></i> Submit Your First Project
                        </h4>
                        <p style="opacity: 0.95; line-height: 1.6; font-size: 1.1rem;">
                            Go to "New Project" and submit your first project. You'll earn 20 points immediately plus the "First Steps" badge!
                        </p>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 1.75rem; border-radius: 1rem;">
                        <h4 style="margin-bottom: 1rem; font-size: 1.4rem;">
                            <i class="fas fa-2"></i> Share Your Ideas
                        </h4>
                        <p style="opacity: 0.95; line-height: 1.6; font-size: 1.1rem;">
                            Post ideas in the "Ideas" section. Each idea earns you 10 points, and you'll get more points when others like them!
                        </p>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; padding: 1.75rem; border-radius: 1rem;">
                        <h4 style="margin-bottom: 1rem; font-size: 1.4rem;">
                            <i class="fas fa-3"></i> Login Daily
                        </h4>
                        <p style="opacity: 0.95; line-height: 1.6; font-size: 1.1rem;">
                            Login every day to build your streak. You'll earn 5 points per day plus unlock streak badges for consecutive logins!
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script src="../assets/js/loader.js"></script>
</body>
</html>
