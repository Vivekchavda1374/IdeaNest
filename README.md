# IdeaNest - Collaborative Academic Project Platform

[![License](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.2.4-blue.svg)](https://www.php.net/)
[![MySQL Version](https://img.shields.io/badge/MySQL-10.4.28--MariaDB-blue.svg)](https://www.mysql.com/)

IdeaNest is a web-based platform designed to facilitate the management, sharing, and review of academic projects. It provides a comprehensive suite of features for students, sub-admins, and administrators, streamlining the project lifecycle from submission to approval.

## ✨ Latest Updates

### Production-Ready Security & Performance (December 2024)
- **Security Enhancements**: CSRF protection, input sanitization, rate limiting
- **Authentication System**: Secure session management and user validation
- **File Management**: Secure upload/download system with proper permissions
- **Database Security**: Prepared statements and error handling
- **Admin Panel**: Enhanced subadmin functionality with project approval workflow
- **UI/UX Improvements**: Modern responsive design with Bootstrap 5
- **Error Handling**: Production-ready error logging and user-friendly messages

## 🚀 Features

### Project Management
- **Secure Project Submission**: Multi-file upload with validation
- **Project Approval Workflow**: Three-tier system (User → SubAdmin → Admin)
- **Real-time Status Tracking**: pending/approved/rejected with notifications
- **File Security**: Protected uploads with access control
- **Project Categories**: Software/Hardware classification system

### Interactive Features
- Real-time AJAX-based like system
  - Instant like count updates
  - Animated button states
  - Error handling with toast notifications
- Enhanced bookmark system
  - Real-time state updates
  - Visual feedback animations
  - Category-based organization
- Dynamic comment system
  - Nested replies
  - Like functionality
  - Real-time updates

- **Project Display**
  - Modern card-based layout with smooth transitions
  - Responsive grid system:
    - 4-5 cards per row on large screens
    - 3 cards per row on medium screens
    - 2 cards per row on tablets
    - 1 card per row on mobile
  - Dynamic project cards with:
    - Hover effects and animations
    - Like/bookmark functionality
    - Quick view options
    - Loading states
  - Project classification badges
  - Project ownership indicators
  - Progress tracking and status updates

- **File Management**
  - Support for multiple file types:
    - Images (jpg, jpeg, png, gif)
    - Videos (mp4, avi, mov)
    - Code files (zip, rar)
    - Documentation (pdf)
  - Real-time upload progress
  - File type validation
  - Secure file storage
  - Preview functionality

- **User Interface**
  - Modern responsive design with glassmorphism effects
  - Intuitive navigation
  - Smooth animations and transitions
  - Dark/Light theme support
  - Mobile-friendly interface
  - Accessibility features
  - Loading states and error handling
  - Toast notifications

### Admin Features
- **Project Review System**
  - Final approval authority for all projects
  - Comprehensive project analytics dashboard
  - Bulk project management capabilities
  - Email notifications for status changes
  - Advanced filtering and search
  
- **User Management**
  - Role-based access control (Admin/SubAdmin/User)
  - User registration approval system
  - Activity monitoring and logging
  - Profile management interface

### SubAdmin Features
- **Project Assignment by Classification**
  - Automatic assignment based on software/hardware expertise
  - First-level project review and approval
  - Rejection with detailed feedback system
  - Project statistics and performance metrics
  - Modal-based project detail viewing

### Security Features
- **Production-Grade Security**
  - CSRF token validation on all forms
  - Input sanitization and XSS protection
  - Rate limiting (30 requests/minute)
  - Secure file upload with type validation
  - SQL injection prevention with prepared statements
  - Session security with httpOnly cookies
  - Error logging without information disclosure
  - Security headers (X-Frame-Options, CSP, etc.)

### Technical Features
- **Performance**
  - Optimized database queries
  - Efficient caching system
  - Lazy loading for images
  - Minified assets
  - Compressed resources
  - AJAX-based interactions
  - Debounced search
  - Throttled API calls

- **Responsive Design**
  - Mobile-first approach
  - Cross-browser compatibility
  - Flexible grid system
  - Adaptive layouts
  - Touch-friendly interfaces
  - Progressive enhancement

### Error Handling
- Graceful error recovery
- User-friendly error messages
- Offline support
- Auto-retry mechanisms
- Form validation
- Data persistence

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.2.4 or higher
- MySQL 10.4.28-MariaDB or higher
- Apache Web Server
- Composer for dependency management

### Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/IdeaNest.git
```

2. Install dependencies:
```bash
composer install
```

3. Set up database:
- Create a new MySQL database
- Import the SQL files from the `db` folder
- Configure database connection in `Login/Login/db.php`

4. Configure web server:
- Point your web server to the project directory
- Ensure proper permissions for uploads folders

5. Start using the application:
- Navigate to the project URL
- Register a new account or log in
- Start exploring features

---

## 🛠 Development

### Project Structure
```
IdeaNest/
├── Admin/
│   ├── subadmin/          # SubAdmin panel
│   └── project_notification.php
├── user/
│   ├── uploads/           # Secure file storage
│   ├── Blog/             # Blog functionality
│   └── forms/            # Project submission forms
├── Login/Login/          # Authentication system
├── config/               # Security configuration
├── includes/             # Error handlers
└── assets/              # CSS/JS assets
```

### Security Configuration
- Environment-based settings in `config/security.php`
- Error handling in `includes/error_handler.php`
- File upload security in `user/uploads/.htaccess`
- Rate limiting and CSRF protection enabled

---

## 📝 Contributing

1. Fork the repository
2. Create your feature branch:
```bash
git checkout -b feature/AmazingFeature
```
3. Commit your changes:
```bash
git commit -m 'Add some AmazingFeature'
```
4. Push to the branch:
```bash
git push origin feature/AmazingFeature
```
5. Open a Pull Request

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 🚀 Recent Improvements

### Database & Backend
- Fixed column reference errors in project approval system
- Implemented secure file upload/download mechanism
- Added proper error handling and logging
- Enhanced database query security

### Frontend & UI
- Bootstrap 5 integration with modern components
- Responsive design for all screen sizes
- Modal-based project viewing system
- Improved form validation and user feedback

### Security Enhancements
- Production-ready security headers
- CSRF protection on all forms
- Rate limiting implementation
- Secure session management

---

## 🙏 Acknowledgments

- PHP community for security best practices
- Bootstrap team for responsive framework
- Font Awesome for iconography
- All contributors and testers

---

## 🔧 Configuration

### Database Setup
1. Import SQL files from `db/` folder
2. Configure connection in `Login/Login/db.php`
3. Ensure proper table structure for projects, users, subadmins

### File Permissions
```bash
chmod 755 user/uploads/
chmod 644 user/uploads/*
chmod 755 logs/
```

### Production Deployment
- Enable HTTPS in `.htaccess`
- Set `display_errors = Off` in PHP
- Configure proper error logging
- Set secure session settings

## 📞 Support

For support, email ideanest.ict@gmail.com or create an issue on GitHub.
