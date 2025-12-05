<?php
/**
 * Add DROP TABLE IF EXISTS statements to SQL file
 */

$inputFile = __DIR__ . '/ictmu6ya_ideanest.sql';
$outputFile = __DIR__ . '/ictmu6ya_ideanest_fixed.sql';

echo "Reading SQL file...\n";
$content = file_get_contents($inputFile);

// Remove the DROP DATABASE statement
$content = preg_replace('/DROP DATABASE IF EXISTS .*?;/i', '', $content);

// Add DROP TABLE IF EXISTS before each CREATE TABLE
$content = preg_replace_callback(
    '/CREATE TABLE `([^`]+)`/i',
    function($matches) {
        $tableName = $matches[1];
        return "DROP TABLE IF EXISTS `$tableName`;\n\nCREATE TABLE `$tableName`";
    },
    $content
);

// Add DROP VIEW IF EXISTS before each CREATE VIEW
$content = preg_replace_callback(
    '/(CREATE ALGORITHM.*?VIEW `([^`]+)`)/is',
    function($matches) {
        $viewName = $matches[2];
        return "DROP VIEW IF EXISTS `$viewName`;\n\n" . $matches[1];
    },
    $content
);

echo "Writing fixed SQL file...\n";
file_put_contents($outputFile, $content);

echo "✓ Fixed SQL file created: $outputFile\n";
echo "File size: " . number_format(filesize($outputFile)) . " bytes\n";
?>
