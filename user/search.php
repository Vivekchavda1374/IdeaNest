<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../Login/Login/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo '<p class="text-center py-2">Please log in to search</p>';
    exit();
}

// Get the search query
$searchTerm = isset($_GET['query']) ? $_GET['query'] : '';

// Sanitize the input to prevent SQL injection
$searchTerm = '%' . $conn->real_escape_string($searchTerm) . '%';

// Array to store results
$results = array();

// Search in projects table
$projectSql = "SELECT id, project_name, description FROM projects WHERE project_name LIKE ? OR description LIKE ? LIMIT 5";
$stmt = $conn->prepare($projectSql);
$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();
$projectResult = $stmt->get_result();

if ($projectResult->num_rows > 0) {
    while ($row = $projectResult->fetch_assoc()) {
        $results[] = array(
            'type' => 'project',
            'id' => $row['id'],
            'title' => $row['project_name'],
            'description' => substr($row['description'], 0, 100) . '...'
        );
    }
}

// Search in blog/ideas table
$blogSql = "SELECT id, project_name, description FROM blog WHERE project_name LIKE ? OR description LIKE ? LIMIT 5";
$stmt = $conn->prepare($blogSql);
$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();
$blogResult = $stmt->get_result();

if ($blogResult->num_rows > 0) {
    while ($row = $blogResult->fetch_assoc()) {
        $results[] = array(
            'type' => 'idea',
            'id' => $row['id'],
            'title' => $row['project_name'],
            'description' => substr($row['description'], 0, 100) . '...'
        );
    }
}

// Close statement and connection
$stmt->close();
$conn->close();

// Display results
if (count($results) > 0) {
    echo '<div class="row g-4 mb-4 align-items-stretch">';
    echo '<div class="col-12 col-md-6 d-flex">';
    echo '<div class="list-group list-group-flush">';

    foreach ($results as $result) {
        $icon = ($result['type'] == 'project') ? 'project-diagram' : 'file-alt';
        $link = ($result['type'] == 'project') ? 'projects_view.php?id=' . $result['id'] : './Blog/view-project.php?id=' . $result['id'];

        echo '<a href="' . $link . '" class="list-group-item list-group-item-action py-2">';
        echo '<div class="d-flex align-items-center">';
        echo '<div class="me-3"><i class="fas fa-' . $icon . ' text-primary"></i></div>';
        echo '<div>';
        echo '<h6 class="mb-0">' . htmlspecialchars($result['title']) . '</h6>';
        echo '<small class="text-muted">' . htmlspecialchars($result['description']) . '</small>';
        echo '<div class="small mt-1"><span class="badge bg-light text-dark">' . ucfirst($result['type']) . '</span></div>';
        echo '</div>';
        echo '</div>';
        echo '</a>';
    }

    echo '</div>';
    echo '</div>';
    echo '<div class="col-12 col-md-6 d-flex">';
    echo '<div class="list-group list-group-flush">';

    foreach ($results as $result) {
        $icon = ($result['type'] == 'project') ? 'project-diagram' : 'file-alt';
        $link = ($result['type'] == 'project') ? 'projects_view.php?id=' . $result['id'] : './Blog/view-project.php?id=' . $result['id'];

        echo '<a href="' . $link . '" class="list-group-item list-group-item-action py-2">';
        echo '<div class="d-flex align-items-center">';
        echo '<div class="me-3"><i class="fas fa-' . $icon . ' text-primary"></i></div>';
        echo '<div>';
        echo '<h6 class="mb-0">' . htmlspecialchars($result['title']) . '</h6>';
        echo '<small class="text-muted">' . htmlspecialchars($result['description']) . '</small>';
        echo '<div class="small mt-1"><span class="badge bg-light text-dark">' . ucfirst($result['type']) . '</span></div>';
        echo '</div>';
        echo '</div>';
        echo '</a>';
    }

    echo '</div>';
    echo '</div>';
    echo '</div>';
} else {
    echo '<p class="text-center py-3">No results found for "' . htmlspecialchars(trim($_GET['query'])) . '"</p>';
    echo '<p class="text-center small text-muted">Try different keywords or check your spelling</p>';
}
?>