# GitHub Integration Feature

## Overview
The GitHub Integration feature allows users to connect their GitHub profiles to IdeaNest, showcasing their repositories, contributions, and GitHub statistics directly within their profile.

## Features

### 🔗 Profile Connection
- Connect GitHub username in Profile Settings
- Automatic profile data synchronization
- Display GitHub bio, location, and company information

### 📊 Statistics Display
- Repository count
- Followers and following count
- Last sync timestamp
- Real-time sync capability

### 📁 Repository Showcase
- Display user's public repositories
- Show repository descriptions, languages, stars, and forks
- Direct links to GitHub repositories
- Private repository indicators

### 🔄 Sync Management
- Manual sync button for real-time updates
- Automatic data refresh on profile updates
- Error handling for invalid usernames

## Installation

### 1. Database Setup
Run the setup script to add required database tables and columns:

```bash
# Navigate to IdeaNest directory
cd /opt/lampp/htdocs/IdeaNest

# Run the setup script via browser
http://localhost/IdeaNest/setup_github_integration.php
```

### 2. File Structure
```
IdeaNest/
├── user/
│   ├── github_service.php          # GitHub API service class
│   ├── github_profile.php          # GitHub profile display page
│   ├── user_profile_setting.php    # Updated with GitHub integration
│   └── api/
│       └── github_sync.php         # AJAX API endpoint
├── github_integration_update.sql   # Database schema updates
└── setup_github_integration.php    # Setup script
```

## Usage

### For Users

1. **Connect GitHub Account**
   - Go to Profile Settings
   - Enter your GitHub username in the GitHub Integration section
   - Save changes to sync your profile

2. **View GitHub Profile**
   - Navigate to GitHub section in sidebar
   - View your repositories and statistics
   - Use "Sync Now" button to refresh data

3. **Manage Connection**
   - Update username in Profile Settings
   - Disconnect account if needed
   - Data automatically refreshes on profile updates

### For Developers

#### GitHub Service Class
```php
$github_service = new GitHubService($conn);

// Sync user's GitHub data
$result = $github_service->syncUserGitHub($user_id, $github_username);

// Get user's GitHub profile data
$github_data = $github_service->getUserGitHubData($user_id);

// Get user's repositories
$repos = $github_service->getUserRepos($user_id);
```

#### API Endpoints
```javascript
// Sync GitHub data
fetch('/user/api/github_sync.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'sync' })
});

// Connect new GitHub account
fetch('/user/api/github_sync.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ 
        action: 'connect', 
        username: 'github_username' 
    })
});
```

## Database Schema

### Updated `register` Table
```sql
ALTER TABLE `register` 
ADD COLUMN `github_username` VARCHAR(100) DEFAULT NULL,
ADD COLUMN `github_profile_url` VARCHAR(255) DEFAULT NULL,
ADD COLUMN `github_repos_count` INT DEFAULT 0,
ADD COLUMN `github_followers` INT DEFAULT 0,
ADD COLUMN `github_following` INT DEFAULT 0,
ADD COLUMN `github_bio` TEXT DEFAULT NULL,
ADD COLUMN `github_location` VARCHAR(100) DEFAULT NULL,
ADD COLUMN `github_company` VARCHAR(100) DEFAULT NULL,
ADD COLUMN `github_last_sync` TIMESTAMP NULL DEFAULT NULL;
```

### New `user_github_repos` Table
```sql
CREATE TABLE `user_github_repos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `repo_name` VARCHAR(255) NOT NULL,
    `repo_full_name` VARCHAR(255) NOT NULL,
    `repo_description` TEXT DEFAULT NULL,
    `repo_url` VARCHAR(255) NOT NULL,
    `language` VARCHAR(50) DEFAULT NULL,
    `stars_count` INT DEFAULT 0,
    `forks_count` INT DEFAULT 0,
    `is_private` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `synced_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `register`(`id`) ON DELETE CASCADE
);
```

## API Integration

### GitHub API Usage
- Uses GitHub REST API v3
- No authentication required for public data
- Rate limit: 60 requests per hour per IP
- Fetches user profile and repository data

### Error Handling
- Invalid username detection
- API rate limit handling
- Network error management
- Database error recovery

## Security Considerations

- No GitHub tokens stored (public data only)
- Input validation for usernames
- SQL injection prevention
- Session-based authentication
- CSRF protection on forms

## Troubleshooting

### Common Issues

1. **GitHub User Not Found**
   - Verify username spelling
   - Check if GitHub profile is public
   - Ensure user exists on GitHub

2. **Sync Failures**
   - Check internet connectivity
   - Verify GitHub API availability
   - Check for rate limiting

3. **Database Errors**
   - Run setup script again
   - Check database permissions
   - Verify table structure

### Debug Mode
Enable error reporting in development:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Future Enhancements

- GitHub OAuth integration for private repositories
- Contribution graph display
- Repository language statistics
- Commit activity timeline
- Integration with project submissions
- GitHub Actions status display

## Contributing

When contributing to this feature:
1. Follow existing code style
2. Add proper error handling
3. Update documentation
4. Test with various GitHub profiles
5. Consider API rate limits

## License

This feature is part of the IdeaNest project and follows the same MIT license terms.