# Security Policy

## Supported Versions

Currently supported versions of IdeaNest with security updates:

| Version | Supported          | Release Date | End of Support |
| ------- | ------------------ | ------------ | -------------- |
| 2.1.x   | :white_check_mark: | Aug 2025     | Current        |
| 2.0.x   | :white_check_mark: | Jun 2025     | Dec 2025      |
| 1.5.x   | :white_check_mark: | Mar 2025     | Sep 2025      |
| < 1.5   | :x:                | -            | -             |

## Security Features

IdeaNest implements the following security measures:

### Authentication & Authorization
- Secure session management
- Role-based access control (RBAC)
- Password hashing using bcrypt
- Two-factor authentication (2FA) support
- Automatic session timeout
- Login attempt rate limiting

### Data Protection
- Input validation and sanitization
- Prepared SQL statements
- XSS protection
- CSRF protection
- File upload validation
- Secure file storage

### Infrastructure Security
- SSL/TLS encryption
- Regular security updates
- Database encryption
- Backup encryption
- Server hardening

## Reporting a Vulnerability

We take security vulnerabilities seriously. Please report them through the following channels:

### Reporting Process
1. **Email**: ideanest.ict@gmail.com
### What to Include
- Detailed description of the vulnerability
- Steps to reproduce
- Impact assessment
- Possible mitigation suggestions
- Your contact information

### Response Timeline
- **Initial Response**: Within 24 hours
- **Status Update**: Within 72 hours
- **Fix Timeline**: Based on severity
  - Critical: 24-48 hours
  - High: 1 week
  - Medium: 2 weeks
  - Low: Next release cycle

### Severity Levels
1. **Critical**
   - Remote code execution
   - Database breach
   - Authentication bypass

2. **High**
   - Information disclosure
   - Privilege escalation
   - Session hijacking

3. **Medium**
   - Cross-site scripting (XSS)
   - Cross-site request forgery (CSRF)
   - SQL injection attempts

4. **Low**
   - UI/UX vulnerabilities
   - Minor configuration issues
   - Non-critical information disclosure

## Security Best Practices

### For Developers
- Follow secure coding guidelines
- Implement input validation
- Use parameterized queries
- Regular security training
- Code review focus on security

### For System Administrators
- Regular security updates
- Monitoring and logging
- Backup procedures
- Access control management
- Security audit trails

### For Users
- Strong password policies
- Regular password changes
- 2FA enablement
- Secure file handling
- Report suspicious activities

## Compliance

IdeaNest adheres to:
- GDPR requirements
- OWASP security standards
- Industry best practices
- Data protection regulations

## Security Updates

Security updates are released:
- Critical patches: Immediate
- Security fixes: Monthly
- Regular updates: Quarterly

## Contact

Security Team:
- Email: ideanest.ict@gmail.com
- PGP Key: [Security Team PGP Key](https://ideanest.com/security/pgp-key)
