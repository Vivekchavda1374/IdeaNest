<?php
/**
 * Setup script for Mentor Email System
 * Run this script once to set up all necessary database tables and configurations
 */

require_once 'Login/Login/db.php';

echo "<h2>Setting up Mentor Email System...</h2>\n";

try {
    // Read and execute the SQL file
    $sql_file = 'db/mentor_email_tables.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception("SQL file not found: $sql_file");
    }
    
    $sql_content = file_get_contents($sql_file);
    $statements = explode(';', $sql_content);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            if ($conn->query($statement)) {
                echo "✓ Executed: " . substr($statement, 0, 50) . "...\n<br>";
            } else {
                echo "✗ Failed: " . substr($statement, 0, 50) . "... - " . $conn->error . "\n<br>";
            }
        }
    }
    
    // Create logs directory
    $logs_dir = 'logs';
    if (!is_dir($logs_dir)) {
        mkdir($logs_dir, 0755, true);
        echo "✓ Created logs directory\n<br>";
    }
    
    // Create mentor email log file
    $log_file = $logs_dir . '/mentor_email_cron.log';
    if (!file_exists($log_file)) {
        touch($log_file);
        chmod($log_file, 0664);
        echo "✓ Created mentor email log file\n<br>";
    }
    
    // Insert default email preferences for existing students
    $insert_preferences = "INSERT IGNORE INTO student_email_preferences (student_id)
                          SELECT id FROM register WHERE role = 'student'";
    
    if ($conn->query($insert_preferences)) {
        $affected_rows = $conn->affected_rows;
        echo "✓ Added email preferences for $affected_rows students\n<br>";
    }
    
    // Test email system
    echo "<h3>Testing Email System...</h3>\n";
    
    // Check if PHPMailer is available
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "✓ PHPMailer is available\n<br>";
    } else {
        echo "✗ PHPMailer not found. Please install via Composer: composer require phpmailer/phpmailer\n<br>";
    }
    
    // Check database tables
    $tables_to_check = [
        'mentor_email_logs',
        'student_email_preferences', 
        'mentor_email_templates',
        'mentor_email_queue',
        'mentor_email_stats'
    ];
    
    foreach ($tables_to_check as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "✓ Table '$table' exists\n<br>";
        } else {
            echo "✗ Table '$table' not found\n<br>";
        }
    }
    
    // Check required columns in existing tables
    $column_checks = [
        'mentor_student_pairs' => ['welcome_sent', 'last_progress_email'],
        'mentoring_sessions' => ['reminder_sent']
    ];
    
    foreach ($column_checks as $table => $columns) {
        foreach ($columns as $column) {
            $result = $conn->query("SHOW COLUMNS FROM $table LIKE '$column'");
            if ($result->num_rows > 0) {
                echo "✓ Column '$table.$column' exists\n<br>";
            } else {
                echo "✗ Column '$table.$column' not found\n<br>";
            }
        }
    }
    
    echo "<h3>Setup Summary</h3>\n";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
    echo "<h4>✅ Mentor Email System Setup Complete!</h4>\n";
    echo "<p><strong>What was set up:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Database tables for email logging and tracking</li>\n";
    echo "<li>Email queue system for reliable delivery</li>\n";
    echo "<li>Student email preferences</li>\n";
    echo "<li>Email statistics tracking</li>\n";
    echo "<li>Automated email templates</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    
    echo "<h4>Next Steps:</h4>\n";
    echo "<ol>\n";
    echo "<li>Set up cron jobs by running: <code>cd cron && ./setup_mentor_email_cron.sh</code></li>\n";
    echo "<li>Configure SMTP settings in the admin panel</li>\n";
    echo "<li>Test the email system from the mentor dashboard</li>\n";
    echo "<li>Monitor email logs in the dashboard</li>\n";
    echo "</ol>\n";
    
    echo "<h4>Available Features:</h4>\n";
    echo "<ul>\n";
    echo "<li><strong>Welcome Emails:</strong> Automatically sent to new student-mentor pairs</li>\n";
    echo "<li><strong>Session Invitations:</strong> Send meeting invites with calendar integration</li>\n";
    echo "<li><strong>Session Reminders:</strong> Automated 24-hour reminders</li>\n";
    echo "<li><strong>Project Feedback:</strong> Detailed feedback emails with ratings</li>\n";
    echo "<li><strong>Progress Updates:</strong> Weekly progress tracking emails</li>\n";
    echo "<li><strong>Email Dashboard:</strong> Comprehensive analytics and monitoring</li>\n";
    echo "</ul>\n";
    
    echo "<p><strong>Access the email system:</strong></p>\n";
    echo "<ul>\n";
    echo "<li><a href='mentor/send_email.php'>Send Emails</a></li>\n";
    echo "<li><a href='mentor/email_dashboard.php'>Email Dashboard</a></li>\n";
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
    echo "<h4>❌ Setup Failed</h4>\n";
    echo "<p>Error: " . $e->getMessage() . "</p>\n";
    echo "</div>\n";
}

$conn->close();
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}
code {
    background: #f4f4f4;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}
</style>