<?php
require_once __DIR__ . '/../../includes/security_init.php';
// Include the database configuration file
//require_once 'con.php';

// Initialize variables to store form data
$erNumber = $projectName = $projectType = $classification = $description = "";
$error_message = $success_message = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input data
    $erNumber = trim(filter_input(INPUT_POST, 'erNumber', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $projectName = trim(filter_input(INPUT_POST, 'projectName', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $projectType = trim(filter_input(INPUT_POST, 'projectType', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $classification = trim(filter_input(INPUT_POST, 'classification', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

    // Validate required fields
    if (empty($erNumber) || empty($projectName) || empty($projectType) || empty($classification) || empty($description)) {
        $error_message = "All fields are required. Please fill in all the required information.";
    } else {
        try {
            // Create database connection using function from con.php
            $conn = createDBConnection();

            if ($conn === false) {
                throw new Exception("Database connection failed. Please try again later.");
            }

            // Prepare the SQL statement using prepared statements to prevent SQL injection
            $stmt = $conn->prepare("INSERT INTO blog (er_number, project_name, project_type, classification, description, submission_date) 
                              VALUES (?, ?, ?, ?, ?, NOW())");

            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . $conn->error);
            }

            // Bind parameters to the prepared statement
            $stmt->bind_param("sssss", $erNumber, $projectName, $projectType, $classification, $description);

            // Execute the statement
            if ($stmt->execute()) {
                $project_id = $conn->insert_id;
                $success_message = "Project submitted successfully! Your project ID is: " . $project_id;

                // Clear form data after successful submission
                $erNumber = $projectName = $projectType = $classification = $description = "";
            } else {
                throw new Exception("Error executing query: " . $stmt->error);
            }

            // Close statement and connection
            $stmt->close();
            $conn->close();
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// The HTML form will be included in the main file, so no need to output it here
