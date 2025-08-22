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
  - Edit existing projects with real-time validation
  - Track project status (pending/approved/rejected)
  - View projects in multi-column grid layout
  - Enhanced filtering and sorting options
  - Advanced search functionality with instant results
  - Bookmark favorite projects for quick access
  
- **Project Display**
  - Responsive multi-column grid layout
  - Dynamic project cards with hover effects
  - Quick view modal for project details
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

- **User Interface**
  - Modern responsive design
  - Intuitive navigation
  - Smooth animations and transitions
  - Dark/Light theme support
  - Mobile-friendly interface
  - Accessibility features

### Admin Features
- **Project Review System**
  - Streamlined approval workflow
  - Batch project processing
  - Detailed project analytics
  - Comment and feedback system
  
- **User Management**
  - Comprehensive user control
  - Role-based access control
  - User activity monitoring
  - Profile management

### Sub-Admin Features
- **Project Assignment**
  - Efficient project distribution
  - Task tracking
  - Progress monitoring
  - Team collaboration tools

### Security Features
- **Authentication & Authorization**
  - Secure login system
  - Role-based permissions
  - Session management
  - Password encryption
  - XSS protection
  - CSRF protection

### Technical Features
- **Performance**
  - Optimized database queries
  - Efficient caching system
  - Lazy loading for images
  - Minified assets
  - Compressed resources

- **Responsive Design**
  - Mobile-first approach
  - Cross-browser compatibility
  - Flexible grid system
  - Adaptive layouts

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

3. Configure database:
   - Import SQL files from `/db` directory
   - Update database credentials in `/Login/Login/db.php`

4. Configure email settings:
   - Update SMTP credentials in configuration
   - Test email functionality

5. Set up file permissions:
   ```bash
   chmod 755 -R user/forms/uploads/
   ```

### Configuration
1. Database Setup:
   - Create required databases
   - Import schema from `/db/ideanest.sql`
   - Configure connection settings

2. Environment Setup:
   - Configure Apache virtual host
   - Set up SSL certificate (recommended)
   - Configure file upload limits

---

## 📖 Documentation

### User Guide
- Project submission guidelines
- File upload specifications
- Search and filter usage
- Bookmark management
- Profile customization

### Admin Guide
- Project approval workflow
- User management procedures
- System configuration
- Reporting features

### Developer Guide
- Code structure
- API documentation
- Database schema
- Security implementations

---

## 🔒 Security

See [SECURITY.md](SECURITY.md) for security policy and reporting vulnerabilities.

---

## 🤝 Contributing

1. Fork the repository
2. Create feature branch
3. Commit changes
4. Push to branch
5. Open pull request

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 👥 Team

- Project Lead: [Name]
- Frontend Developer: [Name]
- Backend Developer: [Name]
- Database Administrator: [Name]
- UI/UX Designer: [Name]

---

## 🔄 Updates & Changelog

### Latest Updates (August 2025)
- Implemented multi-column grid layout for projects
- Enhanced bookmark functionality
- Added real-time project updates
- Improved mobile responsiveness
- Enhanced search capabilities
- Added project filtering options
- Improved user interface animations
- Enhanced security features

---

## 📞 Support

For support, please contact [support@ideanest.com](mailto:support@ideanest.com)
