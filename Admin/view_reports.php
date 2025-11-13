<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: https://ictmu.in/hcd/IdeaNest/Login/Login/login.php");
    exit();
}

include '../Login/Login/db.php';

// Handle report status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $report_id = (int)$_POST['report_id'];
    $status = $_POST['status'];

    $update_sql = "UPDATE idea_reports SET status = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $status, $report_id);
    $update_stmt->execute();
    $update_stmt->close();

    header("Location: view_reports.php");
    exit();
}

// Get all reports with idea and user details
$reports_sql = "SELECT ir.*, b.project_name, b.er_number, r1.name as reporter_name, r2.name as idea_owner_name
                FROM idea_reports ir
                LEFT JOIN blog b ON ir.idea_id = b.id
                LEFT JOIN register r1 ON ir.reporter_id = r1.id
                LEFT JOIN register r2 ON b.user_id = r2.id
                ORDER BY ir.created_at DESC";
$reports_result = $conn->query($reports_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idea Reports - Admin Panel</title>
    <link rel="icon" type="image/png" href="../../assets/image/fevicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-flag me-2"></i>Idea Reports</h2>
                    <a href="admin.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to Admin
                    </a>
                </div>

                <?php if ($reports_result && $reports_result->num_rows > 0) : ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Idea</th>
                                    <th>Reporter</th>
                                    <th>Report Type</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($report = $reports_result->fetch_assoc()) : ?>
                                    <tr>
                                        <td><?php echo $report['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($report['project_name']); ?></strong><br>
                                            <small class="text-muted">ID: <?php echo htmlspecialchars($report['er_number']); ?></small><br>
                                            <small class="text-muted">Owner: <?php echo htmlspecialchars($report['idea_owner_name']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($report['reporter_name']); ?></td>
                                        <td>
                                            <span class="badge bg-warning">
                                                <?php echo ucfirst(str_replace('_', ' ', $report['report_type'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($report['description'])) : ?>
                                                <small><?php echo htmlspecialchars(substr($report['description'], 0, 100)); ?>
                                                <?php if (strlen($report['description']) > 100) :
                                                    ?>...<?php
                                                endif; ?>
                                                </small>
                                            <?php else : ?>
                                                <em class="text-muted">No description</em>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = [
                                                'pending' => 'bg-warning',
                                                'reviewed' => 'bg-info',
                                                'resolved' => 'bg-success'
                                            ];
                                            ?>
                                            <span class="badge <?php echo $status_class[$report['status']] ?? 'bg-secondary'; ?>">
                                                <?php echo ucfirst($report['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?php echo date('M d, Y H:i', strtotime($report['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                                    <?php if ($report['status'] !== 'reviewed') : ?>
                                                        <button type="submit" name="status" value="reviewed" class="btn btn-info btn-sm">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($report['status'] !== 'resolved') : ?>
                                                        <button type="submit" name="status" value="resolved" class="btn btn-success btn-sm">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else : ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No reports found.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>