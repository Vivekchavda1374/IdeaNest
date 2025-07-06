<?php

include '../Login/Login/db.php';

// First, check if the table exists
$table_check_sql = "SHOW TABLES LIKE 'admin_approved_projects'";
$table_check_result = $conn->query($table_check_sql);

// If the table doesn't exist, create it with sample data
if ($table_check_result->num_rows == 0) {
    // Create table
    $create_table_sql = "CREATE TABLE `admin_approved_projects` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `classification` VARCHAR(100) NOT NULL,
        `project_name` VARCHAR(255) NOT NULL,
        `description` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    if ($conn->query($create_table_sql) === TRUE) {
        // Insert sample data
        $sample_data = [
            ['Web Application', 'Online Marketplace', 'E-commerce platform for digital products'],
            ['Mobile Application', 'Fitness Tracker', 'App for tracking workouts and nutrition'],
            ['Desktop Software', 'Video Editor Pro', 'Professional video editing software'],
            ['Web Application', 'Task Management System', 'Project management tool for teams'],
            ['Mobile Application', 'Language Learning App', 'Mobile app for learning new languages'],
            ['IoT Device', 'Smart Home Hub', 'Central control system for smart home devices'],
            ['Embedded Software', 'Automotive Control System', 'Software for vehicle systems management'],
            ['Robotics', 'Warehouse Robot', 'Automated inventory management robot'],
            ['Web Application', 'Cloud Storage Service', 'File storage and sharing platform'],
            ['Electronics Circuit', 'Solar Power Controller', 'Circuit for managing solar panel systems'],
            ['Mobile Application', 'Food Delivery App', 'App connecting customers with local restaurants'],
            ['Web Application', 'Online Learning Platform', 'Interactive educational website']
        ];

        $insert_sql = "INSERT INTO `admin_approved_projects` (`classification`, `project_name`, `description`) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);

        foreach ($sample_data as $data) {
            $stmt->bind_param("sss", $data[0], $data[1], $data[2]);
            $stmt->execute();
        }

        $stmt->close();
        echo "<div class='alert alert-success'>
                <i class='fas fa-check-circle'></i> Sample table 'admin_approved_projects' created with test data.
              </div>";
    } else {
        echo "<div class='alert alert-danger'>
                <i class='fas fa-exclamation-circle'></i> Error creating table: " . $conn->error . "
              </div>";
    }
}

// SQL query to get classification counts
$sql = "SELECT `classification`, COUNT(*) as `count` FROM `admin_approved_projects` GROUP BY `classification`";

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
    <title>IdeaNest Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3f51b5;
            --secondary-color: #f50057;
            --success-color: #4caf50;
            --info-color: #2196f3;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --light-color: #f5f5f5;
            --dark-color: #212121;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.1);a
            --border-radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            color: #333;
            line-height: 1.6;
            padding: 30px 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .logo {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
            display: flex;
            align-items: center;
        }

        .logo i {
            margin-right: 10px;
            font-size: 32px;
        }

        .date-display {
            font-size: 14px;
            color: #666;
            background-color: white;
            padding: 8px 15px;
            border-radius: 20px;
            box-shadow: var(--shadow);
        }

        .date-display i {
            margin-right: 8px;
            color: var(--primary-color);
        }

        .dashboard {
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            width: 100%;
        }

        .dashboard-title {
            color: var(--dark-color);
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }

        .progress-container {
            margin-bottom: 25px;
            padding: 18px;
            border-radius: var(--border-radius);
            background-color: var(--light-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .progress-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .classification-name {
            font-weight: 600;
            font-size: 16px;
            display: flex;
            align-items: center;
        }

        .classification-name i {
            margin-right: 8px;
        }

        .progress-stats {
            display: flex;
            align-items: center;
        }

        .count-badge {
            background-color: rgba(0, 0, 0, 0.2);
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 14px;
            margin-right: 10px;
            font-weight: 500;
        }

        .percentage {
            font-weight: 600;
            font-size: 16px;
        }

        .progress-bar {
            height: 12px;
            border-radius: 6px;
            overflow: hidden;
            background-color: rgba(255, 255, 255, 0.7);
            position: relative;
        }

        .progress-fill {
            height: 100%;
            border-radius: 6px;
            transition: width 1s ease-in-out;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .progress-label {
            position: absolute;
            right: 10px;
            top: -20px;
            color: #333;
            font-size: 12px;
            font-weight: 600;
        }

        .summary-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .summary-card {
            background: linear-gradient(145deg, #fff, #f0f0f0);
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .summary-card:hover {
            transform: translateY(-5px);
        }

        .summary-icon {
            font-size: 28px;
            color: var(--primary-color);
            margin-bottom: 15px;
            background: rgba(63, 81, 181, 0.1);
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 15px;
        }

        .summary-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 5px;
        }

        .summary-label {
            color: #666;
            font-size: 14px;
            font-weight: 500;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
        }

        .alert i {
            margin-right: 10px;
            font-size: 18px;
        }

        .alert-success {
            background-color: rgba(76, 175, 80, 0.2);
            border-left: 4px solid var(--success-color);
            color: #2e7d32;
        }

        .alert-danger {
            background-color: rgba(244, 67, 54, 0.2);
            border-left: 4px solid var(--danger-color);
            color: #c62828;
        }

        /* Icons for different classifications */
        .web-icon { color: #4285F4; }
        .mobile-icon { color: #34A853; }
        .desktop-icon { color: #FBBC05; }
        .embedded-icon { color: #EA4335; }
        .iot-icon { color: #8E24AA; }
        .robotics-icon { color: #00ACC1; }
        .electronics-icon { color: #FF6D00; }

        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .date-display {
                align-self: flex-end;
            }

            .summary-section {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }

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


    <div class="dashboard">
        <h1 class="dashboard-title">Project Classification Dashboard</h1>

        <div class="glass-card p-4" style="width:100%;">
            <h2 class="fw-bold mb-4" style="color: #3a86ff;">Project Classifications</h2>
            <?php if(isset($classifications) && count($classifications) > 0): ?>
                <?php foreach($classifications as $item): ?>
                    <?php
                    $percentage = ($item["count"] / $total_count) * 100;
                    ?>
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-2 justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon" style="background: #3a86ff20; color: #3a86ff;">
                                    <i class="fas fa-code"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold" style="font-size: 1.1rem; color: #3a86ff;">
                                        <?php echo htmlspecialchars($item["classification"]); ?>
                                    </div>
                                    <div class="text-muted small">Projects: <?php echo $item["count"]; ?></div>
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
                    <div class="fw-bold text-secondary">Total Projects: <?php echo $total_count; ?></div>
                    <div class="fw-bold text-secondary">Categories: <?php echo count($classifications); ?></div>
                </div>
            <?php else: ?>
                <div class="alert alert-danger">No classification data available.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Add animations when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Animate progress bars on load
        const progressFills = document.querySelectorAll('.progress-fill');

        progressFills.forEach(fill => {
            const width = fill.style.width;
            fill.style.width = '0';

            setTimeout(() => {
                fill.style.width = width;
            }, 300);
        });
    });
</script>
</body>
</html>