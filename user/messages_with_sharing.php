<?php
/**
 * Messages Page with Idea and Project Sharing
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../Login/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$conversation_id = isset($_GET['conversation']) ? intval($_GET['conversation']) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Anti-injection script - MUST be first -->
    <script src="../assets/js/anti_injection.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - IdeaNest</title>
    <link rel="icon" type="image/png" href="../assets/image/fevicon.png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .messages-container {
            height: 600px;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
        }
        
        .message-bubble {
            max-width: 70%;
            margin-bottom: 15px;
            padding: 12px 16px;
            border-radius: 18px;
            position: relative;
        }
        
        .message-sent {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 4px;
        }
        
        .message-received {
            background: white;
            color: #333;
            margin-right: auto;
            border-bottom-left-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .shared-content-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-top: 10px;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .shared-content-card h6 {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .shared-content-card p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .shared-content-card .badge {
            background: #667eea;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
        }
        
        .view-content-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            transition: transform 0.2s;
        }
        
        .view-content-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 5px;
        }
        
        .message-input-area {
            background: white;
            padding: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .share-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            margin-left: 10px;
        }
        
        .share-btn:hover {
            background: #218838;
        }
    </style>
    <link rel="stylesheet" href="../assets/css/loader.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card mt-4">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-comments"></i> Messages</h5>
                    </div>
                    <div class="card-body p-0">
                        <!-- Messages Display -->
                        <div class="messages-container" id="messagesContainer">
                            <!-- Messages will be loaded here -->
                        </div>
                        
                        <!-- Message Input -->
                        <div class="message-input-area">
                            <div class="input-group">
                                <input type="text" class="form-control" id="messageInput" 
                                       placeholder="Type a message...">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" id="sendMessageBtn">
                                        <i class="fas fa-paper-plane"></i> Send
                                    </button>
                                    <button class="share-btn" id="shareContentBtn">
                                        <i class="fas fa-share-alt"></i> Share Content
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Include Share Modal -->
    <?php include 'share_modal.php'; ?>
    
    <!-- Share Content Selection Modal -->
    <div class="modal fade" id="selectContentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Content to Share</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#myIdeas">My Ideas</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#myProjects">My Projects</a>
                        </li>
                    </ul>
                    
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="myIdeas">
                            <div id="ideasList"></div>
                        </div>
                        <div class="tab-pane fade" id="myProjects">
                            <div id="projectsList"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const conversationId = <?php echo $conversation_id; ?>;
        const userId = <?php echo $user_id; ?>;
        
        // Load messages
        function loadMessages() {
            if (conversationId === 0) return;
            
            $.ajax({
                url: 'api/get_messages.php',
                method: 'GET',
                data: { conversation_id: conversationId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayMessages(response.messages);
                    }
                }
            });
        }
        
        // Display messages
        function displayMessages(messages) {
            const container = $('#messagesContainer');
            container.empty();
            
            messages.forEach(function(msg) {
                const isSent = msg.sender_id === userId;
                const bubbleClass = isSent ? 'message-sent' : 'message-received';
                
                let messageHtml = `
                    <div class="d-flex ${isSent ? 'justify-content-end' : 'justify-content-start'}">
                        <div class="message-bubble ${bubbleClass}">
                            ${msg.content ? `<div>${msg.content}</div>` : ''}
                `;
                
                // Add shared content
                if (msg.shared_content) {
                    const content = msg.shared_content;
                    messageHtml += `
                        <div class="shared-content-card">
                            <span class="badge">${content.type.toUpperCase()}</span>
                            <h6>${content.title}</h6>
                            <p>${content.description}</p>
                            <a href="${content.link}" class="view-content-btn" target="_blank">
                                <i class="fas fa-external-link-alt"></i> View ${content.type}
                            </a>
                        </div>
                    `;
                }
                
                messageHtml += `
                            <div class="message-time">${formatTime(msg.created_at)}</div>
                        </div>
                    </div>
                `;
                
                container.append(messageHtml);
            });
            
            container.scrollTop(container[0].scrollHeight);
        }
        
        // Format time
        function formatTime(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleString();
        }
        
        // Send message
        $('#sendMessageBtn').click(function() {
            const message = $('#messageInput').val().trim();
            if (!message) return;
            
            // Send message logic here
            $('#messageInput').val('');
        });
        
        // Share content button
        $('#shareContentBtn').click(function() {
            loadMyContent();
            $('#selectContentModal').modal('show');
        });
        
        // Load user's ideas and projects
        function loadMyContent() {
            // Load ideas
            $.ajax({
                url: 'api/get_my_ideas.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayIdeas(response.ideas);
                    }
                }
            });
            
            // Load projects
            $.ajax({
                url: 'api/get_my_projects.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayProjects(response.projects);
                    }
                }
            });
        }
        
        // Display ideas
        function displayIdeas(ideas) {
            const container = $('#ideasList');
            container.empty();
            
            if (ideas.length === 0) {
                container.html('<p class="text-muted">No ideas found</p>');
                return;
            }
            
            ideas.forEach(function(idea) {
                const html = `
                    <div class="card mb-2">
                        <div class="card-body">
                            <h6>${idea.project_name}</h6>
                            <p class="text-muted small">${idea.description.substring(0, 100)}...</p>
                            <button class="btn btn-sm btn-primary share-idea-btn" 
                                    data-id="${idea.id}" 
                                    data-title="${idea.project_name}"
                                    data-description="${idea.description}">
                                <i class="fas fa-share"></i> Share This Idea
                            </button>
                        </div>
                    </div>
                `;
                container.append(html);
            });
        }
        
        // Display projects
        function displayProjects(projects) {
            const container = $('#projectsList');
            container.empty();
            
            if (projects.length === 0) {
                container.html('<p class="text-muted">No projects found</p>');
                return;
            }
            
            projects.forEach(function(project) {
                const html = `
                    <div class="card mb-2">
                        <div class="card-body">
                            <h6>${project.project_name}</h6>
                            <p class="text-muted small">${project.description.substring(0, 100)}...</p>
                            <button class="btn btn-sm btn-primary share-project-btn" 
                                    data-id="${project.id}" 
                                    data-title="${project.project_name}"
                                    data-description="${project.description}">
                                <i class="fas fa-share"></i> Share This Project
                            </button>
                        </div>
                    </div>
                `;
                container.append(html);
            });
        }
        
        // Share idea button click
        $(document).on('click', '.share-idea-btn', function() {
            const id = $(this).data('id');
            const title = $(this).data('title');
            const description = $(this).data('description');
            
            $('#selectContentModal').modal('hide');
            setTimeout(() => {
                initShareModal('idea', id, title, description);
            }, 300);
        });
        
        // Share project button click
        $(document).on('click', '.share-project-btn', function() {
            const id = $(this).data('id');
            const title = $(this).data('title');
            const description = $(this).data('description');
            
            $('#selectContentModal').modal('hide');
            setTimeout(() => {
                initShareModal('project', id, title, description);
            }, 300);
        });
        
        // Load messages on page load
        $(document).ready(function() {
            loadMessages();
            setInterval(loadMessages, 5000); // Refresh every 5 seconds
        });
    </script>
<script src="../assets/js/loader.js"></script>
</body>
</html>
