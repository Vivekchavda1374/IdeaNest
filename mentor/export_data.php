<?php

session_start();
require_once '../Login/Login/db.php';

if (!isset($_SESSION['mentor_id'])) {
    header('Location: ../Login/Login/login.php');
    exit;
}

$mentor_id = $_SESSION['mentor_id'];

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="mentor_data_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// Export mentor sessions data
try {
    // Check if user is a mentor
    $mentor_check = "SELECT id FROM register WHERE id = ? AND role = 'mentor'";
    $stmt = $conn->prepare($mentor_check);
    $stmt->bind_param("i", $mentor_id);
    $stmt->execute();
    $is_mentor = $stmt->get_result()->num_rows > 0;

    fputcsv($output, ['Date', 'Student', 'Duration (min)', 'Status', 'Notes']);

    if (!$is_mentor) {
        fputcsv($output, ['Access Denied', 'User is not a mentor', '', '', '']);
    } else {
        $query = "SELECT ms.session_date, r.name as student_name, ms.duration_minutes, ms.status, ms.notes
                  FROM mentoring_sessions ms
                  JOIN mentor_student_pairs msp ON ms.pair_id = msp.id
                  JOIN register r ON msp.student_id = r.id
                  WHERE msp.mentor_id = ?
                  ORDER BY ms.session_date DESC";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $mentor_id);
        $stmt->execute();
        $sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (empty($sessions)) {
            fputcsv($output, ['No sessions found', 'Create sessions first', '', '', '']);
        } else {
            foreach ($sessions as $session) {
                fputcsv($output, [
                    date('Y-m-d H:i', strtotime($session['session_date'])),
                    $session['student_name'],
                    $session['duration_minutes'],
                    ucfirst($session['status']),
                    $session['notes']
                ]);
            }
        }
    }
} catch (Exception $e) {
    fputcsv($output, ['Error', 'Database error occurred', '', '', '']);
}

fclose($output);
