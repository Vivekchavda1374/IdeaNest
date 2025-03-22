<?php
// Initialize variables for form processing
$message = '';
$messageType = '';

// Database connection
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

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $user_name = $_POST['user_name'];
    $project_id = $_POST['project_id'];
    $classification = $_POST['classification'];
    
    // Default status is pending for new submissions
    $status = "pending";
    
    // Get current date for submission_date
    $submission_date = date("Y-m-d H:i:s");
    
    // Handle file uploads
    $uploadOk = true;
    $target_dir = "uploads/";
    
    // Make sure the upload directory exists
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Make sure the photos directory exists
    if (!file_exists($target_dir . "photos/")) {
        mkdir($target_dir . "photos/", 0777, true);
    }
    
    // Process code file upload
    $code_file = "";
    if(isset($_FILES["code_file"]) && $_FILES["code_file"]["error"] == 0) {
        $code_file = basename($_FILES["code_file"]["name"]);
        $target_file = $target_dir . $code_file;
        
        // Check if file already exists
        if (file_exists($target_file)) {
            $code_file = time() . "_" . $code_file; // Add timestamp to make filename unique
            $target_file = $target_dir . $code_file;
        }
        
        // Try to upload file
        if (!move_uploaded_file($_FILES["code_file"]["tmp_name"], $target_file)) {
            $message = "Sorry, there was an error uploading your code file.";
            $messageType = "danger";
            $uploadOk = false;
        }
    } else {
        $message = "Code file is required.";
        $messageType = "danger";
        $uploadOk = false;
    }
    
    // Process guide files upload
    $guide_files = "";
    if(isset($_FILES["guide_files"]) && $_FILES["guide_files"]["error"] == 0) {
        $guide_files = basename($_FILES["guide_files"]["name"]);
        $target_file = $target_dir . $guide_files;
        
        // Check if file already exists
        if (file_exists($target_file)) {
            $guide_files = time() . "_" . $guide_files; // Add timestamp to make filename unique
            $target_file = $target_dir . $guide_files;
        }
        
        // Try to upload file
        if (!move_uploaded_file($_FILES["guide_files"]["tmp_name"], $target_file)) {
            $message = "Sorry, there was an error uploading your guide files.";
            $messageType = "danger";
            $uploadOk = false;
        }
    }
    
    // Process project photos uploads
    $project_photos = array();
    if(isset($_FILES["project_photos"])) {
        // Multiple files were uploaded
        $total_photos = count($_FILES["project_photos"]["name"]);
        
        for($i = 0; $i < $total_photos; $i++) {
            if($_FILES["project_photos"]["error"][$i] == 0) {
                $photo_name = basename($_FILES["project_photos"]["name"][$i]);
                $target_file = $target_dir . "photos/" . $photo_name;
                
                // Check if file already exists
                if (file_exists($target_file)) {
                    $photo_name = time() . "_" . $photo_name; // Add timestamp to make filename unique
                    $target_file = $target_dir . "photos/" . $photo_name;
                }
                
                // Try to upload file
                if (move_uploaded_file($_FILES["project_photos"]["tmp_name"][$i], $target_file)) {
                    $project_photos[] = $photo_name;
                } else {
                    $message = "Sorry, there was an error uploading one of your project photos.";
                    $messageType = "danger";
                    $uploadOk = false;
                }
            }
        }
    }
    
    // Convert project photos array to comma-separated string
    $project_photos_str = implode(",", $project_photos);
    
    // If files uploaded successfully, insert data into database
    if($uploadOk) {
        // Prepare SQL statement to insert data
        $stmt = $conn->prepare("INSERT INTO user_submited_projects (user_name, project_id, classicifation, submission_date, code_file, status, project_photos, guide_files) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $user_name, $project_id, $classification, $submission_date, $code_file, $status, $project_photos_str, $guide_files);
        
        // Execute the statement
        if($stmt->execute()) {
            $message = "Project submitted successfully! Your submission will be reviewed.";
            $messageType = "success";
            
            // Clear form data after successful submission
            $user_name = $project_id = $classification = "";
        } else {
            $message = "Error: " . $stmt->error;
            $messageType = "danger";
        }
        
        $stmt->close();
    }
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Project</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
    .form-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }

    .required-field::after {
        content: " *";
        color: red;
    }

    .preview-image {
        max-width: 100px;
        max-height: 100px;
        margin: 5px;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 5px;
    }

    #imagePreview {
        display: flex;
        flex-wrap: wrap;
        margin-top: 10px;
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="form-container">
            <h1 class="mb-4">Submit New Project</h1>

            <?php if(!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="user_name" class="form-label required-field">Your Name</label>
                        <input type="text" class="form-control" id="user_name" name="user_name" required
                            value="<?php echo isset($user_name) ? htmlspecialchars($user_name) : ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="project_id" class="form-label required-field">Project ID</label>
                        <input type="text" class="form-control" id="project_id" name="project_id" required
                            value="<?php echo isset($project_id) ? htmlspecialchars($project_id) : ''; ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="classification" class="form-label required-field">Classification</label>
                    <select class="form-select" id="classification" name="classification" required>
                        <option value="" disabled <?php echo !isset($classification) ? 'selected' : ''; ?>>Select a
                            classification</option>
                        <option value="Beginner"
                            <?php echo (isset($classification) && $classification == 'Beginner') ? 'selected' : ''; ?>>
                            Beginner</option>
                        <option value="Intermediate"
                            <?php echo (isset($classification) && $classification == 'Intermediate') ? 'selected' : ''; ?>>
                            Intermediate</option>
                        <option value="Advanced"
                            <?php echo (isset($classification) && $classification == 'Advanced') ? 'selected' : ''; ?>>
                            Advanced</option>
                        <option value="Expert"
                            <?php echo (isset($classification) && $classification == 'Expert') ? 'selected' : ''; ?>>
                            Expert</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="code_file" class="form-label required-field">Code File</label>
                    <input type="file" class="form-control" id="code_file" name="code_file" required>
                    <small class="text-muted">Upload your project code file (ZIP, RAR, or other file formats).</small>
                </div>

                <div class="mb-3">
                    <label for="guide_files" class="form-label">Guide Files (Optional)</label>
                    <input type="file" class="form-control" id="guide_files" name="guide_files">
                    <small class="text-muted">Upload documentation or guide files (PDF, DOC, etc.)</small>
                </div>

                <div class="mb-3">
                    <label for="project_photos" class="form-label">Project Photos (Optional)</label>
                    <input type="file" class="form-control" id="project_photos" name="project_photos[]" multiple
                        accept="image/*" onchange="previewImages()">
                    <small class="text-muted">Upload screenshots or photos of your project (up to 5 images).</small>
                    <div id="imagePreview"></div>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="terms" required>
                        <label class="form-check-label" for="terms">
                            I confirm that this is my original work and I have the rights to submit it.
                        </label>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="reset" class="btn btn-secondary">Clear Form</button>
                    <button type="submit" class="btn btn-primary">Submit Project</button>
                </div>
            </form>

            <div class="mt-4 text-center">
                <a href="view_projects.php" class="btn btn-outline-primary">View All Projects</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Function to preview images before upload
    function previewImages() {
        var preview = document.getElementById('imagePreview');
        preview.innerHTML = ''; // Clear previous previews

        var files = document.getElementById('project_photos').files;

        if (files.length > 5) {
            alert('You can only upload a maximum of 5 images.');
            document.getElementById('project_photos').value = '';
            return;
        }

        for (var i = 0; i < files.length; i++) {
            var file = files[i];

            // Only process image files
            if (!file.type.match('image.*')) {
                continue;
            }

            var img = document.createElement('img');
            img.classList.add('preview-image');
            img.file = file;
            preview.appendChild(img);

            var reader = new FileReader();
            reader.onload = (function(aImg) {
                return function(e) {
                    aImg.src = e.target.result;
                };
            })(img);

            reader.readAsDataURL(file);
        }
    }
    </script>
</body>

</html>