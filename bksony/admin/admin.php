<?php
// Database connection
$servername = "localhost";
$username = "root"; // As specified
$password = ""; // Empty password
$dbname = "ideanest"; // As specified

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create the users table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','moderator','admin') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($create_table_sql) === FALSE) {
    die("Error creating table: " . $conn->error);
}

// Initialize variables
$message = "";
$records = [];
$edit_id = "";
$edit_name = "";
$edit_email = "";
$edit_role = "";

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add new user
    if (isset($_POST['add_user'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        
        $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $name, $email, $password, $role);
        
        if ($stmt->execute()) {
            $message = "User added successfully";
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
    
    // Update user
    if (isset($_POST['update_user'])) {
        $id = $_POST['user_id'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        
        $sql = "UPDATE users SET name=?, email=?, role=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $name, $email, $role, $id);
        
        if ($stmt->execute()) {
            $message = "User updated successfully";
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
    
    // Delete user
    if (isset($_POST['delete_user'])) {
        $id = $_POST['user_id'];
        
        $sql = "DELETE FROM users WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = "User deleted successfully";
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get user for editing
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $sql = "SELECT * FROM users WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $edit_name = $row['name'];
        $edit_email = $row['email'];
        $edit_role = $row['role'];
    }
    $stmt->close();
}

// Fetch all users
$sql = "SELECT id, name, email, role, created_at FROM users ORDER BY id DESC";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IdeaNest Admin Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
    .sidebar {
        min-height: 100vh;
        background-color: #212529;
    }

    .sidebar .nav-link {
        color: rgba(255, 255, 255, 0.8);
    }

    .sidebar .nav-link:hover {
        color: #fff;
    }

    .sidebar .nav-link.active {
        color: #fff;
        background-color: rgba(255, 255, 255, 0.1);
    }

    .main-content {
        padding: 20px;
    }

    .alert-dismissible {
        cursor: pointer;
    }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="d-flex align-items-center justify-content-center mb-4">
                        <h3 class="text-white">IdeaNest Admin</h3>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="bi bi-people me-2"></i>Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="bi bi-lightbulb me-2"></i>Ideas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="bi bi-file-text me-2"></i>Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="bi bi-gear me-2"></i>Settings
                            </a>
                        </li>
                        <li class="nav-item mt-5">
                            <a class="nav-link" href="#">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
                            data-bs-target="#addUserModal">
                            <i class="bi bi-person-plus"></i> Add New User
                        </button>
                    </div>
                </div>

                <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Users</h6>
                                        <h3 class="card-text"><?php echo count($records); ?></h3>
                                    </div>
                                    <i class="bi bi-people fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Active Ideas</h6>
                                        <h3 class="card-text">27</h3>
                                    </div>
                                    <i class="bi bi-lightbulb fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Pending Reviews</h6>
                                        <h3 class="card-text">12</h3>
                                    </div>
                                    <i class="bi bi-hourglass-split fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Completed Projects</h6>
                                        <h3 class="card-text">35</h3>
                                    </div>
                                    <i class="bi bi-check-circle fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">User Management</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($records)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No users found</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($records as $record): ?>
                                    <tr>
                                        <td><?php echo $record['id']; ?></td>
                                        <td><?php echo htmlspecialchars($record['name']); ?></td>
                                        <td><?php echo htmlspecialchars($record['email']); ?></td>
                                        <td>
                                            <?php if ($record['role'] == 'admin'): ?>
                                            <span class="badge bg-danger">Admin</span>
                                            <?php elseif ($record['role'] == 'moderator'): ?>
                                            <span class="badge bg-warning">Moderator</span>
                                            <?php else: ?>
                                            <span class="badge bg-info">User</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $record['created_at']; ?></td>
                                        <td>
                                            <a href="?edit=<?php echo $record['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                                data-bs-target="#deleteModal<?php echo $record['id']; ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>

                                            <!-- Delete Modal -->
                                            <div class="modal fade" id="deleteModal<?php echo $record['id']; ?>"
                                                tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel">Confirm Delete
                                                            </h5>
                                                            <button type="button" class="btn-close"
                                                                data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            Are you sure you want to delete user:
                                                            <strong><?php echo htmlspecialchars($record['name']); ?></strong>?
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">Cancel</button>
                                                            <form method="post">
                                                                <input type="hidden" name="user_id"
                                                                    value="<?php echo $record['id']; ?>">
                                                                <button type="submit" name="delete_user"
                                                                    class="btn btn-danger">Delete</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="user">User</option>
                                <option value="moderator">Moderator</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <?php if (!empty($edit_id)): ?>
    <div class="modal fade show" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel"
        style="display: block; background: rgba(0,0,0,0.5);" aria-modal="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <a href="?" type="button" class="btn-close" aria-label="Close"></a>
                </div>
                <div class="modal-body">
                    <form method="post">
                        <input type="hidden" name="user_id" value="<?php echo $edit_id; ?>">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name"
                                value="<?php echo htmlspecialchars($edit_name); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email"
                                value="<?php echo htmlspecialchars($edit_email); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_role" class="form-label">Role</label>
                            <select class="form-select" id="edit_role" name="role" required>
                                <option value="user" <?php echo ($edit_role == 'user') ? 'selected' : ''; ?>>User
                                </option>
                                <option value="moderator" <?php echo ($edit_role == 'moderator') ? 'selected' : ''; ?>>
                                    Moderator</option>
                                <option value="admin" <?php echo ($edit_role == 'admin') ? 'selected' : ''; ?>>Admin
                                </option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <a href="?" class="btn btn-secondary">Cancel</a>
                            <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap 5 JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Auto-show edit modal
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($edit_id)): ?>
        var editModal = document.getElementById('editUserModal');
        if (editModal) {
            new bootstrap.Modal(editModal).show();
        }
        <?php endif; ?>

        // Auto-close alerts after 5 seconds
        var alertList = document.querySelectorAll('.alert-dismissible');
        alertList.forEach(function(alert) {
            setTimeout(function() {
                var closeButton = alert.querySelector('.btn-close');
                if (closeButton) {
                    closeButton.click();
                }
            }, 5000);
        });
    });
    </script>
</body>

</html>