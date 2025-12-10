<?php
/**
 * Query Result Caching System
 * Caches expensive query results to reduce database load
 */

class QueryCache {
    private $cache_dir;
    private $default_ttl = 300; // 5 minutes default
    
    public function __construct($cache_dir = null) {
        $this->cache_dir = $cache_dir ?? __DIR__ . '/../cache/queries';
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }
    
    /**
     * Get cached result or execute query
     */
    public function remember($key, $callback, $ttl = null) {
        $ttl = $ttl ?? $this->default_ttl;
        $cache_file = $this->getCacheFile($key);
        
        // Check if cache exists and is valid
        if (file_exists($cache_file)) {
            $cache_data = json_decode(file_get_contents($cache_file), true);
            
            if ($cache_data && time() < $cache_data['expires_at']) {
                return $cache_data['data'];
            }
        }
        
        // Execute callback and cache result
        $data = $callback();
        
        $cache_data = [
            'data' => $data,
            'expires_at' => time() + $ttl,
            'created_at' => time()
        ];
        
        file_put_contents($cache_file, json_encode($cache_data));
        
        return $data;
    }
    
    /**
     * Clear specific cache
     */
    public function forget($key) {
        $cache_file = $this->getCacheFile($key);
        if (file_exists($cache_file)) {
            unlink($cache_file);
        }
    }
    
    /**
     * Clear all cache
     */
    public function flush() {
        $files = glob($this->cache_dir . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
    
    /**
     * Clear expired cache
     */
    public function clearExpired() {
        $files = glob($this->cache_dir . '/*.cache');
        $cleared = 0;
        
        foreach ($files as $file) {
            $cache_data = json_decode(file_get_contents($file), true);
            if ($cache_data && time() >= $cache_data['expires_at']) {
                unlink($file);
                $cleared++;
            }
        }
        
        return $cleared;
    }
    
    /**
     * Get cache file path
     */
    private function getCacheFile($key) {
        $hash = md5($key);
        return $this->cache_dir . '/' . $hash . '.cache';
    }
    
    /**
     * Get cache statistics
     */
    public function getStats() {
        $files = glob($this->cache_dir . '/*.cache');
        $total = count($files);
        $expired = 0;
        $total_size = 0;
        
        foreach ($files as $file) {
            $total_size += filesize($file);
            $cache_data = json_decode(file_get_contents($file), true);
            if ($cache_data && time() >= $cache_data['expires_at']) {
                $expired++;
            }
        }
        
        return [
            'total_entries' => $total,
            'expired_entries' => $expired,
            'active_entries' => $total - $expired,
            'total_size' => $total_size,
            'total_size_mb' => round($total_size / 1024 / 1024, 2)
        ];
    }
}

// Create global instance
$query_cache = new QueryCache();

/**
 * Helper function for caching queries
 */
function cache_query($key, $callback, $ttl = 300) {
    global $query_cache;
    return $query_cache->remember($key, $callback, $ttl);
}

/**
 * Example usage:
 * 
 * // Cache project list for 5 minutes
 * $projects = cache_query('projects_approved', function() use ($conn) {
 *     $result = $conn->query("SELECT * FROM admin_approved_projects WHERE status = 'approved'");
 *     return $result->fetch_all(MYSQLI_ASSOC);
 * }, 300);
 * 
 * // Cache user stats for 10 minutes
 * $user_stats = cache_query("user_stats_{$user_id}", function() use ($conn, $user_id) {
 *     $stmt = $conn->prepare("SELECT * FROM v_user_stats WHERE id = ?");
 *     $stmt->bind_param("i", $user_id);
 *     $stmt->execute();
 *     return $stmt->get_result()->fetch_assoc();
 * }, 600);
 */
?>
