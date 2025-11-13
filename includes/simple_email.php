<?php

/**
 * Simple Email Function for Production Environments
 */

function sendSimpleEmail($to, $subject, $message, $from_email = 'noreply@ideanest.com', $from_name = 'IdeaNest') {
    // Basic email headers
    $headers = array(
        'From' => "$from_name <$from_email>",
        'Reply-To' => $from_email,
        'X-Mailer' => 'PHP/' . phpversion(),
        'Content-Type' => 'text/html; charset=UTF-8'
    );
    
    // Convert headers array to string
    $header_string = '';
    foreach ($headers as $key => $value) {
        $header_string .= "$key: $value\r\n";
    }
    
    // Send email using PHP's mail() function
    $result = mail($to, $subject, $message, $header_string);
    
    if ($result) {
        error_log("Email sent successfully to: $to");
        return true;
    } else {
        error_log("Failed to send email to: $to");
        return false;
    }
}

/**
 */
}

/**
 * Send email with fallback
 */
function sendEmailWithFallback($to, $subject, $message, $from_email = 'noreply@ideanest.com', $from_name = 'IdeaNest') {
        try {
            
            
            
            
            
            $subject = $subject;
            $body = $message;
            
            return $mail->send();
        } catch (Exception $e) {
            // Fallback to simple email
            return sendSimpleEmail($to, $subject, $message, $from_email, $from_name);
        }
    } else {
        // Use simple email function
        return sendSimpleEmail($to, $subject, $message, $from_email, $from_name);
    }
}
