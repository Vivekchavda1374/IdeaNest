<?php
/**
 * ThunderScan - Ultra-Powerful PHP Syntax & Error Scanner
 * Comprehensive testing suite for IdeaNest project
 * 
 * Features:
 * - Deep syntax analysis
 * - Security vulnerability detection
 * - Performance monitoring
 * - Code quality assessment
 */

class ThunderScan {
    private $projectPath;
    private $results = [];
    private $startTime;
    private $totalFiles = 0;
    private $errors = [];
    private $warnings = [];
    private $securityIssues = [];
    
    public function __construct($projectPath = null) {
        $this->projectPath = $projectPath ?: __DIR__;
        $this->startTime = microtime(true);
    }
    
    /**
     * Main scanning method - ThunderScan's core power
     */
    public function thunderScan() {
        echo "⚡ THUNDERSCAN INITIATED ⚡\n";
        echo "🔍 Scanning project: " . $this->projectPath . "\n";
        echo str_repeat("=", 60) . "\n";
        
        $this->scanSyntaxErrors();
        $this->scanSecurityVulnerabilities();
        $this->scanPerformanceIssues();
        $this->scanCodeQuality();
        $this->scanDependencies();
        $this->generateReport();
        
        return $this->results;
    }
    
    /**
     * Lightning-fast syntax error detection
     */
    private function scanSyntaxErrors() {
        echo "⚡ Scanning for syntax errors...\n";
        
        $phpFiles = $this->getPhpFiles();
        $syntaxErrors = [];
        
        foreach ($phpFiles as $file) {
            $output = [];
            $returnCode = 0;
            exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $returnCode);
            
            if ($returnCode !== 0) {
                $syntaxErrors[] = [
                    'file' => $file,
                    'error' => implode("\n", $output)
                ];
            }
        }
        
        $this->results['syntax'] = [
            'total_files' => count($phpFiles),
            'errors' => $syntaxErrors,
            'status' => empty($syntaxErrors) ? 'PASS' : 'FAIL'
        ];
        
        echo "✅ Syntax check complete: " . count($syntaxErrors) . " errors found\n";
    }
    
    /**
     * Security vulnerability detection
     */
    private function scanSecurityVulnerabilities() {
        echo "🛡️ Scanning for security vulnerabilities...\n";
        
        $phpFiles = $this->getPhpFiles();
        $securityIssues = [];
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            $issues = $this->checkSecurityPatterns($content, $file);
            if (!empty($issues)) {
                $securityIssues[$file] = $issues;
            }
        }
        
        $this->results['security'] = [
            'total_files' => count($phpFiles),
            'issues' => $securityIssues,
            'status' => empty($securityIssues) ? 'SECURE' : 'VULNERABLE'
        ];
        
        echo "🛡️ Security scan complete: " . count($securityIssues) . " files with issues\n";
    }
    
    /**
     * Check for dangerous security patterns
     */
    private function checkSecurityPatterns($content, $file) {
        $patterns = [
            'SQL Injection' => [
                '/(\$_(?:GET|POST|REQUEST)\[.*?\].*?(?:mysql_query|mysqli_query|query)\s*\()/i',
                '/(\$_(?:GET|POST|REQUEST)\[.*?\].*?\$.*?->query\s*\()/i'
            ],
            'XSS Vulnerability' => [
                '/echo\s+\$_(?:GET|POST|REQUEST)\[.*?\]/i',
                '/print\s+\$_(?:GET|POST|REQUEST)\[.*?\]/i'
            ],
            'File Inclusion' => [
                '/(?:include|require|include_once|require_once)\s*\(\s*\$_(?:GET|POST|REQUEST)\[.*?\]\s*\)/i'
            ],
            'Command Injection' => [
                '/(?:exec|system|shell_exec|passthru)\s*\(\s*\$_(?:GET|POST|REQUEST)\[.*?\]\s*\)/i'
            ],
            'Weak Password Hashing' => [
                '/md5\s*\(\s*\$password/i',
                '/sha1\s*\(\s*\$password/i'
            ],
            'Unescaped Output' => [
                '/echo\s+[^;]*\$_(?:GET|POST|REQUEST)\[.*?\][^;]*;/i'
            ]
        ];
        
        $issues = [];
        foreach ($patterns as $type => $patternList) {
            foreach ($patternList as $pattern) {
                if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                    foreach ($matches[0] as $match) {
                        $issues[] = [
                            'type' => $type,
                            'line' => substr_count(substr($content, 0, $match[1]), "\n") + 1,
                            'code' => trim($match[0]),
                            'severity' => $this->getSeverity($type)
                        ];
                    }
                }
            }
        }
        
        return $issues;
    }
    
    /**
     * Performance issue detection
     */
    private function scanPerformanceIssues() {
        echo "🚀 Scanning for performance issues...\n";
        
        $phpFiles = $this->getPhpFiles();
        $performanceIssues = [];
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            $issues = $this->checkPerformancePatterns($content, $file);
            if (!empty($issues)) {
                $performanceIssues[$file] = $issues;
            }
        }
        
        $this->results['performance'] = [
            'total_files' => count($phpFiles),
            'issues' => $performanceIssues,
            'status' => empty($performanceIssues) ? 'OPTIMIZED' : 'NEEDS_OPTIMIZATION'
        ];
        
        echo "🚀 Performance scan complete: " . count($performanceIssues) . " files with issues\n";
    }
    
    /**
     * Check for performance anti-patterns
     */
    private function checkPerformancePatterns($content, $file) {
        $patterns = [
            'N+1 Query Problem' => [
                '/while\s*\([^)]*\)\s*\{[^}]*\$.*?->query\s*\([^)]*\)[^}]*\}/i'
            ],
            'Large File Operations' => [
                '/file_get_contents\s*\(\s*["\'][^"\']*\.(?:log|txt|csv)["\']\s*\)/i'
            ],
            'Inefficient Loops' => [
                '/for\s*\([^)]*\)\s*\{[^}]*\$.*?->query\s*\([^)]*\)[^}]*\}/i'
            ],
            'Memory Intensive Operations' => [
                '/array_merge\s*\(\s*\$.*?,\s*\$.*?\s*\)/i'
            ]
        ];
        
        $issues = [];
        foreach ($patterns as $type => $patternList) {
            foreach ($patternList as $pattern) {
                if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                    foreach ($matches[0] as $match) {
                        $issues[] = [
                            'type' => $type,
                            'line' => substr_count(substr($content, 0, $match[1]), "\n") + 1,
                            'code' => trim($match[0]),
                            'severity' => 'MEDIUM'
                        ];
                    }
                }
            }
        }
        
        return $issues;
    }
    
    /**
     * Code quality assessment
     */
    private function scanCodeQuality() {
        echo "📊 Scanning code quality...\n";
        
        $phpFiles = $this->getPhpFiles();
        $qualityIssues = [];
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            $issues = $this->checkQualityPatterns($content, $file);
            if (!empty($issues)) {
                $qualityIssues[$file] = $issues;
            }
        }
        
        $this->results['quality'] = [
            'total_files' => count($phpFiles),
            'issues' => $qualityIssues,
            'status' => empty($qualityIssues) ? 'HIGH_QUALITY' : 'NEEDS_IMPROVEMENT'
        ];
        
        echo "📊 Quality scan complete: " . count($qualityIssues) . " files with issues\n";
    }
    
    /**
     * Check for code quality issues
     */
    private function checkQualityPatterns($content, $file) {
        $patterns = [
            'Long Lines' => [
                '/(.{120,})/'
            ],
            'Missing Documentation' => [
                '/function\s+\w+\s*\([^)]*\)\s*\{[^}]*\}/i'
            ],
            'Complex Functions' => [
                '/function\s+\w+\s*\([^)]*\)\s*\{[^}]{200,}\}/i'
            ],
            'Hardcoded Values' => [
                '/["\'][^"\']{20,}["\']/'
            ]
        ];
        
        $issues = [];
        foreach ($patterns as $type => $patternList) {
            foreach ($patternList as $pattern) {
                if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                    foreach ($matches[0] as $match) {
                        $issues[] = [
                            'type' => $type,
                            'line' => substr_count(substr($content, 0, $match[1]), "\n") + 1,
                            'code' => trim($match[0]),
                            'severity' => 'LOW'
                        ];
                    }
                }
            }
        }
        
        return $issues;
    }
    
    /**
     * Dependency analysis
     */
    private function scanDependencies() {
        echo "📦 Scanning dependencies...\n";
        
        $composerFile = $this->projectPath . '/composer.json';
        $dependencies = [];
        
        if (file_exists($composerFile)) {
            $composerData = json_decode(file_get_contents($composerFile), true);
            $dependencies = $composerData['require'] ?? [];
        }
        
        $this->results['dependencies'] = [
            'composer_file' => file_exists($composerFile),
            'dependencies' => $dependencies,
            'status' => 'ANALYZED'
        ];
        
        echo "📦 Dependency scan complete\n";
    }
    
    /**
     * Get all PHP files in project
     */
    private function getPhpFiles() {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->projectPath)
        );
        
        $phpFiles = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }
        
        $this->totalFiles = count($phpFiles);
        return $phpFiles;
    }
    
    /**
     * Get severity level for issue type
     */
    private function getSeverity($type) {
        $severityMap = [
            'SQL Injection' => 'CRITICAL',
            'XSS Vulnerability' => 'HIGH',
            'File Inclusion' => 'HIGH',
            'Command Injection' => 'CRITICAL',
            'Weak Password Hashing' => 'HIGH',
            'Unescaped Output' => 'MEDIUM'
        ];
        
        return $severityMap[$type] ?? 'LOW';
    }
    
    /**
     * Generate comprehensive report
     */
    private function generateReport() {
        $endTime = microtime(true);
        $executionTime = round($endTime - $this->startTime, 2);
        
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "⚡ THUNDERSCAN COMPLETE ⚡\n";
        echo str_repeat("=", 60) . "\n";
        
        echo "📊 SCAN SUMMARY:\n";
        echo "   Files Scanned: " . $this->totalFiles . "\n";
        echo "   Execution Time: " . $executionTime . " seconds\n";
        echo "   Syntax Errors: " . count($this->results['syntax']['errors']) . "\n";
        echo "   Security Issues: " . count($this->results['security']['issues']) . "\n";
        echo "   Performance Issues: " . count($this->results['performance']['issues']) . "\n";
        echo "   Quality Issues: " . count($this->results['quality']['issues']) . "\n";
        
        // Save detailed report
        $this->saveDetailedReport($executionTime);
        
        echo "\n📄 Detailed report saved to: thunder_scan_report.json\n";
        echo "⚡ ThunderScan mission accomplished! ⚡\n";
    }
    
    /**
     * Save detailed report to file
     */
    private function saveDetailedReport($executionTime) {
        $report = [
            'scan_info' => [
                'tool' => 'ThunderScan',
                'version' => '1.0.0',
                'timestamp' => date('Y-m-d H:i:s'),
                'execution_time' => $executionTime,
                'total_files' => $this->totalFiles
            ],
            'results' => $this->results
        ];
        
        file_put_contents(
            $this->projectPath . '/thunder_scan_report.json',
            json_encode($report, JSON_PRETTY_PRINT)
        );
    }
}

// Auto-run if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $scanner = new ThunderScan();
    $results = $scanner->thunderScan();
}
?>
