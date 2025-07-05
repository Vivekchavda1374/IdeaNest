<?php

include '../Login/Login/db.php';

// First, check if the table exists
$table_check_sql = "SHOW TABLES LIKE 'blog'";
$table_check_result = $conn->query($table_check_sql);

// If the table doesn't exist, create it with sample data
if ($table_check_result->num_rows == 0) {
    // Create table based on the specified structure
    $create_table_sql = "CREATE TABLE `blog` (
        `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
        `er_number` VARCHAR(50) NOT NULL,
        `project_name` VARCHAR(100) NOT NULL,
        `project_type` ENUM('software', 'hardware') NOT NULL,
        `classification` VARCHAR(50) NOT NULL,
        `description` TEXT NOT NULL,
        `submission_date` DATETIME NOT NULL,
        `status` ENUM('pending', 'in_progress', 'completed', 'rejected') DEFAULT 'pending',
        `priority11` ENUM('low', 'medium', 'high') DEFAULT 'medium',
        `assigned_to` VARCHAR(100) DEFAULT NULL,
        `completion_date` DATE DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";

    if ($conn->query($create_table_sql) === TRUE) {
        // Insert sample data
        $sample_data = [
            ['ER001', 'Online Marketplace', 'software', 'Web Application', 'E-commerce platform for digital products', '2024-03-15 09:00:00', 'in_progress', 'high', 'John Doe', '2024-04-15'],
            ['ER002', 'Fitness Tracker', 'software', 'Mobile Application', 'App for tracking workouts and nutrition', '2024-03-10 10:30:00', 'completed', 'medium', 'Jane Smith', '2024-03-25'],
            ['ER003', 'Video Editor Pro', 'software', 'Desktop Software', 'Professional video editing software', '2024-03-05 14:45:00', 'in_progress', 'medium', 'Mark Johnson', '2024-04-10'],
            ['ER004', 'Task Management System', 'software', 'Web Application', 'Project management tool for teams', '2024-03-20 11:15:00', 'pending', 'low', NULL, NULL],
            ['ER005', 'Language Learning App', 'software', 'Mobile Application', 'Mobile app for learning new languages', '2024-03-18 16:30:00', 'in_progress', 'medium', 'Emily Brown', '2024-04-20'],
            ['ER006', 'Smart Home Hub', 'hardware', 'IoT Device', 'Central control system for smart home devices', '2024-03-12 13:00:00', 'completed', 'high', 'David Wilson', '2024-03-30'],
            ['ER007', 'Automotive Control System', 'hardware', 'Embedded Software', 'Software for vehicle systems management', '2024-03-08 09:45:00', 'in_progress', 'high', 'Sarah Miller', '2024-04-05'],
            ['ER008', 'Warehouse Robot', 'hardware', 'Robotics', 'Automated inventory management robot', '2024-03-22 10:00:00', 'pending', 'medium', NULL, NULL],
            ['ER009', 'Cloud Storage Service', 'software', 'Web Application', 'File storage and sharing platform', '2024-03-25 15:15:00', 'pending', 'medium', NULL, NULL],
            ['ER010', 'Solar Power Controller', 'hardware', 'Electronics Circuit', 'Circuit for managing solar panel systems', '2024-03-14 08:30:00', 'completed', 'medium', 'Robert Taylor', '2024-03-28'],
            ['ER011', 'Food Delivery App', 'software', 'Mobile Application', 'App connecting customers with local restaurants', '2024-03-19 12:45:00', 'in_progress', 'high', 'Jennifer Lee', '2024-04-25'],
            ['ER012', 'Online Learning Platform', 'software', 'Web Application', 'Interactive educational website', '2024-03-16 14:00:00', 'in_progress', 'medium', 'Michael Brown', '2024-04-18']
        ];

        $insert_sql = "INSERT INTO `blog` (`er_number`, `project_name`, `project_type`, `classification`, `description`, `submission_date`, `status`, `priority1`, `assigned_to`, `completion_date`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);

        foreach ($sample_data as $data) {
            $stmt->bind_param("ssssssssss", $data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], $data[7], $data[8], $data[9]);
            $stmt->execute();
        }

        $stmt->close();
        echo "<div class='alert alert-success'>
                <i class='fas fa-check-circle'></i> Sample table 'blog' created with test data.
              </div>";
    } else {
        echo "<div class='alert alert-danger'>
                <i class='fas fa-exclamation-circle'></i> Error creating table: " . $conn->error . "
              </div>";
    }
}

// SQL query to get classification counts
$sql = "SELECT `classification`, COUNT(*) as `count` FROM `blog` GROUP BY `classification`";

try {
    $result = $conn->query($sql);

    if ($result === false) {
        throw new Exception("Query failed: " . $conn->error);
    }

    // Calculate total for percentage
    $total_count = 0;
    $classifications = [];

    if ($result->num_rows > 0) {
        // First loop to get total count
        while($row = $result->fetch_assoc()) {
            $total_count += $row["count"];
            $classifications[] = $row;
        }
    }

    // Get status counts for summary cards
    $status_sql = "SELECT `status`, COUNT(*) as `count` FROM `blog` GROUP BY `status`";
    $status_result = $conn->query($status_sql);
    $status_counts = [];

    if ($status_result && $status_result->num_rows > 0) {
        while($row = $status_result->fetch_assoc()) {
            $status_counts[$row["status"]] = $row["count"];
        }
    }

    // Get project type counts
    $type_sql = "SELECT `project_type`, COUNT(*) as `count` FROM `blog` GROUP BY `project_type`";
    $type_result = $conn->query($type_sql);
    $type_counts = [];

    if ($type_result && $type_result->num_rows > 0) {
        while($row = $type_result->fetch_assoc()) {
            $type_counts[$row["project_type"]] = $row["count"];
        }
    }

    // Get priority1 distribution
    $priority1_sql = "SELECT `priority1`, COUNT(*) as `count` FROM `blog` GROUP BY `priority1`";
    $priority1_result = $conn->query($priority1_sql);
    $priority1_counts = [];

    if ($priority1_result && $priority1_result->num_rows > 0) {
        while($row = $priority1_result->fetch_assoc()) {
            $priority1_counts[$row["priority1"]] = $row["count"];
        }
    }

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>
            <i class='fas fa-exclamation-circle'></i> Error: " . $e->getMessage() . "
          </div>";
}

// Define colors for different classification types
$colors = [
    "Web Application" => "#4285F4",
    "Mobile Application" => "#34A853",
    "Desktop Software" => "#FBBC05",
    "Embedded Software" => "#EA4335",
    "IoT Device" => "#8E24AA",
    "Robotics" => "#00ACC1",
    "Electronics Circuit" => "#FF6D00"
];

// Define colors for status types
$status_colors = [
    "pending" => "#FBBC05",
    "in_progress" => "#4285F4",
    "completed" => "#34A853",
    "rejected" => "#EA4335"
];

// Define colors for priority1
$priority1_colors = [
    "low" => "#8BC34A",
    "medium" => "#FFC107",
    "high" => "#F44336"
];

// Define colors for project types
$type_colors = [
    "software" => "#3F51B5",
    "hardware" => "#FF5722"
];

// Get the current date for display
$current_date = date("F j, Y");

// Close the connection
$conn->close();
?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>IdeaNest - Idea Dashboard</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            .glass-card {
                background: rgba(255,255,255,0.7);
                border-radius: 1.5rem;
                box-shadow: 0 8px 32px 0 rgba(58,134,255,0.10);
                backdrop-filter: blur(8px);
                border: 1px solid rgba(255,255,255,0.18);
                transition: transform 0.2s, box-shadow 0.2s;
                margin-bottom: 2rem;
            }
            .glass-card:hover {
                transform: translateY(-4px) scale(1.02);
                box-shadow: 0 16px 48px 0 rgba(58,134,255,0.18);
            }
            .stat-icon {
                width: 48px;
                height: 48px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.5rem;
                margin-right: 1rem;
                box-shadow: 0 2px 8px rgba(58,134,255,0.10);
            }
            .progress {
                height: 16px;
                border-radius: 8px;
                background: #e9ecef;
                overflow: hidden;
            }
            .progress-bar {
                border-radius: 8px;
                font-weight: 600;
                font-size: 0.95rem;
            }
        </style>
    </head>
<body>

    <div class="glass-card p-4" style="width:100%;">
        <h2 class="fw-bold mb-4" style="color: #8338ec;">Idea Classifications</h2>
        <?php if(isset($classifications) && count($classifications) > 0): ?>
            <?php foreach($classifications as $item): ?>
                <?php
                $percentage = ($item["count"] / $total_count) * 100;
                ?>
                <div class="mb-4">
                    <div class="d-flex align-items-center mb-2 justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon" style="background: #8338ec20; color: #8338ec;">
                                <i class="fas fa-code"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold" style="font-size: 1.1rem; color: #8338ec;">
                                    <?php echo htmlspecialchars($item["classification"]); ?>
                                </div>
                                <div class="text-muted small">Ideas: <?php echo $item["count"]; ?></div>
                            </div>
                        </div>
                        <div class="fw-bold text-secondary" style="min-width: 56px; text-align: right;">
                            <?php echo number_format($percentage, 1); ?>%
                        </div>
                    </div>
                    <div class="progress modern-progress mb-2" style="height: 18px; background: #e9ecef; border-radius: 12px; width: 100%;">
                        <div class="progress-bar" role="progressbar"
                             style="width: <?php echo $percentage; ?>%; background: linear-gradient(90deg, #3a86ff 0%, #8338ec 100%); font-weight: 600; font-size: 1rem; border-radius: 12px;"
                             aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="d-flex justify-content-between align-items-center mt-4">
                <div class="fw-bold text-secondary">Total Ideas: <?php echo $total_count; ?></div>
                <div class="fw-bold text-secondary">Categories: <?php echo count($classifications); ?></div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">No classification data available.</div>
        <?php endif; ?>
    </div>

</body>
</html>