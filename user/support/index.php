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
    <title>Support & Queries - IdeaNest</title>
    <link rel="icon" type="image/png" href="../../assets/image/fevicon.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8eaf6 100%);
            min-height: 100vh;
        }
        
        .main-content {
            margin-left: 280px;
            padding: 2rem;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }
        
        .support-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }
        
        .page-header p {
            color: #6b7280;
            font-size: 1.1rem;
        }
        
        .support-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 968px) {
            .support-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .support-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
        }
        
        .support-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(102, 126, 234, 0.2);
            border-color: #667eea;
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .card-header h2 {
            color: #1f2937;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            color: #374151;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            font-size: 0.95rem;
            background: #f9fafb;
            color: #1f2937;
            transition: all 0.3s;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .submit-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.5);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        .queries-list {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        .query-item {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        
        .query-item:hover {
            border-color: #667eea;
            background: white;
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
        }
        
        .query-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        
        .query-title {
            color: #1f2937;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        
        .query-category {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .category-technical { background: #667eea; color: white; }
        .category-account { background: #10b981; color: white; }
        .category-feature { background: #f59e0b; color: white; }
        .category-bug { background: #ef4444; color: white; }
        .category-other { background: #764ba2; color: white; }
        
        .query-description {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .query-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .query-date {
            color: #9ca3af;
            font-size: 0.85rem;
        }
        
        .query-status {
            padding: 0.375rem 0.875rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-in-progress { background: #dbeafe; color: #1e40af; }
        .status-resolved { background: #d1fae5; color: #065f46; }
        .status-closed { background: #e5e7eb; color: #374151; }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #9ca3af;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .empty-state h3 {
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: none;
        }
        
        .alert.show {
            display: block;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-in-progress { background: #dbeafe; color: #1e40af; }
        .status-resolved { background: #d1fae5; color: #065f46; }
        .status-closed { background: #e5e7eb; color: #374151; }
        
        .query-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .btn-edit, .btn-delete, .btn-view-response {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-edit {
            background: #667eea;
            color: white;
        }
        
        .btn-edit:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        .btn-delete {
            background: #ef4444;
            color: white;
        }
        
        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }
        
        .btn-view-response {
            background: #10b981;
            color: white;
        }
        
        .btn-view-response:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        
        .admin-response-box {
            background: #ecfdf5;
            border-left: 4px solid #10b981;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .admin-response-box h4 {
            color: #065f46;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .admin-response-box p {
            color: #047857;
            line-height: 1.6;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-size: 1.25rem;
            cursor: pointer;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }
        
        .modal-body {
            padding: 1.5rem;
            overflow-y: auto;
        }
        
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }
        
        .btn-cancel {
            padding: 0.625rem 1.25rem;
            background: #e5e7eb;
            color: #374151;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-cancel:hover {
            background: #d1d5db;
        }
        
        .btn-save {
            padding: 0.625rem 1.25rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .query-updated {
            font-size: 0.75rem;
            color: #9ca3af;
            font-style: italic;
            margin-top: 0.25rem;
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
        <div class="support-container">
            <div class="page-header">
                <h1><i class="fas fa-headset"></i> Support & Queries</h1>
                <p>Need help? Submit your query and we'll get back to you soon.</p>
            </div>

            <div class="support-grid">
                <div class="support-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <h2>Submit Query</h2>
                    </div>

                    <div id="alertBox" class="alert"></div>

                    <form id="queryForm">
                        <div class="form-group">
                            <label for="subject">Subject *</label>
                            <input type="text" id="subject" name="subject" required placeholder="Brief description of your issue">
                        </div>

                        <div class="form-group">
                            <label for="category">Category *</label>
                            <select id="category" name="category" required>
                                <option value="">Select a category</option>
                                <option value="technical">Technical Issue</option>
                                <option value="account">Account Related</option>
                                <option value="feature">Feature Request</option>
                                <option value="bug">Bug Report</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="description">Description *</label>
                            <textarea id="description" name="description" required placeholder="Provide detailed information about your query..."></textarea>
                        </div>

                        <button type="submit" class="submit-btn">
                            <i class="fas fa-paper-plane"></i> Submit Query
                        </button>
                    </form>
                </div>

                <div class="support-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-list-alt"></i>
                        </div>
                        <h2>Quick Help</h2>
                    </div>

                    <div style="color: #6b7280; line-height: 1.8;">
                        <h3 style="color: #1f2937; margin-bottom: 1rem; font-size: 1.1rem;">
                            <i class="fas fa-info-circle" style="color: #667eea;"></i> Common Issues
                        </h3>
                        <ul style="list-style: none; padding: 0;">
                            <li style="padding: 0.5rem 0; border-bottom: 1px solid #e5e7eb;">
                                <i class="fas fa-chevron-right" style="color: #667eea; margin-right: 0.5rem;"></i>
                                Can't upload project files
                            </li>
                            <li style="padding: 0.5rem 0; border-bottom: 1px solid #e5e7eb;">
                                <i class="fas fa-chevron-right" style="color: #667eea; margin-right: 0.5rem;"></i>
                                Profile picture not updating
                            </li>
                            <li style="padding: 0.5rem 0; border-bottom: 1px solid #e5e7eb;">
                                <i class="fas fa-chevron-right" style="color: #667eea; margin-right: 0.5rem;"></i>
                                Messages not sending
                            </li>
                            <li style="padding: 0.5rem 0; border-bottom: 1px solid #e5e7eb;">
                                <i class="fas fa-chevron-right" style="color: #667eea; margin-right: 0.5rem;"></i>
                                Mentor request pending
                            </li>
                            <li style="padding: 0.5rem 0;">
                                <i class="fas fa-chevron-right" style="color: #667eea; margin-right: 0.5rem;"></i>
                                Achievement not unlocked
                            </li>
                        </ul>

                        <h3 style="color: #1f2937; margin: 1.5rem 0 1rem; font-size: 1.1rem;">
                            <i class="fas fa-clock" style="color: #10b981;"></i> Response Time
                        </h3>
                        <p>We typically respond within 24-48 hours. Urgent issues are prioritized.</p>
                    </div>
                </div>
            </div>

            <div class="queries-list">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <h2>My Queries</h2>
                </div>

                <div id="queriesList">
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No queries yet</h3>
                        <p>Your submitted queries will appear here</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Query Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Query</h3>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="editQueryId">
                    <div class="form-group">
                        <label for="editSubject">Subject *</label>
                        <input type="text" id="editSubject" name="subject" required>
                    </div>
                    <div class="form-group">
                        <label for="editCategory">Category *</label>
                        <select id="editCategory" name="category" required>
                            <option value="technical">Technical Issue</option>
                            <option value="account">Account Related</option>
                            <option value="feature">Feature Request</option>
                            <option value="bug">Bug Report</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editDescription">Description *</label>
                        <textarea id="editDescription" name="description" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                <button class="btn-save" onclick="saveEdit()">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>

    <script src="../../assets/js/loader.js"></script>
    <script>
        const currentUserId = <?php echo $current_user_id; ?>;

        // Load user queries on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadQueries();
        });

        // Handle form submission
        document.getElementById('queryForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData();
            formData.append('action', 'submit_query');
            formData.append('subject', document.getElementById('subject').value);
            formData.append('category', document.getElementById('category').value);
            formData.append('description', document.getElementById('description').value);

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('Query submitted successfully! We\'ll get back to you soon.', 'success');
                    document.getElementById('queryForm').reset();
                    loadQueries();
                } else {
                    showAlert(data.message || 'Failed to submit query', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'error');
            }
        });

        async function loadQueries() {
            try {
                const response = await fetch('api.php?action=get_queries');
                const data = await response.json();

                const queriesList = document.getElementById('queriesList');

                if (data.success && data.queries && data.queries.length > 0) {
                    queriesList.innerHTML = '';

                    data.queries.forEach(query => {
                        const queryItem = document.createElement('div');
                        queryItem.className = 'query-item';
                        
                        const hasResponse = query.admin_response && query.admin_response.trim() !== '';
                        const canEdit = query.status === 'pending' || query.status === 'in-progress';
                        const updatedText = query.updated_at !== query.created_at ? 
                            `<div class="query-updated">Updated: ${formatDate(query.updated_at)}</div>` : '';
                        
                        queryItem.innerHTML = `
                            <div class="query-header">
                                <div>
                                    <div class="query-title">${escapeHtml(query.subject)}</div>
                                    <span class="query-category category-${query.category}">${query.category}</span>
                                </div>
                            </div>
                            <div class="query-description">${escapeHtml(query.description)}</div>
                            ${hasResponse ? `
                                <div class="admin-response-box">
                                    <h4><i class="fas fa-reply"></i> Admin Response</h4>
                                    <p>${escapeHtml(query.admin_response)}</p>
                                </div>
                            ` : ''}
                            <div class="query-footer">
                                <div>
                                    <span class="query-date">
                                        <i class="fas fa-calendar"></i> ${formatDate(query.created_at)}
                                    </span>
                                    ${updatedText}
                                </div>
                                <span class="query-status status-${query.status}">${formatStatus(query.status)}</span>
                            </div>
                            ${canEdit ? `
                                <div class="query-actions">
                                    <button class="btn-edit" onclick="editQuery(${query.id}, '${escapeForJs(query.subject)}', '${query.category}', '${escapeForJs(query.description)}')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn-delete" onclick="deleteQuery(${query.id})">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            ` : ''}
                        `;
                        queriesList.appendChild(queryItem);
                    });
                } else {
                    queriesList.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>No queries yet</h3>
                            <p>Your submitted queries will appear here</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading queries:', error);
            }
        }

        function showAlert(message, type) {
            const alertBox = document.getElementById('alertBox');
            alertBox.className = `alert alert-${type} show`;
            alertBox.textContent = message;

            setTimeout(() => {
                alertBox.classList.remove('show');
            }, 5000);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;

            if (diff < 60000) return 'Just now';
            if (diff < 3600000) return Math.floor(diff / 60000) + ' minutes ago';
            if (diff < 86400000) return Math.floor(diff / 3600000) + ' hours ago';
            if (diff < 604800000) return Math.floor(diff / 86400000) + ' days ago';

            return date.toLocaleDateString();
        }

        function formatStatus(status) {
            return status.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase());
        }

        function escapeForJs(text) {
            return text.replace(/'/g, "\\'").replace(/"/g, '\\"').replace(/\n/g, '\\n').replace(/\r/g, '');
        }

        // Edit Query
        function editQuery(id, subject, category, description) {
            document.getElementById('editQueryId').value = id;
            document.getElementById('editSubject').value = subject;
            document.getElementById('editCategory').value = category;
            document.getElementById('editDescription').value = description;
            document.getElementById('editModal').classList.add('show');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('show');
            document.getElementById('editForm').reset();
        }

        async function saveEdit() {
            const queryId = document.getElementById('editQueryId').value;
            const subject = document.getElementById('editSubject').value.trim();
            const category = document.getElementById('editCategory').value;
            const description = document.getElementById('editDescription').value.trim();

            if (!subject || !category || !description) {
                showAlert('All fields are required', 'error');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'update_query');
                formData.append('query_id', queryId);
                formData.append('subject', subject);
                formData.append('category', category);
                formData.append('description', description);

                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('Query updated successfully!', 'success');
                    closeEditModal();
                    loadQueries();
                } else {
                    showAlert(data.message || 'Failed to update query', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'error');
            }
        }

        // Delete Query
        async function deleteQuery(id) {
            if (!confirm('Are you sure you want to delete this query? This action cannot be undone.')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'delete_query');
                formData.append('query_id', id);

                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('Query deleted successfully!', 'success');
                    loadQueries();
                } else {
                    showAlert(data.message || 'Failed to delete query', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'error');
            }
        }

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>
