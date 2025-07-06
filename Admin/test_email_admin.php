<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Admin Email Functionality Test</h2>";

// Include the project notification file
include "project_notification.php";

// Test the email function directly
echo "<h3>Testing Email Function:</h3>";

// Test with a sample project ID (you can change this to a real project ID)
$test_project_id = 1; // Change this to an actual project ID from your database

echo "<p>Testing email for project ID: $test_project_id</p>";

// Test approved email
echo "<h4>Testing Approved Email:</h4>";
$email_options = [
    'subject' => 'Test: Your Project Has Been Approved',
    'custom_text' => 'This is a test email to verify the admin email functionality.',
    'include_project_details' => true
];

$result = sendProjectStatusEmail($test_project_id, 'approved', '', $email_options);

if ($result['success']) {
    echo "<p style='color: green;'>✅ Approved email test: " . $result['message'] . "</p>";
} else {
    echo "<p style='color: red;'>❌ Approved email test failed: " . $result['message'] . "</p>";
}

// Test rejected email
echo "<h4>Testing Rejected Email:</h4>";
$email_options = [
    'subject' => 'Test: Update About Your Project',
    'custom_text' => 'This is a test email to verify the admin email functionality.',
    'include_project_details' => true
];

$result = sendProjectStatusEmail($test_project_id, 'rejected', 'Test rejection reason for email verification', $email_options);

if ($result['success']) {
    echo "<p style='color: green;'>✅ Rejected email test: " . $result['message'] . "</p>";
} else {
    echo "<p style='color: red;'>❌ Rejected email test failed: " . $result['message'] . "</p>";
}

echo "<h3>Test Complete!</h3>";
echo "<p>Check your email inbox for test messages.</p>";
echo "<p><strong>Note:</strong> Make sure to change the project ID to a real one from your database for actual testing.</p>";
?> 