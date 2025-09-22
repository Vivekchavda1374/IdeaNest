#!/usr/bin/env php
<?php

/**
 * Mentor Email Cron Job
 * Runs automated mentor email tasks
 *
 * Schedule:
 * - Every hour: Session reminders
 * - Every day at 9 AM: Welcome emails for new pairs
 * - Every Sunday at 9 AM: Weekly progress updates
 */

require_once dirname(__DIR__) . '/Login/Login/db.php';
require_once dirname(__DIR__) . '/mentor/automated_emails.php';

// Log file for cron activities
$log_file = dirname(__DIR__) . '/logs/mentor_email_cron.log';

function logMessage($message)
{
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
    echo "[$timestamp] $message\n";
}

try {
    logMessage("Starting mentor email cron job");

    $automated_emails = new AutomatedMentorEmails($conn);

    // Always check for session reminders (runs every hour)
    logMessage("Checking for session reminders...");
    $automated_emails->sendSessionReminders();

    // Check for welcome emails (runs daily)
    $current_hour = (int)date('H');
    if ($current_hour == 9) { // 9 AM
        logMessage("Sending welcome emails to new pairs...");
        $automated_emails->sendWelcomeToNewPairs();
    }

    // Weekly progress updates (Sundays at 9 AM)
    $current_day = (int)date('w'); // 0 = Sunday
    if ($current_day == 0 && $current_hour == 9) {
        logMessage("Sending weekly progress updates...");
        $automated_emails->sendWeeklyProgressUpdates();
    }

    // Process email queue
    logMessage("Processing email queue...");
    processEmailQueue();

    // Update email statistics
    logMessage("Updating email statistics...");
    updateEmailStats();

    logMessage("Mentor email cron job completed successfully");
} catch (Exception $e) {
    logMessage("Error in mentor email cron job: " . $e->getMessage());
    exit(1);
}

function processEmailQueue()
{
    global $conn;

    // Get pending emails from queue
    $query = "SELECT * FROM mentor_email_queue 
              WHERE status = 'pending' 
              AND scheduled_at <= NOW() 
              AND attempts < max_attempts 
              ORDER BY priority ASC, scheduled_at ASC 
              LIMIT 50";

    $result = $conn->query($query);

    while ($email = $result->fetch_assoc()) {
        try {
            // Mark as processing
            $update_query = "UPDATE mentor_email_queue SET status = 'processing', attempts = attempts + 1 WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("i", $email['id']);
            $stmt->execute();

            // Process the email
            $email_system = new MentorEmailSystem($conn, $email['mentor_id']);
            $email_data = json_decode($email['email_data'], true);

            $success = false;
            switch ($email['email_type']) {
                case 'welcome_message':
                    $success = $email_system->sendWelcomeMessage($email['recipient_id']);
                    break;
                case 'session_invitation':
                    $success = $email_system->sendSessionInvitation($email['recipient_id'], $email_data);
                    break;
                case 'session_reminder':
                    $success = $email_system->sendSessionReminder($email['recipient_id'], $email_data);
                    break;
                case 'project_feedback':
                    $success = $email_system->sendProjectFeedback($email['recipient_id'], $email_data['project_id'], $email_data['feedback']);
                    break;
                case 'progress_update':
                    $success = $email_system->sendProgressUpdate($email['recipient_id'], $email_data);
                    break;
            }

            // Update queue status
            if ($success) {
                $update_query = "UPDATE mentor_email_queue SET status = 'sent', processed_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("i", $email['id']);
                $stmt->execute();
                logMessage("Processed queued email ID: " . $email['id']);
            } else {
                if ($email['attempts'] >= $email['max_attempts']) {
                    $update_query = "UPDATE mentor_email_queue SET status = 'failed', processed_at = NOW(), error_message = 'Max attempts reached' WHERE id = ?";
                } else {
                    $update_query = "UPDATE mentor_email_queue SET status = 'pending', scheduled_at = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE id = ?";
                }
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("i", $email['id']);
                $stmt->execute();
            }
        } catch (Exception $e) {
            // Mark as failed
            $update_query = "UPDATE mentor_email_queue SET status = 'failed', processed_at = NOW(), error_message = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $e->getMessage(), $email['id']);
            $stmt->execute();
            logMessage("Failed to process queued email ID: " . $email['id'] . " - " . $e->getMessage());
        }
    }
}

function updateEmailStats()
{
    global $conn;

    $today = date('Y-m-d');

    // Get today's email statistics for each mentor
    $stats_query = "SELECT 
                        mentor_id,
                        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as emails_sent,
                        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as emails_failed,
                        SUM(CASE WHEN email_type = 'welcome_message' AND status = 'sent' THEN 1 ELSE 0 END) as welcome_emails,
                        SUM(CASE WHEN email_type = 'session_invitation' AND status = 'sent' THEN 1 ELSE 0 END) as session_invitations,
                        SUM(CASE WHEN email_type = 'session_reminder' AND status = 'sent' THEN 1 ELSE 0 END) as session_reminders,
                        SUM(CASE WHEN email_type = 'project_feedback' AND status = 'sent' THEN 1 ELSE 0 END) as project_feedback,
                        SUM(CASE WHEN email_type = 'progress_update' AND status = 'sent' THEN 1 ELSE 0 END) as progress_updates
                    FROM mentor_email_logs 
                    WHERE DATE(sent_at) = ? 
                    GROUP BY mentor_id";

    $stmt = $conn->prepare($stats_query);
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($stats as $stat) {
        $insert_query = "INSERT INTO mentor_email_stats 
                        (mentor_id, date, emails_sent, emails_failed, welcome_emails, session_invitations, session_reminders, project_feedback, progress_updates)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                        emails_sent = VALUES(emails_sent),
                        emails_failed = VALUES(emails_failed),
                        welcome_emails = VALUES(welcome_emails),
                        session_invitations = VALUES(session_invitations),
                        session_reminders = VALUES(session_reminders),
                        project_feedback = VALUES(project_feedback),
                        progress_updates = VALUES(progress_updates),
                        updated_at = CURRENT_TIMESTAMP";

        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param(
            "isiiiiiiii",
            $stat['mentor_id'],
            $today,
            $stat['emails_sent'],
            $stat['emails_failed'],
            $stat['welcome_emails'],
            $stat['session_invitations'],
            $stat['session_reminders'],
            $stat['project_feedback'],
            $stat['progress_updates']
        );
        $stmt->execute();
    }

    logMessage("Updated email statistics for " . count($stats) . " mentors");
}
?>
