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
  - View projects in responsive grid layout (4 columns on desktop)
  - Enhanced filtering and sorting options
  - Advanced search functionality with instant results
  - Bookmark favorite projects for quick access
  - Real-time like system with animations
  - Interactive comment system with nested replies
  
- **Project Display**
  - Modern card-based layout with smooth transitions
  - Responsive grid system:
    - 4 cards per row on large screens
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

- **Interactive Features**
  - Real-time like system with animations
  - Comment system with:
    - Nested replies
    - Real-time updates
    - Markdown support
    - Emoji support
  - Social sharing options
  - Project bookmarking
  - Quick actions menu

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
  - Modern responsive design
  - Intuitive navigation
  - Smooth animations and transitions
  - Dark/Light theme support
  - Mobile-friendly interface
  - Accessibility features
  - Loading states and error handling
  - Toast notifications

### Admin Features
- **Project Review System**
  - Streamlined approval workflow
  - Batch project processing
  - Detailed project analytics
  - Comment and feedback system
  - Activity monitoring
  
- **User Management**
  - Comprehensive user control
  - Role-based access control
  - User activity monitoring
  - Profile management
  - Bulk actions support

### Sub-Admin Features
- **Project Assignment**
  - Efficient project distribution
  - Task tracking
  - Progress monitoring
  - Team collaboration tools
  - Performance analytics

### Security Features
- **Authentication & Authorization**
  - Secure login system
  - Role-based permissions
  - Session management
  - Password encryption
  - XSS protection
  - CSRF protection
  - Rate limiting
  - Input validation

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

### Building Assets
```bash
# Install dependencies
npm install

# Build assets
npm run build
```

### Running Tests
```bash
# Run PHPUnit tests
./vendor/bin/phpunit

# Run JavaScript tests
npm test
```

### Coding Standards
- Follow PSR-12 coding standard
- Use ESLint for JavaScript
- Run code quality checks:
```bash
composer check-style
composer fix-style
```

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

## 🙏 Acknowledgments

- PHP community
- Bootstrap team
- Font Awesome
- All contributors

---

## 📞 Support

For support, email support@ideanest.com or join our Discord channel.
