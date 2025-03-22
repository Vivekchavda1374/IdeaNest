<?php
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

// Initialize variables
$message = "";
$message_type = "";

// Check if user is logged in (You would need a proper session handling system)
$user_id = 1; // This is just a placeholder. In a real system, you'd get this from the session

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_project'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    
    // Basic validation
    if (empty($title) || empty($description)) {
        $message = "Please fill in all fields";
        $message_type = "danger";
    } else {
        // Insert project into the database
        $sql = "INSERT INTO projects (title, description, user_id, status) VALUES (?, ?, ?, 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $title, $description, $user_id);
        
        if ($stmt->execute()) {
            $message = "Project submitted successfully! It will be reviewed by our team.";
            $message_type = "success";
            // Clear form fields after successful submission
            $title = "";
            $description = "";
        } else {
            $message = "Error: " . $stmt->error;
            $message_type = "danger";
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Project - IdeaNest</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
    body {
        background-color: #f8f9fa;
    }

    .form-container {
        max-width: 800px;
        margin: 50px auto;
        padding: 30px;
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    }

    .header-icon {
        font-size: 3rem;
        color: #0d6efd;
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="form-container">
            <div class="text-center mb-4">
                <i class="bi bi-lightbulb header-icon"></i>
                <h2 class="mt-3">Submit Your Project</h2>
                <p class="text-muted">Share your innovative idea with the IdeaNest community</p>
            </div>

            <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <form method="post" class="needs-validation" novalidate>
                <div class="mb-4">
                    <label for="title" class="form-label">Project Title</label>
                    <input type="text" class="form-control form-control-lg" id="title" name="title"
                        placeholder="Enter a descriptive title for your project"
                        value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" required>
                    <div class="invalid-feedback">
                        Please provide a project title.
                    </div>
                </div>

                <div class="mb-4">
                    <label for="description" class="form-label">Project Description</label>
                    <textarea class="form-control" id="description" name="description" rows="8"
                        placeholder="Describe your project, its goals, target audience, and how it can be implemented"
                        required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                    <div class="invalid-feedback">
                        Please provide a project description.
                    </div>
                    <div class="form-text">
                        Be as detailed as possible. This will help our team understand your project better.
                    </div>
                </div>

                <div class="mb-4 d-grid">
                    <button type="submit" name="submit_project" class="btn btn-primary btn-lg">
                        <i class="bi bi-send me-2"></i> Submit Project
                    </button>
                </div>

                <div class="text-center text-muted">
                    <small>Your project will be reviewed by our team and you will be notified once it's
                        approved.</small>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap 5 JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Example starter JavaScript for disabling form submissions if there are invalid fields
    (function() {
        'use strict';

        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        var forms = document.querySelectorAll('.needs-validation');

        // Loop over them and prevent submission
        Array.prototype.slice.call(forms)
            .forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }

                    form.classList.add('was-validated');
                }, false);
            });
    })();

    // Auto-close alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
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