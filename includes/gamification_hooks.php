<?php
/**
 * Gamification Hooks - Automatic Points Tracking
 * Include this file in actions that should award points
 */

require_once __DIR__ . '/gamification.php';

/**
 * Award points when project is submitted
 */
function gamification_project_submitted($user_id, $project_id) {
    $gamification = getGamification();
    $gamification->addPoints($user_id, 20, 'project_submit', $project_id, 'Submitted a new project');
}

/**
 * Award points when project is approved
 */
function gamification_project_approved($user_id, $project_id) {
    $gamification = getGamification();
    $gamification->addPoints($user_id, 50, 'project_approved', $project_id, 'Project approved by admin');
}

/**
 * Award points when idea is posted
 */
function gamification_idea_posted($user_id, $idea_id) {
    $gamification = getGamification();
    $gamification->addPoints($user_id, 10, 'idea_posted', $idea_id, 'Shared a new idea');
}

/**
 * Award points when receiving a like
 */
function gamification_like_received($user_id, $content_id, $content_type = 'idea') {
    $gamification = getGamification();
    $gamification->addPoints($user_id, 2, 'like_received', $content_id, "Received a like on {$content_type}");
}

/**
 * Award points when making a comment
 */
function gamification_comment_made($user_id, $comment_id) {
    $gamification = getGamification();
    $gamification->addPoints($user_id, 3, 'comment_made', $comment_id, 'Made a helpful comment');
}

/**
 * Award points when mentor session is completed
 */
function gamification_mentor_session_completed($user_id, $session_id) {
    $gamification = getGamification();
    $gamification->addPoints($user_id, 25, 'mentor_session', $session_id, 'Completed a mentor session');
}

/**
 * Update daily streak on login
 */
function gamification_daily_login($user_id) {
    $gamification = getGamification();
    return $gamification->updateStreak($user_id);
}

/**
 * Check and award badges after any action
 */
function gamification_check_badges($user_id) {
    $gamification = getGamification();
    return $gamification->checkAndAwardBadges($user_id);
}
