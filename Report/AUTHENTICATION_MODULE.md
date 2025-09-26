# Authentication Management Module

## 📋 Overview

The Authentication Management Module handles user authentication, session management, and role-based access control across the IdeaNest platform. It provides secure login mechanisms, session handling, and comprehensive access control for different user roles.

## 🔐 Core Components

### AuthenticationManager Class

```php
/**
 * AuthenticationManager Class
 * Handles user authentication, session management, and role-based access control
 * 
 * Key Methods:
 * - authenticateUser(): Validates user credentials with bcrypt password verification
 * - createSession(): Establishes secure user sessions with timeout management
 * - validateRole(): Implements role-based access control for system resources
 * - handleOAuthCallback(): Processes Google OAuth authentication responses
 */
```

## 🔑 Key Functionalities

### User Authentication

#### Traditional Login
- **Email/Password Validation**: Secure credential verification using bcrypt
- **Account Status Check**: Validates active account status before login
- **Failed Attempt Tracking**: Monitors and limits failed login attempts
- **Password Strength Validation**: Enforces strong password requirements

#### Google OAuth Integration
- **OAuth 2.0 Flow**: Complete Google authentication implementation
- **Profile Completion**: Automatic profile creation from Google data
- **Account Linking**: Links existing accounts with Google profiles
- **Secure Token Handling**: Proper OAuth token management and validation

### Session Management

#### Secure Session Creation
```php
// Session configuration
session_start([
    'cookie_lifetime' => 3600,
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);
```

#### Session Security Features
- **Session Timeout**: Automatic session expiration after inactivity
- **Session Regeneration**: Regular session ID regeneration for security
- **Cross-Site Protection**: CSRF token validation for state changes
- **Secure Cookie Settings**: HTTPOnly and Secure flags for session cookies

### Role-Based Access Control

#### User Roles
- **Student**: Basic user with project submission and idea sharing capabilities
- **Mentor**: Extended access for student mentoring and session management
- **SubAdmin**: Project review and approval capabilities
- **Admin**: Full system administration and user management

#### Access Control Implementation
```php
/**
 * Role validation and access control
 * 
 * @param string $requiredRole Minimum required role for access
 * @param string $userRole Current user's role
 * @return bool Access granted or denied
 */
function validateRole($requiredRole, $userRole) {
    $roleHierarchy = ['student', 'mentor', 'subadmin', 'admin'];
    $requiredLevel = array_search($requiredRole, $roleHierarchy);
    $userLevel = array_search($userRole, $roleHierarchy);
    
    return $userLevel >= $requiredLevel;
}
```

## 🛡️ Security Features

### Password Security
- **Bcrypt Hashing**: Industry-standard password hashing with salt
- **Password Complexity**: Minimum requirements for password strength
- **Password Reset**: Secure password reset with email verification
- **Account Lockout**: Temporary lockout after multiple failed attempts

### Input Validation
- **Email Validation**: Comprehensive email format and domain validation
- **SQL Injection Prevention**: Prepared statements for all database queries
- **XSS Protection**: Input sanitization and output encoding
- **CSRF Protection**: Token-based validation for form submissions

### Session Security
- **Session Hijacking Prevention**: Regular session ID regeneration
- **Session Fixation Protection**: New session ID on authentication
- **Concurrent Session Management**: Control over multiple active sessions
- **Secure Logout**: Complete session cleanup on logout

## 📁 File Structure

```
Login/Login/
├── login.php              # Main login interface
├── register.php           # User registration system
├── google_auth.php        # Google OAuth implementation
├── google_callback.php    # OAuth callback handler
├── forgot_password.php    # Password reset functionality
├── reset_password.php     # Password reset form
├── logout.php            # Secure logout implementation
├── db.php               # Database connection configuration
└── auth_functions.php   # Authentication utility functions
```

## 🔄 Authentication Flow

### Login Process
1. **User Input**: Email and password submission
2. **Input Validation**: Server-side validation of credentials
3. **Database Lookup**: Secure user record retrieval
4. **Password Verification**: Bcrypt password hash comparison
5. **Session Creation**: Secure session establishment
6. **Role Assignment**: User role determination and assignment
7. **Redirect**: Appropriate dashboard redirection based on role

### OAuth Flow
1. **OAuth Initiation**: Redirect to Google OAuth endpoint
2. **User Authorization**: Google account authentication
3. **Callback Processing**: OAuth response handling
4. **Profile Creation**: Automatic user profile creation
5. **Session Establishment**: Secure session creation
6. **Profile Completion**: Additional information collection if needed

## 🔧 Configuration

### Database Configuration
```php
// Database connection settings
$host = "localhost";
$username = "root";
$password = "";
$database = "ideanest";

// Connection with error handling
try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    die("Database connection error");
}
```

### Google OAuth Configuration
```php
// Google OAuth settings
$google_client_id = "your_google_client_id";
$google_client_secret = "your_google_client_secret";
$google_redirect_uri = "https://yourdomain.com/Login/Login/google_callback.php";
```

## 📊 Security Monitoring

### Login Attempt Tracking
- **Failed Login Logging**: Record all failed authentication attempts
- **IP Address Monitoring**: Track login attempts by IP address
- **Suspicious Activity Detection**: Identify unusual login patterns
- **Account Lockout Management**: Temporary account suspension for security

### Session Monitoring
- **Active Session Tracking**: Monitor concurrent user sessions
- **Session Duration Analysis**: Track session length and activity
- **Logout Event Logging**: Record all logout events and reasons
- **Security Event Alerts**: Notifications for security-related events

## 🧪 Testing

### Unit Tests
- **Authentication Logic**: Test credential validation functions
- **Session Management**: Verify session creation and destruction
- **Role Validation**: Test access control mechanisms
- **Password Security**: Validate password hashing and verification

### Integration Tests
- **Database Integration**: Test user data retrieval and storage
- **OAuth Integration**: Verify Google authentication flow
- **Session Persistence**: Test session data across requests
- **Security Features**: Validate CSRF and XSS protection

## 🔍 Troubleshooting

### Common Issues
- **Login Failures**: Check database connectivity and user credentials
- **Session Problems**: Verify session configuration and cookie settings
- **OAuth Errors**: Validate Google OAuth configuration and credentials
- **Access Denied**: Check role assignments and permission settings

### Debug Information
- **Error Logging**: Comprehensive error logging for troubleshooting
- **Debug Mode**: Development mode with detailed error reporting
- **Session Debugging**: Tools for session state inspection
- **Authentication Logs**: Detailed logging of authentication events

## 📈 Performance Optimization

### Database Optimization
- **Connection Pooling**: Efficient database connection management
- **Query Optimization**: Optimized user lookup queries
- **Index Usage**: Strategic indexing on authentication-related columns
- **Caching Strategy**: Session data caching for improved performance

### Security Performance
- **Password Hashing**: Optimized bcrypt cost factor for security vs performance
- **Session Storage**: Efficient session data storage and retrieval
- **Rate Limiting**: Login attempt rate limiting to prevent brute force
- **Token Management**: Efficient CSRF token generation and validation

This authentication module provides a robust foundation for secure user access and session management across the IdeaNest platform.