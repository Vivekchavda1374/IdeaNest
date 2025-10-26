<?php
/**
 * CodeDetective - Advanced Static Analysis & Code Quality Checker
 * DevCheck Lab for IdeaNest
 * 
 * Features:
 * - Static code analysis
 * - Code quality metrics
 * - Performance optimization suggestions
 * - Best practices compliance
 * - Code complexity analysis
 * - Dependency analysis
 */

class CodeDetective {
    private $projectPath;
    private $analysisReport = [];
    private $qualityMetrics = [];
    private $complexityScores = [];
    
    public function __construct($projectPath = null) {
        $this->projectPath = $projectPath ?: __DIR__;
    }
    
    /**
     * Main analysis method - CodeDetective's core investigation
     */
    public function investigate() {
        echo "🔍 CODEDETECTIVE INVESTIGATION INITIATED 🔍\n";
        echo "🕵️ Analyzing code quality and structure...\n";
        echo str_repeat("=", 60) . "\n";
        
        $this->analyzeCodeStructure();
        $this->analyzeCodeQuality();
        $this->analyzeComplexity();
        $this->analyzePerformance();
        $this->analyzeBestPractices();
        $this->analyzeDependencies();
        $this->generateAnalysisReport();
        
        return $this->analysisReport;
    }
    
    /**
     * Analyze code structure and organization
     */
    private function analyzeCodeStructure() {
        echo "🏗️ Analyzing code structure...\n";
        
        $phpFiles = $this->getPhpFiles();
        $structureMetrics = [
            'total_files' => count($phpFiles),
            'total_lines' => 0,
            'total_functions' => 0,
            'total_classes' => 0,
            'average_file_size' => 0,
            'largest_file' => '',
            'largest_file_size' => 0
        ];
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            $lines = substr_count($content, "\n") + 1;
            $structureMetrics['total_lines'] += $lines;
            
            if ($lines > $structureMetrics['largest_file_size']) {
                $structureMetrics['largest_file_size'] = $lines;
                $structureMetrics['largest_file'] = basename($file);
            }
            
            // Count functions and classes
            $structureMetrics['total_functions'] += substr_count($content, 'function ');
            $structureMetrics['total_classes'] += substr_count($content, 'class ');
        }
        
        $structureMetrics['average_file_size'] = round($structureMetrics['total_lines'] / $structureMetrics['total_files']);
        
        $this->analysisReport['structure'] = $structureMetrics;
        
        echo "✅ Structure analysis complete\n";
    }
    
    /**
     * Analyze code quality metrics
     */
    private function analyzeCodeQuality() {
        echo "📊 Analyzing code quality...\n";
        
        $phpFiles = $this->getPhpFiles();
        $qualityIssues = [];
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            $issues = $this->detectQualityIssues($content, $file);
            if (!empty($issues)) {
                $qualityIssues[$file] = $issues;
            }
        }
        
        $this->analysisReport['quality'] = [
            'total_files' => count($phpFiles),
            'files_with_issues' => count($qualityIssues),
            'issues' => $qualityIssues,
            'quality_score' => $this->calculateQualityScore($qualityIssues)
        ];
        
        echo "✅ Quality analysis complete\n";
    }
    
    /**
     * Detect code quality issues
     */
    private function detectQualityIssues($content, $file) {
        $issues = [];
        
        // Long lines
        $lines = explode("\n", $content);
        foreach ($lines as $lineNum => $line) {
            if (strlen($line) > 120) {
                $issues[] = [
                    'type' => 'Long Line',
                    'line' => $lineNum + 1,
                    'severity' => 'LOW',
                    'description' => 'Line exceeds 120 characters',
                    'suggestion' => 'Break long lines for better readability'
                ];
            }
        }
        
        // Missing documentation
        if (preg_match_all('/function\s+(\w+)\s*\([^)]*\)\s*\{/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $index => $match) {
                $functionName = $match[0];
                $functionPos = $match[1];
                
                // Check if there's documentation before the function
                $beforeFunction = substr($content, max(0, $functionPos - 200), 200);
                if (!preg_match('/\/\*\*.*?\*\//s', $beforeFunction)) {
                    $issues[] = [
                        'type' => 'Missing Documentation',
                        'line' => substr_count(substr($content, 0, $functionPos), "\n") + 1,
                        'severity' => 'MEDIUM',
                        'description' => "Function '{$functionName}' lacks documentation",
                        'suggestion' => 'Add PHPDoc comments for better code documentation'
                    ];
                }
            }
        }
        
        // Hardcoded values
        if (preg_match_all('/["\']([^"\']{20,})["\']/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $issues[] = [
                    'type' => 'Hardcoded Value',
                    'line' => substr_count(substr($content, 0, $match[1]), "\n") + 1,
                    'severity' => 'LOW',
                    'description' => 'Long hardcoded string detected',
                    'suggestion' => 'Consider using constants or configuration files'
                ];
            }
        }
        
        // Unused variables
        if (preg_match_all('/\$(\w+)\s*=/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $varName = $match[0];
                $varPos = $match[1];
                
                // Check if variable is used after assignment
                $afterVar = substr($content, $varPos + strlen($varName) + 1);
                if (!preg_match('/\$' . preg_quote($varName, '/') . '/', $afterVar)) {
                    $issues[] = [
                        'type' => 'Unused Variable',
                        'line' => substr_count(substr($content, 0, $varPos), "\n") + 1,
                        'severity' => 'LOW',
                        'description' => "Variable '\${$varName}' appears to be unused",
                        'suggestion' => 'Remove unused variables to clean up code'
                    ];
                }
            }
        }
        
        return $issues;
    }
    
    /**
     * Analyze code complexity
     */
    private function analyzeComplexity() {
        echo "🧮 Analyzing code complexity...\n";
        
        $phpFiles = $this->getPhpFiles();
        $complexityData = [];
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            $complexity = $this->calculateComplexity($content, $file);
            if ($complexity['score'] > 10) {
                $complexityData[$file] = $complexity;
            }
        }
        
        $this->analysisReport['complexity'] = [
            'total_files' => count($phpFiles),
            'complex_files' => count($complexityData),
            'complexity_data' => $complexityData
        ];
        
        echo "✅ Complexity analysis complete\n";
    }
    
    /**
     * Calculate cyclomatic complexity
     */
    private function calculateComplexity($content, $file) {
        $complexity = 1; // Base complexity
        
        // Count decision points
        $decisionPoints = [
            'if', 'elseif', 'else', 'while', 'for', 'foreach', 'switch', 'case',
            'catch', 'and', 'or', '&&', '||', '?', ':', 'break', 'continue'
        ];
        
        foreach ($decisionPoints as $point) {
            $complexity += substr_count($content, $point);
        }
        
        // Count functions
        $functionCount = substr_count($content, 'function ');
        
        return [
            'score' => $complexity,
            'functions' => $functionCount,
            'average_complexity' => $functionCount > 0 ? round($complexity / $functionCount, 2) : 0,
            'recommendation' => $complexity > 20 ? 'Consider refactoring to reduce complexity' : 'Complexity is manageable'
        ];
    }
    
    /**
     * Analyze performance patterns
     */
    private function analyzePerformance() {
        echo "🚀 Analyzing performance patterns...\n";
        
        $phpFiles = $this->getPhpFiles();
        $performanceIssues = [];
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            $issues = $this->detectPerformanceIssues($content, $file);
            if (!empty($issues)) {
                $performanceIssues[$file] = $issues;
            }
        }
        
        $this->analysisReport['performance'] = [
            'total_files' => count($phpFiles),
            'files_with_issues' => count($performanceIssues),
            'issues' => $performanceIssues
        ];
        
        echo "✅ Performance analysis complete\n";
    }
    
    /**
     * Detect performance issues
     */
    private function detectPerformanceIssues($content, $file) {
        $issues = [];
        
        // Inefficient loops
        if (preg_match_all('/for\s*\([^)]*\)\s*\{[^}]*\$.*?->query\s*\([^)]*\)[^}]*\}/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $issues[] = [
                    'type' => 'N+1 Query Problem',
                    'line' => substr_count(substr($content, 0, $match[1]), "\n") + 1,
                    'severity' => 'HIGH',
                    'description' => 'Database query inside loop detected',
                    'suggestion' => 'Consider using JOIN queries or batch operations'
                ];
            }
        }
        
        // Large file operations
        if (preg_match_all('/file_get_contents\s*\(\s*["\'][^"\']*\.(?:log|txt|csv)["\']\s*\)/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $issues[] = [
                    'type' => 'Large File Operation',
                    'line' => substr_count(substr($content, 0, $match[1]), "\n") + 1,
                    'severity' => 'MEDIUM',
                    'description' => 'Reading large file with file_get_contents',
                    'suggestion' => 'Consider using file streams for large files'
                ];
            }
        }
        
        // Memory intensive operations
        if (preg_match_all('/array_merge\s*\(\s*\$.*?,\s*\$.*?\s*\)/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $issues[] = [
                    'type' => 'Memory Intensive Operation',
                    'line' => substr_count(substr($content, 0, $match[1]), "\n") + 1,
                    'severity' => 'MEDIUM',
                    'description' => 'Array merge operation detected',
                    'suggestion' => 'Consider using array_merge_recursive or array_replace'
                ];
            }
        }
        
        return $issues;
    }
    
    /**
     * Analyze best practices compliance
     */
    private function analyzeBestPractices() {
        echo "📋 Analyzing best practices compliance...\n";
        
        $phpFiles = $this->getPhpFiles();
        $bestPractices = [
            'psr_compliance' => 0,
            'error_handling' => 0,
            'input_validation' => 0,
            'security_practices' => 0,
            'total_files' => count($phpFiles)
        ];
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            
            // PSR compliance check
            if ($this->checkPSRCompliance($content)) {
                $bestPractices['psr_compliance']++;
            }
            
            // Error handling check
            if ($this->checkErrorHandling($content)) {
                $bestPractices['error_handling']++;
            }
            
            // Input validation check
            if ($this->checkInputValidation($content)) {
                $bestPractices['input_validation']++;
            }
            
            // Security practices check
            if ($this->checkSecurityPractices($content)) {
                $bestPractices['security_practices']++;
            }
        }
        
        $this->analysisReport['best_practices'] = $bestPractices;
        
        echo "✅ Best practices analysis complete\n";
    }
    
    /**
     * Check PSR compliance
     */
    private function checkPSRCompliance($content) {
        // Check for proper indentation (4 spaces)
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            if (preg_match('/^(\t|\s{1,3}|\s{5,})/', $line)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Check error handling
     */
    private function checkErrorHandling($content) {
        return strpos($content, 'try') !== false && strpos($content, 'catch') !== false;
    }
    
    /**
     * Check input validation
     */
    private function checkInputValidation($content) {
        return preg_match('/filter_var|htmlspecialchars|strip_tags/', $content);
    }
    
    /**
     * Check security practices
     */
    private function checkSecurityPractices($content) {
        return preg_match('/password_hash|password_verify|prepared.*statement/', $content);
    }
    
    /**
     * Analyze dependencies
     */
    private function analyzeDependencies() {
        echo "📦 Analyzing dependencies...\n";
        
        $composerFile = $this->projectPath . '/composer.json';
        $dependencies = [];
        
        if (file_exists($composerFile)) {
            $composerData = json_decode(file_get_contents($composerFile), true);
            $dependencies = [
                'composer_file_exists' => true,
                'require' => $composerData['require'] ?? [],
                'require_dev' => $composerData['require-dev'] ?? [],
                'autoload' => $composerData['autoload'] ?? []
            ];
        } else {
            $dependencies = ['composer_file_exists' => false];
        }
        
        $this->analysisReport['dependencies'] = $dependencies;
        
        echo "✅ Dependency analysis complete\n";
    }
    
    /**
     * Calculate overall quality score
     */
    private function calculateQualityScore($qualityIssues) {
        $totalIssues = 0;
        $criticalIssues = 0;
        
        foreach ($qualityIssues as $fileIssues) {
            foreach ($fileIssues as $issue) {
                $totalIssues++;
                if ($issue['severity'] === 'HIGH') $criticalIssues++;
            }
        }
        
        $score = 100;
        $score -= $totalIssues * 2;
        $score -= $criticalIssues * 10;
        
        return max(0, $score);
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
     * Generate comprehensive analysis report
     */
    private function generateAnalysisReport() {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "🔍 CODEDETECTIVE ANALYSIS REPORT 🔍\n";
        echo str_repeat("=", 60) . "\n";
        
        $structure = $this->analysisReport['structure'];
        $quality = $this->analysisReport['quality'];
        $complexity = $this->analysisReport['complexity'];
        $bestPractices = $this->analysisReport['best_practices'];
        
        echo "📊 CODE STRUCTURE:\n";
        echo "   Total Files: " . $structure['total_files'] . "\n";
        echo "   Total Lines: " . number_format($structure['total_lines']) . "\n";
        echo "   Total Functions: " . $structure['total_functions'] . "\n";
        echo "   Total Classes: " . $structure['total_classes'] . "\n";
        echo "   Average File Size: " . $structure['average_file_size'] . " lines\n";
        echo "   Largest File: " . $structure['largest_file'] . " (" . $structure['largest_file_size'] . " lines)\n\n";
        
        echo "📈 CODE QUALITY:\n";
        echo "   Quality Score: " . $quality['quality_score'] . "/100\n";
        echo "   Files with Issues: " . $quality['files_with_issues'] . "/" . $quality['total_files'] . "\n\n";
        
        echo "🧮 COMPLEXITY ANALYSIS:\n";
        echo "   Complex Files: " . $complexity['complex_files'] . "/" . $complexity['total_files'] . "\n\n";
        
        echo "📋 BEST PRACTICES:\n";
        echo "   PSR Compliance: " . round(($bestPractices['psr_compliance'] / $bestPractices['total_files']) * 100, 1) . "%\n";
        echo "   Error Handling: " . round(($bestPractices['error_handling'] / $bestPractices['total_files']) * 100, 1) . "%\n";
        echo "   Input Validation: " . round(($bestPractices['input_validation'] / $bestPractices['total_files']) * 100, 1) . "%\n";
        echo "   Security Practices: " . round(($bestPractices['security_practices'] / $bestPractices['total_files']) * 100, 1) . "%\n";
        
        // Save detailed report
        $this->saveAnalysisReport();
        
        echo "\n📄 Detailed analysis report saved to: codedetective_analysis_report.json\n";
        echo "🔍 CodeDetective investigation complete! 🔍\n";
    }
    
    /**
     * Save detailed analysis report
     */
    private function saveAnalysisReport() {
        $report = [
            'analysis_info' => [
                'tool' => 'CodeDetective',
                'version' => '1.0.0',
                'timestamp' => date('Y-m-d H:i:s'),
                'project_path' => $this->projectPath
            ],
            'analysis_report' => $this->analysisReport
        ];
        
        file_put_contents(
            $this->projectPath . '/codedetective_analysis_report.json',
            json_encode($report, JSON_PRETTY_PRINT)
        );
    }
}

// Auto-run if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $detective = new CodeDetective();
    $results = $detective->investigate();
}
?>
