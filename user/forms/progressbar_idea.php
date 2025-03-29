<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ideanest";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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
            :root {
                --primary-color: #3f51b5;
                --secondary-color: #f50057;
                --success-color: #4caf50;
                --info-color: #2196f3;
                --warning-color: #ff9800;
                --danger-color: #f44336;
                --light-color: #f5f5f5;
                --dark-color: #212121;
                --shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
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

            .dashboard-grid {
                display: grid;
                grid-template-columns: 2fr 1fr;
                gap: 30px;
                margin-top: 30px;
            }

            .classification-section {
                background: white;
                padding: 25px;
                border-radius: var(--border-radius);
                box-shadow: var(--shadow);
            }

            .status-section {
                background: white;
                padding: 25px;
                border-radius: var(--border-radius);
                box-shadow: var(--shadow);
                display: flex;
                flex-direction: column;
                gap: 15px;
            }

            .section-title {
                font-size: 18px;
                font-weight: 600;
                margin-bottom: 20px;
                color: var(--primary-color);
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
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

            .status-card {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 15px;
                border-radius: var(--border-radius);
                background-color: var(--light-color);
                transition: transform 0.3s ease;
            }

            .status-card:hover {
                transform: translateX(5px);
            }

            .status-info {
                display: flex;
                align-items: center;
            }

            .status-icon {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-right: 15px;
                color: white;
                font-size: 18px;
            }

            .status-text {
                font-weight: 600;
                text-transform: capitalize;
            }

            .status-count {
                font-size: 20px;
                font-weight: 700;
            }

            .project-type-section {
                display: flex;
                justify-content: space-between;
                margin-top: 30px;
                gap: 20px;
            }

            .type-card {
                flex: 1;
                background: white;
                border-radius: var(--border-radius);
                box-shadow: var(--shadow);
                padding: 20px;
                text-align: center;
                transition: transform 0.3s ease;
            }

            .type-card:hover {
                transform: translateY(-5px);
            }

            .type-icon {
                width: 70px;
                height: 70px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 15px;
                color: white;
                font-size: 28px;
            }

            .type-name {
                font-weight: 600;
                margin-bottom: 5px;
                text-transform: uppercase;
                font-size: 16px;
            }

            .type-count {
                font-size: 24px;
                font-weight: 700;
            }

            .priority1-section {
                margin-top: 30px;
                background: white;
                border-radius: var(--border-radius);
                box-shadow: var(--shadow);
                padding: 25px;
            }

            .priority1-bars {
                display: flex;
                justify-content: space-between;
                margin-top: 15px;
                gap: 15px;
            }

            .priority1-bar {
                flex: 1;
                text-align: center;
            }

            .priority1-fill {
                width: 100%;
                background-color: #f0f0f0;
                border-radius: 8px;
                overflow: hidden;
                position: relative;
            }

            .priority1-progress {
                height: 80px;
                transition: height 1s ease-in-out;
                display: flex;
                align-items: flex-end;
                justify-content: center;
                color: white;
                font-weight: 700;
                font-size: 18px;
                padding-bottom: 10px;
            }

            .priority1-label {
                margin-top: 10px;
                font-weight: 600;
                text-transform: uppercase;
                font-size: 14px;
                color: #666;
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

            @media (max-width: 992px) {
                .dashboard-grid {
                    grid-template-columns: 1fr;
                }

                .project-type-section {
                    flex-direction: column;
                }
            }

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

                .priority1-bars {
                    flex-direction: column;
                }

                .priority1-progress {
                    height: 50px;
                }
            }
        </style>
    </head>
<body>

    <div class="dashboard">
    <h1 class="dashboard-title">Idea Management Dashboard</h1>

<?php if(isset($classifications) && count($classifications) > 0): ?>
    <div class="dashboard-grid">
        <div class="classification-section">
            <h2 class="section-title">Idea Classifications</h2>
            <?php foreach($classifications as $item): ?>
                <?php
                $percentage = ($item["count"] / $total_count) * 100;
                $color = isset($colors[$item["classification"]]) ? $colors[$item["classification"]] : "#6c757d";

                // Determine icon based on classification
                $icon = 'fas fa-code';
                switch($item["classification"]) {
                    case 'Web Application':
                        $icon = 'fas fa-globe web-icon';
                        break;
                    case 'Mobile Application':
                        $icon = 'fas fa-mobile-alt mobile-icon';
                        break;
                    case 'Desktop Software':
                        $icon = 'fas fa-desktop desktop-icon';
                        break;
                    case 'Embedded Software':
                        $icon = 'fas fa-microchip embedded-icon';
                        break;
                    case 'IoT Device':
                        $icon = 'fas fa-network-wired iot-icon';
                        break;
                    case 'Robotics':
                        $icon = 'fas fa-robot robotics-icon';
                        break;
                    case 'Electronics Circuit':
                        $icon = 'fas fa-bolt electronics-icon';
                        break;
                }
                ?>
                <div class="progress-container">
                    <div class="progress-header">
                        <div class="classification-name">
                            <i class="<?php echo $icon; ?>"></i>
                            <?php echo htmlspecialchars($item["classification"]); ?>
                        </div>
                        <div class="progress-stats">
                                    <span class="count-badge" style="background-color: <?php echo $color; ?>80;">
                                        <?php echo $item["count"]; ?> Ideas
                                    </span>
                            <span class="percentage"><?php echo number_format($percentage, 1); ?>%</span>
                        </div>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $percentage; ?>%; background-color: <?php echo $color; ?>">
                        </div>
                        <?php if($percentage > 5): ?>
                            <div class="progress-label">
                                <?php echo number_format($percentage, 1); ?>%
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="status-section">
            <h2 class="section-title">Idea Status</h2>

            <?php foreach(["pending", "in_progress", "completed", "rejected"] as $status): ?>
                <?php
                $count = isset($status_counts[$status]) ? $status_counts[$status] : 0;
                $color = isset($status_colors[$status]) ? $status_colors[$status] : "#6c757d";
                $icon = "fas fa-question";

                switch($status) {
                    case "pending":
                        $icon = "fas fa-clock";
                        break;
                    case "in_progress":
                        $icon = "fas fa-spinner";
                        break;
                    case "completed":
                        $icon = "fas fa-check";
                        break;
                    case "rejected":
                        $icon = "fas fa-times";
                        break;
                }
                ?>
                <div class="status-card">
                    <div class="status-info">
                        <div class="status-icon" style="background-color: <?php echo $color; ?>">
                            <i class="<?php echo $icon; ?>"></i>
                        </div>
                        <div class="status-text"><?php echo str_replace('_', ' ', $status); ?></div>
                    </div>
                    <div class="status-count"><?php echo $count; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="project-type-section">
        <?php foreach(["software", "hardware"] as $type): ?>
            <?php
            $count = isset($type_counts[$type]) ? $type_counts[$type] : 0;
            $color = isset($type_colors[$type]) ? $type_colors[$type] : "#6c757d";
            $icon = $type == "software" ? "fas fa-code" : "fas fa-microchip";
            $percentage = $total_count > 0 ? ($count / $total_count) * 100 : 0;
            ?>
            <div class="type-card">
                <div class="type-icon" style="background-color: <?php echo $color; ?>">
                    <i class="<?php echo $icon; ?>"></i>
                </div>
                <div class="type-name"><?php echo $type; ?></div>
                <div class="type-count"><?php echo $count; ?></div>
                <div class="percentage"><?php echo number_format($percentage, 1); ?>% of total</div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="priority1-section">
        <h2 class="section-title">priority1 Distribution</h2>
        <div class="priority1-bars">
            <?php
            $max_priority1_count = 0;
            foreach($priority1_counts as $count) {
                if($count > $max_priority1_count) $max_priority1_count = $count;
            }
            ?>

            <?php foreach(["low", "medium", "high"] as $priority1): ?>
                <?php
                $count = isset($priority1_counts[$priority1]) ? $priority1_counts[$priority1] : 0;
                $color = isset($priority1_colors[$priority1]) ? $priority1_colors[$priority1] : "#6c757d";
                $height_percentage = $max_priority1_count > 0 ? ($count / $max_priority1_count) * 100 : 0;
                ?>
                <div class="priority1-bar">
                    <div class="priority1-fill">
                        <div class="priority1-progress" style="height: <?php echo $height_percentage; ?>%; background-color: <?php echo $color; ?>">
                            <?php echo $count; ?>
                        </div>
                    </div>
                    <div class="priority1-label"><?php echo $priority1; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="summary-section">
    <div class="summary-card">
        <div class="summary-icon">
            <i class="fas fa-project-diagram"></i>
        </div>
        <div class="summary-value"><?php echo $total_count; ?></div>
        <div class="summary-label">Total Ideas</div>
    </div>
    <div class="summary-card">
        <div class="summary-icon">
            <i class="fas fa-layer-group"></i>
        </div>
        <div class="summary-value"><?php echo count($classifications); ?></div>
        <div class="summary-label">Categories</div>
    </div>
    <div class="summary-card">
    <?php
    $max_classification = "";
    $max_count = 0;
    foreach($classifications as $item) {
        if($item["count"] > $max_count) {
            $max_count = $item["count"];
            $max_classification = $item["classification"];
        }
    }

    // Get icon for top category
    $top_icon = 'fas fa-code';
    $top_icon_color = isset($colors[$max_classification]) ? $colors[$max_classification] : "#6c757d";
    switch($max_classification) {
        case 'Web Application':
            $top_icon = 'fas fa-globe';
            break;
        case 'Mobile Application':
            $top_icon = 'fas fa-mobile-alt';
            break;
        case 'Desktop Software':
            $top_icon = 'fas fa-desktop';
            break;
        case 'Embedded Software':
            $top_icon = 'fas fa-microchip';
            break;
        case 'IoT Device':
            $top_icon = 'fas fa-network-wired';
            break;
        case 'Robotics':
            $top_icon = 'fas fa-robot';
            break;
        case 'Electronics Circuit':
            $top_icon = 'fas fa-bolt';
            break;
    }
    ?>
        <div class="summary-icon" style="color: <?php echo $top_icon_color; ?>;">
            <i class="<?php echo $top_icon; ?>"></i>
        </div>
        <div class="summary-value"><?php echo $max_classification; ?></div>
        <div class="summary-label">Top Category</div>
    </div>
        <div class="summary-card">
            <?php
            $completion_percentage = isset($status_counts['completed']) ?
                ($status_counts['completed'] / $total_count) * 100 : 0;
            ?>
            <div class="summary-icon">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="summary-value"><?php echo number_format($completion_percentage, 1); ?>%</div>
            <div class="summary-label">Completion Rate</div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> No Idea data available.
    </div>
<?php endif; ?>
    </div>
</div>

<script>
    // Animation for progress bars when page loads
    document.addEventListener('DOMContentLoaded', function() {
        const progressFills = document.querySelectorAll('.progress-fill');
        const priority1Bars = document.querySelectorAll('.priority1-progress');

        setTimeout(() => {
            progressFills.forEach(fill => {
                const width = fill.getAttribute('data-width');
                fill.style.width = width;
            });

            priority1Bars.forEach(bar => {
                const height = bar.getAttribute('data-height');
                bar.style.height = height;
            });
        }, 300);
    });
</script>
</body>
</html>