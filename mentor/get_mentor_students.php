<?php
/**
 * Unified Mentor Student Retrieval
 * This file provides a single source of truth for getting mentor's students
 * Checks both mentor_requests and mentor_student_pairs tables
 */

function getMentorStudents($conn, $mentor_id) {
    $students = [];
    
    // Try mentor_requests first (newer system)
    $query = "SELECT 
                mr.id as pair_id,
                mr.student_id,
                mr.project_id,
                mr.created_at as paired_at,
                r.name as student_name,
                r.email as student_email,
                r.department,
                r.enrollment_number,
                p.project_name,
                p.classification,
                p.description,
                NULL as rating,
                NULL as feedback,
                'mentor_requests' as source_table
              FROM mentor_requests mr
              JOIN register r ON mr.student_id = r.id 
              LEFT JOIN projects p ON mr.project_id = p.id 
              WHERE mr.mentor_id = ? AND mr.status = 'accepted'";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $mentor_id);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Also check mentor_student_pairs (older system)
    $query2 = "SELECT 
                msp.id as pair_id,
                msp.student_id,
                msp.project_id,
                msp.paired_at,
                r.name as student_name,
                r.email as student_email,
                r.department,
                r.enrollment_number,
                p.project_name,
                p.classification,
                p.description,
                msp.rating,
                msp.feedback,
                'mentor_student_pairs' as source_table
              FROM mentor_student_pairs msp
              JOIN register r ON msp.student_id = r.id 
              LEFT JOIN projects p ON msp.project_id = p.id 
              WHERE msp.mentor_id = ? AND msp.status = 'active'";
    
    $stmt = $conn->prepare($query2);
    $stmt->bind_param("i", $mentor_id);
    $stmt->execute();
    $students2 = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Merge results, avoiding duplicates by student_id
    $student_ids = array_column($students, 'student_id');
    foreach ($students2 as $student) {
        if (!in_array($student['student_id'], $student_ids)) {
            $students[] = $student;
        }
    }
    
    return $students;
}

function getMentorSessions($conn, $mentor_id, $status = 'scheduled') {
    $sessions = [];
    
    // Try with mentor_student_pairs first
    $query = "SELECT 
                ms.*,
                r.name as student_name,
                p.project_name,
                TIMESTAMPDIFF(HOUR, NOW(), ms.session_date) as hours_until,
                'mentor_student_pairs' as source_table
              FROM mentoring_sessions ms
              JOIN mentor_student_pairs msp ON ms.pair_id = msp.id
              JOIN register r ON msp.student_id = r.id
              LEFT JOIN projects p ON msp.project_id = p.id
              WHERE msp.mentor_id = ? AND ms.status = ?
              ORDER BY ms.session_date ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $mentor_id, $status);
    $stmt->execute();
    $sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Also try with mentor_requests
    $query2 = "SELECT 
                ms.*,
                r.name as student_name,
                p.project_name,
                TIMESTAMPDIFF(HOUR, NOW(), ms.session_date) as hours_until,
                'mentor_requests' as source_table
              FROM mentoring_sessions ms
              JOIN mentor_requests mr ON ms.pair_id = mr.id
              JOIN register r ON mr.student_id = r.id
              LEFT JOIN projects p ON mr.project_id = p.id
              WHERE mr.mentor_id = ? AND mr.status = 'accepted' AND ms.status = ?
              ORDER BY ms.session_date ASC";
    
    $stmt = $conn->prepare($query2);
    $stmt->bind_param("is", $mentor_id, $status);
    $stmt->execute();
    $sessions2 = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Merge results, avoiding duplicates by session id
    $session_ids = array_column($sessions, 'id');
    foreach ($sessions2 as $session) {
        if (!in_array($session['id'], $session_ids)) {
            $sessions[] = $session;
        }
    }
    
    // Sort by session_date
    usort($sessions, function($a, $b) {
        return strtotime($a['session_date']) - strtotime($b['session_date']);
    });
    
    return $sessions;
}

function getMentorStats($conn, $mentor_id) {
    $stats = [
        'active_students' => 0,
        'completed_students' => 0,
        'avg_rating' => 0,
        'total_sessions' => 0
    ];
    
    // Count from mentor_requests
    $query = "SELECT COUNT(*) as count FROM mentor_requests WHERE mentor_id = ? AND status = 'accepted'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $mentor_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['active_students'] += $result['count'];
    $stmt->close();
    
    // Count from mentor_student_pairs (active)
    $query = "SELECT COUNT(*) as count FROM mentor_student_pairs WHERE mentor_id = ? AND status = 'active'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $mentor_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['active_students'] += $result['count'];
    $stmt->close();
    
    // Count completed
    $query = "SELECT COUNT(*) as count FROM mentor_student_pairs WHERE mentor_id = ? AND status = 'completed'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $mentor_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['completed_students'] = $result['count'];
    $stmt->close();
    
    // Get average rating
    $query = "SELECT AVG(rating) as avg_rating FROM mentor_student_pairs WHERE mentor_id = ? AND rating IS NOT NULL";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $mentor_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['avg_rating'] = $result['avg_rating'] ?? 0;
    $stmt->close();
    
    return $stats;
}
?>
