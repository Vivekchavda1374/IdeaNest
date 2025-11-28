<?php
require_once __DIR__ . '/../includes/security_init.php';
session_start();
require_once '../Login/Login/db.php';

if (!isset($_SESSION['mentor_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$mentor_id = $_SESSION['mentor_id'];
$format = $_GET['format'] ?? 'csv';

// Get session data from existing database structure
$sessions = [];
try {
    // First check if mentor exists in register table with mentor role
    $mentor_check = "SELECT id FROM register WHERE id = ? AND role = 'mentor'";
    $stmt = $conn->prepare($mentor_check);
    $stmt->bind_param("i", $mentor_id);
    $stmt->execute();
    $mentor_exists = $stmt->get_result()->num_rows > 0;

    if ($mentor_exists) {
        $query = "SELECT ms.*, r.name as student_name, p.project_name,
                  CASE ms.status 
                    WHEN 'scheduled' THEN 'Scheduled'
                    WHEN 'completed' THEN 'Completed'
                    WHEN 'cancelled' THEN 'Cancelled'
                  END as status_text
                  FROM mentoring_sessions ms
                  JOIN mentor_student_pairs msp ON ms.pair_id = msp.id
                  JOIN register r ON msp.student_id = r.id
                  LEFT JOIN projects p ON msp.project_id = p.id
                  WHERE msp.mentor_id = ?
                  ORDER BY ms.session_date DESC";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $mentor_id);
        $stmt->execute();
        $sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    $sessions = [];
}

if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="mentoring_sessions_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Student', 'Project', 'Duration (min)', 'Status', 'Notes', 'Meeting Link']);

    if (empty($sessions)) {
        fputcsv($output, ['No sessions found', '', '', '', '', '', '']);
    } else {
        foreach ($sessions as $session) {
            fputcsv($output, [
                date('Y-m-d H:i', strtotime($session['session_date'])),
                $session['student_name'],
                $session['project_name'] ?? 'General',
                $session['duration_minutes'],
                $session['status_text'],
                $session['notes'],
                $session['meeting_link'] ?? ''
            ]);
        }
    }

    fclose($output);
} else {
    header('Content-Type: application/json');
    echo json_encode($sessions ?: []);
}
