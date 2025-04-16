<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = ""; // Empty password as per your request
$dbname = "ideanest";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set default active tab
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'active';

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_term = "%{$search}%";

// Handle user block action
if (isset($_POST['block_user'])) {
    $user_id = $_POST['user_id'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // First, get ALL current data from register table
        $get_stmt = $conn->prepare("SELECT * FROM register WHERE id = ?");
        $get_stmt->bind_param("i", $user_id);
        $get_stmt->execute();
        $result = $get_stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("User not found in active users list!");
        }
        
        $user_data = $result->fetch_assoc();
        
        // Check if enrollment number already exists in removed_user table
        if (!empty($user_data['enrollment_number'])) {
            $check_stmt = $conn->prepare("SELECT COUNT(*) AS count FROM removed_user WHERE enrollment_number = ?");
            $check_stmt->bind_param("s", $user_data['enrollment_number']);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $row = $check_result->fetch_assoc();
            
            if ($row['count'] > 0) {
                throw new Exception("User with enrollment number {$user_data['enrollment_number']} is already in the blocked users list!");
            }
        }
        
        // Delete from removed_user table if user with the same ID exists
        // This ensures we don't have ID conflicts when inserting
        $delete_check_stmt = $conn->prepare("DELETE FROM removed_user WHERE id = ?");
        $delete_check_stmt->bind_param("i", $user_id);
        $delete_check_stmt->execute();
        
        // Build dynamic SQL for inserting all columns
        $columns = array_keys($user_data);
        $column_names = implode(", ", $columns);
        $column_placeholders = str_repeat("?, ", count($columns) - 1) . "?";
        
        // Insert into removed_user table WITH THE SAME ID and ALL data from register
        $insert_sql = "INSERT INTO removed_user ($column_names) VALUES ($column_placeholders)";
        $insert_stmt = $conn->prepare($insert_sql);
        
        // Dynamic binding of parameters
        $param_type = "";
        $param_values = array();
        foreach ($user_data as $value) {
            if (is_int($value)) {
                $param_type .= "i";
            } elseif (is_double($value)) {
                $param_type .= "d";
            } else {
                $param_type .= "s";
            }
            $param_values[] = $value;
        }
        
        // Create reference array for bind_param
        $params = array();
        $params[] = &$param_type;
        foreach ($param_values as &$value) {
            $params[] = &$value;
        }
        
        // Call bind_param with dynamic parameters
        call_user_func_array(array($insert_stmt, 'bind_param'), $params);
        $insert_result = $insert_stmt->execute();
        
        if (!$insert_result) {
            throw new Exception("Failed to add user to blocked list: " . $conn->error);
        }
        
        // Then delete from register table
        $delete_stmt = $conn->prepare("DELETE FROM register WHERE id = ?");
        $delete_stmt->bind_param("i", $user_id);
        $delete_result = $delete_stmt->execute();
        
        if (!$delete_result) {
            throw new Exception("Failed to remove user from active list: " . $conn->error);
        }
        
        // Commit transaction
        $conn->commit();
        
        // Set success message
        $success_message = "User access removed successfully!";
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        $error_message = "Error: " . $e->getMessage();
    }
}

// Handle restore access action
if (isset($_POST['restore_access'])) {
    $user_id = $_POST['user_id'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // First, get ALL current data from removed_user table
        $get_stmt = $conn->prepare("SELECT * FROM removed_user WHERE id = ?");
        $get_stmt->bind_param("i", $user_id);
        $get_stmt->execute();
        $result = $get_stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("User not found in blocked users list!");
        }
        
        $user_data = $result->fetch_assoc();
        
        // Check if enrollment number already exists in register table
        if (!empty($user_data['enrollment_number'])) {
            $check_stmt = $conn->prepare("SELECT COUNT(*) AS count FROM register WHERE enrollment_number = ?");
            $check_stmt->bind_param("s", $user_data['enrollment_number']);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $row = $check_result->fetch_assoc();
            
            if ($row['count'] > 0) {
                throw new Exception("User with enrollment number {$user_data['enrollment_number']} is already in the active users list!");
            }
        }
        
        // Delete from register table if user with the same ID exists
        // This ensures we don't have ID conflicts when inserting
        $delete_check_stmt = $conn->prepare("DELETE FROM register WHERE id = ?");
        $delete_check_stmt->bind_param("i", $user_id);
        $delete_check_stmt->execute();
        
        // Build dynamic SQL for inserting all columns
        $columns = array_keys($user_data);
        $column_names = implode(", ", $columns);
        $column_placeholders = str_repeat("?, ", count($columns) - 1) . "?";
        
        // Insert back into register table WITH THE SAME ID and ALL data from removed_user
        $insert_sql = "INSERT INTO register ($column_names) VALUES ($column_placeholders)";
        $insert_stmt = $conn->prepare($insert_sql);
        
        // Dynamic binding of parameters
        $param_type = "";
        $param_values = array();
        foreach ($user_data as $value) {
            if (is_int($value)) {
                $param_type .= "i";
            } elseif (is_double($value)) {
                $param_type .= "d";
            } else {
                $param_type .= "s";
            }
            $param_values[] = $value;
        }
        
        // Create reference array for bind_param
        $params = array();
        $params[] = &$param_type;
        foreach ($param_values as &$value) {
            $params[] = &$value;
        }
        
        // Call bind_param with dynamic parameters
        call_user_func_array(array($insert_stmt, 'bind_param'), $params);
        $insert_result = $insert_stmt->execute();
        
        if (!$insert_result) {
            throw new Exception("Failed to restore user to active list: " . $conn->error);
        }
        
        // Then delete from removed_user table
        $delete_stmt = $conn->prepare("DELETE FROM removed_user WHERE id = ?");
        $delete_stmt->bind_param("i", $user_id);
        $delete_result = $delete_stmt->execute();
        
        if (!$delete_result) {
            throw new Exception("Failed to remove user from blocked list: " . $conn->error);
        }
        
        // Commit transaction
        $conn->commit();
        
        // Set success message
        $success_message = "User access restored successfully!";
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        $error_message = "Error: " . $e->getMessage();
    }
}

// We will not automatically add the unique constraint as it might cause issues
// Let's handle duplicate enrollment numbers at the application level instead

// Get all active users with search functionality
if (!empty($search)) {
    $active_users_sql = "SELECT id, name, email, enrollment_number, gr_number FROM register 
                        WHERE name LIKE ? OR gr_number LIKE ? OR enrollment_number LIKE ?";
    $stmt = $conn->prepare($active_users_sql);
    $stmt->bind_param("sss", $search_term, $search_term, $search_term);
    $stmt->execute();
    $active_users_result = $stmt->get_result();
} else {
    $active_users_sql = "SELECT id, name, email, enrollment_number, gr_number FROM register";
    $active_users_result = $conn->query($active_users_sql);
}

// Get all blocked users with search functionality
if (!empty($search)) {
    $blocked_users_sql = "SELECT id, name, email, enrollment_number, gr_number FROM removed_user 
                        WHERE name LIKE ? OR gr_number LIKE ? OR enrollment_number LIKE ?";
    $stmt = $conn->prepare($blocked_users_sql);
    $stmt->bind_param("sss", $search_term, $search_term, $search_term);
    $stmt->execute();
    $blocked_users_result = $stmt->get_result();
} else {
    $blocked_users_sql = "SELECT id, name, email, enrollment_number, gr_number FROM removed_user";
    $blocked_users_result = $conn->query($blocked_users_sql);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    .tab-pane {
        padding: 20px 0;
    }

    .nav-tabs .nav-link {
        font-weight: 500;
    }

    .nav-tabs .nav-link.active {
        border-bottom: 3px solid #0d6efd;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, .075);
    }

    .card {
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }

    .card-header {
        font-weight: 500;
    }

    .search-box {
        margin-bottom: 1.5rem;
    }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-user-shield me-2"></i>
                IdeaNest User Management
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Admin Dashboard</h5>
                    </div>
                    <div class="card-body">
                        <?php if(isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>

                        <?php if(isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>

                        <!-- Search Box -->
                        <div class="search-box">
                            <form class="row g-3" method="get">
                                <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                                <div class="col-md-9">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control" name="search"
                                            placeholder="Search by name, GR number or enrollment number"
                                            value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary w-100">Search</button>
                                    <?php if(!empty($search)): ?>
                                    <a href="?tab=<?php echo $active_tab; ?>"
                                        class="btn btn-outline-secondary w-100 mt-2">Clear Search</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>

                        <!-- Nav tabs -->
                        <ul class="nav nav-tabs" id="userTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo $active_tab == 'active' ? 'active' : ''; ?>"
                                    id="active-users-tab" data-bs-toggle="tab" data-bs-target="#active-users"
                                    type="button" role="tab" aria-controls="active-users"
                                    aria-selected="<?php echo $active_tab == 'active' ? 'true' : 'false'; ?>">
                                    <i class="fas fa-users me-2"></i>Active Users
                                    <?php if ($active_users_result->num_rows > 0): ?>
                                    <span
                                        class="badge bg-primary ms-1"><?php echo $active_users_result->num_rows; ?></span>
                                    <?php endif; ?>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo $active_tab == 'blocked' ? 'active' : ''; ?>"
                                    id="blocked-users-tab" data-bs-toggle="tab" data-bs-target="#blocked-users"
                                    type="button" role="tab" aria-controls="blocked-users"
                                    aria-selected="<?php echo $active_tab == 'blocked' ? 'true' : 'false'; ?>">
                                    <i class="fas fa-user-slash me-2"></i>Blocked Users
                                    <?php if ($blocked_users_result->num_rows > 0): ?>
                                    <span
                                        class="badge bg-danger ms-1"><?php echo $blocked_users_result->num_rows; ?></span>
                                    <?php endif; ?>
                                </button>
                            </li>
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content">
                            <!-- Active Users Tab -->
                            <div class="tab-pane fade <?php echo $active_tab == 'active' ? 'show active' : ''; ?>"
                                id="active-users" role="tabpanel" aria-labelledby="active-users-tab">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Enrollment Number</th>
                                                <th>GR Number</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if ($active_users_result->num_rows > 0) {
                                                while($row = $active_users_result->fetch_assoc()) {
                                                    echo "<tr>";
                                                    echo "<td>" . htmlspecialchars($row["id"]) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row["name"]) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row["email"]) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row["enrollment_number"]) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row["gr_number"]) . "</td>";
                                                    echo "<td>
                                                            <form method='post' action=''>
                                                                <input type='hidden' name='user_id' value='" . $row["id"] . "'>
                                                                <button type='submit' name='block_user' class='btn btn-danger btn-sm'>
                                                                    <i class='fas fa-ban me-1'></i> Remove Access
                                                                </button>
                                                            </form>
                                                        </td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                if (!empty($search)) {
                                                    echo "<tr><td colspan='6' class='text-center'>No active users found matching: '" . htmlspecialchars($search) . "'</td></tr>";
                                                } else {
                                                    echo "<tr><td colspan='6' class='text-center'>No active users found</td></tr>";
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Blocked Users Tab -->
                            <div class="tab-pane fade <?php echo $active_tab == 'blocked' ? 'show active' : ''; ?>"
                                id="blocked-users" role="tabpanel" aria-labelledby="blocked-users-tab">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Enrollment Number</th>
                                                <th>GR Number</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if ($blocked_users_result->num_rows > 0) {
                                                while($row = $blocked_users_result->fetch_assoc()) {
                                                    echo "<tr>";
                                                    echo "<td>" . htmlspecialchars($row["id"]) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row["name"]) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row["email"]) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row["enrollment_number"]) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row["gr_number"]) . "</td>";
                                                    echo "<td>
                                                            <form method='post' action=''>
                                                                <input type='hidden' name='user_id' value='" . $row["id"] . "'>
                                                                <button type='submit' name='restore_access' class='btn btn-success btn-sm'>
                                                                    <i class='fas fa-user-check me-1'></i> Restore Access
                                                                </button>
                                                            </form>
                                                        </td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                if (!empty($search)) {
                                                    echo "<tr><td colspan='6' class='text-center'>No blocked users found matching: '" . htmlspecialchars($search) . "'</td></tr>";
                                                } else {
                                                    echo "<tr><td colspan='6' class='text-center'>No blocked users found</td></tr>";
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // JavaScript to maintain the active tab and search parameters after form submission
    document.addEventListener('DOMContentLoaded', function() {
        // Get the stored tab from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');

        if (tabParam) {
            // If tab parameter exists in URL, activate that tab
            const tabToActivate = document.querySelector('#userTabs button[data-bs-target="#' + tabParam +
                '-users"]');
            if (tabToActivate) {
                const tab = new bootstrap.Tab(tabToActivate);
                tab.show();
            }
        }

        // Add event listeners to tabs to store the active tab
        const tabs = document.querySelectorAll('#userTabs button');
        tabs.forEach(function(tab) {
            tab.addEventListener('shown.bs.tab', function(event) {
                const targetId = event.target.getAttribute('data-bs-target').replace('#',
                        '')
                    .replace('-users', '');
                // Update URL without refreshing page, preserving search term
                const searchParam = urlParams.get('search') ? '&search=' + urlParams.get(
                    'search') : '';
                history.replaceState(null, null, '?tab=' + targetId + searchParam);
            });
        });

        // Make table rows clickable for better UX (optional)
        const tableRows = document.querySelectorAll('tbody tr');
        tableRows.forEach(function(row) {
            row.style.cursor = 'pointer';
            row.addEventListener('click', function(event) {
                // Prevent click on form elements from triggering the row click
                if (event.target.tagName !== 'BUTTON' && event.target.tagName !== 'I' &&
                    event
                    .target.tagName !== 'INPUT') {
                    // You could add functionality here like showing user details in a modal
                    // For now, let's just highlight the row
                    this.classList.add('table-primary');
                    setTimeout(() => {
                        this.classList.remove('table-primary');
                    }, 300);
                }
            });
        });
    });
    </script>
</body>

</html>