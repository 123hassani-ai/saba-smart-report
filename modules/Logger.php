<?php
/**
 * Logger Class - مدیریت لاگ‌ها
 * 
 * @author Saba Reporting System
 * @version 2.0
 */

class Logger 
{
    private static $instance = null;
    private $logFile;
    private $logDir = 'logs';
    
    private function __construct() 
    {
        $this->createLogDirectory();
        $this->logFile = $this->logDir . '/sync_' . date('Y-m-d') . '.log';
    }
    
    /**
     * Singleton pattern
     */
    public static function getInstance() 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * ایجاد پوشه لاگ
     */
    private function createLogDirectory() 
    {
        if (!file_exists($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }
    
    /**
     * ثبت لاگ
     */
    public function log($message, $level = 'INFO') 
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // خروجی کنسول در CLI
        if (php_sapi_name() === 'cli') {
            $colors = [
                'ERROR' => "\033[31m", // قرمز
                'WARNING' => "\033[33m", // زرد  
                'INFO' => "\033[32m", // سبز
                'DEBUG' => "\033[36m" // آبی
            ];
            $color = $colors[strtoupper($level)] ?? "\033[0m";
            echo $color . $logEntry . "\033[0m";
        }
    }
    
    /**
     * لاگ خطا
     */
    public function error($message) 
    {
        $this->log($message, 'ERROR');
    }
    
    /**
     * لاگ هشدار
     */
    public function warning($message) 
    {
        $this->log($message, 'WARNING');
    }
    
    /**
     * لاگ اطلاعات
     */
    public function info($message) 
    {
        $this->log($message, 'INFO');
    }
    
    /**
     * لاگ دیباگ
     */
    public function debug($message) 
    {
        $this->log($message, 'DEBUG');
    }
    
    /**
     * خواندن لاگ‌های اخیر
     */
    public function getRecentLogs($lines = 100) 
    {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $content = file_get_contents($this->logFile);
        $logs = explode("\n", $content);
        $logs = array_filter($logs); // حذف خطوط خالی
        
        return array_slice($logs, -$lines);
    }
    
    /**
     * پاک کردن لاگ‌های قدیمی
     */
    public function cleanup($daysToKeep = 7) 
    {
        $files = glob($this->logDir . '/sync_*.log');
        $cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $this->info("حذف لاگ قدیمی: " . basename($file));
            }
        }
    }
}
