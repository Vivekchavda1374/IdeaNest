# Database Schema Design

## 📋 Overview

The IdeaNest database schema is designed with a focus on data integrity, performance, and scalability. It implements a comprehensive relational structure with foreign key constraints, strategic indexing, and optimized queries to support all platform functionalities.

## 🏗️ Schema Architecture

### Core Design Principles
- **Normalization**: Third Normal Form (3NF) compliance for data integrity
- **Foreign Key Relationships**: Maintain referential integrity across all tables
- **Strategic Indexing**: Optimized indexes on frequently queried columns
- **Transaction Support**: InnoDB storage engine for ACID compliance
- **Scalability**: Design supports horizontal and vertical scaling

## 📊 Core Tables Structure

### User Management Tables

#### register (Users)
```sql
CREATE TABLE register (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'mentor', 'subadmin', 'admin') DEFAULT 'student',
    profile_image VARCHAR(255),
    github_username VARCHAR(100),
    github_profile_data JSON,
    github_last_sync TIMESTAMP NULL,
    phone VARCHAR(20),
    bio TEXT,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_github_username (github_username)
);
```

#### user_activity_log
```sql
CREATE TABLE user_activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    activity_description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES register(id) ON DELETE CASCADE,
    INDEX idx_user_activity (user_id, created_at),
    INDEX idx_activity_type (activity_type)
);
```

### Project Management Tables

#### projects (Project Submissions)
```sql
CREATE TABLE projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category ENUM('Software', 'Hardware') NOT NULL,
    difficulty ENUM('Beginner', 'Intermediate', 'Advanced') NOT NULL,
    team_size INT DEFAULT 1,
    development_time VARCHAR(100),
    target_audience TEXT,
    project_goals TEXT,
    technologies_used JSON,
    project_files JSON,
    status ENUM('pending', 'subadmin_approved', 'approved', 'rejected') DEFAULT 'pending',
    assigned_subadmin INT,
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES register(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_subadmin) REFERENCES register(id) ON DELETE SET NULL,
    INDEX idx_user_projects (user_id),
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_difficulty (difficulty),
    INDEX idx_submission_date (submission_date)
);
```

#### admin_approved_projects
```sql
CREATE TABLE admin_approved_projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    admin_id INT NOT NULL,
    approval_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    admin_comments TEXT,
    featured BOOLEAN DEFAULT FALSE,
    view_count INT DEFAULT 0,
    like_count INT DEFAULT 0,
    download_count INT DEFAULT 0,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES register(id) ON DELETE RESTRICT,
    INDEX idx_approval_date (approval_date),
    INDEX idx_featured (featured),
    INDEX idx_popularity (view_count, like_count)
);
```

#### denial_projects
```sql
CREATE TABLE denial_projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    denied_by INT NOT NULL,
    denial_reason TEXT NOT NULL,
    denial_category ENUM('quality', 'content', 'technical', 'policy') NOT NULL,
    denial_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    can_resubmit BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (denied_by) REFERENCES register(id) ON DELETE RESTRICT,
    INDEX idx_denial_date (denial_date),
    INDEX idx_denial_category (denial_category)
);
```

## 👨🏫 Mentor System Tables

#### mentors
```sql
CREATE TABLE mentors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    specializations JSON,
    max_students INT DEFAULT 10,
    current_students INT DEFAULT 0,
    availability_schedule JSON,
    bio TEXT,
    experience_years INT,
    success_rate DECIMAL(5,2) DEFAULT 0.00,
    total_sessions INT DEFAULT 0,
    average_rating DECIMAL(3,2) DEFAULT 0.00,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES register(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_specializations ((CAST(specializations AS CHAR(255)))),
    INDEX idx_success_rate (success_rate)
);
```

#### mentor_student_pairs
```sql
CREATE TABLE mentor_student_pairs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    mentor_id INT NOT NULL,
    student_id INT NOT NULL,
    pairing_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'completed', 'terminated') DEFAULT 'active',
    goals TEXT,
    progress_notes TEXT,
    satisfaction_score INT,
    completion_date TIMESTAMP NULL,
    
    FOREIGN KEY (mentor_id) REFERENCES mentors(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES register(id) ON DELETE CASCADE,
    UNIQUE KEY unique_active_pair (mentor_id, student_id, status),
    INDEX idx_mentor_pairs (mentor_id, status),
    INDEX idx_student_pairs (student_id, status)
);
```

#### mentoring_sessions
```sql
CREATE TABLE mentoring_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    mentor_id INT NOT NULL,
    student_id INT NOT NULL,
    session_type ENUM('regular', 'adhoc', 'group', 'workshop') DEFAULT 'regular',
    scheduled_time TIMESTAMP NOT NULL,
    duration_minutes INT DEFAULT 60,
    meeting_link VARCHAR(500),
    agenda TEXT,
    notes TEXT,
    status ENUM('scheduled', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    feedback_score INT,
    feedback_comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (mentor_id) REFERENCES mentors(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES register(id) ON DELETE CASCADE,
    INDEX idx_mentor_sessions (mentor_id, scheduled_time),
    INDEX idx_student_sessions (student_id, scheduled_time),
    INDEX idx_session_status (status)
);
```

## 👥 SubAdmin Management Tables

#### subadmins
```sql
CREATE TABLE subadmins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    classification ENUM('Software', 'Hardware', 'Both') NOT NULL,
    expertise_areas JSON,
    projects_reviewed INT DEFAULT 0,
    approval_rate DECIMAL(5,2) DEFAULT 0.00,
    average_review_time INT DEFAULT 0, -- in hours
    workload_capacity INT DEFAULT 20,
    current_workload INT DEFAULT 0,
    status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES register(id) ON DELETE CASCADE,
    INDEX idx_classification (classification),
    INDEX idx_status (status),
    INDEX idx_workload (current_workload, workload_capacity)
);
```

#### support_tickets
```sql
CREATE TABLE support_tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subadmin_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    assigned_admin INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    
    FOREIGN KEY (subadmin_id) REFERENCES register(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_admin) REFERENCES register(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created_date (created_at)
);
```

## 💡 Ideas and Blog System Tables

#### blog (Ideas)
```sql
CREATE TABLE blog (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    tags JSON,
    category VARCHAR(100),
    status ENUM('published', 'draft', 'reported', 'hidden') DEFAULT 'published',
    view_count INT DEFAULT 0,
    like_count INT DEFAULT 0,
    comment_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES register(id) ON DELETE CASCADE,
    INDEX idx_user_ideas (user_id),
    INDEX idx_status (status),
    INDEX idx_created_date (created_at),
    INDEX idx_popularity (like_count, view_count),
    FULLTEXT idx_content_search (title, content)
);
```

#### idea_likes
```sql
CREATE TABLE idea_likes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    idea_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES register(id) ON DELETE CASCADE,
    FOREIGN KEY (idea_id) REFERENCES blog(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_idea_like (user_id, idea_id),
    INDEX idx_idea_likes (idea_id)
);
```

#### idea_comments
```sql
CREATE TABLE idea_comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    idea_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_comment_id INT NULL,
    content TEXT NOT NULL,
    status ENUM('active', 'hidden', 'deleted') DEFAULT 'active',
    like_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (idea_id) REFERENCES blog(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES register(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_comment_id) REFERENCES idea_comments(id) ON DELETE CASCADE,
    INDEX idx_idea_comments (idea_id, created_at),
    INDEX idx_user_comments (user_id),
    INDEX idx_parent_comments (parent_comment_id)
);
```

## 📧 Email and Notification Tables

#### mentor_email_queue
```sql
CREATE TABLE mentor_email_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    mentor_id INT NOT NULL,
    recipient_type ENUM('student', 'group', 'all') NOT NULL,
    recipient_ids JSON,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    scheduled_time TIMESTAMP NULL,
    status ENUM('pending', 'processing', 'sent', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    last_attempt TIMESTAMP NULL,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (mentor_id) REFERENCES mentors(id) ON DELETE CASCADE,
    INDEX idx_status_priority (status, priority),
    INDEX idx_scheduled_time (scheduled_time),
    INDEX idx_mentor_queue (mentor_id, status)
);
```

#### notification_logs
```sql
CREATE TABLE notification_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    notification_type VARCHAR(50) NOT NULL,
    subject VARCHAR(255),
    message TEXT,
    recipient_email VARCHAR(150),
    status ENUM('pending', 'sent', 'failed', 'bounced') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    delivery_attempts INT DEFAULT 0,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES register(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_notification_type (notification_type),
    INDEX idx_sent_date (sent_at)
);
```

## 🔒 Security and Audit Tables

#### admin_logs
```sql
CREATE TABLE admin_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50), -- user, project, mentor, etc.
    target_id INT,
    details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (admin_id) REFERENCES register(id) ON DELETE CASCADE,
    INDEX idx_admin_actions (admin_id, created_at),
    INDEX idx_action_type (action),
    INDEX idx_target (target_type, target_id)
);
```

#### deleted_ideas
```sql
CREATE TABLE deleted_ideas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    original_idea_id INT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    deletion_reason TEXT,
    deleted_by INT NOT NULL,
    deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES register(id) ON DELETE CASCADE,
    FOREIGN KEY (deleted_by) REFERENCES register(id) ON DELETE RESTRICT,
    INDEX idx_deletion_date (deleted_at),
    INDEX idx_deleted_by (deleted_by)
);
```

## 📊 Performance Optimization

### Indexing Strategy
```sql
-- Composite indexes for common query patterns
CREATE INDEX idx_project_status_date ON projects(status, submission_date);
CREATE INDEX idx_user_role_status ON register(role, status);
CREATE INDEX idx_mentor_availability ON mentors(status, current_students, max_students);
CREATE INDEX idx_session_mentor_date ON mentoring_sessions(mentor_id, scheduled_time, status);

-- Full-text search indexes
CREATE FULLTEXT INDEX idx_project_search ON projects(title, description);
CREATE FULLTEXT INDEX idx_idea_search ON blog(title, content);
```

### Query Optimization Examples
```sql
-- Optimized project listing with pagination
SELECT p.*, u.name as user_name, u.profile_image
FROM projects p
JOIN register u ON p.user_id = u.id
WHERE p.status = 'approved'
ORDER BY p.submission_date DESC
LIMIT 20 OFFSET 0;

-- Efficient mentor-student pairing query
SELECT m.*, u.name, u.email,
       (m.max_students - m.current_students) as available_slots
FROM mentors m
JOIN register u ON m.user_id = u.id
WHERE m.status = 'active'
  AND m.current_students < m.max_students
  AND JSON_CONTAINS(m.specializations, '"Software Development"')
ORDER BY m.success_rate DESC, available_slots DESC;
```

## 🔧 Database Configuration

### Connection Settings
```php
// Database configuration for optimal performance
$dbConfig = [
    'host' => 'localhost', // Database host - keep as localhost for database connection
    'dbname' => 'ideanest',
    'username' => 'ideanest_user',
    'password' => 'secure_password',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]
];
```

### Performance Tuning
```sql
-- MySQL configuration recommendations
SET innodb_buffer_pool_size = 1G;
SET innodb_log_file_size = 256M;
SET innodb_flush_log_at_trx_commit = 2;
SET query_cache_size = 128M;
SET max_connections = 200;
```

## 🧪 Database Testing

### Data Integrity Tests
```sql
-- Test foreign key constraints
INSERT INTO projects (user_id, title, description, category, difficulty)
VALUES (999999, 'Test Project', 'Test Description', 'Software', 'Beginner');
-- Should fail with foreign key constraint error

-- Test unique constraints
INSERT INTO register (name, email, password)
VALUES ('Test User', 'existing@email.com', 'password');
-- Should fail if email already exists
```

### Performance Tests
```sql
-- Test query performance with EXPLAIN
EXPLAIN SELECT * FROM projects 
WHERE status = 'approved' 
ORDER BY submission_date DESC 
LIMIT 20;

-- Monitor slow queries
SET long_query_time = 1;
SET slow_query_log = ON;
```

## 📈 Scaling Considerations

### Horizontal Scaling
- **Read Replicas**: Separate read and write operations
- **Sharding Strategy**: Partition data by user_id or geographic region
- **Connection Pooling**: Implement connection pooling for high concurrency

### Vertical Scaling
- **Memory Optimization**: Increase buffer pool size for better caching
- **SSD Storage**: Use SSD storage for improved I/O performance
- **CPU Optimization**: Optimize queries to reduce CPU usage

This database schema provides a robust foundation for the IdeaNest platform with proper normalization, indexing, and performance optimization strategies.