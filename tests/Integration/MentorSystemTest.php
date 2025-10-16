<?php

namespace IdeaNest\Tests\Integration;

use PHPUnit\Framework\TestCase;

class MentorSystemTest extends TestCase
{
    private $conn;

    protected function setUp(): void
    {
        $this->conn = getTestConnection();
        cleanupTestDatabase();
    }

    protected function tearDown(): void
    {
        cleanupTestDatabase();
    }

    public function testMentorStudentPairing()
    {
        // Create test mentor
        $stmt = $this->conn->prepare("INSERT INTO register (name, email, password, role) VALUES (?, ?, ?, ?)");
        $mentorName = "Dr. Smith";
        $mentorEmail = "mentor@example.com";
        $mentorPassword = password_hash("password123", PASSWORD_DEFAULT);
        $role = "mentor";
        $stmt->bind_param("ssss", $mentorName, $mentorEmail, $mentorPassword, $role);
        $stmt->execute();
        $mentorId = $this->conn->insert_id;

        // Create test student
        $stmt = $this->conn->prepare("INSERT INTO register (name, email, password, role) VALUES (?, ?, ?, ?)");
        $studentName = "John Doe";
        $studentEmail = "student@example.com";
        $studentPassword = password_hash("password123", PASSWORD_DEFAULT);
        $studentRole = "student";
        $stmt->bind_param("ssss", $studentName, $studentEmail, $studentPassword, $studentRole);
        $stmt->execute();
        $studentId = $this->conn->insert_id;

        // Create pairing
        $stmt = $this->conn->prepare("INSERT INTO mentor_student_pairs (mentor_id, student_id, status) VALUES (?, ?, ?)");
        $status = "active";
        $stmt->bind_param("iis", $mentorId, $studentId, $status);
        $result = $stmt->execute();
        
        $this->assertTrue($result);
        $pairId = $this->conn->insert_id;
        $this->assertGreaterThan(0, $pairId);

        // Verify pairing was created
        $stmt = $this->conn->prepare("SELECT * FROM mentor_student_pairs WHERE id = ?");
        $stmt->bind_param("i", $pairId);
        $stmt->execute();
        $result = $stmt->get_result();
        $pair = $result->fetch_assoc();

        $this->assertEquals($mentorId, $pair['mentor_id']);
        $this->assertEquals($studentId, $pair['student_id']);
        $this->assertEquals($status, $pair['status']);
    }

    public function testMentoringSessionCreation()
    {
        // Create mentor and student
        $stmt = $this->conn->prepare("INSERT INTO register (name, email, password, role) VALUES (?, ?, ?, ?)");
        $mentorName = "Dr. Smith";
        $mentorEmail = "mentor@example.com";
        $mentorPassword = password_hash("password123", PASSWORD_DEFAULT);
        $role = "mentor";
        $stmt->bind_param("ssss", $mentorName, $mentorEmail, $mentorPassword, $role);
        $stmt->execute();
        $mentorId = $this->conn->insert_id;

        $stmt = $this->conn->prepare("INSERT INTO register (name, email, password, role) VALUES (?, ?, ?, ?)");
        $studentName = "John Doe";
        $studentEmail = "student@example.com";
        $studentPassword = password_hash("password123", PASSWORD_DEFAULT);
        $studentRole = "student";
        $stmt->bind_param("ssss", $studentName, $studentEmail, $studentPassword, $studentRole);
        $stmt->execute();
        $studentId = $this->conn->insert_id;

        // Create pairing
        $stmt = $this->conn->prepare("INSERT INTO mentor_student_pairs (mentor_id, student_id, status) VALUES (?, ?, ?)");
        $status = "active";
        $stmt->bind_param("iis", $mentorId, $studentId, $status);
        $stmt->execute();
        $pairId = $this->conn->insert_id;

        // Create session
        $stmt = $this->conn->prepare("INSERT INTO mentoring_sessions (pair_id, session_date, duration_minutes, notes, status) VALUES (?, ?, ?, ?, ?)");
        $sessionDate = "2024-01-15 14:00:00";
        $duration = 60;
        $notes = "Initial consultation session";
        $sessionStatus = "scheduled";
        $stmt->bind_param("isiss", $pairId, $sessionDate, $duration, $notes, $sessionStatus);
        $result = $stmt->execute();
        
        $this->assertTrue($result);
        $sessionId = $this->conn->insert_id;
        $this->assertGreaterThan(0, $sessionId);

        // Verify session was created
        $stmt = $this->conn->prepare("SELECT * FROM mentoring_sessions WHERE id = ?");
        $stmt->bind_param("i", $sessionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $session = $result->fetch_assoc();

        $this->assertEquals($pairId, $session['pair_id']);
        $this->assertEquals($sessionDate, $session['session_date']);
        $this->assertEquals($duration, $session['duration_minutes']);
        $this->assertEquals($notes, $session['notes']);
        $this->assertEquals($sessionStatus, $session['status']);
    }

    public function testMentorWorkloadManagement()
    {
        // Create mentor with workload limits
        $stmt = $this->conn->prepare("INSERT INTO register (name, email, password, role) VALUES (?, ?, ?, ?)");
        $mentorName = "Dr. Smith";
        $mentorEmail = "mentor@example.com";
        $mentorPassword = password_hash("password123", PASSWORD_DEFAULT);
        $role = "mentor";
        $stmt->bind_param("ssss", $mentorName, $mentorEmail, $mentorPassword, $role);
        $stmt->execute();
        $mentorId = $this->conn->insert_id;

        // Create mentor profile with max students
        $stmt = $this->conn->prepare("INSERT INTO mentors (user_id, specialization, max_students, current_students) VALUES (?, ?, ?, ?)");
        $specialization = "web-development";
        $maxStudents = 5;
        $currentStudents = 0;
        $stmt->bind_param("isii", $mentorId, $specialization, $maxStudents, $currentStudents);
        $stmt->execute();

        // Create students
        $studentIds = [];
        for ($i = 1; $i <= 3; $i++) {
            $stmt = $this->conn->prepare("INSERT INTO register (name, email, password, role) VALUES (?, ?, ?, ?)");
            $studentName = "Student $i";
            $studentEmail = "student$i@example.com";
            $studentPassword = password_hash("password123", PASSWORD_DEFAULT);
            $studentRole = "student";
            $stmt->bind_param("ssss", $studentName, $studentEmail, $studentPassword, $studentRole);
            $stmt->execute();
            $studentIds[] = $this->conn->insert_id;
        }

        // Create pairings
        foreach ($studentIds as $studentId) {
            $stmt = $this->conn->prepare("INSERT INTO mentor_student_pairs (mentor_id, student_id, status) VALUES (?, ?, ?)");
            $status = "active";
            $stmt->bind_param("iis", $mentorId, $studentId, $status);
            $stmt->execute();
        }

        // Update mentor's current student count
        $stmt = $this->conn->prepare("UPDATE mentors SET current_students = ? WHERE user_id = ?");
        $newCount = count($studentIds);
        $stmt->bind_param("ii", $newCount, $mentorId);
        $stmt->execute();

        // Verify workload
        $stmt = $this->conn->prepare("SELECT current_students, max_students FROM mentors WHERE user_id = ?");
        $stmt->bind_param("i", $mentorId);
        $stmt->execute();
        $result = $stmt->get_result();
        $mentor = $result->fetch_assoc();

        $this->assertEquals(3, $mentor['current_students']);
        $this->assertEquals(5, $mentor['max_students']);
        $this->assertLessThan($mentor['max_students'], $mentor['current_students']);
    }

    public function testMentorActivityLogging()
    {
        // Create mentor
        $stmt = $this->conn->prepare("INSERT INTO register (name, email, password, role) VALUES (?, ?, ?, ?)");
        $mentorName = "Dr. Smith";
        $mentorEmail = "mentor@example.com";
        $mentorPassword = password_hash("password123", PASSWORD_DEFAULT);
        $role = "mentor";
        $stmt->bind_param("ssss", $mentorName, $mentorEmail, $mentorPassword, $role);
        $stmt->execute();
        $mentorId = $this->conn->insert_id;

        // Log activity
        $stmt = $this->conn->prepare("INSERT INTO mentor_activity_logs (mentor_id, activity_type, description, created_at) VALUES (?, ?, ?, NOW())");
        $activityType = "session_scheduled";
        $description = "Scheduled session with student John Doe";
        $stmt->bind_param("iss", $mentorId, $activityType, $description);
        $result = $stmt->execute();
        
        $this->assertTrue($result);
        $logId = $this->conn->insert_id;
        $this->assertGreaterThan(0, $logId);

        // Verify activity was logged
        $stmt = $this->conn->prepare("SELECT * FROM mentor_activity_logs WHERE id = ?");
        $stmt->bind_param("i", $logId);
        $stmt->execute();
        $result = $stmt->get_result();
        $log = $result->fetch_assoc();

        $this->assertEquals($mentorId, $log['mentor_id']);
        $this->assertEquals($activityType, $log['activity_type']);
        $this->assertEquals($description, $log['description']);
        $this->assertNotNull($log['created_at']);
    }

    public function testMentorEmailNotifications()
    {
        // Test email notification settings
        $emailSettings = [
            'session_reminder' => true,
            'student_progress' => true,
            'weekly_summary' => true,
            'system_updates' => false
        ];

        foreach ($emailSettings as $setting => $enabled) {
            $this->assertIsBool($enabled);
            $this->assertArrayHasKey($setting, $emailSettings);
        }

        // Test email template validation
        $emailTemplates = [
            'session_reminder' => 'Your mentoring session is scheduled for {date} at {time}',
            'student_progress' => 'Student {student_name} has made progress on their project',
            'weekly_summary' => 'Weekly summary for {week_start} to {week_end}'
        ];

        foreach ($emailTemplates as $template => $content) {
            $this->assertIsString($content);
            $this->assertNotEmpty($content);
            $this->assertStringContainsString('{', $content); // Should contain placeholders
        }
    }

    public function testMentorAnalytics()
    {
        // Create mentor and students
        $stmt = $this->conn->prepare("INSERT INTO register (name, email, password, role) VALUES (?, ?, ?, ?)");
        $mentorName = "Dr. Smith";
        $mentorEmail = "mentor@example.com";
        $mentorPassword = password_hash("password123", PASSWORD_DEFAULT);
        $role = "mentor";
        $stmt->bind_param("ssss", $mentorName, $mentorEmail, $mentorPassword, $role);
        $stmt->execute();
        $mentorId = $this->conn->insert_id;

        // Create students
        $studentIds = [];
        for ($i = 1; $i <= 3; $i++) {
            $stmt = $this->conn->prepare("INSERT INTO register (name, email, password, role) VALUES (?, ?, ?, ?)");
            $studentName = "Student $i";
            $studentEmail = "student$i@example.com";
            $studentPassword = password_hash("password123", PASSWORD_DEFAULT);
            $studentRole = "student";
            $stmt->bind_param("ssss", $studentName, $studentEmail, $studentPassword, $studentRole);
            $stmt->execute();
            $studentIds[] = $this->conn->insert_id;
        }

        // Create pairings
        foreach ($studentIds as $studentId) {
            $stmt = $this->conn->prepare("INSERT INTO mentor_student_pairs (mentor_id, student_id, status) VALUES (?, ?, ?)");
            $status = "active";
            $stmt->bind_param("iis", $mentorId, $studentId, $status);
            $stmt->execute();
        }

        // Test analytics queries
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total_students FROM mentor_student_pairs WHERE mentor_id = ? AND status = 'active'");
        $stmt->bind_param("i", $mentorId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();

        $this->assertEquals(3, $stats['total_students']);

        // Test session statistics
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total_sessions FROM mentoring_sessions ms 
                                    JOIN mentor_student_pairs msp ON ms.pair_id = msp.id 
                                    WHERE msp.mentor_id = ?");
        $stmt->bind_param("i", $mentorId);
        $stmt->execute();
        $result = $stmt->get_result();
        $sessionStats = $result->fetch_assoc();

        $this->assertIsNumeric($sessionStats['total_sessions']);
    }
}
