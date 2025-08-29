<?php
/**
 * Windows Entry Point - نسخه ویندوز سیستم گزارش‌گیری سبا
 * Windows Specific Version of Saba Reporting System
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 300);

// Load required modules
require_once 'modules/Logger.php';
require_once 'modules/config/ConfigManager.php';

/**
 * Windows Application Class - بهینه شده برای ویندوز
 */
class SabaWindowsApp {
    
    private $logger;
    private $config;
    private $sqlServerConn;
    private $cloudConn;
    private $logFile;
    
    public function __construct() {
        $this->createRequiredDirectories();
        $this->logger = Logger::getInstance();
        $this->config = new ConfigManager();
        $this->logFile = 'logs/sync_' . date('Y-m-d') . '.log';
        
        $this->logger->info('Saba Windows System Started');
        
        if ($this->hasValidConfig()) {
            $this->initializeConnections();
        }
    }
    
    public function run() {
        try {
            // Check if this is a web request or CLI
            if (php_sapi_name() === 'cli') {
                $this->runCLI();
            } else {
                $this->runWeb();
            }
            
        } catch (Exception $e) {
            $this->logger->error('Application Error: ' . $e->getMessage());
            $this->showError($e->getMessage());
        }
    }
    
    /**
     * ایجاد دایرکتوری‌های مورد نیاز
     */
    private function createRequiredDirectories() {
        $dirs = ['logs', 'config', 'temp'];
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
        }
    }
    
    /**
     * بررسی وجود تنظیمات معتبر
     */
    private function hasValidConfig() {
        $config = $this->config->getConfig();
        return !empty($config['sql_server']['server']) && 
               !empty($config['sql_server']['database']);
    }
    
    /**
     * راه‌اندازی اتصالات
     */
    private function initializeConnections() {
        try {
            $this->connectToSQLServer();
            
            $config = $this->config->getConfig();
            if (!empty($config['cloud']['host'])) {
                $this->connectToCloudDatabase();
            }
        } catch (Exception $e) {
            $this->logger->warning("Connection initialization failed: " . $e->getMessage());
        }
    }
    
    /**
     * اتصال به SQL Server - بهینه شده برای ویندوز
     */
    private function connectToSQLServer() {
        $config = $this->config->getConfig();
        $sqlConfig = $config['sql_server'];
        $method = $sqlConfig['connection_method'] ?? 'com';
        
        $this->logger->info("Connecting to SQL Server via: " . $method);
        
        try {
            // بررسی COM Extension
            if ($method === 'com' && !class_exists('COM')) {
                $this->logger->warning("COM extension not available, falling back to alternative method");
                throw new Exception("COM extension not loaded");
            }
            
            if ($method === 'com' && class_exists('COM')) {
                $this->connectViaCOM($sqlConfig);
            } else {
                // تلاش برای اتصال SQLSRV در ویندوز
                $this->connectViaSQLSRV($sqlConfig);
            }
            
            $this->logger->info("✅ SQL Server connection established");
            
        } catch (Exception $e) {
            $this->logger->error("❌ SQL Server connection failed: " . $e->getMessage());
            $this->sqlServerConn = null; // اطمینان از null بودن
            // عدم پرتاب exception تا برنامه متوقف نشود
        }
    }
    
    /**
     * اتصال از طریق COM Object (Windows Native) - Fixed Version
     */
    private function connectViaCOM($sqlConfig) {
        // بررسی مجدد COM
        if (!class_exists('COM')) {
            throw new Exception("COM extension is not available");
        }
        
        $server = $sqlConfig['server'];
        $database = $sqlConfig['database'];
        $username = $sqlConfig['username'] ?? '';
        $password = $sqlConfig['password'] ?? '';
        
        try {
            $this->logger->info("Creating COM ADODB.Connection object...");
            
            // استفاده از ADO Connection - مشابه sync-service-odbc.php
            $conn = new COM("ADODB.Connection");
            
            // استفاده از connection string بهتر
            $connectionString = "Provider=SQLOLEDB;Data Source={$server};Initial Catalog={$database};";
            
            if (!empty($username)) {
                $connectionString .= "User ID={$username};Password={$password};";
                $this->logger->info("Using SQL Server Authentication for user: {$username}");
            } else {
                $connectionString .= "Integrated Security=SSPI;";
                $this->logger->info("Using Windows Authentication");
            }
            
            $this->logger->info("Connection String: " . str_replace($password, '***', $connectionString));
            $this->logger->info("Attempting to connect...");
            
            $conn->Open($connectionString);
            
            // ذخیره COM object مستقیماً
            $this->sqlServerConn = $conn;
            
            $this->logger->info("✅ COM Connection established successfully");
            
        } catch (Exception $e) {
            $this->sqlServerConn = null;
            $this->logger->error("COM connection failed: " . $e->getMessage());
            throw new Exception("COM connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * اتصال از طریق SQLSRV Driver
     */
    private function connectViaSQLSRV($sqlConfig) {
        $server = $sqlConfig['server'];
        $database = $sqlConfig['database'];
        $username = $sqlConfig['username'];
        $password = $sqlConfig['password'];
        $port = $sqlConfig['port'] ?? '1433';
        
        // SQLSRV connection برای ویندوز
        if (extension_loaded('sqlsrv')) {
            $dsn = "sqlsrv:Server={$server},{$port};Database={$database}";
            
            $this->sqlServerConn = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 30
            ]);
            
            $this->logger->info("Connected via SQLSRV: " . $server);
        } else {
            throw new Exception("SQLSRV extension not loaded");
        }
    }
    
    /**
     * اتصال به پایگاه داده ابری
     */
    private function connectToCloudDatabase() {
        $config = $this->config->getConfig();
        $cloudConfig = $config['cloud'];
        
        try {
            $dsn = "mysql:host={$cloudConfig['host']};port={$cloudConfig['port']};dbname={$cloudConfig['database']};charset=utf8mb4";
            
            $this->cloudConn = new PDO($dsn, $cloudConfig['username'], $cloudConfig['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                PDO::ATTR_TIMEOUT => 30
            ]);
            
            $this->logger->info("✅ Cloud database connected: " . $cloudConfig['host']);
            
        } catch (PDOException $e) {
            $this->logger->error("❌ Cloud database connection failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * اجرای حالت CLI
     */
    private function runCLI() {
        global $argv;
        
        $command = $argv[1] ?? 'help';
        
        switch ($command) {
            case 'sync':
                $this->performSync();
                break;
            case 'test':
                $this->testConnections();
                break;
            case 'config':
                $this->showConfig();
                break;
            case 'tables':
                $this->showTables();
                break;
            case 'help':
            default:
                $this->showHelp();
                break;
        }
    }
    
    /**
     * اجرای حالت وب
     */
    private function runWeb() {
        $action = $_GET['action'] ?? 'dashboard';
        
        switch ($action) {
            case 'api':
                $this->handleAPI();
                break;
            case 'dashboard':
            default:
                $this->showDashboard();
                break;
        }
    }
    
    /**
     * انجام همگام‌سازی
     */
    private function performSync() {
        echo "🚀 شروع همگام‌سازی...\n";
        
        if (!$this->sqlServerConn) {
            echo "❌ اتصال SQL Server برقرار نیست\n";
            return;
        }
        
        if (!$this->cloudConn) {
            echo "❌ اتصال Cloud Database برقرار نیست\n";
            return;
        }
        
        try {
            $tables = $this->getSQLServerTables();
            $totalRecords = 0;
            $startTime = microtime(true);
            
            foreach ($tables as $table) {
                echo "📊 همگام‌سازی جدول: {$table['name']} ({$table['records']} رکورد)\n";
                
                $records = $this->getTableData($table['name']);
                if (!empty($records)) {
                    $this->syncTableToCloud($table['name'], $records);
                    $totalRecords += count($records);
                }
            }
            
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            
            echo "✅ همگام‌سازی کامل شد\n";
            echo "📊 تعداد کل رکوردها: {$totalRecords}\n";
            echo "⏱️ زمان اجرا: {$executionTime} ثانیه\n";
            
        } catch (Exception $e) {
            echo "❌ خطا در همگام‌سازی: " . $e->getMessage() . "\n";
            $this->logger->error("Sync error: " . $e->getMessage());
        }
    }
    
    /**
     * تست اتصالات
     */
    private function testConnections() {
        echo "🔍 تست اتصالات...\n";
        
        // تست SQL Server
        echo "📡 تست اتصال SQL Server: ";
        if ($this->testSQLServerConnection()) {
            echo "✅ موفق\n";
        } else {
            echo "❌ ناموفق\n";
        }
        
        // تست Cloud Database
        echo "☁️ تست اتصال Cloud Database: ";
        if ($this->testCloudConnection()) {
            echo "✅ موفق\n";
        } else {
            echo "❌ ناموفق\n";
        }
    }
    
    /**
     * تست اتصال SQL Server - Enhanced Version
     */
    private function testSQLServerConnection() {
        try {
            // بررسی COM Extension
            if (!class_exists('COM')) {
                $this->logger->warning("COM extension not available - SQL Server connection disabled");
                return false;
            }
            
            // تلاش برای اتصال در صورت عدم وجود
            if (!$this->sqlServerConn) {
                try {
                    $this->logger->info("SQL Server connection not found, attempting to connect...");
                    $this->connectToSQLServer();
                } catch (Exception $e) {
                    $this->logger->error("SQL Server connection failed during test: " . $e->getMessage());
                    return false;
                }
            }
            
            // بررسی نهایی اتصال
            if (!$this->sqlServerConn) {
                $this->logger->warning("SQL Server connection is null after connection attempt");
                return false;
            }
            
            // Test query - مناسب برای COM Object
            try {
                $this->logger->info("Running test query on SQL Server...");
                
                // COM Object uses Execute method
                $rs = $this->sqlServerConn->Execute("SELECT @@VERSION as version, @@SERVERNAME as server_name, DB_NAME() as database_name");
                
                if (!$rs->EOF) {
                    $version = $rs->Fields("version")->Value;
                    $serverName = $rs->Fields("server_name")->Value;
                    $dbName = $rs->Fields("database_name")->Value;
                    
                    $this->logger->info("✅ SQL Server test successful:");
                    $this->logger->info("  Server: " . $serverName);
                    $this->logger->info("  Database: " . $dbName);
                    $this->logger->info("  Version: " . substr($version, 0, 100));
                    
                    $rs->Close();
                    return true;
                } else {
                    $this->logger->warning("SQL Server query returned no results");
                    return false;
                }
                
            } catch (Exception $e) {
                $this->logger->error("SQL Server test query failed: " . $e->getMessage());
                return false;
            }
            
        } catch (Exception $e) {
            $this->logger->error("SQL Server test failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * تست اتصال Cloud Database
     */
    private function testCloudConnection() {
        try {
            if (!$this->cloudConn) {
                $this->connectToCloudDatabase();
            }
            
            $stmt = $this->cloudConn->query("SELECT 1");
            return $stmt !== false;
            
        } catch (Exception $e) {
            $this->logger->error("Cloud test failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * دریافت جداول SQL Server
     */
    private function getSQLServerTables() {
        $tables = [];
        
        try {
            // بررسی اتصال
            if (!$this->sqlServerConn) {
                $this->logger->warning("SQL Server connection is null, trying to connect...");
                $this->connectToSQLServer();
            }
            
            if (!$this->sqlServerConn) {
                $this->logger->error("Could not establish SQL Server connection");
                return $tables;
            }
            
            $query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME";
            
            if ($this->sqlServerConn instanceof COM) {
                $rs = $this->sqlServerConn->Execute($query);
                
                while (!$rs->EOF) {
                    $tableName = $rs->Fields("TABLE_NAME")->Value;
                    
                    // Get record count
                    try {
                        $countQuery = "SELECT COUNT(*) as cnt FROM [{$tableName}]";
                        $countRs = $this->sqlServerConn->Execute($countQuery);
                        $recordCount = $countRs->Fields("cnt")->Value;
                    } catch (Exception $e) {
                        $recordCount = 0;
                    }
                    
                    $tables[] = [
                        'name' => $tableName,
                        'records' => $recordCount
                    ];
                    
                    $rs->MoveNext();
                }
            } else {
                $stmt = $this->sqlServerConn->query($query);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $tableName = $row['TABLE_NAME'];
                    
                    try {
                        $countStmt = $this->sqlServerConn->query("SELECT COUNT(*) as cnt FROM [{$tableName}]");
                        $countRow = $countStmt->fetch(PDO::FETCH_ASSOC);
                        $recordCount = $countRow['cnt'];
                    } catch (Exception $e) {
                        $recordCount = 0;
                    }
                    
                    $tables[] = [
                        'name' => $tableName,
                        'records' => $recordCount
                    ];
                }
            }
            
        } catch (Exception $e) {
            $this->logger->error("Failed to get tables: " . $e->getMessage());
        }
        
        return $tables;
    }
    
    /**
     * دریافت داده‌های جدول
     */
    private function getTableData($tableName, $limit = 1000) {
        $data = [];
        
        try {
            $query = "SELECT TOP {$limit} * FROM [{$tableName}]";
            
            if ($this->sqlServerConn instanceof COM) {
                $rs = $this->sqlServerConn->Execute($query);
                
                while (!$rs->EOF) {
                    $record = [];
                    for ($i = 0; $i < $rs->Fields->Count; $i++) {
                        $field = $rs->Fields($i);
                        $record[$field->Name] = $field->Value;
                    }
                    $data[] = $record;
                    $rs->MoveNext();
                }
            } else {
                $stmt = $this->sqlServerConn->query($query);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
        } catch (Exception $e) {
            $this->logger->error("Failed to get table data for {$tableName}: " . $e->getMessage());
        }
        
        return $data;
    }
    
    /**
     * همگام‌سازی جدول به Cloud
     */
    private function syncTableToCloud($tableName, $data) {
        if (empty($data)) return;
        
        try {
            // Create table if not exists
            $this->createCloudTable($tableName, $data[0]);
            
            // Clear existing data
            $this->cloudConn->exec("DELETE FROM `{$tableName}`");
            
            // Insert new data
            $this->insertCloudData($tableName, $data);
            
            $this->logger->info("Synced table {$tableName} - " . count($data) . " records");
            
        } catch (Exception $e) {
            $this->logger->error("Failed to sync table {$tableName}: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * ایجاد جدول در Cloud
     */
    private function createCloudTable($tableName, $sampleRecord) {
        $columns = [];
        
        foreach ($sampleRecord as $key => $value) {
            $type = 'TEXT';
            if (is_int($value)) {
                $type = 'INT';
            } elseif (is_float($value)) {
                $type = 'DECIMAL(10,2)';
            } elseif (is_bool($value)) {
                $type = 'TINYINT(1)';
            }
            
            $columns[] = "`{$key}` {$type}";
        }
        
        $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (" . implode(', ', $columns) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->cloudConn->exec($sql);
    }
    
    /**
     * درج داده در Cloud
     */
    private function insertCloudData($tableName, $data) {
        if (empty($data)) return;
        
        $keys = array_keys($data[0]);
        $placeholders = ':' . implode(', :', $keys);
        $columns = '`' . implode('`, `', $keys) . '`';
        
        $sql = "INSERT INTO `{$tableName}` ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->cloudConn->prepare($sql);
        
        foreach ($data as $record) {
            $stmt->execute($record);
        }
    }
    
    /**
     * نمایش جداول
     */
    private function showTables() {
        echo "📊 جداول موجود در SQL Server:\n";
        
        $tables = $this->getSQLServerTables();
        foreach ($tables as $table) {
            echo "  - {$table['name']} ({$table['records']} رکورد)\n";
        }
    }
    
    /**
     * نمایش تنظیمات
     */
    private function showConfig() {
        $config = $this->config->getConfig();
        echo "⚙️ تنظیمات فعلی:\n";
        echo json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
    
    /**
     * نمایش راهنما
     */
    private function showHelp() {
        echo "📖 راهنمای استفاده - نسخه ویندوز\n\n";
        echo "دستورات موجود:\n";
        echo "  sync     - انجام همگام‌سازی کامل\n";
        echo "  test     - تست اتصالات\n";
        echo "  tables   - نمایش جداول SQL Server\n";
        echo "  config   - نمایش تنظیمات\n";
        echo "  help     - نمایش این راهنما\n\n";
        echo "مثال‌ها:\n";
        echo "  php windows.php sync\n";
        echo "  php windows.php test\n";
        echo "  php windows.php tables\n";
    }
    
    /**
     * نمایش داشبورد وب
     */
    private function showDashboard() {
        ?>
        <!DOCTYPE html>
        <html lang="fa" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>سیستم گزارش‌گیری سبا - ویندوز</title>
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap');
                
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: 'Vazirmatn', Arial, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    direction: rtl;
                }
                
                .container {
                    max-width: 1200px;
                    margin: 0 auto;
                    padding: 20px;
                }
                
                .header {
                    background: rgba(255, 255, 255, 0.1);
                    backdrop-filter: blur(10px);
                    border-radius: 20px;
                    padding: 30px;
                    text-align: center;
                    margin-bottom: 30px;
                    border: 1px solid rgba(255, 255, 255, 0.2);
                }
                
                .header h1 {
                    color: white;
                    font-size: 2.5em;
                    font-weight: 700;
                    margin-bottom: 10px;
                }
                
                .header p {
                    color: rgba(255, 255, 255, 0.8);
                    font-size: 1.2em;
                }
                
                .status-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                    gap: 20px;
                    margin-bottom: 30px;
                }
                
                .status-card {
                    background: rgba(255, 255, 255, 0.1);
                    backdrop-filter: blur(10px);
                    border-radius: 15px;
                    padding: 25px;
                    text-align: center;
                    border: 1px solid rgba(255, 255, 255, 0.2);
                    transition: transform 0.3s ease;
                }
                
                .status-card:hover {
                    transform: translateY(-5px);
                }
                
                .status-card h3 {
                    color: white;
                    font-size: 1.5em;
                    margin-bottom: 15px;
                    font-weight: 600;
                }
                
                .status-indicator {
                    font-size: 1.2em;
                    font-weight: 500;
                    padding: 10px 20px;
                    border-radius: 25px;
                    display: inline-block;
                    margin-top: 10px;
                }
                
                .status-connected {
                    background: rgba(40, 167, 69, 0.8);
                    color: white;
                }
                
                .status-disconnected {
                    background: rgba(220, 53, 69, 0.8);
                    color: white;
                }
                
                .actions {
                    text-align: center;
                    margin: 30px 0;
                }
                
                .btn {
                    display: inline-block;
                    padding: 15px 30px;
                    margin: 10px;
                    border: none;
                    border-radius: 25px;
                    font-size: 1.1em;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    text-decoration: none;
                    font-family: 'Vazirmatn', Arial, sans-serif;
                }
                
                .btn-primary {
                    background: linear-gradient(45deg, #28a745, #20c997);
                    color: white;
                }
                
                .btn-secondary {
                    background: linear-gradient(45deg, #6c757d, #495057);
                    color: white;
                }
                
                .btn-info {
                    background: linear-gradient(45deg, #17a2b8, #138496);
                    color: white;
                }
                
                .btn:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
                }
                
                .output-area {
                    background: rgba(0, 0, 0, 0.3);
                    border-radius: 15px;
                    padding: 20px;
                    min-height: 300px;
                    font-family: 'Courier New', monospace;
                    color: #00ff41;
                    font-size: 14px;
                    line-height: 1.6;
                    overflow-y: auto;
                    border: 1px solid rgba(255, 255, 255, 0.1);
                }
                
                .loading {
                    text-align: center;
                    color: rgba(255, 255, 255, 0.7);
                    font-size: 1.1em;
                }
                
                .info-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                    gap: 15px;
                    margin-top: 20px;
                }
                
                .info-item {
                    background: rgba(255, 255, 255, 0.05);
                    padding: 15px;
                    border-radius: 10px;
                    border: 1px solid rgba(255, 255, 255, 0.1);
                }
                
                .info-label {
                    color: rgba(255, 255, 255, 0.7);
                    font-size: 0.9em;
                    margin-bottom: 5px;
                }
                
                .info-value {
                    color: white;
                    font-weight: 500;
                    font-size: 1.1em;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🪟 سیستم گزارش‌گیری سبا</h1>
                    <p>نسخه ویندوز - همگام‌سازی SQL Server به Cloud Database</p>
                </div>
                
                <div class="status-grid">
                    <div class="status-card">
                        <h3>📡 SQL Server</h3>
                        <div class="status-indicator <?php echo $this->testSQLServerConnection() ? 'status-connected' : 'status-disconnected'; ?>">
                            <?php echo $this->testSQLServerConnection() ? '✅ متصل' : '❌ قطع'; ?>
                        </div>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">سرور</div>
                                <div class="info-value"><?php echo $this->config->getConfig()['sql_server']['server'] ?? 'نامشخص'; ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">دیتابیس</div>
                                <div class="info-value"><?php echo $this->config->getConfig()['sql_server']['database'] ?? 'نامشخص'; ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="status-card">
                        <h3>☁️ Cloud Database</h3>
                        <div class="status-indicator <?php echo $this->testCloudConnection() ? 'status-connected' : 'status-disconnected'; ?>">
                            <?php echo $this->testCloudConnection() ? '✅ متصل' : '❌ قطع'; ?>
                        </div>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">سرور</div>
                                <div class="info-value"><?php echo $this->config->getConfig()['cloud']['host'] ?? 'نامشخص'; ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">دیتابیس</div>
                                <div class="info-value"><?php echo $this->config->getConfig()['cloud']['database'] ?? 'نامشخص'; ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="status-card">
                        <h3>📊 آمار جداول</h3>
                        <div class="status-indicator status-connected">
                            <?php 
                            $tables = $this->getSQLServerTables();
                            echo count($tables) . ' جدول';
                            ?>
                        </div>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">کل رکوردها</div>
                                <div class="info-value">
                                    <?php 
                                    $totalRecords = array_sum(array_column($tables, 'records'));
                                    echo number_format($totalRecords);
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="actions">
                    <button onclick="startSync()" class="btn btn-primary">🚀 شروع همگام‌سازی</button>
                    <button onclick="testConnections()" class="btn btn-secondary">🔍 تست اتصالات</button>
                    <button onclick="showTables()" class="btn btn-info">📊 نمایش جداول</button>
                    <button onclick="showConfig()" class="btn btn-secondary">⚙️ تنظیمات</button>
                </div>
                
                <div id="output" class="output-area">
                    <div class="loading">آماده برای اجرای دستورات...</div>
                </div>
            </div>
            
            <script>
                function updateOutput(message) {
                    document.getElementById('output').innerHTML = '<pre>' + message + '</pre>';
                }
                
                function startSync() {
                    updateOutput('🚀 در حال شروع همگام‌سازی...\nلطفاً صبر کنید...');
                    
                    fetch('?action=api&endpoint=sync', {method: 'POST'})
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                updateOutput('✅ همگام‌سازی با موفقیت انجام شد!\n\n' + (data.output || JSON.stringify(data, null, 2)));
                            } else {
                                updateOutput('❌ خطا در همگام‌سازی:\n' + data.message);
                            }
                        })
                        .catch(error => {
                            updateOutput('❌ خطا در ارسال درخواست:\n' + error.message);
                        });
                }
                
                function testConnections() {
                    updateOutput('🔍 در حال تست اتصالات...');
                    
                    fetch('?action=api&endpoint=test')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                let result = '📊 نتایج تست اتصالات:\n\n';
                                result += '📡 SQL Server: ' + (data.data.sql_server ? '✅ موفق' : '❌ ناموفق') + '\n';
                                result += '☁️ Cloud Database: ' + (data.data.cloud_db ? '✅ موفق' : '❌ ناموفق') + '\n';
                                updateOutput(result);
                            } else {
                                updateOutput('❌ خطا در تست:\n' + data.message);
                            }
                            
                            // Refresh page after 2 seconds to update status
                            setTimeout(() => location.reload(), 2000);
                        })
                        .catch(error => {
                            updateOutput('❌ خطا در تست:\n' + error.message);
                        });
                }
                
                function showTables() {
                    updateOutput('📊 در حال دریافت جداول...');
                    
                    fetch('?action=api&endpoint=tables')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.data.length > 0) {
                                let result = '📋 جداول موجود:\n\n';
                                data.data.forEach((table, index) => {
                                    result += `${index + 1}. ${table.name} - ${table.records} رکورد\n`;
                                });
                                updateOutput(result);
                            } else {
                                updateOutput('⚠️ هیچ جدولی یافت نشد یا خطا در دریافت جداول');
                            }
                        })
                        .catch(error => {
                            updateOutput('❌ خطا در دریافت جداول:\n' + error.message);
                        });
                }
                
                function showConfig() {
                    updateOutput('⚙️ در حال نمایش تنظیمات...');
                    
                    fetch('?action=api&endpoint=config')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                updateOutput('⚙️ تنظیمات فعلی:\n\n' + JSON.stringify(data.data, null, 2));
                            } else {
                                updateOutput('❌ خطا در نمایش تنظیمات:\n' + data.message);
                            }
                        })
                        .catch(error => {
                            updateOutput('❌ خطا:\n' + error.message);
                        });
                }
            </script>
        </body>
        </html>
        <?php
    }
    
    /**
     * Handle API requests
     */
    private function handleAPI() {
        header('Content-Type: application/json; charset=utf-8');
        
        $endpoint = $_GET['endpoint'] ?? '';
        
        try {
            switch ($endpoint) {
                case 'sync':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        ob_start();
                        $this->performSync();
                        $output = ob_get_clean();
                        echo json_encode(['success' => true, 'output' => $output]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                    }
                    break;
                    
                case 'test':
                    $sqlTest = $this->testSQLServerConnection();
                    $cloudTest = $this->testCloudConnection();
                    
                    echo json_encode([
                        'success' => true,
                        'data' => [
                            'sql_server' => $sqlTest,
                            'cloud_db' => $cloudTest
                        ]
                    ]);
                    break;
                    
                case 'tables':
                    $tables = $this->getSQLServerTables();
                    echo json_encode(['success' => true, 'data' => $tables]);
                    break;
                    
                case 'config':
                    $config = $this->config->getConfig();
                    // حذف رمزهای عبور برای امنیت
                    if (isset($config['sql_server']['password'])) {
                        $config['sql_server']['password'] = '***';
                    }
                    if (isset($config['cloud']['password'])) {
                        $config['cloud']['password'] = '***';
                    }
                    echo json_encode(['success' => true, 'data' => $config]);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * نمایش خطا
     */
    private function showError($message) {
        if (php_sapi_name() === 'cli') {
            echo "❌ خطا: $message\n";
        } else {
            echo "<h1>خطا</h1><p>$message</p>";
        }
    }
}

// Run the Windows application
$app = new SabaWindowsApp();
$app->run();
?>
