# IdeaNest Email Management System

Complete email configuration, testing, and monitoring suite for the IdeaNest platform.

## 📧 Available Scripts

### 1. Master Email Manager
```bash
./email_manager.sh
```
**Interactive menu-driven interface for all email operations**
- Configuration validation and testing
- SMTP connectivity testing  
- Email failure monitoring and reporting
- Cron job management
- Log file management
- System maintenance tools

### 2. Email Test Suite
```bash
./email_test_suite.sh [option]
```
**Comprehensive email system testing**

Options:
- `test-all` - Run complete email system test
- `test-config` - Test email configuration only
- `test-smtp` - Test SMTP connection
- `test-weekly` - Test weekly notifications
- `test-mentor` - Test mentor email system
- `test-approval` - Test project approval emails
- `check-cron` - Check cron job status
- `setup-cron` - Setup email cron jobs
- `view-logs` - View email logs
- `clear-logs` - Clear email logs
- `fix-permissions` - Fix file permissions
- `status` - Show email system status

### 3. Email Failure Monitor
```bash
./email_failure_monitor.sh [option]
```
**Monitor and report email system failures**

Options:
- `monitor` - Run complete failure monitoring
- `quick-check` - Run quick health check
- `database` - Check database for email failures
- `smtp` - Test SMTP connectivity
- `cron` - Check cron job execution
- `config` - Check email configuration
- `report` - Generate detailed failure report
- `view-failures` - View recent failures
- `clear-failures` - Clear failure log

### 4. Email Configuration Validator
```bash
./validate_email_config.sh
```
**Quick validation of email system configuration**
- Checks required files
- Validates environment configuration
- Tests database connection
- Tests PHPMailer setup
- Tests SMTP connectivity
- Checks cron jobs
- Validates file permissions
- Optional test email sending

### 5. Email System Setup
```bash
./setup_email_system.sh
```
**One-time setup for the complete email system**
- Creates required directories
- Sets file permissions
- Checks dependencies
- Tests database connection
- Initializes configuration
- Creates log files
- Optional cron job setup

## 🚀 Quick Start Guide

### Initial Setup
1. **Run the setup script:**
   ```bash
   ./setup_email_system.sh
   ```

2. **Configure your email settings:**
   Edit the `.env` file with your SMTP credentials:
   ```bash
   nano .env
   ```

3. **Validate configuration:**
   ```bash
   ./validate_email_config.sh
   ```

4. **Test the complete system:**
   ```bash
   ./email_test_suite.sh test-all
   ```

### Daily Operations
1. **Use the master manager:**
   ```bash
   ./email_manager.sh
   ```

2. **Quick health check:**
   ```bash
   ./email_failure_monitor.sh quick-check
   ```

3. **Monitor failures:**
   ```bash
   ./email_failure_monitor.sh monitor
   ```

## 📊 Monitoring & Maintenance

### Regular Health Checks
```bash
# Daily health check
./email_failure_monitor.sh quick-check

# Weekly comprehensive test
./email_test_suite.sh test-all

# Monthly failure report
./email_failure_monitor.sh report
```

### Log Management
```bash
# View all logs
./email_test_suite.sh view-logs

# View recent failures
./email_failure_monitor.sh view-failures

# Clear old logs
./email_test_suite.sh clear-logs
```

### Cron Job Management
```bash
# Setup cron jobs
./email_test_suite.sh setup-cron

# Check cron status
./email_test_suite.sh check-cron

# Test weekly notifications
./email_test_suite.sh test-weekly

# Test mentor emails
./email_test_suite.sh test-mentor
```

## 🔧 Configuration Files

### Environment Variables (.env)
```bash
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_SECURE=tls
FROM_EMAIL=your-email@gmail.com
FROM_NAME=IdeaNest
```

### Email Configuration (config/email_config.php)
- PHPMailer setup and configuration
- SSL/TLS settings
- Error handling
- Database integration

## 📁 Log Files

All logs are stored in the `logs/` directory:

- `email_test_results.log` - Test execution results
- `email_failures.log` - Email failure tracking
- `weekly_notifications.log` - Weekly notification execution
- `mentor_emails.log` - Mentor email system logs
- `email_failure_report_*.txt` - Detailed failure reports
- `email_statistics_*.txt` - System statistics exports

## 🚨 Troubleshooting

### Common Issues

1. **SMTP Connection Failed**
   ```bash
   ./email_test_suite.sh test-smtp
   ```
   - Check SMTP credentials in .env
   - Verify Gmail app password
   - Check firewall settings

2. **Emails Not Sending**
   ```bash
   ./email_failure_monitor.sh database
   ```
   - Check notification_logs table
   - Verify email settings in admin panel
   - Test SMTP connectivity

3. **Cron Jobs Not Running**
   ```bash
   ./email_test_suite.sh check-cron
   ```
   - Check crontab: `crontab -l`
   - Verify file permissions
   - Check log files for errors

4. **Permission Issues**
   ```bash
   ./email_test_suite.sh fix-permissions
   ```
   - Fix file and directory permissions
   - Check web server user permissions

### Error Diagnosis
```bash
# Complete system diagnosis
./email_manager.sh
# Choose option 7 (Show Email System Status)

# Generate detailed failure report
./email_failure_monitor.sh report

# View troubleshooting guide
./email_manager.sh
# Choose option 22 (Troubleshooting Guide)
```

## 📈 Performance Monitoring

### Automated Monitoring Setup
Add to crontab for automated monitoring:
```bash
# Daily health check at 8 AM
0 8 * * * /opt/lampp/htdocs/IdeaNest/email_failure_monitor.sh quick-check >> /opt/lampp/htdocs/IdeaNest/logs/daily_health.log 2>&1

# Weekly failure report on Sundays at 10 AM
0 10 * * 0 /opt/lampp/htdocs/IdeaNest/email_failure_monitor.sh report >> /opt/lampp/htdocs/IdeaNest/logs/weekly_reports.log 2>&1
```

### Statistics and Reports
```bash
# Export system statistics
./email_manager.sh
# Choose option 16 (Export Email Statistics)

# Generate comprehensive failure report
./email_failure_monitor.sh report
```

## 🔐 Security Considerations

1. **Protect sensitive files:**
   ```bash
   chmod 600 .env
   chmod 755 logs/
   ```

2. **Use app passwords for Gmail:**
   - Enable 2FA on Gmail account
   - Generate app-specific password
   - Use app password in .env file

3. **Regular security updates:**
   - Keep PHPMailer updated via Composer
   - Monitor security advisories
   - Regular system updates

## 📞 Support

For issues and questions:
- Check the troubleshooting guide: `./email_manager.sh` (option 22)
- View system information: `./email_manager.sh` (option 21)
- Generate failure report: `./email_failure_monitor.sh report`
- Email: ideanest.ict@gmail.com

## 📝 Script Summary

| Script | Purpose | Usage |
|--------|---------|-------|
| `email_manager.sh` | Master interface | Interactive menu |
| `email_test_suite.sh` | Complete testing | `./email_test_suite.sh [option]` |
| `email_failure_monitor.sh` | Failure monitoring | `./email_failure_monitor.sh [option]` |
| `validate_email_config.sh` | Configuration validation | `./validate_email_config.sh` |
| `setup_email_system.sh` | Initial setup | `./setup_email_system.sh` |

---

**Made with ❤️ for the IdeaNest Email System**

*For the complete IdeaNest documentation, see the main README.md file.*