<?php
// projects.php - Display user projects based on session ID

// Start the session to access session variables
session_start();

// Database connection details
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'ideanest';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header('Location: login.php');
    exit;
}

// Get user_id from session
$user_id = $_SESSION['user_id'];

// Connect to database
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Prepare SQL query to fetch ALL project columns
$sql = "SELECT * FROM projects WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Get user profile information
$user_sql = "SELECT * FROM projects WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Optional: Get the column names from the projects table
$columns_query = "SHOW COLUMNS FROM projects";
$columns_result = $conn->query($columns_query);
$columns = [];
if ($columns_result) {
    while ($col = $columns_result->fetch_assoc()) {
        $columns[] = $col['Field'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Projects</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    :root {
        --primary-color: #4a6fdc;
        --secondary-color: #f8f9fa;
        --accent-color: #3d5abb;
        --text-color: #333;
        --light-gray: #f5f5f5;
        --border-color: #e0e0e0;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        line-height: 1.6;
        margin: 0;
        padding: 0;
        background-color: var(--light-gray);
        color: var(--text-color);
    }

    .container {
        max-width: 1200px;
        margin: 2rem auto;
        background-color: #fff;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        border-bottom: 2px solid var(--border-color);
        padding-bottom: 1rem;
    }

    .header h1 {
        color: var(--primary-color);
        margin: 0;
        font-size: 2.2rem;
    }

    .user-profile {
        background-color: var(--secondary-color);
        padding: 1.5rem;
        border-radius: 10px;
        margin-bottom: 2rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        border-left: 5px solid var(--primary-color);
    }

    .user-profile h2 {
        margin-top: 0;
        color: var(--primary-color);
    }

    .user-info {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
    }

    .user-info-item {
        padding: 0.5rem 0;
    }

    .project-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
    }

    .project-item {
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 1.5rem;
        background-color: #fff;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        position: relative;
    }

    .project-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    }

    .project-title {
        margin-top: 0;
        color: var(--primary-color);
        border-bottom: 2px solid var(--border-color);
        padding-bottom: 0.5rem;
    }

    .project-details {
        margin: 1rem 0;
    }

    .project-detail {
        display: flex;
        margin-bottom: 0.5rem;
    }

    .project-detail i {
        width: 20px;
        margin-right: 0.5rem;
        color: var(--primary-color);
    }

    .project-date {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 1rem;
    }

    .project-status {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: bold;
        margin-bottom: 1rem;
    }

    .status-active {
        background-color: #e6f7e6;
        color: #28a745;
    }

    .status-pending {
        background-color: #fff3cd;
        color: #ffc107;
    }

    .status-completed {
        background-color: #cce5ff;
        color: #0d6efd;
    }

    .project-actions {
        margin-top: 1rem;
        display: flex;
        gap: 0.5rem;
    }

    .btn {
        padding: 0.5rem 1rem;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        transition: background-color 0.2s;
    }

    .btn i {
        margin-right: 0.5rem;
    }

    .btn-primary {
        background-color: var(--primary-color);
        color: white;
    }

    .btn-primary:hover {
        background-color: var(--accent-color);
    }

    .btn-outline {
        border: 1px solid var(--primary-color);
        color: var(--primary-color);
    }

    .btn-outline:hover {
        background-color: rgba(74, 111, 220, 0.1);
    }

    .logout-btn {
        background-color: #f44336;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        font-weight: 500;
    }

    .logout-btn i {
        margin-right: 0.5rem;
    }

    .create-project-btn {
        background-color: var(--primary-color);
        color: white;
        text-decoration: none;
        padding: 0.5rem 1rem;
        border-radius: 5px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
    }

    .create-project-btn i {
        margin-right: 0.5rem;
    }

    .no-projects {
        text-align: center;
        padding: 3rem;
        background-color: var(--secondary-color);
        border-radius: 10px;
        border: 1px dashed var(--border-color);
    }

    .no-projects h2 {
        color: var(--primary-color);
    }

    .no-projects .create-project-btn {
        margin-top: 1rem;
        display: inline-flex;
    }

    .details-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }

    .details-table th,
    .details-table td {
        padding: 8px;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
    }

    .details-table th {
        background-color: var(--secondary-color);
        font-weight: 600;
    }

    /* For smaller screens */
    @media (max-width: 768px) {
        .container {
            padding: 1rem;
            margin: 1rem;
        }

        .project-list {
            grid-template-columns: 1fr;
        }

        .details-table,
        .details-table th,
        .details-table td {
            display: block;
        }

        .details-table th {
            text-align: left;
        }

        .details-table td {
            text-align: left;
            border-bottom: 0;
            border-left: 3px solid var(--primary-color);
        }

        .details-table td:before {
            content: attr(data-label);
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }

        .details-table tr {
            margin-bottom: 1rem;
            display: block;
            border-bottom: 2px solid var(--border-color);
        }
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-project-diagram"></i> My Projects</h1>
            <div>
                <a href="create-project.php" class="create-project-btn">
                    <i class="fas fa-plus"></i> New Project
                </a>
                <!-- <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a> -->
            </div>
        </div>

        <?php if ($user): ?>
        <div class="user-profile">
            <h2><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user['username'] ?? 'User'); ?>'s
                Profile</h2>
            <div class="user-info">
                <?php if (isset($user['email'])): ?>
                <div class="user-info-item">
                    <i class="fas fa-envelope"></i> <strong>Email:</strong>
                    <?php echo htmlspecialchars($user['email']); ?>
                </div>
                <?php endif; ?>

                <?php if (isset($user['join_date'])): ?>
                <div class="user-info-item">
                    <i class="fas fa-calendar-alt"></i> <strong>Member since:</strong>
                    <?php echo date('F j, Y', strtotime($user['join_date'])); ?>
                </div>
                <?php endif; ?>

                <?php if (isset($user['role'])): ?>
                <div class="user-info-item">
                    <i class="fas fa-user-tag"></i> <strong>Role:</strong>
                    <?php echo htmlspecialchars($user['role']); ?>
                </div>
                <?php endif; ?>

                <div class="user-info-item">
                    <i class="fas fa-folder"></i> <strong>Total Projects:</strong> <?php echo $result->num_rows; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($result->num_rows > 0): ?>
        <div class="project-list">
            <?php while ($row = $result->fetch_assoc()): ?>
            <div class="project-item">
                <h3 class="project-title"><?php echo htmlspecialchars($row['project_name'] ?? 'Untitled Project'); ?>
                </h3>

                <?php if (isset($row['status'])): 
                    $status_class = '';
                    switch(strtolower($row['status'])) {
                        case 'active':
                            $status_class = 'status-active';
                            break;
                        case 'pending':
                            $status_class = 'status-pending';
                            break;
                        case 'completed':
                            $status_class = 'status-completed';
                            break;
                        default:
                            $status_class = '';
                    }
                ?>
                <span class="project-status <?php echo $status_class; ?>">
                    <?php echo htmlspecialchars($row['status']); ?>
                </span>
                <?php endif; ?>

                <!-- Display common columns in a nice layout -->
                <div class="project-details">
                    <?php if (isset($row['description']) && !empty($row['description'])): ?>
                    <div class="project-detail">
                        <i class="fas fa-align-left"></i>
                        <div><?php echo htmlspecialchars($row['description']); ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($row['created_at'])): ?>
                    <div class="project-detail">
                        <i class="far fa-calendar-plus"></i>
                        <div>Created: <?php echo date('F j, Y', strtotime($row['created_at'])); ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($row['updated_at'])): ?>
                    <div class="project-detail">
                        <i class="far fa-calendar-check"></i>
                        <div>Updated: <?php echo date('F j, Y', strtotime($row['updated_at'])); ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($row['deadline'])): ?>
                    <div class="project-detail">
                        <i class="far fa-clock"></i>
                        <div>Deadline: <?php echo date('F j, Y', strtotime($row['deadline'])); ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($row['priority'])): ?>
                    <div class="project-detail">
                        <i class="fas fa-flag"></i>
                        <div>Priority: <?php echo htmlspecialchars($row['priority']); ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($row['category'])): ?>
                    <div class="project-detail">
                        <i class="fas fa-tag"></i>
                        <div>Category: <?php echo htmlspecialchars($row['category']); ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($row['team_size'])): ?>
                    <div class="project-detail">
                        <i class="fas fa-users"></i>
                        <div>Team Size: <?php echo htmlspecialchars($row['team_size']); ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($row['budget'])): ?>
                    <div class="project-detail">
                        <i class="fas fa-dollar-sign"></i>
                        <div>Budget: <?php echo htmlspecialchars($row['budget']); ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($row['client_id'])): ?>
                    <div class="project-detail">
                        <i class="fas fa-user-tie"></i>
                        <div>Client ID: <?php echo htmlspecialchars($row['client_id']); ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($row['manager_id'])): ?>
                    <div class="project-detail">
                        <i class="fas fa-user-cog"></i>
                        <div>Manager ID: <?php echo htmlspecialchars($row['manager_id']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Show ALL remaining columns in a table format -->
                <h4 style="margin-top: 1.5rem; color: var(--primary-color);">All Project Details</h4>
                <table class="details-table">
                    <tr>
                        <th>Field</th>
                        <th>Value</th>
                    </tr>
                    <?php foreach ($row as $key => $value): 
                        // Skip already displayed fields to avoid duplication
                        if (in_array($key, ['id', 'project_name', 'description', 'status', 'created_at', 'updated_at', 'deadline', 'priority', 'category', 'team_size', 'budget', 'client_id', 'manager_id'])) {
                            continue;
                        }
                    ?>
                    <tr>
                        <td data-label="Field"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $key))); ?>
                        </td>
                        <td data-label="Value">
                            <?php 
                            // Format the value based on its apparent type
                            if (is_null($value)) {
                                echo '<em>Not set</em>';
                            } elseif (empty($value)) {
                                echo '<em>Empty</em>';
                            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/', $value)) {
                                // Looks like a date
                                echo date('F j, Y g:i A', strtotime($value));
                            } else {
                                echo htmlspecialchars($value);
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>

                <div class="project-actions">

                    <a href="edit-project.php?id=<?php echo $row['id']; ?>" class="btn btn-outline">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="no-projects">
            <i class="fas fa-folder-open" style="font-size: 3rem; color: #aaa; margin-bottom: 1rem;"></i>
            <h2>No projects found</h2>
            <p>You haven't created any projects yet. Get started by creating your first project.</p>
            <a href="create-project.php" class="create-project-btn">
                <i class="fas fa-plus"></i> Create Your First Project
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php
    // Close database connection
    $stmt->close();
    $user_stmt->close();
    $conn->close();
    ?>
</body>

</html>