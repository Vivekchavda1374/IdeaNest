<?php

require_once __DIR__ . '/../includes/autoload_simple.php';
require_once __DIR__ . '/../Login/Login/db.php';

// Mentor email system without vendor dependencies
function processMentorEmails() {
    global $conn;
    
    try {
        // Get pending mentor emails
        $query = "SELECT * FROM mentor_email_queue 
                 WHERE status = 'pending' 
                 ORDER BY priority DESC, created_at ASC 
                 LIMIT 10";
        $result = mysqli_query($conn, $query);
        
        $mailer = new SMTPMailer();
        $processedCount = 0;
        
        while ($email = mysqli_fetch_assoc($result)) {
            $success = $mailer->send(
                $email['recipient_email'],
                $email['subject'],
                $email['message']
            );
            
            if ($success) {
                // Update status to sent
                $updateQuery = "UPDATE mentor_email_queue 
                               SET status = 'sent', sent_at = NOW() 
                               WHERE id = ?";
                $stmt = mysqli_prepare($conn, $updateQuery);
                mysqli_stmt_bind_param($stmt, 'i', $email['id']);
                mysqli_stmt_execute($stmt);
                
                echo "Sent email to: " . $email['recipient_email'] . "\n";
                $processedCount++;
            } else {
                // Update retry count
                $retryQuery = "UPDATE mentor_email_queue 
                              SET retry_count = retry_count + 1,
                                  status = CASE 
                                    WHEN retry_count >= 3 THEN 'failed'
                                    ELSE 'pending'
                                  END
                              WHERE id = ?";
                $stmt = mysqli_prepare($conn, $retryQuery);
                mysqli_stmt_bind_param($stmt, 'i', $email['id']);
                mysqli_stmt_execute($stmt);
                
                echo "Failed to send email to: " . $email['recipient_email'] . "\n";
            }
        }
        
        echo "Processed {$processedCount} mentor emails.\n";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// Run the function
processMentorEmails();