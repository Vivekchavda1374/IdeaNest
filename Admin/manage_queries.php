<?php
require_once __DIR__ . '/../includes/security_init.php';
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../Login/Login/login.php');
    exit;
}
require_once '../Login/Login/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage User Queries - Admin</title>
    <link rel="icon" type="image/png" href="../assets/image/fevicon.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
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
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .page-header h1 i {
            color: #6366f1;
        }
        
        .page-header p {
            color: #6b7280;
            font-size: 1.1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .stat-icon.pending { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-icon.in-progress { background: linear-gradient(135deg, #6366f1, #4f46e5); }
        .stat-icon.resolved { background: linear-gradient(135deg, #10b981, #059669); }
        .stat-icon.total { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        
        .stat-info h3 {
            color: #1f2937;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .stat-info p {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .filters {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            color: #374151;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #f9fafb;
            color: #1f2937;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #6366f1;
            background: white;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .queries-container {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        
        .query-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s;
        }
        
        .query-card:hover {
            background: white;
            border-color: #6366f1;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(99, 102, 241, 0.15);
        }
        
        .query-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        
        .query-user {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }
        
        .user-info h4 {
            color: #1f2937;
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }
        
        .user-info p {
            color: #6b7280;
            font-size: 0.85rem;
        }
        
        .query-title {
            color: #1f2937;
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }
        
        .query-category {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }
        
        .category-technical { background: #6366f1; color: white; }
        .category-account { background: #10b981; color: white; }
        .category-feature { background: #f59e0b; color: white; }
        .category-bug { background: #ef4444; color: white; }
        .category-other { background: #8b5cf6; color: white; }
        
        .query-description {
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .query-actions {
            display: flex;
            gap: 0.75rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .status-select {
            flex: 1;
            padding: 0.625rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: white;
            color: #1f2937;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .status-select:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .btn {
            padding: 0.625rem 1.25rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }
        
        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        
        .response-area {
            display: none;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .response-area.show {
            display: block;
        }
        
        .response-area textarea {
            width: 100%;
            padding: 0.875rem;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: white;
            color: #1f2937;
            font-family: inherit;
            font-size: 0.9rem;
            min-height: 100px;
            resize: vertical;
            margin-bottom: 0.75rem;
            transition: all 0.3s;
        }
        
        .response-area textarea:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .admin-response {
            background: #ecfdf5;
            border-left: 3px solid #10b981;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .admin-response h4 {
            color: #065f46;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .admin-response p {
            color: #047857;
            line-height: 1.6;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #9ca3af;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #d1d5db;
        }
        
        .empty-state h3 {
            color: #6b7280;
            margin-bottom: 0.5rem;
        }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-headset"></i> Manage User Queries</h1>
            <p>View and respond to user support queries</p>
        </div>

        <div class="stats-grid" id="statsGrid">
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3 id="pendingCount">0</h3>
                    <p>Pending</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon in-progress">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="stat-info">
                    <h3 id="inProgressCount">0</h3>
                    <p>In Progress</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon resolved">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3 id="resolvedCount">0</h3>
                    <p>Resolved</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-list"></i>
                </div>
                <div class="stat-info">
                    <h3 id="totalCount">0</h3>
                    <p>Total Queries</p>
                </div>
            </div>
        </div>

        <div class="filters">
            <div class="filter-group">
                <label>Status</label>
                <select id="filterStatus" onchange="loadQueries()">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="in-progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Category</label>
                <select id="filterCategory" onchange="loadQueries()">
                    <option value="">All Categories</option>
                    <option value="technical">Technical</option>
                    <option value="account">Account</option>
                    <option value="feature">Feature Request</option>
                    <option value="bug">Bug Report</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Search</label>
                <input type="text" id="searchQuery" placeholder="Search queries..." oninput="loadQueries()">
            </div>
        </div>

        <div class="queries-container">
            <div id="queriesList">
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Loading queries...</h3>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadQueries();
            loadStats();
        });

        async function loadQueries() {
            const status = document.getElementById('filterStatus').value;
            const category = document.getElementById('filterCategory').value;
            const search = document.getElementById('searchQuery').value;

            try {
                const params = new URLSearchParams({
                    action: 'get_all_queries',
                    status: status,
                    category: category,
                    search: search
                });

                const response = await fetch(`query_api.php?${params}`);
                const data = await response.json();

                const queriesList = document.getElementById('queriesList');

                if (data.success && data.queries && data.queries.length > 0) {
                    queriesList.innerHTML = '';

                    data.queries.forEach(query => {
                        const queryCard = createQueryCard(query);
                        queriesList.appendChild(queryCard);
                    });
                } else {
                    queriesList.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>No queries found</h3>
                            <p>No user queries match your filters</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading queries:', error);
            }
        }

        async function loadStats() {
            try {
                const response = await fetch('query_api.php?action=get_stats');
                const data = await response.json();

                if (data.success) {
                    document.getElementById('pendingCount').textContent = data.stats.pending || 0;
                    document.getElementById('inProgressCount').textContent = data.stats.in_progress || 0;
                    document.getElementById('resolvedCount').textContent = data.stats.resolved || 0;
                    document.getElementById('totalCount').textContent = data.stats.total || 0;
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        function createQueryCard(query) {
            const card = document.createElement('div');
            card.className = 'query-card';
            
            const initial = query.user_name ? query.user_name.charAt(0).toUpperCase() : 'U';
            const hasResponse = query.admin_response && query.admin_response.trim() !== '';
            
            card.innerHTML = `
                <div class="query-user">
                    <div class="user-avatar">${initial}</div>
                    <div class="user-info">
                        <h4>${escapeHtml(query.user_name || 'Unknown User')}</h4>
                        <p>${escapeHtml(query.user_email || 'No email')}</p>
                    </div>
                </div>
                <div class="query-title">${escapeHtml(query.subject)}</div>
                <span class="query-category category-${query.category}">${query.category}</span>
                <div class="query-description">${escapeHtml(query.description)}</div>
                <div style="color: #64748b; font-size: 0.85rem; margin-bottom: 1rem;">
                    <i class="fas fa-calendar"></i> ${formatDate(query.created_at)}
                </div>
                ${hasResponse ? `
                    <div class="admin-response">
                        <h4><i class="fas fa-reply"></i> Admin Response</h4>
                        <p>${escapeHtml(query.admin_response)}</p>
                    </div>
                ` : ''}
                <div class="query-actions">
                    <select class="status-select" id="status-${query.id}">
                        <option value="pending" ${query.status === 'pending' ? 'selected' : ''}>Pending</option>
                        <option value="in-progress" ${query.status === 'in-progress' ? 'selected' : ''}>In Progress</option>
                        <option value="resolved" ${query.status === 'resolved' ? 'selected' : ''}>Resolved</option>
                        <option value="closed" ${query.status === 'closed' ? 'selected' : ''}>Closed</option>
                    </select>
                    <button class="btn btn-primary" onclick="toggleResponse(${query.id})">
                        <i class="fas fa-reply"></i> Respond
                    </button>
                    <button class="btn btn-success" onclick="updateQuery(${query.id})">
                        <i class="fas fa-save"></i> Update
                    </button>
                </div>
                <div class="response-area" id="response-${query.id}">
                    <textarea id="response-text-${query.id}" placeholder="Type your response here...">${hasResponse ? escapeHtml(query.admin_response) : ''}</textarea>
                    <button class="btn btn-success" onclick="saveResponse(${query.id})">
                        <i class="fas fa-paper-plane"></i> Send Response
                    </button>
                </div>
            `;
            
            return card;
        }

        function toggleResponse(queryId) {
            const responseArea = document.getElementById(`response-${queryId}`);
            responseArea.classList.toggle('show');
        }

        async function updateQuery(queryId) {
            const status = document.getElementById(`status-${queryId}`).value;

            try {
                const formData = new FormData();
                formData.append('action', 'update_query');
                formData.append('query_id', queryId);
                formData.append('status', status);

                const response = await fetch('query_api.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert('Query updated successfully');
                    loadQueries();
                    loadStats();
                } else {
                    alert(data.message || 'Failed to update query');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred');
            }
        }

        async function saveResponse(queryId) {
            const responseText = document.getElementById(`response-text-${queryId}`).value.trim();

            if (!responseText) {
                alert('Please enter a response');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'save_response');
                formData.append('query_id', queryId);
                formData.append('response', responseText);

                const response = await fetch('query_api.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert('Response sent successfully');
                    loadQueries();
                    loadStats();
                } else {
                    alert(data.message || 'Failed to send response');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred');
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString();
        }
    </script>
</body>
</html>
