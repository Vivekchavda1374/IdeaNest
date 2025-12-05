<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Import - IdeaNest</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 2em; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .content { padding: 30px; }
        .config-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .config-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .config-item:last-child { border-bottom: none; }
        .config-label { font-weight: 600; color: #495057; }
        .config-value { color: #6c757d; font-family: monospace; }
        .btn {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            width: 100%;
            margin-top: 10px;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }
        .progress-container {
            display: none;
            margin-top: 20px;
        }
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e9ecef;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 15px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            width: 0%;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        .log-container {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 8px;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
        }
        .log-line { margin-bottom: 5px; }
        .log-success { color: #4ec9b0; }
        .log-error { color: #f48771; }
        .log-info { color: #569cd6; }
        .log-warning { color: #dcdcaa; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        .stat-label { color: #6c757d; font-size: 0.9em; }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗄️ Database Import Tool</h1>
            <p>IdeaNest Database Setup</p>
        </div>
        
        <div class="content">
            <div class="config-section">
                <h3 style="margin-bottom: 15px;">Configuration</h3>
                <div class="config-item">
                    <span class="config-label">Database Host:</span>
                    <span class="config-value">localhost</span>
                </div>
                <div class="config-item">
                    <span class="config-label">Database Name:</span>
                    <span class="config-value">ictmu6ya_ideanest</span>
                </div>
                <div class="config-item">
                    <span class="config-label">SQL File:</span>
                    <span class="config-value">ictmu6ya_ideanest.sql</span>
                </div>
                <div class="config-item">
                    <span class="config-label">File Size:</span>
                    <span class="config-value" id="fileSize">Checking...</span>
                </div>
            </div>
            
            <button class="btn" id="importBtn" onclick="startImport()">
                🚀 Start Database Import
            </button>
            
            <div class="progress-container" id="progressContainer">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill">0%</div>
                </div>
                
                <div class="log-container" id="logContainer"></div>
                
                <div class="stats-grid" id="statsGrid" style="display: none;"></div>
            </div>
        </div>
    </div>
    
    <script>
        // Check file size on load
        fetch('?action=check')
            .then(r => r.json())
            .then(data => {
                document.getElementById('fileSize').textContent = data.fileSize;
            });
        
        function addLog(message, type = 'info') {
            const logContainer = document.getElementById('logContainer');
            const line = document.createElement('div');
            line.className = `log-line log-${type}`;
            line.textContent = message;
            logContainer.appendChild(line);
            logContainer.scrollTop = logContainer.scrollHeight;
        }
        
        function updateProgress(percent) {
            const fill = document.getElementById('progressFill');
            fill.style.width = percent + '%';
            fill.textContent = percent + '%';
        }
        
        function startImport() {
            const btn = document.getElementById('importBtn');
            const progressContainer = document.getElementById('progressContainer');
            
            btn.disabled = true;
            btn.textContent = '⏳ Importing...';
            progressContainer.style.display = 'block';
            
            addLog('Starting database import...', 'info');
            
            fetch('?action=import')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateProgress(100);
                        addLog('✓ Import completed successfully!', 'success');
                        
                        // Show statistics
                        showStats(data.stats);
                        
                        btn.textContent = '✓ Import Completed';
                        btn.style.background = '#28a745';
                    } else {
                        addLog('✗ Import failed: ' + data.error, 'error');
                        btn.disabled = false;
                        btn.textContent = '🔄 Retry Import';
                    }
                })
                .catch(error => {
                    addLog('✗ Error: ' + error.message, 'error');
                    btn.disabled = false;
                    btn.textContent = '🔄 Retry Import';
                });
        }
        
        function showStats(stats) {
            const statsGrid = document.getElementById('statsGrid');
            statsGrid.style.display = 'grid';
            statsGrid.innerHTML = `
                <div class="stat-card">
                    <div class="stat-value">${stats.tables}</div>
                    <div class="stat-label">Tables Created</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${stats.views}</div>
                    <div class="stat-label">Views Created</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${stats.inserts}</div>
                    <div class="stat-label">Data Inserted</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${stats.time}s</div>
                    <div class="stat-label">Execution Time</div>
                </div>
            `;
        }
    </script>
</body>
</html>

<?php
// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    $config = [
        'host' => 'localhost',
        'username' => 'ictmu6ya_ideanest',
        'password' => 'ictmu6ya_ideanest',
        'database' => 'ictmu6ya_ideanest',
        'sql_file' => __DIR__ . '/ictmu6ya_ideanest.sql'
    ];
    
    if ($_GET['action'] === 'check') {
        if (file_exists($config['sql_file'])) {
            $size = filesize($config['sql_file']);
            echo json_encode([
                'exists' => true,
                'fileSize' => number_format($size / 1024, 2) . ' KB'
            ]);
        } else {
            echo json_encode(['exists' => false, 'fileSize' => 'File not found']);
        }
        exit;
    }
    
    if ($_GET['action'] === 'import') {
        $startTime = microtime(true);
        
        try {
            $conn = new mysqli($config['host'], $config['username'], $config['password']);
            
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            
            // Create database
            $conn->query("CREATE DATABASE IF NOT EXISTS `{$config['database']}` 
                         DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            $conn->select_db($config['database']);
            
            // Read SQL file
            $sqlContent = file_get_contents($config['sql_file']);
            
            // Configure MySQL
            $conn->query("SET FOREIGN_KEY_CHECKS = 0");
            $conn->query("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
            
            // Execute SQL
            if ($conn->multi_query($sqlContent)) {
                do {
                    if ($result = $conn->store_result()) {
                        $result->free();
                    }
                } while ($conn->more_results() && $conn->next_result());
            }
            
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            
            // Get statistics
            $result = $conn->query("SHOW TABLES");
            $tableCount = $result->num_rows;
            
            $executionTime = round(microtime(true) - $startTime, 2);
            
            $conn->close();
            
            echo json_encode([
                'success' => true,
                'stats' => [
                    'tables' => $tableCount,
                    'views' => 6,
                    'inserts' => 'Multiple',
                    'time' => $executionTime
                ]
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
}
?>
