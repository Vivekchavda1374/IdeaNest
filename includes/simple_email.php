<?php

/**
 * Simple Email Function for Production Environments
 * Fallback when PHPMailer is not available
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
 * Check if PHPMailer is available
 */
function isPHPMailerAvailable() {
    return class_exists('PHPMailer\\PHPMailer\\PHPMailer');
}

/**
 * Send email with fallback
 */
function sendEmailWithFallback($to, $subject, $message, $from_email = 'noreply@ideanest.com', $from_name = 'IdeaNest') {
    if (isPHPMailerAvailable()) {
        // Use PHPMailer if available
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ideanest.ict@gmail.com';
            $mail->Password = 'luou xlhs ojuw auvx';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            $mail->setFrom($from_email, $from_name);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            
            return $mail->send();
        } catch (Exception $e) {
            error_log("PHPMailer failed: " . $e->getMessage());
            // Fallback to simple email
            return sendSimpleEmail($to, $subject, $message, $from_email, $from_name);
        }
    } else {
        // Use simple email function
        return sendSimpleEmail($to, $subject, $message, $from_email, $from_name);
    }
}
