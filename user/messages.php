<?php
include '../Login/Login/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login/Login/login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$current_user_name = $_SESSION['user_name'];

// Fetch users to chat with (excluding current user)
$users_query = "SELECT r.id, r.name, 
                (SELECT COUNT(*) FROM user_messages um 
                 WHERE um.sender_id = r.id AND um.receiver_id = ? AND um.is_read = 0) as unread_count
                FROM register r 
                WHERE r.id != ?
                ORDER BY unread_count DESC";
$stmt = $conn->prepare($users_query);
$stmt->bind_param("ii", $current_user_id, $current_user_id);
$stmt->execute();
$users_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - IdeaNest</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .chat-container {
            display: flex;
            height: calc(100vh - 100px);
        }
        .users-list {
            width: 300px;
            border-right: 1px solid #ddd;
            overflow-y: auto;
        }
        .chat-window {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .messages-list {
            flex-grow: 1;
            overflow-y: auto;
            padding: 15px;
            display: flex;
            flex-direction: column-reverse; /* Newest messages at bottom */
        }
        .message-input {
            display: flex;
            padding: 15px;
            background-color: #f8f9fa;
        }
        .message-input textarea {
            flex-grow: 1;
            margin-right: 10px;
        }
        .user-chat-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            position: relative;
        }
        .user-chat-item:hover {
            background-color: #f1f3f5;
        }
        .user-chat-item.unread {
            background-color: #e6f2ff;
            font-weight: bold;
        }
        .unread-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7em;
        }
        .message {
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 8px;
            max-width: 70%;
            clear: both;
        }
        .sent {
            align-self: flex-end;
            background-color: #007bff;
            color: white;
            float: right;
            margin-left: auto;
        }
        .received {
            align-self: flex-start;
            background-color: #e9ecef;
            float: left;
            margin-right: auto;
        }
        .received.unread {
            background-color: #d1ecf1;
            font-weight: bold;
        }
        .message-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        .message-sender {
            font-weight: bold;
            font-size: 0.8em;
        }
        .message-time {
            font-size: 0.7em;
            color: #6c757d;
        }
        .message-content {
            clear: both;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="chat-container">
            <div class="users-list">
                <h4 class="p-3 border-bottom">Chats</h4>
                <?php while ($user = $users_result->fetch_assoc()): ?>
                    <div class="user-chat-item <?php echo $user['unread_count'] > 0 ? 'unread' : ''; ?>" 
                         data-user-id="<?php echo $user['id']; ?>"
                         onclick="openChat(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')">
                        <?php echo htmlspecialchars($user['name']); ?>
                        <?php if ($user['unread_count'] > 0): ?>
                            <span class="unread-badge"><?php echo $user['unread_count']; ?></span>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <div class="chat-window">
                <div id="chatHeader" class="p-3 border-bottom">
                    <h4 id="chatUserName">Select a user to chat</h4>
                </div>
                <div id="messagesList" class="messages-list d-flex flex-column">
                    <!-- Messages will be loaded here -->
                </div>
                <div class="message-input">
                    <textarea id="messageInput" class="form-control" placeholder="Type your message..." rows="3" disabled></textarea>
                    <button id="sendButton" class="btn btn-primary" onclick="sendMessage()" disabled>Send</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    let currentChatUserId = null;

    function openChat(userId, userName) {
        currentChatUserId = userId;
        document.getElementById('chatUserName').textContent = userName;
        
        // Enable message input and send button
        document.getElementById('messageInput').disabled = false;
        document.getElementById('sendButton').disabled = false;

        // Fetch messages for this user
        fetch(`get_messages.php?user_id=${userId}`)
            .then(response => response.json())
            .then(messages => {
                const messagesList = document.getElementById('messagesList');
                messagesList.innerHTML = '';
                messages.forEach(msg => {
                    const msgElement = document.createElement('div');
                    msgElement.classList.add('message');
                    msgElement.classList.add(msg.sender_id == <?php echo $current_user_id; ?> ? 'sent' : 'received');
                    
                    // Highlight unread messages
                    if (msg.sender_id != <?php echo $current_user_id; ?> && !msg.is_read) {
                        msgElement.classList.add('unread');
                    }
                    
                    // Create message details
                    const detailsElement = document.createElement('div');
                    detailsElement.classList.add('message-details');
                    
                    const senderElement = document.createElement('span');
                    senderElement.classList.add('message-sender');
                    senderElement.textContent = msg.sender_id == <?php echo $current_user_id; ?> ? 'You' : msg.sender_name;
                    
                    const timeElement = document.createElement('span');
                    timeElement.classList.add('message-time');
                    timeElement.textContent = msg.formatted_time;
                    
                    detailsElement.appendChild(senderElement);
                    detailsElement.appendChild(timeElement);
                    
                    const contentElement = document.createElement('div');
                    contentElement.classList.add('message-content');
                    contentElement.textContent = msg.message_text;
                    
                    msgElement.appendChild(detailsElement);
                    msgElement.appendChild(contentElement);
                    
                    messagesList.appendChild(msgElement);
                });
                
                // Update user list to remove unread badge
                const userItem = document.querySelector(`.user-chat-item[data-user-id="${userId}"]`);
                if (userItem) {
                    userItem.classList.remove('unread');
                    const badge = userItem.querySelector('.unread-badge');
                    if (badge) badge.remove();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to load messages');
            });
    }

    function sendMessage() {
        const messageInput = document.getElementById('messageInput');
        const messageText = messageInput.value.trim();

        if (messageText && currentChatUserId) {
            fetch('send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `receiver_id=${currentChatUserId}&message_text=${encodeURIComponent(messageText)}`
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    // Create message element
                    const messagesList = document.getElementById('messagesList');
                    const msgElement = document.createElement('div');
                    msgElement.classList.add('message', 'sent');
                    
                    // Create message details
                    const detailsElement = document.createElement('div');
                    detailsElement.classList.add('message-details');
                    
                    const senderElement = document.createElement('span');
                    senderElement.classList.add('message-sender');
                    senderElement.textContent = 'You';
                    
                    const timeElement = document.createElement('span');
                    timeElement.classList.add('message-time');
                    timeElement.textContent = 'Just now';
                    
                    detailsElement.appendChild(senderElement);
                    detailsElement.appendChild(timeElement);
                    
                    const contentElement = document.createElement('div');
                    contentElement.classList.add('message-content');
                    contentElement.textContent = messageText;
                    
                    msgElement.appendChild(detailsElement);
                    msgElement.appendChild(contentElement);
                    
                    // Prepend the new message (since messages are now in reverse order)
                    messagesList.insertBefore(msgElement, messagesList.firstChild);
                    
                    // Clear input
                    messageInput.value = '';
                } else {
                    alert('Failed to send message');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to send message');
            });
        }
    }

    // Optional: Add enter key support for sending messages
    document.getElementById('messageInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?> 