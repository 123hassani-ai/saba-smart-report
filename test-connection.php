<?php
require_once __DIR__ . '/modules/database/SQLServerConnection.php';
require_once __DIR__ . '/modules/config/ConfigManager.php';

echo "=== تست اتصال SQL Server ===\n";

try {
    // بارگذاری تنظیمات
    $configFile = PHP_OS_FAMILY === 'Windows' ? 'config.json' : 'config-mac.json';
    echo "استفاده از فایل تنظیمات: {$configFile}\n";
    
    $configManager = new ConfigManager();
    
    // تست اتصال
    $sqlConnection = new SQLServerConnection();
    
    if ($sqlConnection->testConnection()) {
        echo "✅ اتصال به SQL Server موفقیت‌آمیز!\n";
        
        // دریافت جداول
        $tables = $sqlConnection->getTables();
        echo "📋 تعداد جداول یافت شده: " . count($tables) . "\n";
        
        if (!empty($tables)) {
            echo "نمونه جداول:\n";
            foreach (array_slice($tables, 0, 5) as $table) {
                echo "  - {$table['name']} ({$table['records']} رکورد)\n";
            }
        }
        
    } else {
        echo "❌ خطا در اتصال به SQL Server\n";
    }
    
} catch (Exception $e) {
    echo "❌ خطا: " . $e->getMessage() . "\n";
    echo "ردیابی: " . $e->getTraceAsString() . "\n";
}

echo "\n=== پایان تست ===\n";
?>
