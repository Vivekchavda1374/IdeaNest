# IdeaNest - Collaborative Academic Project Platform

[![License](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.2.4-blue.svg)](https://www.php.net/)
[![MySQL Version](https://img.shields.io/badge/MySQL-10.4.28--MariaDB-blue.svg)](https://www.mysql.com/)

IdeaNest is a web-based platform designed to facilitate the management, sharing, and review of academic projects. It provides a comprehensive suite of features for students, sub-admins, and administrators, streamlining the project lifecycle from submission to approval.

---

## ✨ Key Features

### Student Features
- **Project Submission:** Submit projects with support for multiple file types (images, videos, code files, PDFs, ZIP).
- **Project Browsing & Searching:** Browse and search for projects using keywords, categories, and filters.
- **Personal Bookmarking:** Save favorite projects to a personal bookmarking system.
- **User Profile Management:** Manage personal profile information.
- **Submission Tracking:** Track the status of submitted projects.
- **Blog/List View:** View projects in a blog-style or list format.

### Admin Features
- **Project Approval/Rejection:** Review and approve or reject submitted projects.
- **User Management:** Manage user accounts and roles.
- **Email Notifications:** Send email notifications using PHPMailer.
- **System Settings:** Configure system-wide settings.
- **Sub-Admin Management:** Manage sub-administrator accounts.
- **Project Notifications:** Handle notifications related to project submissions and approvals.

### Sub-Admin Features
- **Specialized Project Review:** Review projects within specific domains.
- **Profile Management:** Manage personal profile information.
- **Project Assignment:** Handle assigned projects for review.

---

## 📁 Project Structure
```
IdeaNest/
├── Admin/ # Administrative Interface
│ ├── admin_view_project.php # Project review interface
│ ├── admin.php # Admin dashboard
│ ├── project_approvel.php # Project approval system
│ ├── settings.php # System settings
│ ├── user_manage_by_admin.php # User management
│ └── subadmin/ # Sub-admin section
│ ├── add_subadmin.php
│ ├── dashboard.php
│ └── profile.php
├── Login/ # Authentication System
│ ├── dashboard.php
│ ├── db.php # Database connection
│ ├── login.php # Login system
│ └── register.php # User registration
├── user/ # User Interface
│ ├── all_projects.php # Project listing
│ ├── bookmark.php # Bookmarking system
│ ├── project_details.php # Detailed project view
│ ├── search.php # Search functionality
│ ├── Blog/ # Project blog system
│ │ ├── form.php
│ │ └── list-project.php
│ └── forms/ # Project submission
│ └── new_project_add.php
├── db/ # Database Scripts
│ └── ideanest.sql # Main database structure
└── assets/ # Assets (CSS, JS, Images)
├── css/
│ └── style.css # Main stylesheet
└── js/
└── script.js # Main script
```


---

## 🗄️ Database Structure

Key tables:
- `admin_approved_projects`: Stores approved projects.
- `projects`: Manages project submissions.
- `register`: User management.
- `bookmark`: Handles project bookmarks.
- `subadmins`: Sub-administrator management.
- `notification_logs`: System notifications.
- `admin_settings`: System configuration.

---

## 🛠️ Technical Implementation

### Authentication
- Session-based authentication.
- Password hashing for security.
- Role-based access control (Admin, Sub-admin, User).

### File Management
- Supports multiple file types: Images, Videos, Documents, Code files.
- Organized upload directories for different file types.

### Email
- PHPMailer integration.
- Notification templates.
- SMTP configuration.

---

## 📦 Dependencies
- [PHPMailer](https://github.com/PHPMailer/PHPMailer) - For email functionality.
- [Composer](https://getcomposer.org/) - For dependency management.

---

## 🚀 Installation

1. Clone the repository.
2. Import the database using `/db/ideanest.sql`.
3. Configure the database connection in `Login/db.php`.
4. Set up email configuration in admin settings.
5. Ensure proper permissions for upload directories.

---

## 💻 Requirements
- PHP 8.2.4+
- MySQL 10.4.28-MariaDB+
- Apache Server (or equivalent)
- Composer

---

## 🔐 Security Measures
- Password hashing.
- SQL injection prevention (prepared statements/parameterized queries).
- File upload validation.
- Secure session management.
- Input sanitization.

---

## 👥 User Roles

### Admin
- Full system access.
- User management.
- Project approval.
- System settings configuration.

### Sub-Admin
- Domain-specific project review.
- Limited administrative access.

### User
- Project submission.
- Project viewing.
- Bookmarking.
- Profile management.

---

## 📧 Contact
For support or queries:
- Email: **ideanest.ict@gmail.com**

---

## 🤝 Contributing

We welcome contributions!

**Focus Areas:**
- UI/UX improvements.
- Security enhancements.
- Performance optimization.
- Documentation improvements.
- Bug fixes.

**Testing Areas:**
- Project submission.
- File uploads.
- User authentication.
- Admin features.

