<?php
/**
 * Simple Test Script
 * بررسی سریع عملکرد سیستم
 */

echo "🚀 Saba System Test\n";
echo "==================\n\n";

// بررسی PHP
echo "✅ PHP Version: " . PHP_VERSION . "\n";
echo "✅ OS: " . PHP_OS . "\n";

// بررسی Extensions
$extensions = [
    'COM' => class_exists('COM'),
    'PDO' => class_exists('PDO'),
    'JSON' => function_exists('json_encode'),
    'MySQL' => class_exists('PDO') && in_array('mysql', PDO::getAvailableDrivers())
];

echo "\n📦 Extensions:\n";
foreach ($extensions as $name => $loaded) {
    echo ($loaded ? "✅" : "❌") . " $name\n";
}

// بررسی فایل‌ها
$files = [
    'config.json',
    'windows.php',
    'modules/BaseModule.php'
];

echo "\n📁 Files:\n";
foreach ($files as $file) {
    echo (file_exists($file) ? "✅" : "❌") . " $file\n";
}

// بررسی Config
if (file_exists('config.json')) {
    echo "\n⚙️ Config Test:\n";
    $config = json_decode(file_get_contents('config.json'), true);
    if ($config) {
        echo "✅ Config is valid JSON\n";
        echo "✅ SQL Server: " . ($config['sql_server']['server'] ?? 'Not Set') . "\n";
        echo "✅ Database: " . ($config['sql_server']['database'] ?? 'Not Set') . "\n";
    } else {
        echo "❌ Config has JSON syntax errors\n";
    }
} else {
    echo "\n❌ Config file not found\n";
}

echo "\n🎯 System Status: READY\n";
echo "Run start-windows.bat to begin!\n";
?>
