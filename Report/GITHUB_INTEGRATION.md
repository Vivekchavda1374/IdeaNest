# GitHub Integration Module

## 📋 Overview

The GitHub Integration Module provides seamless connectivity between IdeaNest and GitHub, enabling users to link their GitHub profiles, sync repository data, and display their development portfolio within the platform. It implements efficient API handling with rate limiting and caching strategies.

## 🏗️ Core Components

### GitHubIntegration Class

```php
/**
 * GitHubIntegration Class
 * Manages GitHub API connectivity and profile synchronization
 * 
 * Key Methods:
 * - syncGitHubProfile(): Fetches and displays GitHub profile data
 * - fetchRepositories(): Retrieves repository information with error handling
 * - updateProfileData(): Synchronizes GitHub data with user profiles
 * - handleApiRateLimit(): Manages API rate limiting with efficient caching
 */
```

## 📁 Integration Structure

```
user/
├── github_profile.php        # GitHub profile display page
├── github_service.php        # GitHub API service class
├── github_ajax.php          # AJAX endpoints for GitHub operations
├── sync_github.php          # Profile synchronization handler
├── github_loader.js         # Frontend JavaScript for GitHub features
└── user_profile_setting.php # Profile settings with GitHub integration
```

## 🔗 GitHub API Integration

### API Configuration
```php
/**
 * GitHub API configuration and initialization
 */
class GitHubService {
    private $apiUrl = 'https://api.github.com';
    private $userAgent = 'IdeaNest-Platform/1.0';
    private $rateLimit = 60; // requests per hour for unauthenticated
    private $cache = [];
    
    public function __construct($accessToken = null) {
        $this->accessToken = $accessToken;
        $this->rateLimit = $accessToken ? 5000 : 60;
    }
}
```

### Core API Methods

#### Profile Data Retrieval
```php
/**
 * Fetch GitHub user profile information
 * 
 * @param string $username GitHub username
 * @return array User profile data or error information
 */
public function getUserProfile($username) {
    $cacheKey = "github_profile_{$username}";
    
    // Check cache first
    if ($cached = $this->getFromCache($cacheKey)) {
        return $cached;
    }
    
    $url = "{$this->apiUrl}/users/{$username}";
    $response = $this->makeApiRequest($url);
    
    if ($response['success']) {
        $this->saveToCache($cacheKey, $response['data'], 3600); // 1 hour cache
        return $response['data'];
    }
    
    return $response;
}
```

#### Repository Data Fetching
```php
/**
 * Fetch user repositories with pagination support
 * 
 * @param string $username GitHub username
 * @param int $page Page number for pagination
 * @param int $perPage Items per page (max 100)
 * @return array Repository data with pagination info
 */
public function getUserRepositories($username, $page = 1, $perPage = 30) {
    $cacheKey = "github_repos_{$username}_p{$page}";
    
    if ($cached = $this->getFromCache($cacheKey)) {
        return $cached;
    }
    
    $url = "{$this->apiUrl}/users/{$username}/repos";
    $params = [
        'page' => $page,
        'per_page' => $perPage,
        'sort' => 'updated',
        'direction' => 'desc'
    ];
    
    $response = $this->makeApiRequest($url, $params);
    
    if ($response['success']) {
        $this->saveToCache($cacheKey, $response['data'], 1800); // 30 minutes cache
        return $response['data'];
    }
    
    return $response;
}
```

## 🔄 Profile Synchronization

### Sync Process Flow
1. **User Initiation**: User enters GitHub username in profile settings
2. **Validation**: System validates GitHub username existence
3. **Data Fetching**: Retrieve profile and repository data from GitHub API
4. **Data Processing**: Process and format GitHub data for display
5. **Database Update**: Store synchronized data in local database
6. **Cache Management**: Update cache with fresh data
7. **User Notification**: Confirm successful synchronization

### Database Schema
```sql
-- GitHub integration fields in user table
ALTER TABLE register ADD COLUMN github_username VARCHAR(100);
ALTER TABLE register ADD COLUMN github_profile_data JSON;
ALTER TABLE register ADD COLUMN github_last_sync TIMESTAMP;
ALTER TABLE register ADD COLUMN github_sync_status ENUM('never', 'success', 'failed', 'pending');

-- Separate table for repository data
CREATE TABLE github_repositories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    repo_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    description TEXT,
    html_url VARCHAR(500) NOT NULL,
    language VARCHAR(50),
    stars_count INT DEFAULT 0,
    forks_count INT DEFAULT 0,
    updated_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES register(id),
    UNIQUE KEY unique_user_repo (user_id, repo_id)
);
```

## ⚡ Rate Limiting and Caching

### Rate Limit Management
```php
/**
 * Handle GitHub API rate limiting
 * 
 * @param array $headers Response headers from GitHub API
 * @return array Rate limit information
 */
private function handleRateLimit($headers) {
    $rateLimit = [
        'limit' => $headers['X-RateLimit-Limit'] ?? $this->rateLimit,
        'remaining' => $headers['X-RateLimit-Remaining'] ?? 0,
        'reset' => $headers['X-RateLimit-Reset'] ?? time() + 3600
    ];
    
    // Store rate limit info in session
    $_SESSION['github_rate_limit'] = $rateLimit;
    
    // If rate limit exceeded, calculate wait time
    if ($rateLimit['remaining'] <= 0) {
        $waitTime = $rateLimit['reset'] - time();
        throw new Exception("Rate limit exceeded. Try again in {$waitTime} seconds.");
    }
    
    return $rateLimit;
}
```

### Caching Strategy
```php
/**
 * Multi-level caching for GitHub data
 */
class GitHubCache {
    private $memoryCache = [];
    private $sessionCache = true;
    private $fileCache = true;
    
    /**
     * Get data from cache with fallback levels
     */
    public function get($key) {
        // Level 1: Memory cache
        if (isset($this->memoryCache[$key])) {
            return $this->memoryCache[$key];
        }
        
        // Level 2: Session cache
        if ($this->sessionCache && isset($_SESSION['github_cache'][$key])) {
            $cached = $_SESSION['github_cache'][$key];
            if ($cached['expires'] > time()) {
                $this->memoryCache[$key] = $cached['data'];
                return $cached['data'];
            }
        }
        
        // Level 3: File cache
        if ($this->fileCache) {
            return $this->getFromFileCache($key);
        }
        
        return null;
    }
}
```

## 🎨 Frontend Integration

### AJAX Profile Sync
```javascript
/**
 * GitHub profile synchronization via AJAX
 */
function syncGitHubProfile() {
    const username = document.getElementById('github_username').value;
    const syncButton = document.getElementById('sync_github');
    const statusDiv = document.getElementById('sync_status');
    
    if (!username.trim()) {
        showError('Please enter a GitHub username');
        return;
    }
    
    // Show loading state
    syncButton.disabled = true;
    syncButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';
    statusDiv.innerHTML = '<div class="alert alert-info">Fetching GitHub data...</div>';
    
    fetch('github_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            action: 'sync_profile',
            username: username
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('GitHub profile synchronized successfully!');
            updateProfileDisplay(data.profile);
        } else {
            showError(data.message || 'Synchronization failed');
        }
    })
    .catch(error => {
        showError('Network error occurred');
        console.error('Sync error:', error);
    })
    .finally(() => {
        syncButton.disabled = false;
        syncButton.innerHTML = '<i class="fab fa-github"></i> Sync Now';
    });
}
```

### Repository Display
```javascript
/**
 * Display GitHub repositories with pagination
 */
function displayRepositories(repos, totalCount) {
    const container = document.getElementById('github_repositories');
    
    if (!repos || repos.length === 0) {
        container.innerHTML = '<p class="text-muted">No repositories found.</p>';
        return;
    }
    
    let html = '<div class="row">';
    
    repos.forEach(repo => {
        html += `
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="card-title">
                            <a href="${repo.html_url}" target="_blank" class="text-decoration-none">
                                <i class="fab fa-github"></i> ${repo.name}
                            </a>
                        </h6>
                        <p class="card-text text-muted small">${repo.description || 'No description'}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                ${repo.language ? `<span class="badge badge-secondary">${repo.language}</span>` : ''}
                            </small>
                            <small class="text-muted">
                                <i class="fas fa-star"></i> ${repo.stargazers_count}
                                <i class="fas fa-code-branch ml-2"></i> ${repo.forks_count}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}
```

## 🔒 Security Considerations

### API Security
- **No Token Storage**: Personal access tokens not stored in database
- **Rate Limit Respect**: Proper rate limiting to avoid API abuse
- **Input Validation**: GitHub username validation and sanitization
- **Error Handling**: Secure error messages without sensitive information

### Data Privacy
- **Public Data Only**: Only public GitHub information is accessed
- **User Consent**: Explicit user consent for GitHub integration
- **Data Retention**: Configurable data retention policies
- **Cache Security**: Secure caching with appropriate expiration

## 📊 Analytics and Monitoring

### Integration Metrics
```php
/**
 * Track GitHub integration usage and performance
 */
function trackGitHubMetrics() {
    return [
        'total_synced_users' => getTotalSyncedUsers(),
        'sync_success_rate' => getSyncSuccessRate(),
        'api_calls_today' => getApiCallsCount('today'),
        'cache_hit_rate' => getCacheHitRate(),
        'average_sync_time' => getAverageSyncTime(),
        'popular_languages' => getPopularLanguages()
    ];
}
```

### Performance Monitoring
- **API Response Times**: Monitor GitHub API response performance
- **Cache Effectiveness**: Track cache hit rates and performance gains
- **Sync Success Rates**: Monitor synchronization success and failure rates
- **User Engagement**: Track GitHub feature usage and adoption

## 🧪 Testing

### Unit Tests
```php
/**
 * Test GitHub API integration functionality
 */
class GitHubIntegrationTest extends PHPUnit\Framework\TestCase {
    
    public function testUserProfileFetch() {
        $github = new GitHubService();
        $profile = $github->getUserProfile('octocat');
        
        $this->assertIsArray($profile);
        $this->assertArrayHasKey('login', $profile);
        $this->assertEquals('octocat', $profile['login']);
    }
    
    public function testRateLimitHandling() {
        $github = new GitHubService();
        
        // Mock rate limit exceeded scenario
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Rate limit exceeded');
        
        $github->handleRateLimitExceeded();
    }
}
```

### Integration Tests
- **API Connectivity**: Test GitHub API connection and responses
- **Database Integration**: Verify data storage and retrieval
- **Cache Functionality**: Test caching mechanisms and expiration
- **Error Handling**: Validate error scenarios and recovery

## 🔧 Configuration

### Environment Configuration
```php
// GitHub integration settings
$githubConfig = [
    'api_url' => 'https://api.github.com',
    'user_agent' => 'IdeaNest-Platform/1.0',
    'cache_duration' => [
        'profile' => 3600,      // 1 hour
        'repositories' => 1800,  // 30 minutes
        'rate_limit' => 300     // 5 minutes
    ],
    'rate_limits' => [
        'unauthenticated' => 60,    // per hour
        'authenticated' => 5000     // per hour
    ],
    'pagination' => [
        'default_per_page' => 30,
        'max_per_page' => 100
    ]
];
```

## 🔍 Troubleshooting

### Common Issues
- **Rate Limit Exceeded**: Implement proper caching and request throttling
- **Invalid Username**: Validate GitHub username format and existence
- **API Timeouts**: Implement retry logic with exponential backoff
- **Cache Issues**: Monitor cache performance and implement fallbacks

### Debug Tools
- **API Request Logging**: Log all GitHub API requests and responses
- **Cache Monitoring**: Track cache performance and hit rates
- **Error Tracking**: Comprehensive error logging and monitoring
- **Performance Metrics**: Monitor sync times and success rates

This GitHub Integration Module provides seamless connectivity between IdeaNest and GitHub, enabling users to showcase their development work and maintain synchronized profiles across both platforms.