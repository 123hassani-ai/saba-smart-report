<?php
require_once __DIR__ . '/../BaseModule.php';

/**
 * Configuration Manager - مدیریت تنظیمات
 * 
 * @author Saba Reporting System
 * @version 2.0
 */

class ConfigManager extends BaseModule 
{
    private $configFile;
    private $defaultConfig;
    
    public function __construct() 
    {
        parent::__construct();
        $this->configFile = __DIR__ . '/../../config.json';
        $this->initializeDefaultConfig();
    }
    
    /**
     * مقادیر پیش‌فرض تنظیمات
     */
    private function initializeDefaultConfig() 
    {
        $this->defaultConfig = [
            'sql_server' => [
                'server' => '',
                'database' => '',
                'username' => '',
                'password' => '',
                'port' => '1433',
                'connection_method' => 'odbc'
            ],
            'cloud' => [
                'host' => '',
                'database' => '',
                'username' => '',
                'password' => '',
                'port' => '3306'
            ],
            'settings' => [
                'auto_sync_interval' => 300,
                'batch_size' => 1000,
                'max_execution_time' => 300,
                'log_level' => 'info',
                'timezone' => 'Asia/Tehran'
            ],
            'dashboard' => [
                'items_per_page' => 50,
                'refresh_interval' => 30,
                'theme' => 'default'
            ]
        ];
    }
    
    /**
     * دریافت تنظیمات
     */
    public function getConfig() 
    {
        return $this->load();
    }
    
    /**
     * بارگذاری تنظیمات
     */
    public function load() 
    {
        try {
            if (!file_exists($this->configFile)) {
                $this->createDefaultConfig();
            }
            
            $config = json_decode(file_get_contents($this->configFile), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('فرمت JSON تنظیمات معتبر نیست');
            }
            
            // ادغام با تنظیمات پیش‌فرض (بدون recursive)
            return array_merge($this->defaultConfig, $config);
            
        } catch (Exception $e) {
            $this->log("خطا در بارگذاری تنظیمات: " . $e->getMessage(), 'error');
            return $this->defaultConfig;
        }
    }
    
    /**
     * ذخیره تنظیمات
     */
    public function save($config) 
    {
        try {
            $this->validateConfig($config);
            
            $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            if (file_put_contents($this->configFile, $json, LOCK_EX) === false) {
                throw new Exception('امکان ذخیره تنظیمات وجود ندارد');
            }
            
            $this->log("تنظیمات با موفقیت ذخیره شد");
            return true;
            
        } catch (Exception $e) {
            $this->log("خطا در ذخیره تنظیمات: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * اعتبارسنجی تنظیمات
     */
    private function validateConfig($config) 
    {
        $rules = [
            'sql_server' => ['required' => true],
            'sql_server.server' => ['required' => true],
            'sql_server.database' => ['required' => true],
            'sql_server.port' => ['required' => true, 'type' => 'port'],
        ];
        
        // اعتبارسنجی ساختار تو در تو
        foreach ($rules as $path => $rule) {
            $value = $this->getNestedValue($config, $path);
            
            if ($rule['required'] && empty($value)) {
                throw new InvalidArgumentException("فیلد {$path} الزامی است");
            }
            
            if (isset($rule['type']) && !empty($value)) {
                $this->validateType($value, $rule['type'], $path);
            }
        }
    }
    
    /**
     * اعتبارسنجی نوع داده
     */
    private function validateType($value, $type, $path) 
    {
        switch ($type) {
            case 'string':
                if (!is_string($value)) {
                    throw new InvalidArgumentException("فیلد {$path} باید رشته باشد");
                }
                break;
            case 'int':
                if (!is_int($value) && !ctype_digit($value)) {
                    throw new InvalidArgumentException("فیلد {$path} باید عدد صحیح باشد");
                }
                break;
            case 'bool':
                if (!is_bool($value)) {
                    throw new InvalidArgumentException("فیلد {$path} باید boolean باشد");
                }
                break;
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException("فیلد {$path} باید ایمیل معتبر باشد");
                }
                break;
            case 'ip':
                if (!filter_var($value, FILTER_VALIDATE_IP)) {
                    throw new InvalidArgumentException("فیلد {$path} باید IP معتبر باشد");
                }
                break;
            case 'port':
                $port = (int)$value;
                if ($port < 1 || $port > 65535) {
                    throw new InvalidArgumentException("فیلد {$path} باید پورت معتبر (1-65535) باشد");
                }
                break;
        }
    }

    /**
     * دریافت مقدار از ساختار تو در تو
     */
    private function getNestedValue($array, $path) 
    {
        $keys = explode('.', $path);
        $value = $array;
        
        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }
        
        return $value;
    }
    
    /**
     * ایجاد تنظیمات پیش‌فرض
     */
    private function createDefaultConfig() 
    {
        $this->save($this->defaultConfig);
        $this->log("فایل تنظیمات پیش‌فرض ایجاد شد");
    }
    
    /**
     * بررسی صحت تنظیمات
     */
    public function isValid() 
    {
        try {
            $config = $this->load();
            $this->validateConfig($config);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * دریافت بخش خاصی از تنظیمات
     */
    public function getSection($section) 
    {
        $config = $this->load();
        return $config[$section] ?? [];
    }
    
    /**
     * بروزرسانی بخش خاصی از تنظیمات
     */
    public function updateSection($section, $data) 
    {
        $config = $this->load();
        $config[$section] = array_merge($config[$section] ?? [], $data);
        return $this->save($config);
    }
}
