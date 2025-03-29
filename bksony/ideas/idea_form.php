<?php
// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Set the timezone for accurate datetime
    date_default_timezone_set('Asia/Kolkata'); // Change to your timezone

    // Collect form data
    // $erNumber = $_POST['er_number'];
    $projectName = $_POST['projectName'];
    $projectType = $_POST['projectType'];
    $classification = $_POST['classification'];
    $description = $_POST['description'];
    
    // Get current date and time
    $submissionDateTime = date('Y-m-d H:i:s');
    
    // Get the session ID
    $sessionId = session_id();
    
    // Validate the data
    if (empty($erNumber) || empty($projectName) || empty($projectType) || 
        empty($classification) || empty($description)) {
        $error_message = "Error: All fields are required";
    } else {
        // Database connection parameters
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "ideanest";
        
        // Create database connection
        $conn = new mysqli($servername, $username, $password, $dbname);
        
        // Check connection
        if ($conn->connect_error) {
            $error_message = "Connection failed: " . $conn->connect_error;
        } else {
            // Prepare and bind the SQL statement to prevent SQL injection
            $stmt = $conn->prepare("INSERT INTO projects (id, er_number, project_name, project_type, classification, description, submission_datetime) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $sessionId, $erNumber, $projectName, $projectType, $classification, $description, $submissionDateTime);
            
            // Execute the statement
            if ($stmt->execute()) {
                $success_message = "Project submitted successfully on " . $submissionDateTime;
                // Clear form data after successful submission
                $erNumber = $projectName = $projectType = $classification = $description = "";
            } else {
                $error_message = "Error: " . $stmt->error;
            }
            
            // Close statement and connection
            $stmt->close();
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Submission Form</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    .card {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .form-container {
        max-width: 800px;
        margin: 30px auto;
    }

    .submission-info {
        font-size: 0.9rem;
        color: #6c757d;
    }

    .alert {
        margin-bottom: 20px;
    }
    </style>
</head>

<body>
    <div class="container form-container">
        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <?php if (isset($success_message)): ?>
        <div class="alert alert-success" role="alert">
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">Project Submission Form</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="erNumber" class="form-label">ER Number of User</label>
                            <input type="text" class="form-control" id="erNumber" name="erNumber"
                                value="<?php echo isset($erNumber) ? htmlspecialchars($erNumber) : ''; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="projectName" class="form-label">Project Name</label>
                            <input type="text" class="form-control" id="projectName" name="projectName"
                                value="<?php echo isset($projectName) ? htmlspecialchars($projectName) : ''; ?>"
                                required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="projectType" class="form-label">Project Type</label>
                        <select class="form-select" id="projectType" name="projectType" required
                            onchange="updateClassifications()">
                            <option value="">Select Project Type</option>
                            <option value="software"
                                <?php echo (isset($projectType) && $projectType == 'software') ? 'selected' : ''; ?>>
                                Software</option>
                            <option value="hardware"
                                <?php echo (isset($projectType) && $projectType == 'hardware') ? 'selected' : ''; ?>>
                                Hardware</option>
                        </select>
                    </div>

                    <div class="mb-3" id="classificationsContainer">
                        <label for="classification" class="form-label">Classification</label>
                        <select class="form-select" id="classification" name="classification" required>
                            <option value="">Select Project Type First</option>
                            <?php if (isset($projectType) && $projectType == 'software'): ?>
                            <option value="webapp"
                                <?php echo (isset($classification) && $classification == 'webapp') ? 'selected' : ''; ?>>
                                Web App</option>
                            <option value="mobileapp"
                                <?php echo (isset($classification) && $classification == 'mobileapp') ? 'selected' : ''; ?>>
                                Mobile App</option>
                            <option value="desktopapp"
                                <?php echo (isset($classification) && $classification == 'desktopapp') ? 'selected' : ''; ?>>
                                Desktop App</option>
                            <option value="embeddedsystem"
                                <?php echo (isset($classification) && $classification == 'embeddedsystem') ? 'selected' : ''; ?>>
                                Embedded System</option>
                            <?php elseif (isset($projectType) && $projectType == 'hardware'): ?>
                            <option value="iotdevice"
                                <?php echo (isset($classification) && $classification == 'iotdevice') ? 'selected' : ''; ?>>
                                IOT Device</option>
                            <option value="robotics"
                                <?php echo (isset($classification) && $classification == 'robotics') ? 'selected' : ''; ?>>
                                Robotics</option>
                            <option value="electroniccircuit"
                                <?php echo (isset($classification) && $classification == 'electroniccircuit') ? 'selected' : ''; ?>>
                                Electronic Circuit</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5"
                            required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                    </div>

                    <div class="submission-info mb-3">
                        <p>Submission Date and Time will be automatically recorded by the system.</p>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Submit Project</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    function updateClassifications() {
        const projectType = document.getElementById('projectType').value;
        const classificationSelect = document.getElementById('classification');

        // Reset classification options
        classificationSelect.innerHTML = '';

        if (projectType === 'software') {
            const softwareOptions = [{
                    value: '',
                    text: 'Select Classification'
                },
                {
                    value: 'webapp',
                    text: 'Web App'
                },
                {
                    value: 'mobileapp',
                    text: 'Mobile App'
                },
                {
                    value: 'desktopapp',
                    text: 'Desktop App'
                },
                {
                    value: 'embeddedsystem',
                    text: 'Embedded System'
                }
            ];

            softwareOptions.forEach(option => {
                const optionElement = document.createElement('option');
                optionElement.value = option.value;
                optionElement.textContent = option.text;
                classificationSelect.appendChild(optionElement);
            });

        } else if (projectType === 'hardware') {
            const hardwareOptions = [{
                    value: '',
                    text: 'Select Classification'
                },
                {
                    value: 'iotdevice',
                    text: 'IOT Device'
                },
                {
                    value: 'robotics',
                    text: 'Robotics'
                },
                {
                    value: 'electroniccircuit',
                    text: 'Electronic Circuit'
                }
            ];

            hardwareOptions.forEach(option => {
                const optionElement = document.createElement('option');
                optionElement.value = option.value;
                optionElement.textContent = option.text;
                classificationSelect.appendChild(optionElement);
            });

        } else {
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Select Project Type First';
            classificationSelect.appendChild(defaultOption);
        }
    }

    // Run this on page load to ensure correct classification options are displayed
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('projectType').value) {
            updateClassifications();
        }
    });
    </script>
</body>

</html>