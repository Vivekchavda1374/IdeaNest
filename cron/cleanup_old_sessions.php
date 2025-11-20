<?php
/**
 * Cleanup Old Sessions
 * Removes completed sessions older than 90 days
 */

require_once dirname(__DIR__) . '/Login/Login/db.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting session cleanup...\n";

// Archive old completed sessions
$archiveQuery = "INSERT INTO mentoring_sessions_archive 
    SELECT * FROM mentoring_sessions 
    WHERE status = 'completed' 
    AND session_date < DATE_SUB(NOW(), INTERVAL 90 DAY)";

$result = $conn->query($archiveQuery);
$archived = $conn->affected_rows;

echo "Archived $archived old sessions.\n";

// Delete archived sessions from main table
$deleteQuery = "DELETE FROM mentoring_sessions 
    WHERE status = 'completed' 
    AND session_date < DATE_SUB(NOW(), INTERVAL 90 DAY)";

$conn->query($deleteQuery);
$deleted = $conn->affected_rows;

echo "Deleted $deleted sessions from main table.\n";

// Update missed sessions
$missedQuery = "UPDATE mentoring_sessions 
    SET status = 'missed' 
    WHERE status = 'scheduled' 
    AND session_date < DATE_SUB(NOW(), INTERVAL 2 HOUR)";

$conn->query($missedQuery);
$missed = $conn->affected_rows;

echo "Marked $missed sessions as missed.\n";

echo "[" . date('Y-m-d H:i:s') . "] Cleanup complete!\n";
?>
