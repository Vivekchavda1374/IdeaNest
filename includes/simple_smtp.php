<?php

// Redirect to new SMTP mailer
require_once __DIR__ . '/smtp_mailer.php';

// Backward compatibility functions
function sendEmail($to, $subject, $message, $from_email = 'ideanest.ict@gmail.com', $from_name = 'IdeaNest') {
    $mailer = new SMTPMailer();
    return $mailer->send($to, $subject, $message);
}