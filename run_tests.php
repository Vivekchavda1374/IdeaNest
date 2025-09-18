<?php
// Simple test runner for IdeaNest
echo "Running IdeaNest Test Suite...\n";
echo "================================\n";

// Change to tests directory
chdir(__DIR__ . '/tests');

// Include and run the test runner
require_once 'TestRunner.php';
?>