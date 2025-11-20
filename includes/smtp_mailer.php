<?php

class SMTPMailer {
    private $host;
    private $port;
    private $username;
    private $password;
    private $from_email;
    private $from_name;
    private $socket;
    
    public function __construct($config = null) {
        if ($config) {
            $this->host = $config['host'];
            $this->port = $config['port'];
            $this->username = $config['username'];
            $this->password = $config['password'];
            $this->from_email = $config['from_email'];
            $this->from_name = $config['from_name'];
        } else {
            $this->loadDefaultConfig();
        }
    }
    
    private function loadDefaultConfig() {
        // Load from environment variables
        $this->host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $this->port = $_ENV['SMTP_PORT'] ?? 587;
        $this->username = $_ENV['SMTP_USERNAME'] ?? '';
        $this->password = $_ENV['SMTP_PASSWORD'] ?? '';
        $this->from_email = $_ENV['FROM_EMAIL'] ?? '';
        $this->from_name = $_ENV['FROM_NAME'] ?? 'IdeaNest';
        
        // Log warning if credentials are missing
        if (empty($this->username) || empty($this->password)) {
            error_log("SMTP: Credentials not configured in .env file");
        }
    }
    
    public function send($to, $subject, $message, $isHTML = true) {
        try {
            if (!$this->connect()) {
                error_log("SMTP: Failed to connect to {$this->host}:{$this->port}");
                return false;
            }
            
            if (!$this->authenticate()) {
                error_log("SMTP: Authentication failed for {$this->username}");
                $this->disconnect();
                return false;
            }
            
            if (!$this->sendMessage($to, $subject, $message, $isHTML)) {
                error_log("SMTP: Failed to send message to {$to}");
                $this->disconnect();
                return false;
            }
            
            $this->disconnect();
            error_log("SMTP: Email sent successfully to {$to}");
            return true;
        } catch (Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            return false;
        }
    }
    
    private function connect() {
        $this->socket = fsockopen($this->host, $this->port, $errno, $errstr, 30);
        if (!$this->socket) {
            return false;
        }
        
        $response = $this->getResponse();
        if (!$this->checkResponse($response, '220')) {
            return false;
        }
        
        $this->sendCommand("EHLO localhost");
        $this->getMultilineResponse();
        
        $this->sendCommand("STARTTLS");
        $response = $this->getResponse();
        if (!$this->checkResponse($response, '220')) {
            return false;
        }
        
        if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            return false;
        }
        
        $this->sendCommand("EHLO localhost");
        $this->getMultilineResponse();
        
        return true;
    }
    
    private function authenticate() {
        $this->sendCommand("AUTH LOGIN");
        $response = $this->getResponse();
        if (!$this->checkResponse($response, '334')) {
            return false;
        }
        
        $this->sendCommand(base64_encode($this->username));
        $response = $this->getResponse();
        if (!$this->checkResponse($response, '334')) {
            return false;
        }
        
        $this->sendCommand(base64_encode($this->password));
        $response = $this->getResponse();
        return $this->checkResponse($response, '235');
    }
    
    private function sendMessage($to, $subject, $message, $isHTML) {
        $this->sendCommand("MAIL FROM: <{$this->from_email}>");
        $response = $this->getResponse();
        if (!$this->checkResponse($response, '250')) {
            error_log("SMTP Error: MAIL FROM failed - " . trim($response));
            return false;
        }
        
        $this->sendCommand("RCPT TO: <{$to}>");
        $response = $this->getResponse();
        if (!$this->checkResponse($response, '250')) {
            error_log("SMTP Error: RCPT TO failed - " . trim($response));
            return false;
        }
        
        $this->sendCommand("DATA");
        $response = $this->getResponse();
        if (!$this->checkResponse($response, '354')) {
            error_log("SMTP Error: DATA command failed - " . trim($response));
            return false;
        }
        
        $headers = $this->buildHeaders($to, $subject, $isHTML);
        $this->sendCommand($headers . $message . "\r\n.");
        
        $response = $this->getResponse();
        if (!$this->checkResponse($response, '250')) {
            error_log("SMTP Error: Message send failed - " . trim($response));
            return false;
        }
        return true;
    }
    
    private function buildHeaders($to, $subject, $isHTML) {
        $headers = "Subject: {$subject}\r\n";
        $headers .= "From: {$this->from_name} <{$this->from_email}>\r\n";
        $headers .= "To: {$to}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        
        if ($isHTML) {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        } else {
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        }
        
        $headers .= "\r\n";
        return $headers;
    }
    
    private function sendCommand($command) {
        fwrite($this->socket, $command . "\r\n");
    }
    
    private function getResponse() {
        return fgets($this->socket, 512);
    }
    
    private function getMultilineResponse() {
        while ($response = fgets($this->socket, 512)) {
            if (substr($response, 3, 1) == ' ') {
                break;
            }
        }
        return $response;
    }
    
    private function checkResponse($response, $expectedCode) {
        return substr($response, 0, 3) == $expectedCode;
    }
    
    private function disconnect() {
        if ($this->socket) {
            $this->sendCommand("QUIT");
            fclose($this->socket);
        }
    }
}

// Helper functions for backward compatibility
function sendSMTPEmail($to, $subject, $message, $conn = null) {
    $mailer = new SMTPMailer();
    return $mailer->send($to, $subject, $message);
}

function sendEmailWithConfig($to, $subject, $message, $conn = null) {
    return sendSMTPEmail($to, $subject, $message, $conn);
}

function getEmailConfig($conn = null) {
    return [
        'host' => $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com',
        'port' => $_ENV['SMTP_PORT'] ?? 587,
        'username' => $_ENV['SMTP_USERNAME'] ?? '',
        'password' => $_ENV['SMTP_PASSWORD'] ?? '',
        'from_email' => $_ENV['FROM_EMAIL'] ?? '',
        'from_name' => $_ENV['FROM_NAME'] ?? 'IdeaNest'
    ];
}