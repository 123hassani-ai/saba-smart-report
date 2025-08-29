<?php
/**
 * Base Module Class
 * کلاس پایه برای همه ماژول‌ها
 * 
 * @author Saba Reporting System
 * @version 2.0
 */

abstract class BaseModule 
{
    protected $config;
    protected $logger;
    
    public function __construct($config = null) 
    {
        $this->config = $config ?: $this->loadConfig();
        $this->logger = Logger::getInstance();
    }
    
    /**
     * بارگذاری تنظیمات
     */
    protected function loadConfig() 
    {
        $configFile = __DIR__ . '/../config.json';
        if (file_exists($configFile)) {
            return json_decode(file_get_contents($configFile), true);
        }
        return [];
    }
    
    /**
     * ثبت لاگ
     */
    protected function log($message, $level = 'info') 
    {
        $this->logger->log($message, $level);
    }
    
    /**
     * ولیدیشن داده‌ها
     */
    protected function validate($data, $rules) 
    {
        foreach ($rules as $field => $rule) {
            if ($rule['required'] && empty($data[$field])) {
                throw new InvalidArgumentException("فیلد {$field} الزامی است");
            }
            
            if (isset($rule['type']) && !empty($data[$field])) {
                $this->validateType($data[$field], $rule['type'], $field);
            }
        }
        return true;
    }
    
    /**
     * بررسی نوع داده
     */
    private function validateType($value, $type, $field) 
    {
        switch ($type) {
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException("فرمت {$field} نامعتبر است");
                }
                break;
            case 'ip':
                if (!filter_var($value, FILTER_VALIDATE_IP)) {
                    throw new InvalidArgumentException("آدرس IP {$field} نامعتبر است");
                }
                break;
            case 'port':
                if (!is_numeric($value) || $value < 1 || $value > 65535) {
                    throw new InvalidArgumentException("شماره پورت {$field} نامعتبر است");
                }
                break;
        }
    }
    
    /**
     * تبدیل نتیجه به JSON
     */
    protected function jsonResponse($data, $success = true) 
    {
        return json_encode([
            'success' => $success,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * تبدیل خطا به JSON
     */
    protected function jsonError($message, $code = 500) 
    {
        return json_encode([
            'success' => false,
            'error' => $message,
            'code' => $code,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * برگرداندن پاسخ موفق
     */
    protected function success($data, $message = null) 
    {
        return [
            'success' => true,
            'data' => $data,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * برگرداندن پاسخ خطا
     */
    protected function error($message, $code = 500, $data = null) 
    {
        return [
            'success' => false,
            'message' => $message,
            'code' => $code,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}
