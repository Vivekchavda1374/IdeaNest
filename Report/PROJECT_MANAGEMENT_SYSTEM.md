# Project Management System

## 📋 Overview

The Project Management System is the core module of IdeaNest that handles project submission, approval workflow, file management, and project lifecycle tracking. It implements a three-tier approval process with comprehensive file handling and security features.

## 🏗️ Core Components

### ProjectManager Class

```php
/**
 * ProjectManager Class
 * Manages project submission, approval workflow, and file handling
 * 
 * Key Methods:
 * - submitProject(): Handles project submission with file upload validation
 * - processApproval(): Manages three-tier approval workflow with status updates
 * - validateFileUpload(): Implements secure file upload with type and size validation
 * - trackProjectStatus(): Provides real-time project status monitoring
 */
```

## 📁 Project Structure

```
user/forms/
├── new_project_add.php     # Project submission form
├── edit_project.php        # Project editing interface
├── uploads/               # Secure file storage directory
│   ├── images/           # Project images
│   ├── videos/           # Project videos
│   ├── documents/        # Project documents
│   └── code/            # Source code files
└── validation.php        # File upload validation
```

## 🔄 Project Workflow

### Three-Tier Approval Process

#### Stage 1: User Submission
- **Project Creation**: Students submit projects with detailed information
- **File Upload**: Multiple file types supported with validation
- **Initial Validation**: Basic validation and formatting checks
- **Status**: Project marked as "Pending" for Faculty review

#### Stage 2: Faculty Review
- **Assignment**: Projects automatically assigned based on classification
- **Review Process**: Facultys evaluate project quality and completeness
- **Decision Options**:
  - **Approve**: Forward to Hod sir for final approval
  - **Reject**: Return with detailed feedback
  - **Request Changes**: Ask for modifications

#### Stage 3: Hod sir Final Approval
- **Final Review**: Hod sir conducts final quality assessment
- **Publication Decision**: Approve for public display or reject
- **Status Update**: Project marked as "Approved" or "Rejected"
- **Notification**: Automated notifications sent to all stakeholders

## 📊 Project Data Structure

### Core Project Information
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
    status ENUM('pending', 'Faculty_approved', 'approved', 'rejected') DEFAULT 'pending',
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES register(id)
);
```

### Approved Projects Table
```sql
CREATE TABLE hod sir_approved_projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    hod sir_id INT NOT NULL,
    approval_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    hod sir_comments TEXT,
    featured BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (hod sir_id) REFERENCES register(id)
);
```

## 📁 File Management System

### Supported File Types
- **Images**: JPG, JPEG, PNG, GIF (max 10MB)
- **Videos**: MP4, AVI, MOV (max 50MB)
- **Documents**: PDF, DOC, DOCX, PPT, PPTX (max 10MB)
- **Code**: ZIP, RAR, 7Z (max 25MB)
- **Presentations**: PPT, PPTX, PDF (max 15MB)

### File Upload Security
```php
/**
 * Secure file upload validation
 * 
 * @param array $file Uploaded file information
 * @return array Validation result with status and message
 */
function validateFileUpload($file) {
    $allowedTypes = [
        'image/jpeg', 'image/png', 'image/gif',
        'video/mp4', 'video/avi', 'video/quicktime',
        'application/pdf', 'application/zip',
        'application/vnd.ms-powerpoint'
    ];
    
    $maxSizes = [
        'image' => 10 * 1024 * 1024,  // 10MB
        'video' => 50 * 1024 * 1024,  // 50MB
        'document' => 10 * 1024 * 1024 // 10MB
    ];
    
    // Validation logic implementation
    return validateFile($file, $allowedTypes, $maxSizes);
}
```

### File Storage Structure
```
uploads/
├── {user_id}/
│   ├── {project_id}/
│   │   ├── images/
│   │   │   ├── main_image.jpg
│   │   │   └── gallery/
│   │   ├── videos/
│   │   │   └── demo_video.mp4
│   │   ├── documents/
│   │   │   ├── project_report.pdf
│   │   │   └── presentation.pptx
│   │   └── code/
│   │       └── source_code.zip
│   └── temp/              # Temporary upload storage
└── .htaccess             # Access control configuration
```

## 🔒 Security Features

### File Security
- **Type Validation**: MIME type and extension verification
- **Size Limits**: Configurable file size restrictions
- **Virus Scanning**: Optional antivirus integration
- **Access Control**: Protected download with user verification
- **Path Traversal Prevention**: Secure file path handling

### Data Security
- **SQL Injection Prevention**: Prepared statements for all queries
- **XSS Protection**: Input sanitization and output encoding
- **CSRF Protection**: Token validation for form submissions
- **Access Control**: Role-based file access permissions

## 📈 Project Analytics

### Submission Metrics
- **Daily Submissions**: Track project submission trends
- **Category Distribution**: Monitor project type preferences
- **Approval Rates**: Calculate approval/rejection statistics
- **Processing Time**: Measure review workflow efficiency

### User Engagement
- **Project Views**: Track project visibility and engagement
- **Download Statistics**: Monitor file download patterns
- **Like/Comment Activity**: Measure community interaction
- **Bookmark Trends**: Analyze project popularity

## 🔧 Configuration Options

### File Upload Settings
```php
// File upload configuration
$config = [
    'max_file_size' => 10 * 1024 * 1024,  // 10MB default
    'allowed_extensions' => ['jpg', 'png', 'pdf', 'zip'],
    'upload_path' => 'user/forms/uploads/',
    'temp_path' => 'user/forms/temp/',
    'virus_scan' => false,  // Enable for production
    'image_resize' => true,  // Auto-resize large images
    'watermark' => false    // Add watermark to images
];
```

### Approval Workflow Settings
```php
// Workflow configuration
$workflow = [
    'auto_assign_Faculty' => true,
    'notification_enabled' => true,
    'approval_timeout' => 7 * 24 * 3600,  // 7 days
    'max_revisions' => 3,
    'require_hod sir_approval' => true
];
```

## 🔄 API Endpoints

### Project Submission API
```php
// POST /user/api/submit_project.php
{
    "title": "Project Title",
    "description": "Project Description",
    "category": "Software",
    "difficulty": "Intermediate",
    "team_size": 3,
    "development_time": "3 months",
    "files": ["file1.jpg", "file2.pdf"]
}
```

### Project Status API
```php
// GET /user/api/project_status.php?id={project_id}
{
    "project_id": 123,
    "status": "Faculty_approved",
    "current_reviewer": "Faculty_user",
    "last_updated": "2024-01-15 10:30:00",
    "comments": "Good project, needs minor revisions"
}
```

## 🧪 Testing

### Unit Tests
- **File Upload Validation**: Test file type and size validation
- **Project Submission**: Verify project creation process
- **Approval Workflow**: Test status transitions and notifications
- **Security Features**: Validate input sanitization and access control

### Integration Tests
- **Database Operations**: Test project CRUD operations
- **File System Integration**: Verify file storage and retrieval
- **Email Notifications**: Test automated notification system
- **User Interface**: Validate form submissions and responses

## 🔍 Troubleshooting

### Common Issues

#### File Upload Problems
- **Size Limit Exceeded**: Check PHP upload_max_filesize setting
- **Permission Denied**: Verify directory permissions (755)
- **Type Not Allowed**: Check MIME type validation rules
- **Upload Timeout**: Increase max_execution_time for large files

#### Approval Workflow Issues
- **Stuck in Pending**: Check Faculty assignment logic
- **Missing Notifications**: Verify email configuration
- **Status Not Updating**: Check database transaction handling
- **Access Denied**: Verify user role permissions

### Debug Tools
- **Upload Diagnostics**: Built-in file upload debugging
- **Workflow Tracker**: Visual representation of approval process
- **Error Logging**: Comprehensive error logging system
- **Performance Monitor**: Track system performance metrics

## 📊 Performance Optimization

### Database Optimization
- **Indexing Strategy**: Optimized indexes for project queries
- **Query Optimization**: Efficient project retrieval queries
- **Connection Pooling**: Database connection management
- **Caching Layer**: Project data caching for improved performance

### File System Optimization
- **Storage Efficiency**: Organized file storage structure
- **Compression**: Automatic file compression for storage
- **CDN Integration**: Content delivery network support
- **Cleanup Jobs**: Automated cleanup of temporary files

This Project Management System provides a comprehensive solution for handling the complete project lifecycle from submission to publication in the IdeaNest platform.