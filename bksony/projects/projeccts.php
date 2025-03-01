<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "ideanest";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM projects";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Listings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="projects_styles.css">

    <!-- <style>
    body {
        background-color: rgb(217, 224, 232);
    }

    .card {
        margin: 15px 0;
    }

    .card img {
        height: 200px;
        object-fit: cover;
    }
    </style> -->
</head>

<body>
    <div class="container mt-4">
        <h2 class="text-center mb-4">Projects</h2>
        <div class="row">
            <?php while ($row = $result->fetch_assoc()): ?>
            <div class="col-md-6">
                <div class="card">
                    <img src="<?php echo (!empty($row['image_path']) && file_exists('uploads/' . $row['image_path'])) ? 'uploads/' . $row['image_path'] : 'uploads/default-image.jpg'; ?>"
                        class="card-img-top" alt="Project Image">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($row['project_name']); ?></h5>
                        <p class="card-text"><strong>Type:</strong>
                            <?php echo htmlspecialchars($row['project_type']); ?></p>
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                        <?php if (!empty($row['video_path'])): ?>
                        <video width="100%" height="150" controls>
                            <source src="uploads/<?php echo htmlspecialchars($row['video_path']); ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                        <?php endif; ?>
                        <?php if (!empty($row['instruction_file_path'])): ?>
                        <a href="uploads/<?php echo htmlspecialchars($row['instruction_file_path']); ?>"
                            class="btn btn-primary btn-sm mt-2" download>Download Instructions</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php $conn->close(); ?>