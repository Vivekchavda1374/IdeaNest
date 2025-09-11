<?php
require_once __DIR__ . '/../Login/Login/db.php';

class NotificationServer {
    private $clients = [];
    private $userConnections = [];
    private $conn;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }

    public function start() {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($socket, '0.0.0.0', 8080);
        socket_listen($socket);

        echo "WebSocket server started on port 8080\n";

        while (true) {
            $client = socket_accept($socket);
            $this->handleClient($client);
        }
    }

    private function handleClient($client) {
        $request = socket_read($client, 1024);
        $this->performHandshake($client, $request);
        
        $this->clients[] = $client;
        
        while (true) {
            $msg = $this->unmask(socket_read($client, 1024));
            if (!$msg) break;
            
            $data = json_decode($msg, true);
            if ($data['type'] === 'auth') {
                $this->userConnections[$data['user_id']] = $client;
            }
        }
    }

    private function performHandshake($client, $request) {
        preg_match('/Sec-WebSocket-Key: (.*)\r\n/', $request, $matches);
        $key = base64_encode(pack('H*', sha1($matches[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        
        $response = "HTTP/1.1 101 Switching Protocols\r\n" .
                   "Upgrade: websocket\r\n" .
                   "Connection: Upgrade\r\n" .
                   "Sec-WebSocket-Accept: $key\r\n\r\n";
        
        socket_write($client, $response);
    }

    private function unmask($payload) {
        $length = ord($payload[1]) & 127;
        $masks = substr($payload, 2, 4);
        $data = substr($payload, 6);
        
        $text = '';
        for ($i = 0; $i < strlen($data); ++$i) {
            $text .= $data[$i] ^ $masks[$i % 4];
        }
        return $text;
    }

    public function sendToUser($userId, $message) {
        if (isset($this->userConnections[$userId])) {
            $this->send($this->userConnections[$userId], json_encode($message));
        }
    }

    private function send($client, $msg) {
        $msg = $this->mask($msg);
        socket_write($client, $msg);
    }

    private function mask($text) {
        $b1 = 0x81;
        $length = strlen($text);
        
        if ($length <= 125) {
            $header = pack('CC', $b1, $length);
        } else {
            $header = pack('CCn', $b1, 126, $length);
        }
        
        return $header . $text;
    }
}

$server = new NotificationServer();
$server->start();
?>