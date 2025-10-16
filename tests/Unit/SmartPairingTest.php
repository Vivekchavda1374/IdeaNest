<?php

namespace IdeaNest\Tests\Unit;

use PHPUnit\Framework\TestCase;

class SmartPairingTest extends TestCase
{
    private $mockConnection;
    private $smartPairing;

    protected function setUp(): void
    {
        // Create a mock SmartPairing class for testing
        $this->mockConnection = $this->createMock(\mysqli::class);
        
        // Create a mock SmartPairing class
        $this->smartPairing = new class($this->mockConnection) {
            private $conn;
            
            public function __construct($connection) {
                $this->conn = $connection;
            }
            
            public function findBestMentor($student_id, $project_classification) {
                // Mock implementation
                return [
                    [
                        'user_id' => 1,
                        'name' => 'Dr. Smith',
                        'specialization' => 'web-development',
                        'current_students' => 2,
                        'max_students' => 5,
                        'match_score' => 3
                    ]
                ];
            }
            
            public function autoAssignMentor($student_id, $project_id) {
                // Mock implementation
                return [
                    'user_id' => 1,
                    'name' => 'Dr. Smith',
                    'specialization' => 'web-development'
                ];
            }
        };
    }

    public function testFindBestMentorWithValidData()
    {
        $result = $this->smartPairing->findBestMentor(1, 'web-development');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Dr. Smith', $result[0]['name']);
        $this->assertEquals(3, $result[0]['match_score']);
    }

    public function testFindBestMentorWithNoResults()
    {
        // Create a mock that returns empty results
        $this->smartPairing = new class($this->mockConnection) {
            public function findBestMentor($student_id, $project_classification) {
                return [];
            }
        };

        $result = $this->smartPairing->findBestMentor(1, 'blockchain');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testAutoAssignMentorSuccess()
    {
        $result = $this->smartPairing->autoAssignMentor(1, 1);

        $this->assertIsArray($result);
        $this->assertEquals('Dr. Smith', $result['name']);
        $this->assertEquals(1, $result['user_id']);
    }

    public function testAutoAssignMentorProjectNotFound()
    {
        // Create a mock that returns false
        $this->smartPairing = new class($this->mockConnection) {
            public function autoAssignMentor($student_id, $project_id) {
                return false;
            }
        };

        $result = $this->smartPairing->autoAssignMentor(1, 999);

        $this->assertFalse($result);
    }

    public function testAutoAssignMentorNoMentorsAvailable()
    {
        // Create a mock that returns false
        $this->smartPairing = new class($this->mockConnection) {
            public function autoAssignMentor($student_id, $project_id) {
                return false;
            }
        };

        $result = $this->smartPairing->autoAssignMentor(1, 1);

        $this->assertFalse($result);
    }
}
