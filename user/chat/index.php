<?php
require_once __DIR__ . '/../../includes/security_init.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../Login/Login/login.php');
    exit;
}
require_once '../../Login/Login/db.php';

$current_user_id = $_SESSION['user_id'];
$current_user_name = $_SESSION['user_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - IdeaNest</title>
    <link rel="icon" type="image/png" href="../../assets/image/fevicon.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #0f172a;
            min-height: 100vh;
            overflow: hidden;
        }
        
        .main-content {
            margin-left: 280px;
        }
        
        .chat-container {
            max-width: 100%;
            margin: 0;
            height: 100vh;
            display: flex;
            gap: 0;
            background: #1e293b;
            overflow: hidden;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
        
        .sidebar {
            width: 380px;
            background: #0f172a;
            border-right: 1px solid #334155;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            padding: 24px;
            background: #1e293b;
            color: white;
            border-bottom: 1px solid #334155;
        }
        
        .sidebar-header h2 {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .search-box {
            padding: 16px;
            background: #1e293b;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 1px solid #334155;
            border-radius: 12px;
            font-size: 14px;
            background: #0f172a;
            color: #e2e8f0;
            transition: all 0.3s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box::before {
            content: '\f002';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            left: 32px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 14px;
        }
        
        .conversations-list {
            flex: 1;
            overflow-y: auto;
            background: #0f172a;
        }
        
        .conversations-list::-webkit-scrollbar {
            width: 6px;
        }
        
        .conversations-list::-webkit-scrollbar-track {
            background: #1e293b;
        }
        
        .conversations-list::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 3px;
        }
        
        .conversation-item {
            padding: 16px 20px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            gap: 14px;
            align-items: center;
            border-left: 3px solid transparent;
            position: relative;
        }
        
        .conversation-delete {
            opacity: 0;
            transition: opacity 0.2s;
            margin-left: auto;
            background: #dc2626;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .conversation-item:hover .conversation-delete {
            opacity: 1;
        }
        
        .conversation-delete:hover {
            background: #b91c1c;
            transform: scale(1.05);
        }
        
        .conversation-item::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 20px;
            right: 20px;
            height: 1px;
            background: #1e293b;
        }
        
        .conversation-item:hover {
            background: #1e293b;
        }
        
        .conversation-item.active {
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.1) 0%, transparent 100%);
            border-left-color: #3b82f6;
        }
        
        .conversation-avatar {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 20px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            position: relative;
        }
        
        .conversation-avatar::after {
            content: '';
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 12px;
            height: 12px;
            background: #10b981;
            border: 2px solid #0f172a;
            border-radius: 50%;
        }
        
        .conversation-info {
            flex: 1;
            min-width: 0;
        }
        
        .conversation-name {
            font-weight: 600;
            font-size: 15px;
            margin-bottom: 4px;
            color: #e2e8f0;
        }
        
        .conversation-preview {
            font-size: 13px;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .conversation-item.active .conversation-name {
            color: #3b82f6;
        }
        
        .conversation-item.active .conversation-preview {
            color: #94a3b8;
        }
        
        .conversation-meta {
            text-align: right;
            flex-shrink: 0;
        }
        
        .conversation-time {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 4px;
        }
        
        .conversation-item.active .conversation-time {
            color: #94a3b8;
        }
        
        .unread-badge {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            color: white;
            border-radius: 10px;
            padding: 3px 9px;
            font-size: 11px;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);
        }
        
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #1e293b;
        }
        
        .chat-header {
            padding: 24px;
            border-bottom: 1px solid #334155;
            display: flex;
            align-items: center;
            gap: 16px;
            background: #1e293b;
            backdrop-filter: blur(10px);
        }
        
        .chat-header-avatar {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .chat-header-info h3 {
            font-size: 19px;
            font-weight: 700;
            margin-bottom: 4px;
            color: #e2e8f0;
        }
        
        .chat-header-status {
            font-size: 13px;
            color: #10b981;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .chat-header-actions {
            display: flex;
            gap: 8px;
            margin-left: auto;
        }
        
        .header-action-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: rgba(51, 65, 85, 0.5);
            color: #94a3b8;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .header-action-btn:hover {
            background: #475569;
            color: white;
            transform: scale(1.1);
        }
        
        .header-action-btn.blocked {
            background: #ef4444;
            color: white;
        }
        
        .header-action-btn.blocked:hover {
            background: #dc2626;
        }
        
        .chat-header-status::before {
            content: '';
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            display: inline-block;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
            background: #0f172a;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(139, 92, 246, 0.05) 0%, transparent 50%);
        }
        
        .chat-messages::-webkit-scrollbar {
            width: 8px;
        }
        
        .chat-messages::-webkit-scrollbar-track {
            background: #1e293b;
        }
        
        .chat-messages::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 4px;
        }
        
        .message {
            display: flex;
            margin-bottom: 16px;
            animation: slideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; transform: scale(1); }
            to { opacity: 0; transform: scale(0.8); }
        }
        
        .message.sent {
            justify-content: flex-end;
        }
        
        .message-content {
            max-width: 65%;
            padding: 14px 18px;
            border-radius: 20px;
            word-wrap: break-word;
            position: relative;
        }
        
        .message.received .message-content {
            background: #1e293b;
            border: 1px solid #334155;
            border-bottom-left-radius: 6px;
            color: #e2e8f0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .message.sent .message-content {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            color: white;
            border-bottom-right-radius: 6px;
            box-shadow: 0 4px 16px rgba(59, 130, 246, 0.4);
        }
        
        .message-time {
            font-size: 11px;
            color: #64748b;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .message.sent .message-time {
            color: rgba(255,255,255,0.7);
            text-align: right;
            justify-content: flex-end;
        }
        
        .message-actions {
            position: absolute;
            top: 8px;
            right: 8px;
        }
        
        .message-content {
            position: relative;
        }
        
        .message-menu-btn {
            background: rgba(51, 65, 85, 0.8);
            border: none;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            transition: all 0.2s;
            opacity: 0;
        }
        
        .message:hover .message-menu-btn {
            opacity: 1;
        }
        
        .message-menu-btn:hover {
            background: #475569;
            transform: scale(1.1);
        }
        
        .message.sent .message-menu-btn {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .message.sent .message-menu-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .message-dropdown {
            display: none;
            position: absolute;
            top: 35px;
            right: 0;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            min-width: 120px;
            z-index: 100;
        }
        
        .message-dropdown.active {
            display: block;
        }
        
        .message-dropdown-item {
            padding: 10px 16px;
            cursor: pointer;
            color: #e2e8f0;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background 0.2s;
        }
        
        .message-dropdown-item:first-child {
            border-radius: 8px 8px 0 0;
        }
        
        .message-dropdown-item:last-child {
            border-radius: 0 0 8px 8px;
        }
        
        .message-dropdown-item:hover {
            background: #334155;
        }
        
        .message-dropdown-item.delete:hover {
            background: #dc2626;
            color: white;
        }
        
        .edit-input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #334155;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            background: #0f172a;
            color: #e2e8f0;
        }
        
        .edit-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .edit-actions {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }
        
        .edit-actions button {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
        }
        
        .save-btn {
            background: #10b981;
            color: white;
        }
        
        .cancel-btn {
            background: #6c757d;
            color: white;
        }
        
        .typing-indicator {
            display: none;
            padding: 10px 16px;
            background: white;
            border-radius: 18px;
            width: fit-content;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .typing-indicator.active {
            display: block;
        }
        
        .typing-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #6c757d;
            margin: 0 2px;
            animation: typing 1.4s infinite;
        }
        
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-10px); }
        }
        
        .chat-input {
            padding: 20px 24px;
            border-top: 1px solid #334155;
            background: #1e293b;
            backdrop-filter: blur(10px);
        }
        
        .input-wrapper {
            display: flex;
            gap: 12px;
            align-items: center;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 8px;
            transition: all 0.3s;
        }
        
        .input-wrapper:focus-within {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .input-wrapper input {
            flex: 1;
            padding: 10px 12px;
            border: none;
            background: transparent;
            font-size: 15px;
            color: #e2e8f0;
            outline: none;
        }
        
        .input-wrapper input::placeholder {
            color: #64748b;
        }
        
        .send-btn {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }
        
        .send-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.5);
        }
        
        .send-btn:active {
            transform: translateY(0);
        }
        
        .empty-state {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #64748b;
        }
        
        .empty-state i {
            font-size: 72px;
            margin-bottom: 24px;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .empty-state h3 {
            color: #e2e8f0;
            margin-bottom: 8px;
        }
        
        .empty-state p {
            color: #64748b;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 20px;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6c757d;
        }
        
        .modal-body {
            padding: 20px;
            overflow-y: auto;
        }
        
        .user-list-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .user-list-item:hover {
            background: #f8f9fa;
        }

        /* Shared Content Card Styles */
        .shared-content-card {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(139, 92, 246, 0.08));
            border: 2px solid rgba(59, 130, 246, 0.25);
            border-radius: 16px;
            padding: 16px;
            margin-top: 12px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .message.sent .shared-content-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0.1));
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        .shared-content-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.2);
            border-color: rgba(59, 130, 246, 0.4);
        }
        
        .shared-content-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .shared-content-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .message.sent .shared-content-icon {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
            color: #3b82f6;
        }
        
        .shared-content-title {
            font-weight: 600;
            color: #3b82f6;
            font-size: 0.95rem;
            flex: 1;
        }
        
        .message.sent .shared-content-title {
            color: white;
        }
        
        .shared-content-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white;
            padding: 10px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .message.sent .shared-content-link {
            background: white;
            color: #3b82f6;
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.2);
        }
        
        .shared-content-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
            text-decoration: none;
            color: white;
        }
        
        .message.sent .shared-content-link:hover {
            color: #3b82f6;
            box-shadow: 0 6px 20px rgba(255, 255, 255, 0.3);
        }
        
        .shared-content-link i {
            font-size: 0.85rem;
        }
        
        /* Message text for shared content */
        .message-text {
            line-height: 1.5;
            word-break: break-word;
        }
        
        /* Notification animations */
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                display: none;
            }
            
            .sidebar.active {
                display: flex;
            }
            
            .chat-main {
                display: none;
            }
            
            .chat-main.active {
                display: flex;
            }
        }
    </style>
    <link rel="stylesheet" href="../../assets/css/loader.css">
</head>
<body>
    <?php
        $basePath = '../';
        if (file_exists(__DIR__ . '/../layout.php')) {
            include '../layout.php';
        }
    ?>

    <div class="main-content">
    <div class="chat-container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>Messages</h2>
                <p style="font-size: 14px; opacity: 0.9;">End-to-end encrypted</p>
            </div>
            <div class="search-box">
                <input type="text" placeholder="Search conversations..." id="searchConversations" oninput="filterConversations(this.value)">
            </div>
            <div style="padding: 12px 16px; background: #1e293b; display: flex; gap: 8px;">
                <button onclick="showNewChatModal()" style="flex: 1; padding: 12px; background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%); color: white; border: none; border-radius: 12px; cursor: pointer; font-weight: 600; font-size: 14px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(59, 130, 246, 0.4)'" onmouseout="this.style.transform=''; this.style.boxShadow='0 4px 12px rgba(59, 130, 246, 0.3)'">
                    <i class="fas fa-plus"></i> New Chat
                </button>
                <button onclick="showRequestsModal()" style="padding: 12px 16px; background: #10b981; color: white; border: none; border-radius: 12px; cursor: pointer; font-weight: 600; font-size: 14px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); transition: all 0.3s; position: relative;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(16, 185, 129, 0.4)'" onmouseout="this.style.transform=''; this.style.boxShadow='0 4px 12px rgba(16, 185, 129, 0.3)'">
                    <i class="fas fa-inbox"></i>
                    <span id="requestBadge" style="display: none; position: absolute; top: -4px; right: -4px; background: #ef4444; color: white; border-radius: 10px; padding: 2px 6px; font-size: 10px; font-weight: 700;"></span>
                </button>
            </div>
            <div class="conversations-list" id="conversationsList">
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <p>No conversations yet</p>
                </div>
            </div>
        </div>
        
        <div class="chat-main" id="chatMain">
            <div class="empty-state">
                <i class="fas fa-comment-dots"></i>
                <h3>Select a conversation</h3>
                <p>Choose a conversation to start messaging</p>
            </div>
        </div>
    </div>

    <div class="modal" id="newChatModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>New Chat</h3>
                <button class="modal-close" onclick="closeNewChatModal()">&times;</button>
            </div>
            <div class="modal-body" id="usersList">
                <div style="text-align: center; padding: 20px; color: #6c757d;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 32px;"></i>
                    <p>Loading users...</p>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="requestsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Message Requests</h3>
                <button class="modal-close" onclick="closeRequestsModal()">&times;</button>
            </div>
            <div class="modal-body" id="requestsList">
                <div style="text-align: center; padding: 20px; color: #6c757d;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 32px;"></i>
                    <p>Loading requests...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="encryption.js"></script>
    <script>
        const currentUserId = <?php echo $current_user_id; ?>;
        let currentConversationId = null;
        let currentEncryptionKey = null;
        let messagePolling = null;
        let typingTimeout = null;
        
        // Clear old encryption keys on page load
        Object.keys(sessionStorage).forEach(key => {
            if (key.startsWith('chat_key_')) {
                sessionStorage.removeItem(key);
            }
        });

        async function loadConversations() {
            try {
                const response = await fetch('api.php?action=get_conversations');
                const data = await response.json();
                
                const list = document.getElementById('conversationsList');
                
                if (data.success && data.conversations && data.conversations.length > 0) {
                    list.innerHTML = '';
                    
                    for (const conv of data.conversations) {
                        const item = document.createElement('div');
                        item.className = 'conversation-item';
                        item.dataset.conversationId = conv.id;
                        item.dataset.otherUserId = conv.other_user_id;
                        
                        const initial = conv.other_user_name ? conv.other_user_name.charAt(0).toUpperCase() : 'U';
                        const unreadBadge = conv.unread_count > 0 ? `<span class="unread-badge">${conv.unread_count}</span>` : '';
                        
                        let preview = 'No messages yet';
                        
                        // Check if last message is a shared content
                        if (conv.message_type === 'idea_share') {
                            preview = '💡 Shared an idea';
                        } else if (conv.message_type === 'project_share') {
                            preview = '📁 Shared a project';
                        } else if (conv.encrypted_content && conv.iv && conv.encryption_key) {
                            try {
                                const convKey = await chatEncryption.getOrCreateConversationKey(conv.id, conv.encryption_key);
                                const decrypted = await chatEncryption.decryptMessage(conv.encrypted_content, conv.iv, convKey);
                                if (decrypted && decrypted !== 'Message') {
                                    preview = decrypted.length > 30 ? decrypted.substring(0, 30) + '...' : decrypted;
                                } else {
                                    preview = 'New message';
                                }
                            } catch (e) {
                                // Silently handle decryption errors
                                preview = 'New message';
                            }
                        }
                        
                        item.innerHTML = `
                            <div class="conversation-avatar">${initial}</div>
                            <div class="conversation-info">
                                <div class="conversation-name">${conv.other_user_name || 'User'}</div>
                                <div class="conversation-preview">${preview}</div>
                            </div>
                            <div class="conversation-meta">
                                <div class="conversation-time">${formatTime(conv.last_message_time)}</div>
                                ${unreadBadge}
                            </div>
                            <button class="conversation-delete" onclick="deleteConversation(event, ${conv.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        `;
                        
                        item.onclick = () => openConversation(conv.id, conv.other_user_id, conv.other_user_name, conv.encryption_key);
                        list.appendChild(item);
                    }
                } else {
                    list.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-comments"></i>
                            <p>No conversations yet</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading conversations:', error);
            }
        }

        async function openConversation(conversationId, otherUserId, otherUserName, encryptionKey = null) {
            try {
                currentConversationId = conversationId;
                
                if (encryptionKey) {
                    sessionStorage.removeItem(`chat_key_${conversationId}`);
                }
                
                currentEncryptionKey = await chatEncryption.getOrCreateConversationKey(conversationId, encryptionKey);
                
                document.querySelectorAll('.conversation-item').forEach(item => {
                    item.classList.remove('active');
                });
                document.querySelector(`[data-conversation-id="${conversationId}"]`)?.classList.add('active');
                
                const chatMain = document.getElementById('chatMain');
                const initial = otherUserName ? otherUserName.charAt(0).toUpperCase() : 'U';
            
            chatMain.innerHTML = `
                <div class="chat-header">
                    <div class="chat-header-avatar">${initial}</div>
                    <div class="chat-header-info">
                        <h3>${otherUserName}</h3>
                        <div class="chat-header-status" id="typingStatus">Online</div>
                    </div>
                    <div class="chat-header-actions">
                        <button class="header-action-btn" id="blockBtn" onclick="toggleBlockUser(${otherUserId}, '${otherUserName}')" title="Block user">
                            <i class="fas fa-ban"></i>
                        </button>
                        <button class="header-action-btn" onclick="toggleChatMenu()" title="More options">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                    </div>
                </div>
                <div class="chat-messages" id="chatMessages">
                    <div class="typing-indicator" id="typingIndicator">
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                    </div>
                </div>
                <div class="chat-input">
                    <div class="input-wrapper">
                        <input type="text" placeholder="Type a message..." id="messageInput" data-other-user-id="${otherUserId}">
                        <button class="send-btn" onclick="sendMessage()">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            `;
            
            document.getElementById('messageInput').addEventListener('keypress', (e) => {
                if (e.key === 'Enter') sendMessage();
            });
            
            const messageInput = document.getElementById('messageInput');
            if (messageInput) {
                messageInput.addEventListener('input', handleTyping);
            }
            
            // Check block status
            await checkBlockStatus(otherUserId);
            
            await loadMessages(conversationId);
            startMessagePolling();
            } catch (error) {
                console.error('Error opening conversation:', error);
            }
        }

        let lastMessageCount = 0;
        
        async function loadMessages(conversationId, forceReload = false) {
            try {
                const response = await fetch(`api.php?action=get_messages&conversation_id=${conversationId}`);
                const data = await response.json();
                
                if (data.success) {
                    const messagesContainer = document.getElementById('chatMessages');
                    if (!messagesContainer) return;
                    
                    if (!forceReload && data.messages && data.messages.length === lastMessageCount) {
                        return;
                    }
                    
                    lastMessageCount = data.messages ? data.messages.length : 0;
                    const scrollAtBottom = messagesContainer.scrollHeight - messagesContainer.scrollTop <= messagesContainer.clientHeight + 100;
                    
                    const typingIndicator = messagesContainer.querySelector('.typing-indicator');
                    messagesContainer.innerHTML = '';
                    
                    if (data.messages && data.messages.length > 0) {
                        for (const msg of data.messages) {
                            let decrypted;
                            let isSharedContent = false;
                            let sharedContentHTML = '';
                            
                            // Check if this is a shared content message
                            if (msg.message_type === 'idea_share' || msg.message_type === 'project_share') {
                                isSharedContent = true;
                                const contentType = msg.message_type === 'idea_share' ? 'Idea' : 'Project';
                                const contentId = msg.message_type === 'idea_share' ? msg.shared_idea_id : msg.shared_project_id;
                                const viewLink = msg.message_type === 'idea_share' 
                                    ? `../Blog/idea_details.php?id=${contentId}`
                                    : `../view_idea.php?id=${contentId}`;
                                
                                decrypted = `Shared a ${contentType.toLowerCase()} with you`;
                                
                                // Create shared content card
                                sharedContentHTML = `
                                    <div class="shared-content-card">
                                        <div class="shared-content-header">
                                            <div class="shared-content-icon">
                                                <i class="fas fa-${msg.message_type === 'idea_share' ? 'lightbulb' : 'project-diagram'}"></i>
                                            </div>
                                            <span class="shared-content-title">Shared ${contentType}</span>
                                        </div>
                                        <a href="${viewLink}" target="_blank" class="shared-content-link">
                                            <i class="fas fa-external-link-alt"></i>
                                            <span>View ${contentType}</span>
                                        </a>
                                    </div>
                                `;
                            } else {
                                // Regular message - decrypt normally
                                try {
                                    decrypted = await chatEncryption.decryptMessage(msg.encrypted_content, msg.iv, currentEncryptionKey);
                                } catch (e) {
                                    console.error('Error decrypting message:', e);
                                    decrypted = 'Message';
                                }
                            }
                            
                            const messageDiv = document.createElement('div');
                            messageDiv.className = `message ${msg.sender_id == currentUserId ? 'sent' : 'received'}`;
                            messageDiv.dataset.messageId = msg.id;
                            
                            const escapedText = decrypted.replace(/'/g, "&#39;").replace(/"/g, "&quot;").replace(/\\/g, "&#92;");
                            const displayText = decrypted.replace(/</g, "&lt;").replace(/>/g, "&gt;");
                            const actions = msg.sender_id == currentUserId ? `
                                <div class="message-actions">
                                    <button class="message-menu-btn" onclick="toggleMessageMenu(event, ${msg.id})">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="message-dropdown" id="dropdown-${msg.id}">
                                        ${!isSharedContent ? `
                                        <div class="message-dropdown-item" onclick="editMessage(event, ${msg.id}, '${escapedText}')">
                                            <i class="fas fa-edit"></i> Edit
                                        </div>
                                        ` : ''}
                                        <div class="message-dropdown-item delete" onclick="deleteMessage(event, ${msg.id})">
                                            <i class="fas fa-trash"></i> Delete
                                        </div>
                                    </div>
                                </div>
                            ` : '';
                            
                            messageDiv.innerHTML = `
                                <div class="message-content">
                                    ${actions}
                                    <div class="message-text">${displayText}</div>
                                    ${sharedContentHTML}
                                    <div class="message-time">${formatTime(msg.created_at)}</div>
                                </div>
                            `;
                            messagesContainer.appendChild(messageDiv);
                        }
                    }
                    
                    if (typingIndicator) {
                        messagesContainer.appendChild(typingIndicator);
                    }
                    
                    if (scrollAtBottom) {
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }
                }
            } catch (error) {
                console.error('Error loading messages:', error);
            }
        }

        async function sendMessage() {
            try {
                const input = document.getElementById('messageInput');
                if (!input) return;
                
                const message = input.value.trim();
                
                if (!message || !currentConversationId || !currentEncryptionKey) return;
                
                const otherUserId = input.dataset.otherUserId;
                const encrypted = await chatEncryption.encryptMessage(message, currentEncryptionKey);
                
                const formData = new FormData();
                formData.append('action', 'send_message');
                formData.append('receiver_id', otherUserId);
                formData.append('encrypted_content', encrypted.encrypted);
                formData.append('iv', encrypted.iv);
                
                const response = await fetch('api.php', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success) {
                    input.value = '';
                    lastMessageCount++;
                    await loadMessages(currentConversationId, true);
                } else {
                    alert(data.message || 'Failed to send message');
                }
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Failed to send message. Please try again.');
            }
        }

        function handleTyping() {
            if (!currentConversationId) return;
            
            const formData = new FormData();
            formData.append('action', 'typing');
            formData.append('conversation_id', currentConversationId);
            formData.append('is_typing', '1');
            
            fetch('api.php', { method: 'POST', body: formData });
            
            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => {
                const formData = new FormData();
                formData.append('action', 'typing');
                formData.append('conversation_id', currentConversationId);
                formData.append('is_typing', '0');
                fetch('api.php', { method: 'POST', body: formData });
            }, 2000);
        }

        async function checkTyping() {
            if (!currentConversationId) return;
            
            const response = await fetch(`api.php?action=check_typing&conversation_id=${currentConversationId}`);
            const data = await response.json();
            
            if (data.success) {
                const indicator = document.getElementById('typingIndicator');
                const status = document.getElementById('typingStatus');
                
                if (data.is_typing) {
                    indicator?.classList.add('active');
                    if (status) status.textContent = 'Typing...';
                } else {
                    indicator?.classList.remove('active');
                    if (status) status.textContent = 'Online';
                }
            }
        }

        function startMessagePolling() {
            if (messagePolling) clearInterval(messagePolling);
            
            messagePolling = setInterval(async () => {
                if (currentConversationId) {
                    await loadMessages(currentConversationId, false);
                    await checkTyping();
                }
            }, 3000);
        }

        function formatTime(timestamp) {
            if (!timestamp) return '';
            const date = new Date(timestamp);
            const now = new Date();
            const diff = now - date;
            
            if (diff < 60000) return 'Just now';
            if (diff < 3600000) return Math.floor(diff / 60000) + 'm';
            if (diff < 86400000) return Math.floor(diff / 3600000) + 'h';
            return date.toLocaleDateString();
        }
        
        // Block/Unblock functionality
        let currentBlockStatus = false;
        
        async function checkBlockStatus(userId) {
            try {
                const response = await fetch(`api.php?action=check_block_status&user_id=${userId}`);
                const data = await response.json();
                
                if (data.success) {
                    currentBlockStatus = data.i_blocked_them;
                    updateBlockButton(data.i_blocked_them);
                    
                    // If blocked by them, show message
                    if (data.they_blocked_me) {
                        const messageInput = document.getElementById('messageInput');
                        if (messageInput) {
                            messageInput.disabled = true;
                            messageInput.placeholder = 'You cannot message this user';
                        }
                    }
                }
            } catch (error) {
                console.error('Error checking block status:', error);
            }
        }
        
        function updateBlockButton(isBlocked) {
            const blockBtn = document.getElementById('blockBtn');
            if (blockBtn) {
                if (isBlocked) {
                    blockBtn.classList.add('blocked');
                    blockBtn.title = 'Unblock user';
                    blockBtn.innerHTML = '<i class="fas fa-ban"></i>';
                } else {
                    blockBtn.classList.remove('blocked');
                    blockBtn.title = 'Block user';
                    blockBtn.innerHTML = '<i class="fas fa-ban"></i>';
                }
            }
        }
        
        async function toggleBlockUser(userId, userName) {
            const action = currentBlockStatus ? 'unblock' : 'block';
            const confirmMsg = currentBlockStatus 
                ? `Unblock ${userName}? You will be able to message each other again.`
                : `Block ${userName}? You won't be able to message each other.`;
            
            if (!confirm(confirmMsg)) return;
            
            try {
                const formData = new FormData();
                formData.append('action', currentBlockStatus ? 'unblock_user' : 'block_user');
                formData.append('blocked_id', userId);
                
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    currentBlockStatus = !currentBlockStatus;
                    updateBlockButton(currentBlockStatus);
                    
                    // Show notification
                    showNotification(data.message, 'success');
                    
                    // Disable/enable message input
                    const messageInput = document.getElementById('messageInput');
                    if (messageInput) {
                        messageInput.disabled = currentBlockStatus;
                        messageInput.placeholder = currentBlockStatus 
                            ? 'You have blocked this user' 
                            : 'Type a message...';
                    }
                    
                    // Reload conversations to update UI
                    await loadConversations();
                } else {
                    showNotification(data.message || 'Action failed', 'error');
                }
            } catch (error) {
                console.error('Error toggling block:', error);
                showNotification('Failed to ' + action + ' user', 'error');
            }
        }
        
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
                color: white;
                padding: 16px 24px;
                border-radius: 12px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.2);
                z-index: 10000;
                animation: slideIn 0.3s ease;
                font-weight: 500;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        function toggleChatMenu() {
            // Placeholder for additional menu options
            alert('More options coming soon!');
        }

        async function showNewChatModal() {
            document.getElementById('newChatModal').classList.add('active');
            await loadAvailableUsers();
        }

        function closeNewChatModal() {
            document.getElementById('newChatModal').classList.remove('active');
        }

        async function loadAvailableUsers() {
            try {
                const response = await fetch('api.php?action=get_available_users');
                const data = await response.json();
                
                const usersList = document.getElementById('usersList');
                
                if (data.success && data.users && data.users.length > 0) {
                    usersList.innerHTML = '';
                    
                    for (const user of data.users) {
                        const initial = user.name ? user.name.charAt(0).toUpperCase() : 'U';
                        const item = document.createElement('div');
                        item.className = 'user-list-item';
                        item.innerHTML = `
                            <div class="conversation-avatar" style="width: 45px; height: 45px; font-size: 16px;">${initial}</div>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; font-size: 15px;">${user.name}</div>
                                <div style="font-size: 13px; color: #6c757d;">${user.department || 'Student'}</div>
                            </div>
                        `;
                        item.onclick = () => startNewChat(user.id, user.name);
                        usersList.appendChild(item);
                    }
                } else {
                    usersList.innerHTML = '<div style="text-align: center; padding: 20px; color: #6c757d;"><p>No users available to chat</p></div>';
                }
            } catch (error) {
                console.error('Error loading users:', error);
            }
        }

        async function startNewChat(userId, userName) {
            try {
                const formData = new FormData();
                formData.append('action', 'start_chat');
                formData.append('other_user_id', userId);
                
                const response = await fetch('api.php', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success) {
                    closeNewChatModal();
                    await loadConversations();
                    await openConversation(data.conversation_id, userId, userName, data.encryption_key);
                } else {
                    alert(data.message || 'Failed to start chat');
                }
            } catch (error) {
                console.error('Error starting chat:', error);
            }
        }

        function toggleMessageMenu(event, messageId) {
            event.stopPropagation();
            
            const dropdown = document.getElementById(`dropdown-${messageId}`);
            const allDropdowns = document.querySelectorAll('.message-dropdown');
            
            allDropdowns.forEach(d => {
                if (d.id !== `dropdown-${messageId}`) {
                    d.classList.remove('active');
                }
            });
            
            dropdown.classList.toggle('active');
        }
        
        document.addEventListener('click', () => {
            document.querySelectorAll('.message-dropdown').forEach(d => d.classList.remove('active'));
        });

        async function editMessage(event, messageId, currentText) {
            event.stopPropagation();
            document.querySelectorAll('.message-dropdown').forEach(d => d.classList.remove('active'));
            
            const decodedText = currentText.replace(/&#39;/g, "'").replace(/&quot;/g, '"').replace(/&#92;/g, "\\");
            
            const messageDiv = document.querySelector(`[data-message-id="${messageId}"]`);
            const messageText = messageDiv.querySelector('.message-text');
            const messageTime = messageDiv.querySelector('.message-time');
            
            messageText.innerHTML = `
                <input type="text" class="edit-input" value="${decodedText}" id="editInput${messageId}">
                <div class="edit-actions">
                    <button class="save-btn" onclick="saveEdit(event, ${messageId})">Save</button>
                    <button class="cancel-btn" onclick="cancelEdit(event, ${messageId}, '${currentText}')">Cancel</button>
                </div>
            `;
            
            const input = document.getElementById(`editInput${messageId}`);
            if (input) {
                input.focus();
                input.select();
            }
        }

        async function saveEdit(event, messageId) {
            event.stopPropagation();
            
            try {
                const input = document.getElementById(`editInput${messageId}`);
                if (!input) return;
                
                const newText = input.value.trim();
                
                if (!newText) {
                    alert('Message cannot be empty');
                    return;
                }
                
                const encrypted = await chatEncryption.encryptMessage(newText, currentEncryptionKey);
                
                const formData = new FormData();
                formData.append('action', 'edit_message');
                formData.append('message_id', messageId);
                formData.append('encrypted_content', encrypted.encrypted);
                formData.append('iv', encrypted.iv);
                
                const response = await fetch('api.php', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success) {
                    await loadMessages(currentConversationId, true);
                } else {
                    alert(data.message || 'Failed to edit message');
                }
            } catch (error) {
                console.error('Error editing message:', error);
                alert('Failed to edit message. Please try again.');
            }
        }

        function cancelEdit(event, messageId, originalText) {
            event.stopPropagation();
            
            const decodedText = originalText.replace(/&#39;/g, "'").replace(/&quot;/g, '"').replace(/&#92;/g, "\\");
            const messageDiv = document.querySelector(`[data-message-id="${messageId}"]`);
            const messageText = messageDiv.querySelector('.message-text');
            
            if (messageText) {
                messageText.textContent = decodedText;
            }
        }

        async function deleteMessage(event, messageId) {
            event.stopPropagation();
            document.querySelectorAll('.message-dropdown').forEach(d => d.classList.remove('active'));
            
            if (!confirm('Delete this message?')) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete_message');
                formData.append('message_id', messageId);
                
                const response = await fetch('api.php', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success) {
                    const messageDiv = document.querySelector(`[data-message-id="${messageId}"]`);
                    if (messageDiv) {
                        messageDiv.style.animation = 'fadeOut 0.3s ease-out';
                        setTimeout(() => {
                            lastMessageCount--;
                            loadMessages(currentConversationId, true);
                        }, 300);
                    }
                } else {
                    alert(data.message || 'Failed to delete message');
                }
            } catch (error) {
                console.error('Error deleting message:', error);
                alert('Failed to delete message. Please try again.');
            }
        }

        async function deleteConversation(event, conversationId) {
            event.stopPropagation();
            
            if (!confirm('Delete this conversation? All messages will be removed.')) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete_conversation');
                formData.append('conversation_id', conversationId);
                
                const response = await fetch('api.php', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success) {
                    if (currentConversationId === conversationId) {
                        currentConversationId = null;
                        document.getElementById('chatMain').innerHTML = `
                            <div class="empty-state">
                                <i class="fas fa-comment-dots"></i>
                                <h3>Select a conversation</h3>
                                <p>Choose a conversation to start messaging</p>
                            </div>
                        `;
                    }
                    await loadConversations();
                } else {
                    alert(data.message || 'Failed to delete conversation');
                }
            } catch (error) {
                console.error('Error deleting conversation:', error);
                alert('Failed to delete conversation. Please try again.');
            }
        }

        async function showRequestsModal() {
            document.getElementById('requestsModal').classList.add('active');
            await loadMessageRequests();
        }

        function closeRequestsModal() {
            document.getElementById('requestsModal').classList.remove('active');
        }

        async function loadMessageRequests() {
            try {
                const response = await fetch('api.php?action=get_requests');
                const data = await response.json();
                
                const requestsList = document.getElementById('requestsList');
                const badge = document.getElementById('requestBadge');
                
                if (data.success && data.requests && data.requests.length > 0) {
                    badge.style.display = 'block';
                    badge.textContent = data.requests.length;
                    requestsList.innerHTML = '';
                    
                    for (const req of data.requests) {
                        const initial = req.name ? req.name.charAt(0).toUpperCase() : 'U';
                        const item = document.createElement('div');
                        item.style.cssText = 'padding: 16px; border-bottom: 1px solid #e9ecef; display: flex; align-items: center; gap: 12px;';
                        item.innerHTML = `
                            <div class="conversation-avatar" style="width: 48px; height: 48px; font-size: 18px;">${initial}</div>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; font-size: 15px; margin-bottom: 4px;">${req.name}</div>
                                <div style="font-size: 13px; color: #6c757d;">Wants to message you</div>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <button onclick="acceptRequest(${req.id})" style="padding: 8px 16px; background: #10b981; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600;">
                                    <i class="fas fa-check"></i> Accept
                                </button>
                                <button onclick="rejectRequest(${req.id})" style="padding: 8px 16px; background: #ef4444; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600;">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </div>
                        `;
                        requestsList.appendChild(item);
                    }
                } else {
                    badge.style.display = 'none';
                    requestsList.innerHTML = '<div style="text-align: center; padding: 40px; color: #6c757d;"><i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i><p>No message requests</p></div>';
                }
            } catch (error) {
                console.error('Error loading requests:', error);
            }
        }

        async function acceptRequest(requestId) {
            try {
                const formData = new FormData();
                formData.append('action', 'accept_request');
                formData.append('request_id', requestId);
                
                const response = await fetch('message_request_handler.php', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success) {
                    await loadMessageRequests();
                    closeRequestsModal();
                    
                    if (data.sender_id && data.sender_name) {
                        const startFormData = new FormData();
                        startFormData.append('action', 'start_chat');
                        startFormData.append('other_user_id', data.sender_id);
                        
                        const startResponse = await fetch('api.php', { method: 'POST', body: startFormData });
                        const startData = await startResponse.json();
                        
                        if (startData.success) {
                            await loadConversations();
                            
                            const convResponse = await fetch(`api.php?action=get_conversation_key&conversation_id=${startData.conversation_id}`);
                            const convData = await convResponse.json();
                            const encKey = convData.success ? convData.encryption_key : null;
                            
                            await openConversation(startData.conversation_id, data.sender_id, data.sender_name, encKey);
                        }
                    }
                } else {
                    alert(data.message || 'Failed to accept request');
                }
            } catch (error) {
                console.error('Error accepting request:', error);
            }
        }

        async function rejectRequest(requestId) {
            try {
                const formData = new FormData();
                formData.append('action', 'reject_request');
                formData.append('request_id', requestId);
                
                const response = await fetch('message_request_handler.php', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success) {
                    await loadMessageRequests();
                } else {
                    alert(data.message || 'Failed to reject request');
                }
            } catch (error) {
                console.error('Error rejecting request:', error);
            }
        }

        function filterConversations(searchTerm) {
            const items = document.querySelectorAll('.conversation-item');
            const term = searchTerm.toLowerCase().trim();
            
            items.forEach(item => {
                const name = item.querySelector('.conversation-name')?.textContent.toLowerCase() || '';
                if (name.includes(term)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        loadConversations();
        loadMessageRequests();
        setInterval(loadConversations, 5000);
        setInterval(loadMessageRequests, 10000);
    </script>
    </div>
<script src="../../assets/js/loader.js"></script>
</body>
</html>
