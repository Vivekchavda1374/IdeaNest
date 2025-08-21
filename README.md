# IdeaNest - Collaborative Academic Project Platform

[![License](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.2.4-blue.svg)](https://www.php.net/)
[![MySQL Version](https://img.shields.io/badge/MySQL-10.4.28--MariaDB-blue.svg)](https://www.mysql.com/)

IdeaNest is a web-based platform designed to facilitate the management, sharing, and review of academic projects. It provides a comprehensive suite of features for students, sub-admins, and administrators, streamlining the project lifecycle from submission to approval.

---

## ✨ Key Features

### Student/User Features
- **Project Management**
  - Submit new projects with multiple file attachments
  - Edit existing projects
  - Track project status (pending/approved/rejected)
  - View projects in list or blog format
  - Filter projects by type (software/hardware)
  - Advanced search functionality
  
- **File Management**
  - Support for multiple file types:
    - Images (jpg, jpeg, png, gif)
    - Videos (mp4, avi, mov)
    - Code files (zip, rar)
    - Documentation (pdf)
  - Organized file storage system
  - Secure file upload handling

- **Profile Management**
  - Personal information updates
  - Profile picture management
  - Academic details management
  - Password change functionality
  - View submission history

- **Project Discovery**
  - Advanced search with multiple filters
  - Project categorization (software/hardware)
  - Project classification (web/mobile/IoT etc.)
  - Bookmark favorite projects
  - Sort projects by various criteria

### Admin Features
- **Project Management**
  - Review submitted projects
  - Approve/Reject projects with feedback
  - Bulk project management
  - View project statistics
  - Monitor project submissions

- **User Management**
  - Manage student accounts
  - View user activities
  - Enable/disable user accounts
  - Reset user passwords
  - Export user data

- **System Administration**
  - Configure email settings (SMTP)
  - Manage system notifications
  - Monitor system logs
  - Configure system parameters
  - Backup management

- **Sub-admin Management**
  - Add/remove sub-administrators
  - Assign domain specializations
  - Monitor sub-admin activities
  - Review classification requests
  - Manage permissions

### Sub-Admin Features
- **Domain Management**
  - Manage specific project domains
  - Review domain-specific projects
  - Provide specialized feedback
  - Track domain statistics

- **Project Review System**
  - Review assigned projects
  - Provide detailed feedback
  - Track review history
  - Manage project status
  - Generate review reports

- **Support System**
  - Create support tickets
  - Track ticket status
  - Communicate with admin
  - View resolution history

- **Profile & Settings**
  - Manage personal information
  - Update specialization areas
  - Configure notification preferences
  - View activity history

### Communication Features
- **Notification System**
  - Email notifications
  - System notifications
  - Project status updates
  - Review notifications
  - Custom notification templates

- **Support System**
  - Ticket creation
  - Priority levels
  - Status tracking
  - Response management
  - Resolution tracking

### Security Features
- **Authentication**
  - Secure login system
  - Role-based access control
  - Session management
  - Password encryption
  - Failed login protection

- **Data Protection**
  - Input sanitization
  - SQL injection prevention
  - XSS protection
  - CSRF protection
  - Secure file handling

### Technical Features
- **Performance**
  - Lazy loading implementation
  - Optimized database queries
  - Caching mechanisms
  - Efficient file handling
  - Response optimization

- **Responsive Design**
  - Mobile-friendly interface
  - Adaptive layouts
  - Touch-friendly controls
  - Flexible grids
  - Media optimization

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

---

## 🛠 Installation & Setup

### Prerequisites
- PHP 8.2.4 or higher
- MySQL 10.4.28 (MariaDB) or higher
- Apache web server
- Composer for PHP dependencies

### Installation Steps
1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/IdeaNest.git
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Set up the database:
   - Create a new MySQL database named `ideanest`
   - Import the SQL files from the `db` directory

4. Configure upload permissions:
   ```bash
   # Set proper permissions for upload directories
   sudo chmod -R 777 user/forms/uploads/
   sudo chmod -R 777 user/forms/uploads/images/
   sudo chmod -R 777 user/forms/uploads/videos/
   sudo chmod -R 777 user/forms/uploads/code_files/
   sudo chmod -R 777 user/forms/uploads/instructions/
   ```

5. Configure your environment:
   - Update database credentials in `Login/Login/db.php`
   - Update email settings in admin settings panel

### Troubleshooting

#### Upload Issues
If you encounter "Directory not writable" errors:
1. Verify upload directory permissions:
   ```bash
   ls -l user/forms/uploads/
   ```
2. Ensure directories have proper write permissions (777 for development)
3. Check PHP upload settings in php.ini:
   - upload_max_filesize
   - post_max_size
   - memory_limit
