<?php
include '../Login/Login/db.php';

$project_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($project_id <= 0) {
    header("Location: projects_view.php");
    exit;
}

$sql = "SELECT * FROM projects WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {

    header("Location: projects_view.php");
    exit;
}

$project = $result->fetch_assoc();

$related_sql = "SELECT id, project_name, project_type, language FROM projects 
                WHERE project_type = ? AND id != ? 
                ORDER BY submission_date DESC LIMIT 3";
$related_stmt = $conn->prepare($related_sql);
$related_stmt->bind_param("si", $project['project_type'], $project_id);
$related_stmt->execute();
$related_result = $related_stmt->get_result();
?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($project['project_name']); ?> | IdeaNest</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
       <link rel="stylesheet" href="../assets/css/project_details.css">
    </head>

    <body>

    <div class="hero-section">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="projects_view.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Projects
                </a>

<!--                --><?php
//                $statusClass = 'status-pending';
//                if ($project['project_status'] == 'Approved') {
//                    $statusClass = 'status-approved';
//                } elseif ($project['project_status'] == 'Rejected') {
//                    $statusClass = 'status-rejected';
//                }
//                ?>
<!--                <span class="status-badge --><?php //echo $statusClass; ?><!--" style="background-color: rgba(255, 255, 255, 0.2);">-->
<!--                    --><?php //echo htmlspecialchars($project['project_status']); ?>
                </span>
            </div>

            <h1 class="display-5 fw-bold"><?php echo htmlspecialchars($project['project_name']); ?></h1>

            <div class="d-flex gap-2 mt-3">
                <span class="badge badge-project-type">
                    <?php echo htmlspecialchars($project['project_type']); ?>
                </span>
                <span class="badge badge-language">
                    <?php echo htmlspecialchars($project['language']); ?>
                </span>
                <?php if (!empty($project['classification'])): ?>
                    <span class="badge" style="background-color: rgba(255, 255, 255, 0.2);">
                        <?php echo htmlspecialchars($project['classification']); ?>
                    </span>
                <?php endif; ?>
            </div>

            <div class="mt-3 text-white-50">
                <i class="far fa-calendar-alt me-2"></i>
                Submitted on <?php echo date('F d, Y', strtotime($project['submission_date'])); ?>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row">
            <div class="col-lg-8">

                <div class="project-card">
                    <div class="details-section">
                        <h3 class="section-title">Project Description</h3>
                        <div class="description-content">
                            <?php echo nl2br(htmlspecialchars($project['description'])); ?>
                        </div>
                    </div>

                    <?php if (!empty($project['video_path']) && file_exists('uploads/' . $project['video_path'])): ?>
                        <div class="details-section">
                            <h3 class="section-title">Project Demo</h3>
                            <div class="video-container">
                                <video width="100%" controls>
                                    <source src="uploads/<?php echo htmlspecialchars($project['video_path']); ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($project['additional_info'])): ?>
                        <div class="details-section">
                            <h3 class="section-title">Additional Information</h3>
                            <div class="description-content">
                                <?php echo nl2br(htmlspecialchars($project['additional_info'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex gap-3 mt-4">
                        <?php if (!empty($project['instruction_file_path']) && file_exists('uploads/' . $project['instruction_file_path'])): ?>
                            <a href="uploads/<?php echo htmlspecialchars($project['instruction_file_path']); ?>"
                               class="btn btn-download" download>
                                <i class="fas fa-download"></i> Download Instructions
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($project['code_file_path']) && file_exists('uploads/' . $project['code_file_path'])): ?>
                            <a href="uploads/<?php echo htmlspecialchars($project['code_file_path']); ?>"
                               class="btn btn-code" download>
                                <i class="fas fa-code"></i> Download Code
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Sidebar Content -->
                <div class="project-card">
                    <h3 class="section-title">Project Details</h3>

                        <div class="mb-3">
                            <div class="meta-info">
                                <i class="fas fa-user meta-icon"></i>
                                <strong>Code-File:</strong>
                                <span>
                                <?php if (!empty($project['code_file_path'])): ?>
                                    <a href="<?php echo htmlspecialchars($project['code_file_path']); ?>"
                                       class="btn btn-success btn-sm"
                                       download>
                                       <i class="fas fa-download"></i> Download
                                    </a>
                                <?php else: ?>
                                    Not specified
                                <?php endif; ?>
                                </span>
                        </div>


                        <div class="meta-info">
                            <i class="fas fa-code-branch meta-icon"></i>
                            <strong>Language:</strong>
                            <span><?php echo htmlspecialchars($project['language']); ?></span>
                        </div>

                        <?php if (!empty($project['framework'])): ?>
                            <div class="meta-info">
                                <i class="fas fa-layer-group meta-icon"></i>
                                <strong>Framework:</strong>
                                <span><?php echo htmlspecialchars($project['framework']); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($project['version'])): ?>
                            <div class="meta-info">
                                <i class="fas fa-code-branch meta-icon"></i>
                                <strong>Version:</strong>
                                <span><?php echo htmlspecialchars($project['version']); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($project['requirements'])): ?>
                            <div class="meta-info">
                                <i class="fas fa-list-check meta-icon"></i>
                                <strong>Requirements:</strong>
                                <span><?php echo htmlspecialchars($project['requirements']); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($project['github_link'])): ?>
                            <div class="meta-info">
                                <i class="fab fa-github meta-icon"></i>
                                <strong>GitHub:</strong>
                                <a href="<?php echo htmlspecialchars($project['github_link']); ?>" target="_blank">
                                    View Repository
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($related_result->num_rows > 0): ?>
                        <h3 class="section-title mt-4">Related Projects</h3>
                        <?php while ($related = $related_result->fetch_assoc()): ?>
                            <a href="project_details.php?id=<?php echo $related['id']; ?>" class="text-decoration-none">
                                <div class="related-card">
                                    <h5 class="mb-2"><?php echo htmlspecialchars($related['project_name']); ?></h5>
                                    <div class="d-flex gap-2">
                                        <span class="badge badge-project-type">
                                            <?php echo htmlspecialchars($related['project_type']); ?>
                                        </span>
                                        <span class="badge badge-language">
                                            <?php echo htmlspecialchars($related['language']); ?>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>

<?php
// Close database connections
$stmt->close();
$related_stmt->close();
$conn->close();
?>