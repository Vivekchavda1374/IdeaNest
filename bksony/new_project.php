<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ideanest";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$messageType = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $project_name = $_POST['project_name'];
    $project_type = $_POST['project_type'];
    $classification = isset($_POST['software_classification']) ? $_POST['software_classification'] : (isset($_POST['hardware_classification']) ? $_POST['hardware_classification'] : null);
    $description = $_POST['description'];
    $language = $_POST['language'];

    function uploadFile($file, $folder) {
        if (!empty($file['name'])) {
            $target_dir = "uploads/$folder/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $target_file = $target_dir . basename($file["name"]);
            move_uploaded_file($file["tmp_name"], $target_file);
            return $target_file;
        }
        return null;
    }

    $image_path = uploadFile($_FILES['images'], "images");
    $video_path = uploadFile($_FILES['videos'], "videos");
    $code_file_path = uploadFile($_FILES['code_file'], "code_files");
    $instruction_file_path = uploadFile($_FILES['instruction_file'], "instructions");

    $sql = "INSERT INTO projects (project_name, project_type, classification, description, language, image_path, video_path, code_file_path, instruction_file_path) 
            VALUES ('$project_name', '$project_type', '$classification', '$description', '$language', '$image_path', '$video_path', '$code_file_path', '$instruction_file_path')";

    if ($conn->query($sql) === TRUE) {
        $message = "Project submitted successfully!";
        $messageType = "success";
    } else {
        $message = "Error: " . $conn->error;
        $messageType = "danger";
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Submission</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
    body {
        background-color: rgb(220, 222, 225);
    }

    .container {
        max-width: 700px;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        margin-top: 40px;
        transition: 0.5s;
    }

    .container:hover {
        box-shadow: 0px 1px 10px 1px rgba(0, 0, 0, 0.2);
    }

    .hidden {
        display: none;
    }
    </style>
</head>

<body>
    <div class="container">
        <h3 class="text-center mb-4">Submit Your Project</h3>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Project Name</label>
                <input type="text" class="form-control" name="project_name" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Project Type</label>
                <select class="form-select" name="project_type" id="projectType" required
                    onchange="toggleProjectType()">
                    <option value="">Select Type</option>
                    <option value="software">Software</option>
                    <option value="hardware">Hardware</option>
                </select>
            </div>
            <div class="mb-3 hidden" id="softwareOptions">
                <label class="form-label">Software Classification</label>
                <select class="form-select" name="software_classification">
                    <option value="web">Web Application</option>
                    <option value="mobile">Mobile Application</option>
                    <option value="desktop">Desktop Software</option>
                    <option value="embedded">Embedded Software</option>
                </select>
            </div>
            <div class="mb-3 hidden" id="hardwareOptions">
                <label class="form-label">Hardware Classification</label>
                <select class="form-select" name="hardware_classification">
                    <option value="iot">IoT Device</option>
                    <option value="robotics">Robotics</option>
                    <option value="electronics">Electronics Circuit</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="4" required></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Programming Language</label>
                <input type="text" class="form-control" name="language" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Upload Image</label>
                <input type="file" class="form-control" name="images" accept="image/*">
            </div>
            <div class="mb-3">
                <label class="form-label">Upload Video</label>
                <input type="file" class="form-control" name="videos" accept="video/*">
            </div>
            <div class="mb-3">
                <label class="form-label">Upload Code File</label>
                <input type="file" class="form-control" name="code_file" accept=".zip,.rar,.tar,.gz">
            </div>
            <div class="mb-3">
                <label class="form-label">Upload Instructions</label>
                <input type="file" class="form-control" name="instruction_file" accept=".txt,.pdf,.docx">
            </div>
            <button type="submit" class="btn btn-primary w-100">Submit Project</button>
        </form>
    </div>

    <?php if (!empty($message)) { ?>
    <div class="modal fade show" style="display: block;" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-<?php echo $messageType; ?>">Message</h5>
                    <button type="button" class="btn-close" onclick="window.location.href='';"></button>
                </div>
                <div class="modal-body">
                    <p><?php echo $message; ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>

    <script>
    function toggleProjectType() {
        document.getElementById("softwareOptions").classList.toggle("hidden", document.getElementById("projectType")
            .value !== "software");
        document.getElementById("hardwareOptions").classList.toggle("hidden", document.getElementById("projectType")
            .value !== "hardware");
    }
    </script>
</body>

</html>