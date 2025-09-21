# GitHub Integration Setup Guide

## Overview
This guide explains how to set up and use the GitHub integration feature in IdeaNest.

## Database Setup

1. **Add GitHub fields to the register table:**
   ```sql
   -- Run this SQL script to add GitHub fields
   ALTER TABLE register 
   ADD COLUMN github_username VARCHAR(100) DEFAULT NULL,
   ADD COLUMN github_profile_url VARCHAR(255) DEFAULT NULL,
   ADD COLUMN github_repos_count INT DEFAULT 0,
   ADD COLUMN github_last_sync TIMESTAMP NULL DEFAULT NULL;

   -- Add index for GitHub username
   ALTER TABLE register ADD INDEX idx_github_username (github_username);
   ```

2. **Execute the SQL script:**
   - Navigate to phpMyAdmin or your MySQL client
   - Select the `ideanest` database
   - 

## Features

### User Profile Settings
- **GitHub Username Input**: Users can enter their GitHub username
- **Real-time Sync**: Sync button to fetch latest GitHub data
- **Profile Display**: Shows connected GitHub profile with stats

### Dashboard Integration
- **GitHub Profile Card**: Displays user's GitHub profile information
- **Repository Showcase**: Shows recent repositories with details
- **GitHub Statistics**: Followers, following, and repository counts

### API Integration
- **GitHub API v3**: Fetches public profile and repository data
- **Error Handling**: Graceful handling of API failures
- **Rate Limiting**: Respects GitHub API rate limits

## Usage Instructions

### For Users

1. **Connect GitHub Profile:**
   - Go to Profile Settings
   - Enter your GitHub username in the GitHub Integration section
   - Click "Sync Now" to fetch your profile data
   - Save your profile settings

2. **View GitHub Profile:**
   - Your GitHub profile will appear on the dashboard
   - Shows your avatar, bio, and statistics
   - Displays your recent repositories

3. **Update GitHub Data:**
   - Use the "Sync Now" button to refresh your GitHub data
   - Data is automatically synced when you update your profile

### For Administrators

1. **Monitor GitHub Integration:**
   - Track GitHub integration usage in admin analytics
   - View user GitHub connection statistics
   - Monitor API usage and errors

## File Structure

```
user/
├── github_service.php          # GitHub API service functions
├── github_profile.php          # GitHub profile display component
├── sync_github.php            # AJAX sync endpoint
├── user_profile_setting.php   # Updated profile settings with GitHub
└── api/
    └── github_sync.php        # GitHub sync API endpoint
```

## API Endpoints

### Sync GitHub Profile
- **URL**: `user/sync_github.php`
- **Method**: POST
- **Body**: `{"username": "github_username"}`
- **Response**: GitHub profile data or error message

### GitHub Service Functions
- `fetchGitHubProfile($username)` - Fetch user profile
- `fetchGitHubRepos($username)` - Fetch user repositories
- `syncGitHubData($conn, $userId, $username)` - Sync data to database

## Security Features

- **Input Validation**: GitHub username format validation
- **API Error Handling**: Graceful handling of GitHub API errors
- **Session Management**: Secure user session validation
- **SQL Injection Prevention**: Prepared statements for database queries

## Troubleshooting

### Common Issues

1. **GitHub API Rate Limiting:**
   - GitHub API allows 60 requests per hour for unauthenticated requests
   - If rate limited, users will see an error message
   - Wait for the rate limit to reset

2. **Invalid Username:**
   - Ensure the GitHub username exists and is public
   - Check username format (alphanumeric and hyphens only)

3. **Database Connection:**
   - Ensure database connection is working
   - Check if GitHub fields are added to register table

### Error Messages

- "GitHub username not found" - Username doesn't exist on GitHub
- "Invalid GitHub username format" - Username contains invalid characters
- "Failed to sync GitHub profile" - Database update failed
- "Network error occurred" - GitHub API is unreachable

## Future Enhancements

- **GitHub OAuth Integration**: Full GitHub authentication
- **Repository Analytics**: Detailed repository statistics
- **Contribution Graphs**: GitHub contribution visualization
- **Team Integration**: Connect team GitHub organizations
- **Automated Sync**: Periodic background synchronization

## Support

For issues or questions about GitHub integration:
- Check the troubleshooting section above
- Review the error messages in browser console
- Contact system administrator for database issues