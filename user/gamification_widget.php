<?php
/**
 * Gamification Widget - Display user badges and stats
 * Include this in any profile page
 * 
 * Required: $viewed_user_id variable must be set
 */

if (!isset($viewed_user_id)) {
    return;
}

require_once __DIR__ . '/../includes/gamification.php';
$gamification = new Gamification($conn);

// Initialize user if needed
$gamification->initializeUser($viewed_user_id);

// Get stats
$stats = $gamification->getUserStats($viewed_user_id);
$earned_badges = $gamification->getUserBadges($viewed_user_id);
$rank = $gamification->getUserRank($viewed_user_id);

// Get top 3 badges to display
$top_badges = array_slice($earned_badges, 0, 3);
?>

<style>
.gamification-widget {
    background: white;
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    margin-bottom: 25px;
}

.widget-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.widget-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 10px;
}

.widget-title i {
    color: #f59e0b;
}

.view-all-link {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: color 0.2s;
}

.view-all-link:hover {
    color: #764ba2;
}

.stats-mini-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.stat-mini {
    text-align: center;
    padding: 15px 10px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 12px;
    transition: transform 0.2s;
}

.stat-mini:hover {
    transform: translateY(-3px);
}

.stat-mini-value {
    font-size: 1.8rem;
    font-weight: 900;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 5px;
}

.stat-mini-label {
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badges-showcase {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

.badge-mini {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 12px;
    padding: 15px;
    text-align: center;
    transition: all 0.3s;
    cursor: pointer;
    position: relative;
}

.badge-mini:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.badge-mini-icon {
    width: 50px;
    height: 50px;
    margin: 0 auto 10px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.badge-mini-name {
    font-size: 0.85rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 3px;
}

.badge-mini-rarity {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    padding: 2px 8px;
    border-radius: 8px;
    display: inline-block;
}

.rarity-common { background: #10b981; color: white; }
.rarity-rare { background: #3b82f6; color: white; }
.rarity-epic { background: #8b5cf6; color: white; }
.rarity-legendary { background: #f59e0b; color: white; }

.no-badges {
    text-align: center;
    padding: 30px;
    color: #94a3b8;
}

.no-badges i {
    font-size: 2.5rem;
    margin-bottom: 10px;
    opacity: 0.5;
}

.rank-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}

@media (max-width: 768px) {
    .stats-mini-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .badges-showcase {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="gamification-widget">
    <div class="widget-header">
        <h3 class="widget-title">
            <i class="fas fa-trophy"></i>
            Achievements
        </h3>
        <a href="gamification.php" class="view-all-link">
            View All <i class="fas fa-arrow-right"></i>
        </a>
    </div>
    
    <!-- Mini Stats -->
    <div class="stats-mini-grid">
        <div class="stat-mini">
            <div class="stat-mini-value"><?php echo number_format($stats['total_points'] ?? 0); ?></div>
            <div class="stat-mini-label">Points</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-value"><?php echo $stats['level'] ?? 1; ?></div>
            <div class="stat-mini-label">Level</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-value">#<?php echo $rank; ?></div>
            <div class="stat-mini-label">Rank</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-value"><?php echo $stats['current_streak'] ?? 0; ?></div>
            <div class="stat-mini-label">Streak</div>
        </div>
    </div>
    
    <!-- Top Badges -->
    <?php if (!empty($top_badges)): ?>
        <div class="badges-showcase">
            <?php foreach ($top_badges as $badge): ?>
                <div class="badge-mini" title="<?php echo htmlspecialchars($badge['description']); ?>">
                    <div class="badge-mini-icon" style="background: <?php echo $badge['color']; ?>">
                        <i class="fas <?php echo $badge['icon']; ?>"></i>
                    </div>
                    <div class="badge-mini-name"><?php echo htmlspecialchars($badge['name']); ?></div>
                    <span class="badge-mini-rarity rarity-<?php echo $badge['rarity']; ?>">
                        <?php echo strtoupper($badge['rarity']); ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (count($earned_badges) > 3): ?>
            <div style="text-align: center; margin-top: 15px;">
                <a href="gamification.php" style="color: #667eea; text-decoration: none; font-size: 0.9rem; font-weight: 600;">
                    +<?php echo count($earned_badges) - 3; ?> more badges
                </a>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="no-badges">
            <i class="fas fa-award"></i>
            <p>No badges earned yet</p>
            <small>Complete activities to earn your first badge!</small>
        </div>
    <?php endif; ?>
</div>
