<?php
/**
 * WebGuardian - Advanced Security Vulnerability Scanner
 * FortiTest Security Suite for IdeaNest
 * 
 * Features:
 * - SQL Injection detection
 * - XSS vulnerability scanning
 * - File inclusion vulnerability detection
 * - Authentication bypass detection
 * - Session security analysis
 * - CSRF protection verification
 */

class WebGuardian {
    private $projectPath;
    private $securityReport = [];
    private $vulnerabilities = [];
    private $severityLevels = ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'];
    
    public function __construct($projectPath = null) {
        $this->projectPath = $projectPath ?: __DIR__;
    }
    
    /**
     * Main security scan - WebGuardian's core protection
     */
    public function shieldAudit() {
        echo "🛡️ WEBGUARDIAN SHIELD AUDIT INITIATED 🛡️\n";
        echo "🔒 Scanning for security vulnerabilities...\n";
        echo str_repeat("=", 60) . "\n";
        
        $this->scanSQLInjection();
        $this->scanXSSVulnerabilities();
        $this->scanFileInclusion();
        $this->scanAuthenticationBypass();
        $this->scanSessionSecurity();
        $this->scanCSRFProtection();
        $this->scanInputValidation();
        $this->scanPasswordSecurity();
        $this->generateSecurityReport();
        
        return $this->securityReport;
    }
    
    /**
     * SQL Injection vulnerability detection
     */
    private function scanSQLInjection() {
        echo "🔍 Scanning for SQL Injection vulnerabilities...\n";
        
        $phpFiles = $this->getPhpFiles();
        $sqlIssues = [];
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            $issues = $this->detectSQLInjection($content, $file);
            if (!empty($issues)) {
                $sqlIssues[$file] = $issues;
            }
        }
        
        $this->securityReport['sql_injection'] = [
            'total_files' => count($phpFiles),
            'vulnerable_files' => count($sqlIssues),
            'issues' => $sqlIssues,
            'risk_level' => $this->calculateRiskLevel($sqlIssues)
        ];
        
        echo "✅ SQL Injection scan complete: " . count($sqlIssues) . " vulnerable files\n";
    }
    
    /**
     * Detect SQL injection patterns
     */
    private function detectSQLInjection($content, $file) {
        $patterns = [
            'Direct User Input in Query' => [
                '/(?:mysql_query|mysqli_query|query)\s*\(\s*["\'].*?\$_(?:GET|POST|REQUEST)\[.*?\].*?["\']/i',
                '/(?:mysql_query|mysqli_query|query)\s*\(\s*\$_(?:GET|POST|REQUEST)\[.*?\]/i'
            ],
            'String Concatenation' => [
                '/\$sql\s*=.*?\$_(?:GET|POST|REQUEST)\[.*?\].*?["\']/i',
                '/\$query\s*=.*?\$_(?:GET|POST|REQUEST)\[.*?\].*?["\']/i'
            ],
            'Unescaped Variables' => [
                '/\$.*?->query\s*\(\s*["\'].*?\$_(?:GET|POST|REQUEST)\[.*?\].*?["\']/i'
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
                            'severity' => 'CRITICAL',
                            'recommendation' => 'Use prepared statements with parameterized queries'
                        ];
                    }
                }
            }
        }
        
        return $issues;
    }
    
    /**
     * XSS vulnerability detection
     */
    private function scanXSSVulnerabilities() {
        echo "🔍 Scanning for XSS vulnerabilities...\n";
        
        $phpFiles = $this->getPhpFiles();
        $xssIssues = [];
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            $issues = $this->detectXSS($content, $file);
            if (!empty($issues)) {
                $xssIssues[$file] = $issues;
            }
        }
        
        $this->securityReport['xss'] = [
            'total_files' => count($phpFiles),
            'vulnerable_files' => count($xssIssues),
            'issues' => $xssIssues,
            'risk_level' => $this->calculateRiskLevel($xssIssues)
        ];
        
        echo "✅ XSS scan complete: " . count($xssIssues) . " vulnerable files\n";
    }
    
    /**
     * Detect XSS patterns
     */
    private function detectXSS($content, $file) {
        $patterns = [
            'Direct Output' => [
                '/echo\s+\$_(?:GET|POST|REQUEST)\[.*?\];/i',
                '/print\s+\$_(?:GET|POST|REQUEST)\[.*?\];/i',
                '/<\?=\s*\$_(?:GET|POST|REQUEST)\[.*?\]\s*\?>/i'
            ],
            'Unescaped HTML' => [
                '/echo\s+[^;]*\$_(?:GET|POST|REQUEST)\[.*?\][^;]*;/i',
                '/print\s+[^;]*\$_(?:GET|POST|REQUEST)\[.*?\][^;]*;/i'
            ],
            'JavaScript Injection' => [
                '/<script[^>]*>.*?\$_(?:GET|POST|REQUEST)\[.*?\].*?<\/script>/i'
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
                            'severity' => 'HIGH',
                            'recommendation' => 'Use htmlspecialchars() or htmlentities() to escape output'
                        ];
                    }
                }
            }
        }
        
        return $issues;
    }
    
    /**
     * File inclusion vulnerability detection
     */
    private function scanFileInclusion() {
        echo "🔍 Scanning for file inclusion vulnerabilities...\n";
        
        $phpFiles = $this->getPhpFiles();
        $inclusionIssues = [];
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            $issues = $this->detectFileInclusion($content, $file);
            if (!empty($issues)) {
                $inclusionIssues[$file] = $issues;
            }
        }
        
        $this->securityReport['file_inclusion'] = [
            'total_files' => count($phpFiles),
            'vulnerable_files' => count($inclusionIssues),
            'issues' => $inclusionIssues,
            'risk_level' => $this->calculateRiskLevel($inclusionIssues)
        ];
        
        echo "✅ File inclusion scan complete: " . count($inclusionIssues) . " vulnerable files\n";
    }
    
    /**
     * Detect file inclusion patterns
     */
    private function detectFileInclusion($content, $file) {
        $patterns = [
            'Dynamic Include' => [
                '/(?:include|require|include_once|require_once)\s*\(\s*\$_(?:GET|POST|REQUEST)\[.*?\]\s*\)/i'
            ],
            'Unvalidated Path' => [
                '/(?:include|require|include_once|require_once)\s*\(\s*\$[a-zA-Z_][a-zA-Z0-9_]*\s*\)/i'
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
                            'severity' => 'HIGH',
                            'recommendation' => 'Validate and whitelist file paths before inclusion'
                        ];
                    }
                }
            }
        }
        
        return $issues;
    }
    
    /**
     * Authentication bypass detection
     */
    private function scanAuthenticationBypass() {
        echo "🔍 Scanning for authentication bypass vulnerabilities...\n";
        
        $phpFiles = $this->getPhpFiles();
        $authIssues = [];
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            $issues = $this->detectAuthBypass($content, $file);
            if (!empty($issues)) {
                $authIssues[$file] = $issues;
            }
        }
        
        $this->securityReport['auth_bypass'] = [
            'total_files' => count($phpFiles),
            'vulnerable_files' => count($authIssues),
            'issues' => $authIssues,
            'risk_level' => $this->calculateRiskLevel($authIssues)
        ];
        
        echo "✅ Authentication bypass scan complete: " . count($authIssues) . " vulnerable files\n";
    }
    
    /**
     * Detect authentication bypass patterns
     */
    private function detectAuthBypass($content, $file) {
        $patterns = [
            'Missing Authentication Check' => [
                '/if\s*\(\s*!isset\s*\(\s*\$_(?:SESSION|COOKIE)\[["\']user["\']\]\s*\)\s*\)\s*\{[^}]*\}/i'
            ],
            'Weak Session Validation' => [
                '/if\s*\(\s*\$_(?:SESSION|COOKIE)\[["\']user["\']\]\s*\)\s*\{[^}]*\}/i'
            ],
            'Direct Access to Admin' => [
                '/admin.*?\.php/i'
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
                            'severity' => 'HIGH',
                            'recommendation' => 'Implement proper authentication and authorization checks'
                        ];
                    }
                }
            }
        }
        
        return $issues;
    }
    
    /**
     * Session security analysis
     */
    private function scanSessionSecurity() {
        echo "🔍 Analyzing session security...\n";
        
        $phpFiles = $this->getPhpFiles();
        $sessionIssues = [];
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            $issues = $this->detectSessionIssues($content, $file);
            if (!empty($issues)) {
                $sessionIssues[$file] = $issues;
            }
        }
        
        $this->securityReport['session_security'] = [
            'total_files' => count($phpFiles),
            'vulnerable_files' => count($sessionIssues),
            'issues' => $sessionIssues,
            'risk_level' => $this->calculateRiskLevel($sessionIssues)
        ];
        
        echo "✅ Session security scan complete: " . count($sessionIssues) . " vulnerable files\n";
    }
    
    /**
     * Detect session security issues
     */
    private function detectSessionIssues($content, $file) {
        $patterns = [
            'Missing Session Regeneration' => [
                '/session_start\s*\(\s*\)\s*;[^;]*session_regenerate_id\s*\(\s*\)\s*;/i'
            ],
            'Insecure Session Configuration' => [
                '/session_set_cookie_params\s*\([^)]*\)/i'
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
                            'severity' => 'MEDIUM',
                            'recommendation' => 'Implement secure session configuration'
                        ];
                    }
                }
            }
        }
        
        return $issues;
    }
    
    /**
     * CSRF protection verification
     */
    private function scanCSRFProtection() {
        echo "🔍 Scanning for CSRF protection...\n";
        
        $phpFiles = $this->getPhpFiles();
        $csrfIssues = [];
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            $issues = $this->detectCSRFIssues($content, $file);
            if (!empty($issues)) {
                $csrfIssues[$file] = $issues;
            }
        }
        
        $this->securityReport['csrf_protection'] = [
            'total_files' => count($phpFiles),
            'vulnerable_files' => count($csrfIssues),
            'issues' => $csrfIssues,
            'risk_level' => $this->calculateRiskLevel($csrfIssues)
        ];
        
        echo "✅ CSRF protection scan complete: " . count($csrfIssues) . " vulnerable files\n";
    }
    
    /**
     * Detect CSRF issues
     */
    private function detectCSRFIssues($content, $file) {
        $patterns = [
            'Missing CSRF Token' => [
                '/<form[^>]*method=["\']post["\'][^>]*>[^<]*<input[^>]*name=["\']csrf_token["\'][^>]*>/i'
            ],
            'Unprotected Form' => [
                '/<form[^>]*method=["\']post["\'][^>]*>/i'
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
                            'severity' => 'MEDIUM',
                            'recommendation' => 'Implement CSRF token validation'
                        ];
                    }
                }
            }
        }
        
        return $issues;
    }
    
    /**
     * Input validation analysis
     */
    private function scanInputValidation() {
        echo "🔍 Scanning input validation...\n";
        
        $phpFiles = $this->getPhpFiles();
        $validationIssues = [];
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            $issues = $this->detectValidationIssues($content, $file);
            if (!empty($issues)) {
                $validationIssues[$file] = $issues;
            }
        }
        
        $this->securityReport['input_validation'] = [
            'total_files' => count($phpFiles),
            'vulnerable_files' => count($validationIssues),
            'issues' => $validationIssues,
            'risk_level' => $this->calculateRiskLevel($validationIssues)
        ];
        
        echo "✅ Input validation scan complete: " . count($validationIssues) . " vulnerable files\n";
    }
    
    /**
     * Detect input validation issues
     */
    private function detectValidationIssues($content, $file) {
        $patterns = [
            'Missing Validation' => [
                '/\$_(?:GET|POST|REQUEST)\[.*?\][^;]*;/i'
            ],
            'Weak Validation' => [
                '/if\s*\(\s*empty\s*\(\s*\$_(?:GET|POST|REQUEST)\[.*?\]\s*\)\s*\)/i'
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
                            'severity' => 'MEDIUM',
                            'recommendation' => 'Implement proper input validation and sanitization'
                        ];
                    }
                }
            }
        }
        
        return $issues;
    }
    
    /**
     * Password security analysis
     */
    private function scanPasswordSecurity() {
        echo "🔍 Scanning password security...\n";
        
        $phpFiles = $this->getPhpFiles();
        $passwordIssues = [];
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            $issues = $this->detectPasswordIssues($content, $file);
            if (!empty($issues)) {
                $passwordIssues[$file] = $issues;
            }
        }
        
        $this->securityReport['password_security'] = [
            'total_files' => count($phpFiles),
            'vulnerable_files' => count($passwordIssues),
            'issues' => $passwordIssues,
            'risk_level' => $this->calculateRiskLevel($passwordIssues)
        ];
        
        echo "✅ Password security scan complete: " . count($passwordIssues) . " vulnerable files\n";
    }
    
    /**
     * Detect password security issues
     */
    private function detectPasswordIssues($content, $file) {
        $patterns = [
            'Weak Hashing' => [
                '/md5\s*\(\s*\$password/i',
                '/sha1\s*\(\s*\$password/i'
            ],
            'Plain Text Storage' => [
                '/\$password\s*=\s*\$_(?:GET|POST|REQUEST)\[.*?\];/i'
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
                            'severity' => 'HIGH',
                            'recommendation' => 'Use password_hash() and password_verify() for secure password handling'
                        ];
                    }
                }
            }
        }
        
        return $issues;
    }
    
    /**
     * Calculate risk level based on issues
     */
    private function calculateRiskLevel($issues) {
        $totalIssues = 0;
        $criticalIssues = 0;
        $highIssues = 0;
        
        foreach ($issues as $fileIssues) {
            foreach ($fileIssues as $issue) {
                $totalIssues++;
                if ($issue['severity'] === 'CRITICAL') $criticalIssues++;
                if ($issue['severity'] === 'HIGH') $highIssues++;
            }
        }
        
        if ($criticalIssues > 0) return 'CRITICAL';
        if ($highIssues > 5) return 'HIGH';
        if ($totalIssues > 10) return 'MEDIUM';
        return 'LOW';
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
        
        return $phpFiles;
    }
    
    /**
     * Generate comprehensive security report
     */
    private function generateSecurityReport() {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "🛡️ WEBGUARDIAN SECURITY REPORT 🛡️\n";
        echo str_repeat("=", 60) . "\n";
        
        $totalVulnerabilities = 0;
        $criticalIssues = 0;
        
        foreach ($this->securityReport as $category => $data) {
            if (isset($data['issues'])) {
                $categoryIssues = 0;
                foreach ($data['issues'] as $fileIssues) {
                    $categoryIssues += count($fileIssues);
                    foreach ($fileIssues as $issue) {
                        if ($issue['severity'] === 'CRITICAL') $criticalIssues++;
                    }
                }
                $totalVulnerabilities += $categoryIssues;
                
                echo "📊 " . strtoupper(str_replace('_', ' ', $category)) . ":\n";
                echo "   Vulnerable Files: " . $data['vulnerable_files'] . "\n";
                echo "   Total Issues: " . $categoryIssues . "\n";
                echo "   Risk Level: " . $data['risk_level'] . "\n\n";
            }
        }
        
        echo "🚨 OVERALL SECURITY STATUS:\n";
        echo "   Total Vulnerabilities: " . $totalVulnerabilities . "\n";
        echo "   Critical Issues: " . $criticalIssues . "\n";
        echo "   Overall Risk: " . ($criticalIssues > 0 ? 'CRITICAL' : 'MANAGEABLE') . "\n";
        
        // Save detailed report
        $this->saveSecurityReport();
        
        echo "\n📄 Detailed security report saved to: webguardian_security_report.json\n";
        echo "🛡️ WebGuardian mission accomplished! 🛡️\n";
    }
    
    /**
     * Save detailed security report
     */
    private function saveSecurityReport() {
        $report = [
            'scan_info' => [
                'tool' => 'WebGuardian',
                'version' => '1.0.0',
                'timestamp' => date('Y-m-d H:i:s'),
                'total_files' => $this->securityReport['sql_injection']['total_files'] ?? 0
            ],
            'security_report' => $this->securityReport
        ];
        
        file_put_contents(
            $this->projectPath . '/webguardian_security_report.json',
            json_encode($report, JSON_PRETTY_PRINT)
        );
    }
}

// Auto-run if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $guardian = new WebGuardian();
    $results = $guardian->shieldAudit();
}
?>
