# IdeaNest

A collaborative platform for academic project management and sharing.

## 📋 Actual Implemented Features

### Student Features
- Project submission with multiple file types support (images, videos, code files, PDFs)
- Project browsing and searching capabilities
- Personal bookmarking system for projects
- User profile management
- Project submission tracking
- Blog/list view for projects

### Admin Features
- Complete project approval/rejection system
- User management system
- Email notification system using PHPMailer
- System settings management
- Sub-admin management
- Project notification handling

### Sub-Admin Features
- Specialized project review system
- Profile management
- Project assignment handling

## 🗂️ Actual Project Structure

```
IdeaNest/
├── Admin/                          # Administrative Interface
│   ├── admin_view_project.php     # Project review interface
│   ├── admin.php                  # Admin dashboard
│   ├── project_approvel.php       # Project approval system
│   ├── settings.php               # System settings
│   ├── user_manage_by_admin.php   # User management
│   └── subadmin/                  # Sub-admin section
│       ├── add_subadmin.php
│       ├── dashboard.php
│       └── profile.php
│
├── Login/                         # Authentication System
│   └── Login/
│       ├── dashboard.php
│       ├── db.php                 # Database connection
│       ├── login.php             # Login system
│       └── register.php          # User registration
│
├── user/                         # User Interface
│   ├── all_projects.php         # Project listing
│   ├── bookmark.php             # Bookmarking system
│   ├── project_details.php      # Detailed project view
│   ├── search.php              # Search functionality
│   ├── Blog/                   # Project blog system
│   │   ├── form.php
│   │   └── list-project.php
│   └── forms/                  # Project submission
│       └── new_project_add.php
│
└── db/                         # Database Scripts
    └── ideanest.sql           # Main database structure

```

## 💾 Database Structure

Key tables implemented:
- admin_approved_projects: Stores approved projects
- projects: Manages project submissions
- register: User management
- bookmark: Handles project bookmarks
- subadmins: Sub-administrator management
- notification_logs: System notifications
- admin_settings: System configuration

## 🛠️ Technical Implementation

### Authentication System
- Session-based authentication
- Password hashing for security
- Role-based access (Admin, Sub-admin, User)

### File Management
- Supports multiple file types:
  - Images (.jpg, .png, .gif)
  - Videos (MP4)
  - Documents (PDF)
  - Code files (ZIP)
- Organized upload directories for different file types

### Email System
- PHPMailer integration
- Notification templates
- SMTP configuration

## 📦 Dependencies

- PHPMailer for email functionality
- Composer for dependency management

## 🚀 Installation

1. Clone the repository
2. Import the database using `/db/ideanest.sql`
3. Configure database connection in `Login/Login/db.php`
4. Set up email configuration in admin settings
5. Ensure proper permissions for upload directories

## 💻 Requirements

- PHP 8.2.4
- MySQL 10.4.28-MariaDB
- Apache Server
- Composer

## 🔐 Security Measures

- Password hashing
- SQL injection prevention
- File upload validation
- Session management
- Input sanitization

## 👥 User Roles

### Admin
- Full system access
- User management
- Project approval
- System settings

### Sub-Admin
- Domain-specific project review
- Limited administrative access

### Users
- Project submission
- Project viewing
- Bookmarking
- Profile management

## 📧 Contact

For support or queries:
- Email: ideanest.ict@gmail.com

## 🤝 Contributing

1. Focus on these areas:
   - UI/UX improvements
   - Security enhancements
   - Performance optimization
   - Documentation improvements
   - Bug fixes

2. Testing areas:
   - Project submission
   - File uploads
   - User authentication
   - Admin features
   - Email notifications
