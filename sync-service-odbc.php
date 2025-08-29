<?php
// sync-service-odbc.php - نسخه بدون SQL Server driver خاص
ini_set('display_errors', 1);
error_reporting(E_ALL);

class PHPSyncServiceODBC {
    private $sqlServerConn;
    private $cloudConn;
    private $config;
    private $logFile;
    private $configFile = 'config.json';
    
    public function __construct() {
        $this->createRequiredDirectories();
        $this->config = $this->loadConfig();
        $this->logFile = 'logs/sync_' . date('Y-m-d') . '.log';
        
        if ($this->hasValidConfig()) {
            try {
                $this->initializeConnections();
            } catch (Exception $e) {
                $this->log("⚠️ Connection failed: " . $e->getMessage());
            }
        }
    }
    
    private function createRequiredDirectories() {
        $dirs = ['logs', 'config', 'temp'];
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
        }
    }
    
    private function loadConfig() {
        if (file_exists($this->configFile)) {
            $config = json_decode(file_get_contents($this->configFile), true);
            if ($config) {
                return $config;
            }
        }
        
        return [
            'sql_server' => [
                'server' => 'localhost',
                'database' => '',
                'username' => '',
                'password' => '',
                'port' => '1433',
                'connection_method' => 'odbc' // odbc یا com
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
                'log_level' => 'info'
            ]
        ];
    }
    
    private function saveConfig($config) {
        file_put_contents($this->configFile, json_encode($config, JSON_PRETTY_PRINT));
        $this->config = $config;
    }
    
    private function hasValidConfig() {
        return !empty($this->config['sql_server']['server']) && 
               !empty($this->config['sql_server']['database']);
    }
    
    private function initializeConnections() {
        $this->connectToSQLServer();
        
        if (!empty($this->config['cloud']['host'])) {
            $this->connectToCloudDatabase();
        }
    }
    
    private function connectToSQLServer() {
        $sqlConfig = $this->config['sql_server'];
        $method = $sqlConfig['connection_method'] ?? 'odbc';
        
        if ($method === 'com') {
            // استفاده از COM Object (برای Windows)
            $this->connectViaCOM($sqlConfig);
        } else {
            // استفاده از ODBC
            $this->connectViaODBC($sqlConfig);
        }
    }
    
    private function connectViaODBC($sqlConfig) {
        try {
            // تشخیص سیستم عامل
            $isWindows = (PHP_OS_FAMILY === 'Windows');
            $isMac = (PHP_OS_FAMILY === 'Darwin');
            
            $connectionStrings = [];
            
            if ($isWindows) {
                // Windows ODBC drivers
                $connectionStrings = [
                    "Driver={ODBC Driver 17 for SQL Server};Server={$sqlConfig['server']};Database={$sqlConfig['database']};Uid={$sqlConfig['username']};Pwd={$sqlConfig['password']};",
                    "Driver={SQL Server Native Client 11.0};Server={$sqlConfig['server']};Database={$sqlConfig['database']};Uid={$sqlConfig['username']};Pwd={$sqlConfig['password']};",
                    "Driver={SQL Server};Server={$sqlConfig['server']};Database={$sqlConfig['database']};Uid={$sqlConfig['username']};Pwd={$sqlConfig['password']};",
                ];
            } elseif ($isMac) {
                // macOS FreeTDS drivers
                $connectionStrings = [
                    "DSN=SQLServer;UID={$sqlConfig['username']};PWD={$sqlConfig['password']};",
                    "Driver=/opt/homebrew/lib/libtdsodbc.so;Server={$sqlConfig['server']},{$sqlConfig['port']};Database={$sqlConfig['database']};UID={$sqlConfig['username']};PWD={$sqlConfig['password']};TDS_Version=8.0;",
                ];
            } else {
                // Linux/Unix
                $connectionStrings = [
                    "Driver={FreeTDS};Server={$sqlConfig['server']};Port={$sqlConfig['port']};Database={$sqlConfig['database']};UID={$sqlConfig['username']};PWD={$sqlConfig['password']};TDS_Version=8.0;",
                ];
            }
            
            foreach ($connectionStrings as $dsn) {
                try {
                    $this->sqlServerConn = new PDO("odbc:" . $dsn);
                    $this->sqlServerConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $this->log("✅ SQL Server connected via ODBC on " . PHP_OS_FAMILY . ": " . $dsn);
                    return;
                } catch (Exception $e) {
                    $this->log("⚠️ ODBC attempt failed on " . PHP_OS_FAMILY . ": " . $e->getMessage());
                    continue;
                }
            }
            
            throw new Exception("Cannot connect via ODBC on " . PHP_OS_FAMILY . " - tried " . count($connectionStrings) . " connection strings");
            
        } catch (Exception $e) {
            $this->log("❌ ODBC connection failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function connectViaCOM($sqlConfig) {
        try {
            // استفاده از ADODB.Connection
            $conn = new COM("ADODB.Connection");
            $connectionString = "Provider=SQLOLEDB;Data Source={$sqlConfig['server']};Initial Catalog={$sqlConfig['database']};User ID={$sqlConfig['username']};Password={$sqlConfig['password']};";
            
            $conn->Open($connectionString);
            $this->sqlServerConn = $conn; // ذخیره COM object
            $this->log("✅ SQL Server connected via COM");
            
        } catch (Exception $e) {
            $this->log("❌ COM connection failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function connectToCloudDatabase() {
        $cloudConfig = $this->config['cloud'];
        $dsn = "mysql:host={$cloudConfig['host']};port={$cloudConfig['port']};charset=utf8mb4";
        
        if (!empty($cloudConfig['database'])) {
            $dsn .= ";dbname={$cloudConfig['database']}";
        }
        
        try {
            $this->cloudConn = new PDO($dsn, $cloudConfig['username'], $cloudConfig['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10
            ]);
            
            $this->log("✅ Cloud Database connected successfully");
            
        } catch (PDOException $e) {
            throw new Exception("Cannot connect to Cloud Database: " . $e->getMessage());
        }
    }
    
    // باقی متدها مشابه نسخه قبلی...
    public function startWebInterface() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];
        
        if (strpos($uri, '/api/') === 0) {
            $this->handleAPIRequest($uri, $method);
            return;
        }
        
        switch ($uri) {
            case '/':
            case '/dashboard':
                $this->showDashboard();
                break;
            case '/config':
                $this->showConfigPage();
                break;
            case '/logs':
                $this->showLogsPage();
                break;
            case '/test':
                $this->showTestPage();
                break;
            case '/install':
                $this->showInstallGuide();
                break;
            default:
                $this->show404();
        }
    }
    
    private function handleAPIRequest($uri, $method) {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT');
        header('Access-Control-Allow-Headers: Content-Type');
        
        try {
            switch ($uri) {
                case '/api/config':
                    if ($method === 'GET') {
                        echo json_encode(['success' => true, 'config' => $this->config]);
                    } elseif ($method === 'POST') {
                        $input = json_decode(file_get_contents('php://input'), true);
                        $this->saveConfig($input);
                        echo json_encode(['success' => true, 'message' => 'Configuration saved']);
                    }
                    break;
                    
                case '/api/test-connection':
                    echo json_encode($this->testConnections());
                    break;
                    
                case '/api/tables':
                    echo json_encode($this->getAvailableTables());
                    break;
                    
                case '/api/sync':
                    if ($method === 'POST') {
                        $input = json_decode(file_get_contents('php://input'), true);
                        echo json_encode($this->syncTables($input['tables'] ?? []));
                    }
                    break;
                    
                case '/api/logs':
                    echo json_encode($this->getRecentLogs());
                    break;
                    
                case '/api/drivers':
                    echo json_encode($this->checkDrivers());
                    break;
                    
                case '/api/table-stats':
                    echo json_encode($this->getTableStats());
                    break;
                    
                case '/api/connection-status':
                    echo json_encode($this->getConnectionStatus());
                    break;
                    
                case '/api/sync-history':
                    echo json_encode($this->getSyncHistory());
                    break;
                
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'API endpoint not found']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    private function checkDrivers() {
        $drivers = [
            'pdo_drivers' => PDO::getAvailableDrivers(),
            'odbc_available' => extension_loaded('odbc'),
            'com_available' => class_exists('COM'),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'windows_version' => PHP_OS,
            'php_version' => PHP_VERSION,
            'odbc_drivers' => $this->getODBCDrivers()
        ];
        
        return ['success' => true, 'drivers' => $drivers];
    }
    
    private function getODBCDrivers() {
        $drivers = [];
        
        try {
            // چک کردن ODBC drivers نصب شده
            if (function_exists('odbc_data_source')) {
                $result = odbc_data_source(null, SQL_FETCH_FIRST_SYSTEM);
                while ($result) {
                    $drivers[] = $result;
                    $result = odbc_data_source(null, SQL_FETCH_NEXT);
                }
            }
        } catch (Exception $e) {
            // Ignore errors
        }
        
        return $drivers;
    }
    
    private function getAvailableTables() {
        if (!$this->sqlServerConn) {
            return ['success' => false, 'error' => 'SQL Server not connected'];
        }
        
        try {
            if (is_a($this->sqlServerConn, 'COM')) {
                // استفاده از COM Object
                return $this->getTablesViaCOM();
            } else {
                // استفاده از PDO
                return $this->getTablesViaPDO();
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function getTablesViaPDO() {
        $query = "
            SELECT 
                t.TABLE_NAME,
                COUNT(c.COLUMN_NAME) as COLUMN_COUNT
            FROM INFORMATION_SCHEMA.TABLES t
            LEFT JOIN INFORMATION_SCHEMA.COLUMNS c ON t.TABLE_NAME = c.TABLE_NAME
            WHERE t.TABLE_TYPE = 'BASE TABLE'
            AND t.TABLE_SCHEMA = 'dbo'
            GROUP BY t.TABLE_NAME
            ORDER BY t.TABLE_NAME
        ";
        
        $stmt = $this->sqlServerConn->prepare($query);
        $stmt->execute();
        $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'tables' => array_map(function($table) {
                return [
                    'name' => $table['TABLE_NAME'],
                    'columnCount' => $table['COLUMN_COUNT'],
                    'lastSync' => 'Never',
                    'recordsCount' => 0,
                    'selected' => false
                ];
            }, $tables)
        ];
    }
    
    private function getTablesViaCOM() {
        $recordset = $this->sqlServerConn->Execute("
            SELECT TABLE_NAME, 'Never' as LAST_SYNC, 0 as RECORDS_COUNT
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_TYPE = 'BASE TABLE' 
            AND TABLE_SCHEMA = 'dbo'
            ORDER BY TABLE_NAME
        ");
        
        $tables = [];
        while (!$recordset->EOF) {
            $tables[] = [
                'name' => $recordset->Fields('TABLE_NAME')->Value,
                'columnCount' => 0,
                'lastSync' => $recordset->Fields('LAST_SYNC')->Value,
                'recordsCount' => $recordset->Fields('RECORDS_COUNT')->Value,
                'selected' => false
            ];
            $recordset->MoveNext();
        }
        
        return ['success' => true, 'tables' => $tables];
    }
    
    public function syncTables($tableNames) {
        if (!$this->sqlServerConn || !$this->cloudConn) {
            return ['success' => false, 'error' => 'Database connections not available'];
        }
        
        $results = [];
        
        foreach ($tableNames as $tableName) {
            try {
                $result = $this->syncTable($tableName);
                $results[] = $result;
                $this->log("✅ Table {$tableName} synced: {$result['records']} records");
            } catch (Exception $e) {
                $results[] = [
                    'table' => $tableName,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                    'records' => 0
                ];
                $this->log("❌ Table {$tableName} sync failed: " . $e->getMessage());
            }
        }
        
        return [
            'success' => true,
            'results' => $results,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    private function syncTable($tableName) {
        $this->log("🔄 Starting sync for table: {$tableName}");
        
        $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);
        
        try {
            if (is_a($this->sqlServerConn, 'COM')) {
                $records = $this->getTableDataViaCOM($tableName);
            } else {
                $records = $this->getTableDataViaPDO($tableName);
            }
            
            if (empty($records)) {
                return [
                    'table' => $tableName,
                    'status' => 'empty',
                    'records' => 0,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
            
            $this->createCloudTable($tableName, $records[0]);
            $this->cloudConn->exec("DELETE FROM `{$tableName}_sync`");
            
            $syncedCount = 0;
            foreach ($records as $record) {
                $this->insertCloudRecord($tableName, $record);
                $syncedCount++;
                
                if ($syncedCount % 100 == 0) {
                    $this->log("📊 Synced {$syncedCount} records...");
                }
            }
            
            return [
                'table' => $tableName,
                'status' => 'success', 
                'records' => $syncedCount,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            throw new Exception("Sync failed for {$tableName}: " . $e->getMessage());
        }
    }
    
    private function getTableDataViaPDO($tableName) {
        $query = "SELECT * FROM [{$tableName}]";
        $stmt = $this->sqlServerConn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getTableDataViaCOM($tableName) {
        $recordset = $this->sqlServerConn->Execute("SELECT * FROM [{$tableName}]");
        
        $records = [];
        while (!$recordset->EOF) {
            $record = [];
            for ($i = 0; $i < $recordset->Fields->Count; $i++) {
                $field = $recordset->Fields($i);
                $record[$field->Name] = $field->Value;
            }
            $records[] = $record;
            $recordset->MoveNext();
        }
        
        return $records;
    }
    
    private function createCloudTable($tableName, $sampleRecord) {
        $columns = [];
        foreach ($sampleRecord as $key => $value) {
            $type = 'TEXT';
            
            if (is_int($value)) {
                $type = 'INT';
            } elseif (is_float($value)) {
                $type = 'DECIMAL(15,2)';
            } elseif (is_bool($value)) {
                $type = 'BOOLEAN';
            } elseif (is_string($value) && strlen($value) < 255) {
                $type = 'VARCHAR(500)';
            }
            
            $columns[] = "`{$key}` {$type}";
        }
        
        $createSQL = "
            CREATE TABLE IF NOT EXISTS `{$tableName}_sync` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                " . implode(",\n                ", $columns) . ",
                synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_synced_at (synced_at)
            )
        ";
        
        $this->cloudConn->exec($createSQL);
        $this->log("📋 Table {$tableName}_sync created/updated");
    }
    
    private function insertCloudRecord($tableName, $record) {
        $keys = array_keys($record);
        $values = array_values($record);
        $placeholders = str_repeat('?,', count($keys) - 1) . '?';
        
        $insertSQL = "
            INSERT INTO `{$tableName}_sync` (`" . implode('`,`', $keys) . "`)
            VALUES ({$placeholders})
        ";
        
        $stmt = $this->cloudConn->prepare($insertSQL);
        $stmt->execute($values);
    }
    
    private function testConnections() {
        $result = [
            'sql_server' => false,
            'cloud' => false,
            'messages' => []
        ];
        
        // تست SQL Server
        try {
            if ($this->sqlServerConn) {
                if (is_a($this->sqlServerConn, 'COM')) {
                    $recordset = $this->sqlServerConn->Execute("SELECT 1 as test");
                    $result['sql_server'] = true;
                    $result['messages'][] = "✅ SQL Server: اتصال موفق (COM)";
                } else {
                    $stmt = $this->sqlServerConn->query("SELECT 1");
                    $result['sql_server'] = true;
                    $result['messages'][] = "✅ SQL Server: اتصال موفق (PDO/ODBC)";
                }
            } else {
                $result['messages'][] = "❌ SQL Server: اتصال برقرار نشده";
            }
        } catch (Exception $e) {
            $result['messages'][] = "❌ SQL Server: " . $e->getMessage();
        }
        
        // تست Cloud Database
        try {
            if ($this->cloudConn) {
                $stmt = $this->cloudConn->query("SELECT 1");
                $result['cloud'] = true;
                $result['messages'][] = "✅ Cloud Database: اتصال موفق";
            } elseif (empty($this->config['cloud']['host'])) {
                $result['messages'][] = "⚠️ Cloud Database: تنظیمات وارد نشده";
            } else {
                $result['messages'][] = "❌ Cloud Database: اتصال برقرار نشده";
            }
        } catch (Exception $e) {
            $result['messages'][] = "❌ Cloud Database: " . $e->getMessage();
        }
        
        return $result;
    }
    
    private function getTableStats() {
        try {
            $stats = [
                'total_tables' => 0,
                'synced_tables' => 0,
                'total_records' => 0,
                'last_sync' => null,
                'sync_status' => 'idle'
            ];
            
            if ($this->sqlServerConn) {
                $tables = $this->getAvailableTables();
                if ($tables['success']) {
                    $stats['total_tables'] = count($tables['tables']);
                    
                    foreach ($tables['tables'] as $table) {
                        if (isset($table['records'])) {
                            $stats['total_records'] += (int)$table['records'];
                        }
                        if (isset($table['last_sync']) && $table['last_sync'] !== 'Never') {
                            $stats['synced_tables']++;
                            if (!$stats['last_sync'] || $table['last_sync'] > $stats['last_sync']) {
                                $stats['last_sync'] = $table['last_sync'];
                            }
                        }
                    }
                }
            }
            
            return ['success' => true, 'stats' => $stats];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function getConnectionStatus() {
        $status = [
            'sql_server' => ['connected' => false, 'method' => null, 'info' => null],
            'cloud' => ['connected' => false, 'info' => null],
            'uptime' => time() - (isset($_SESSION['start_time']) ? $_SESSION['start_time'] : time()),
            'last_check' => date('Y-m-d H:i:s')
        ];
        
        try {
            if ($this->sqlServerConn) {
                $status['sql_server']['connected'] = true;
                $status['sql_server']['method'] = $this->config['sql_server']['connection_method'] ?? 'odbc';
                $status['sql_server']['info'] = [
                    'server' => $this->config['sql_server']['server'],
                    'database' => $this->config['sql_server']['database']
                ];
            }
        } catch (Exception $e) {
            $status['sql_server']['error'] = $e->getMessage();
        }
        
        try {
            if ($this->cloudConn) {
                $status['cloud']['connected'] = true;
                $status['cloud']['info'] = [
                    'host' => $this->config['cloud']['host'],
                    'database' => $this->config['cloud']['database']
                ];
            }
        } catch (Exception $e) {
            $status['cloud']['error'] = $e->getMessage();
        }
        
        return ['success' => true, 'status' => $status];
    }
    
    private function getSyncHistory() {
        try {
            $historyFile = 'logs/sync_history.json';
            $history = [];
            
            if (file_exists($historyFile)) {
                $history = json_decode(file_get_contents($historyFile), true) ?? [];
            }
            
            // آخرین 20 عملیات
            $history = array_slice($history, -20);
            
            return ['success' => true, 'history' => array_reverse($history)];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function logSyncOperation($operation, $table, $status, $records = 0) {
        $historyFile = 'logs/sync_history.json';
        $history = [];
        
        if (file_exists($historyFile)) {
            $history = json_decode(file_get_contents($historyFile), true) ?? [];
        }
        
        $history[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'operation' => $operation,
            'table' => $table,
            'status' => $status,
            'records' => $records,
            'duration' => microtime(true) - ($_SESSION['operation_start'] ?? microtime(true))
        ];
        
        file_put_contents($historyFile, json_encode($history));
    }

    private function showInstallGuide() {
        ?>
        <!DOCTYPE html>
        <html dir="rtl" lang="fa">
        <head>
            <meta charset="UTF-8">
            <title>راهنمای نصب و تنظیم</title>
            <link rel="stylesheet" href="data:text/css;base64,<?= base64_encode($this->getCSS()) ?>">
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🛠️ راهنمای نصب و تنظیم</h1>
                    <p>راهنمای کامل برای Windows 7</p>
                </div>
                
                <div class="nav-tabs">
                    <a href="/" class="nav-tab">🏠 داشبورد</a>
                    <a href="/config" class="nav-tab">⚙️ تنظیمات</a>
                    <a href="/test" class="nav-tab">🔧 تست اتصال</a>
                    <a href="/logs" class="nav-tab">📄 گزارشات</a>
                    <a href="/install" class="nav-tab active">🛠️ راهنمای نصب</a>
                </div>
                
                <div class="card">
                    <h3>1️⃣ تنظیم SQL Server</h3>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0;">
                        <h4>فعال کردن TCP/IP:</h4>
                        <ol>
                            <li>SQL Server Configuration Manager را باز کنید</li>
                            <li>SQL Server Network Configuration → Protocols for [Instance] → TCP/IP → Enable</li>
                            <li>SQL Server Services → SQL Server → Restart</li>
                        </ol>
                        
                        <h4>تنظیم Authentication:</h4>
                        <ol>
                            <li>SQL Server Management Studio → سرور → Properties</li>
                            <li>Security → SQL Server and Windows Authentication mode</li>
                            <li>سرور را restart کنید</li>
                        </ol>
                    </div>
                </div>
                
                <div class="card">
                    <h3>2️⃣ تنظیمات ODBC</h3>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0;">
                        <h4>ایجاد ODBC Data Source:</h4>
                        <ol>
                            <li>Control Panel → Administrative Tools → Data Sources (ODBC)</li>
                            <li>System DSN → Add → SQL Server</li>
                            <li>نام: MyDatabase، Server: localhost</li>
                            <li>Authentication تنظیم کنید</li>
                            <li>Default database انتخاب کنید</li>
                        </ol>
                    </div>
                </div>
                
                <div class="card">
                    <h3>3️⃣ روش‌های اتصال</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div style="background: #e8f5e8; padding: 15px; border-radius: 8px;">
                            <h4>✅ ODBC (توصیه اول)</h4>
                            <ul>
                                <li>بدون نیاز به driver خاص</li>
                                <li>سازگار با Windows 7</li>
                                <li>استفاده از PDO_ODBC</li>
                            </ul>
                        </div>
                        
                        <div style="background: #fff3cd; padding: 15px; border-radius: 8px;">
                            <h4>⚠️ COM Object</h4>
                            <ul>
                                <li>استفاده از ADODB</li>
                                <li>نیاز به COM enabled</li>
                                <li>برای حالات خاص</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <h3>4️⃣ تست و عیب‌یابی</h3>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0;">
                        <h4>بررسی وضعیت فعلی:</h4>
                        <button class="btn" onclick="checkDriverStatus()">🔍 بررسی Drivers</button>
                        <div id="driverStatus" style="margin-top: 15px;"></div>
                    </div>
                </div>
            </div>
            
            <script>
                function checkDriverStatus() {
                    document.getElementById('driverStatus').innerHTML = '<div class="loading"></div> در حال بررسی...';
                    
                    fetch('/api/drivers')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                let html = '<div class="log-area" style="height: auto; color: #333; background: #f8f9fa;">';
                                html += '<strong>PDO Drivers:</strong> ' + data.drivers.pdo_drivers.join(', ') + '<br>';
                                html += '<strong>ODBC Available:</strong> ' + (data.drivers.odbc_available ? 'Yes' : 'No') + '<br>';
                                html += '<strong>COM Available:</strong> ' + (data.drivers.com_available ? 'Yes' : 'No') + '<br>';
                                html += '<strong>MySQL Available:</strong> ' + (data.drivers.pdo_mysql ? 'Yes' : 'No') + '<br>';
                                html += '<strong>PHP Version:</strong> ' + data.drivers.php_version + '<br>';
                                html += '<strong>Windows:</strong> ' + data.drivers.windows_version + '<br>';
                                html += '</div>';
                                
                                document.getElementById('driverStatus').innerHTML = html;
                            }
                        })
                        .catch(error => {
                            document.getElementById('driverStatus').innerHTML = '<div style="color: red;">خطا در بررسی</div>';
                        });
                }
            </script>
        </body>
        </html>
        <?php
    }
    
    // باقی متدهای مشابه نسخه قبلی (showDashboard, showConfigPage, etc.)
    private function showDashboard() {
        $connectionStatus = $this->testConnections();
        ?>
        <!DOCTYPE html>
        <html dir="rtl" lang="fa">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>سرویس همگام‌سازی داده‌ها</title>
            <link rel="stylesheet" href="data:text/css;base64,<?= base64_encode($this->getCSS()) ?>">
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🔄 سرویس همگام‌سازی داده‌ها</h1>
                    <p>نسخه ODBC سازگار با Windows 7</p>
                </div>
                
                <div class="nav-tabs">
                    <a href="/" class="nav-tab active">🏠 داشبورد</a>
                    <a href="/config" class="nav-tab">⚙️ تنظیمات</a>
                    <a href="/test" class="nav-tab">🔧 تست اتصال</a>
                    <a href="/logs" class="nav-tab">📄 گزارشات</a>
                    <a href="/install" class="nav-tab">🛠️ راهنمای نصب</a>
                </div>
                
                <div class="status-grid">
                    <div class="status-card <?= $connectionStatus['sql_server'] ? 'status-success' : 'status-error' ?>">
                        <h3>🗄️ SQL Server</h3>
                        <p><?= $connectionStatus['sql_server'] ? '✅ متصل' : '❌ قطع' ?></p>
                    </div>
                    
                    <div class="status-card <?= $connectionStatus['cloud'] ? 'status-success' : 'status-warning' ?>">
                        <h3>☁️ Cloud Database</h3>
                        <p><?= $connectionStatus['cloud'] ? '✅ متصل' : '⚠️ تنظیم نشده' ?></p>
                    </div>
                </div>
                
                <?php if (!$connectionStatus['sql_server']): ?>
                <div class="card" style="background: rgba(255, 193, 7, 0.1); border: 1px solid #ffc107;">
                    <h3 style="color: #856404;">⚠️ مشکل اتصال SQL Server</h3>
                    <p>برای رفع مشکل، ابتدا راهنمای نصب را مطالعه کنید.</p>
                    <a href="/install" class="btn" style="background: #ffc107; color: #212529;">🛠️ راهنمای نصب</a>
                </div>
                <?php elseif (!$this->hasValidConfig()): ?>
                <div class="card">
                    <h3 style="color: #ff6b6b;">⚠️ تنظیمات مورد نیاز</h3>
                    <p>برای شروع، لطفاً ابتدا تنظیمات اتصال را کامل کنید.</p>
                    <a href="/config" class="btn">⚙️ تنظیمات اتصال</a>
                </div>
                <?php else: ?>
                
                <!-- آمار کلی -->
                <div id="statsSection"></div>
                
                <!-- بخش جستجو و فیلتر جداول -->
                <div class="search-filter-section">
                    <h3>🔍 جستجو و مدیریت جداول</h3>
                    
                    <div class="search-box">
                        <input type="text" id="tableSearch" class="search-input" placeholder="جستجو در نام جداول..." onkeyup="filterTables()">
                        <span class="search-icon">🔍</span>
                    </div>
                    
                    <div class="filter-tags">
                        <button class="filter-tag active" onclick="filterByStatus('all')">همه جداول</button>
                        <button class="filter-tag" onclick="filterByStatus('synced')">همگام‌سازی شده</button>
                        <button class="filter-tag" onclick="filterByStatus('not-synced')">همگام‌سازی نشده</button>
                        <button class="filter-tag" onclick="filterByStatus('large')">جداول بزرگ (1000+ رکورد)</button>
                    </div>
                </div>

                <div class="card">
                    <h3>📋 جداول موجود <span class="realtime-indicator online" id="connectionIndicator"></span></h3>
                    <div id="tablesArea">
                        <button class="btn" onclick="loadTables()">🔄 بارگذاری جداول</button>
                    </div>
                </div>
                
                <div class="card">
                    <h3>🚀 عملیات همگام‌سازی</h3>
                    <div style="text-align: center;">
                        <button class="btn" onclick="startSync()" id="syncBtn">
                            ▶️ شروع همگام‌سازی
                        </button>
                        <button class="btn" onclick="stopSync()" style="background: #dc3545;">
                            ⏹️ توقف همگام‌سازی
                        </button>
                    </div>
                </div>
                
                <?php endif; ?>
                
                <div class="card">
                    <h3>📊 گزارش فعالیت</h3>
                    <div class="log-area" id="logArea">
                        در حال بارگذاری گزارشات...
                    </div>
                </div>
            </div>
            
            <script>
                let syncInterval;
                let allTables = [];
                let currentFilter = 'all';
                
                // بارگذاری آمار کلی
                async function loadStats() {
                    try {
                        const response = await fetch('/api/table-stats');
                        const data = await response.json();
                        
                        if (data.success) {
                            const stats = data.stats;
                            document.getElementById('statsSection').innerHTML = `
                                <div class="stats-grid">
                                    <div class="stat-card">
                                        <div class="stat-number">${stats.total_tables}</div>
                                        <div class="stat-label">کل جداول</div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-number">${stats.synced_tables}</div>
                                        <div class="stat-label">همگام‌سازی شده</div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-number">${stats.total_records.toLocaleString()}</div>
                                        <div class="stat-label">کل رکوردها</div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-number">${stats.last_sync ? new Date(stats.last_sync).toLocaleDateString('fa-IR') : 'هرگز'}</div>
                                        <div class="stat-label">آخرین همگام‌سازی</div>
                                    </div>
                                </div>
                            `;
                        }
                    } catch (error) {
                        console.error('Error loading stats:', error);
                    }
                }
                
                // بارگذاری جداول
                async function loadTables() {
                    document.getElementById('tablesArea').innerHTML = '<div class="loading"></div> در حال بارگذاری...';
                    
                    try {
                        const response = await fetch('/api/tables');
                        const data = await response.json();
                        
                        if (data.success) {
                            allTables = data.tables || [];
                            renderTables(allTables);
                        } else {
                            document.getElementById('tablesArea').innerHTML = 
                                '<div style="color: red;">❌ خطا: ' + (data.error || 'مشکل در بارگذاری جداول') + '</div>';
                        }
                    } catch (error) {
                        document.getElementById('tablesArea').innerHTML = 
                            '<div style="color: red;">❌ خطا در ارتباط: ' + error.message + '</div>';
                    }
                }
                
                // نمایش جداول
                function renderTables(tables) {
                    if (!tables || tables.length === 0) {
                        document.getElementById('tablesArea').innerHTML = 
                            '<div style="text-align: center; color: #666;">هیچ جدولی یافت نشد</div>';
                        return;
                    }
                    
                    let html = '<div class="table-list">';
                    
                    tables.forEach((table, index) => {
                        const lastSync = table.last_sync && table.last_sync !== 'Never' 
                            ? new Date(table.last_sync).toLocaleString('fa-IR')
                            : 'هرگز';
                        const records = table.records || 0;
                        const isLarge = records > 1000;
                        const isSynced = table.last_sync && table.last_sync !== 'Never';
                        
                        html += `
                            <div class="table-item" data-table="${table.name}" data-records="${records}" data-synced="${isSynced}">
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <input type="checkbox" id="table_${index}" value="${table.name}">
                                    <div>
                                        <strong style="font-size: 16px;">${table.name}</strong>
                                        <div class="table-meta">
                                            <span>📊 ${records.toLocaleString()} رکورد</span>
                                            <span>🕒 ${lastSync}</span>
                                            ${isLarge ? '<span style="color: #ff9800;">⚠️ جدول بزرگ</span>' : ''}
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <button onclick="showTableDetails('${table.name}')" class="btn" style="padding: 5px 10px; font-size: 12px;">
                                        📋 جزئیات
                                    </button>
                                    <button onclick="syncSingleTable('${table.name}')" class="btn" style="padding: 5px 10px; font-size: 12px; background: #4CAF50;">
                                        🔄 همگام‌سازی
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    
                    html += '</div>';
                    html += `
                        <div style="margin-top: 20px; text-align: center;">
                            <button onclick="selectAllTables()" class="btn" style="margin: 5px;">✅ انتخاب همه</button>
                            <button onclick="deselectAllTables()" class="btn" style="margin: 5px;">❌ حذف انتخاب</button>
                            <button onclick="syncSelectedTables()" class="btn" style="margin: 5px; background: #4CAF50;">
                                🚀 همگام‌سازی انتخاب شده
                            </button>
                        </div>
                    `;
                    
                    document.getElementById('tablesArea').innerHTML = html;
                }
                
                // فیلتر جداول بر اساس جستجو
                function filterTables() {
                    const searchTerm = document.getElementById('tableSearch').value.toLowerCase();
                    const filteredTables = allTables.filter(table => 
                        table.name.toLowerCase().includes(searchTerm)
                    );
                    renderTables(applyStatusFilter(filteredTables));
                }
                
                // فیلتر بر اساس وضعیت
                function filterByStatus(status) {
                    currentFilter = status;
                    
                    // بروزرسانی دکمه‌های فیلتر
                    document.querySelectorAll('.filter-tag').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    event.target.classList.add('active');
                    
                    const searchTerm = document.getElementById('tableSearch').value.toLowerCase();
                    const searchFiltered = allTables.filter(table => 
                        table.name.toLowerCase().includes(searchTerm)
                    );
                    
                    renderTables(applyStatusFilter(searchFiltered));
                }
                
                function applyStatusFilter(tables) {
                    switch (currentFilter) {
                        case 'synced':
                            return tables.filter(t => t.last_sync && t.last_sync !== 'Never');
                        case 'not-synced':
                            return tables.filter(t => !t.last_sync || t.last_sync === 'Never');
                        case 'large':
                            return tables.filter(t => (t.records || 0) > 1000);
                        default:
                            return tables;
                    }
                }
                
                // انتخاب/حذف انتخاب همه جداول
                function selectAllTables() {
                    document.querySelectorAll('input[type="checkbox"][id^="table_"]').forEach(cb => cb.checked = true);
                }
                
                function deselectAllTables() {
                    document.querySelectorAll('input[type="checkbox"][id^="table_"]').forEach(cb => cb.checked = false);
                }
                
                // همگام‌سازی جداول انتخابی
                async function syncSelectedTables() {
                    const selectedTables = Array.from(
                        document.querySelectorAll('input[type="checkbox"][id^="table_"]:checked')
                    ).map(cb => cb.value);
                    
                    if (selectedTables.length === 0) {
                        alert('⚠️ لطفاً حداقل یک جدول انتخاب کنید');
                        return;
                    }
                    
                    await performSync(selectedTables);
                }
                
                // همگام‌سازی تک جدول
                async function syncSingleTable(tableName) {
                    await performSync([tableName]);
                }
                
                // اجرای همگام‌سازی
                async function performSync(tables) {
                    const syncBtn = document.getElementById('syncBtn');
                    const originalText = syncBtn.innerHTML;
                    
                    try {
                        syncBtn.innerHTML = '⏳ در حال همگام‌سازی...';
                        syncBtn.disabled = true;
                        
                        const response = await fetch('/api/sync', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ tables: tables })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            alert('✅ همگام‌سازی موفقیت‌آمیز بود!\\nجداول: ' + tables.join(', '));
                            loadTables(); // رفرش جداول
                            loadStats(); // رفرش آمار
                        } else {
                            alert('❌ خطا در همگام‌سازی: ' + (result.error || 'خطای نامشخص'));
                        }
                    } catch (error) {
                        alert('❌ خطا در ارتباط: ' + error.message);
                    } finally {
                        syncBtn.innerHTML = originalText;
                        syncBtn.disabled = false;
                    }
                }
                
                // نمایش جزئیات جدول
                async function showTableDetails(tableName) {
                    // اینجا می‌توانید مودال یا صفحه‌ای برای نمایش جزئیات جدول اضافه کنید
                    alert('🔍 جزئیات جدول: ' + tableName + '\\n(این قابلیت در نسخه آینده اضافه خواهد شد)');
                }
                
                // بروزرسانی وضعیت اتصال
                async function updateConnectionStatus() {
                    try {
                        const response = await fetch('/api/connection-status');
                        const data = await response.json();
                        
                        if (data.success) {
                            const indicator = document.getElementById('connectionIndicator');
                            const isOnline = data.status.sql_server.connected && data.status.cloud.connected;
                            
                            indicator.className = `realtime-indicator ${isOnline ? 'online' : 'offline'}`;
                        }
                    } catch (error) {
                        document.getElementById('connectionIndicator').className = 'realtime-indicator offline';
                    }
                }
                
                // بارگذاری گزارشات
                async function loadLogs() {
                    try {
                        const response = await fetch('/api/logs');
                        const data = await response.json();
                        
                        if (data.success && data.logs) {
                            document.getElementById('logArea').innerHTML = data.logs.slice(-10).reverse().join('<br>');
                        }
                    } catch (error) {
                        console.error('Error loading logs:', error);
                    }
                }
                
                // اجرای اولیه
                document.addEventListener('DOMContentLoaded', function() {
                    loadStats();
                    loadTables();
                    loadLogs();
                    updateConnectionStatus();
                    
                    // بروزرسانی خودکار هر 30 ثانیه
                    setInterval(() => {
                        updateConnectionStatus();
                        loadLogs();
                    }, 30000);
                });
            </script>
                    
                    fetch('/api/tables')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.tables) {
                                let html = '<div class="table-list">';
                                data.tables.forEach(table => {
                                    html += `
                                        <div class="table-item">
                                            <div>
                                                <strong>${table.name}</strong>
                                                <small>(${table.columnCount || 'N/A'} ستون، ${table.recordsCount || 0} رکورد)</small>
                                            </div>
                                            <div>
                                                <input type="checkbox" id="table_${table.name}" value="${table.name}">
                                                <label for="table_${table.name}">انتخاب</label>
                                            </div>
                                        </div>
                                    `;
                                });
                                html += '</div>';
                                html += '<button class="btn" onclick="syncSelectedTables()">همگام‌سازی انتخاب شده‌ها</button>';
                                document.getElementById('tablesArea').innerHTML = html;
                            } else {
                                document.getElementById('tablesArea').innerHTML = '<p style="color: #dc3545;">خطا در بارگذاری جداول: ' + (data.error || 'Unknown error') + '</p>';
                            }
                        })
                        .catch(error => {
                            document.getElementById('tablesArea').innerHTML = '<p style="color: #dc3545;">خطا در ارتباط با سرور</p>';
                        });
                }
                
                function syncSelectedTables() {
                    const checkboxes = document.querySelectorAll('input[type="checkbox"]:checked');
                    const selectedTables = Array.from(checkboxes).map(cb => cb.value);
                    
                    if (selectedTables.length === 0) {
                        alert('لطفاً حداقل یک جدول انتخاب کنید');
                        return;
                    }
                    
                    const syncBtn = document.getElementById('syncBtn');
                    syncBtn.innerHTML = '<div class="loading"></div> در حال همگام‌سازی...';
                    syncBtn.disabled = true;
                    
                    fetch('/api/sync', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({tables: selectedTables})
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('همگام‌سازی با موفقیت انجام شد!');
                            loadLogs();
                        } else {
                            alert('خطا در همگام‌سازی: ' + data.error);
                        }
                    })
                    .catch(error => {
                        alert('خطا در ارتباط با سرور');
                    })
                    .finally(() => {
                        syncBtn.innerHTML = '▶️ شروع همگام‌سازی';
                        syncBtn.disabled = false;
                    });
                }
                
                function startSync() {
                    loadTables();
                }
                
                function stopSync() {
                    if (syncInterval) {
                        clearInterval(syncInterval);
                        syncInterval = null;
                        alert('همگام‌سازی خودکار متوقف شد');
                    }
                }
                
                function loadLogs() {
                    fetch('/api/logs')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById('logArea').innerHTML = data.logs.join('\n') || 'هیچ گزارشی موجود نیست';
                            }
                        })
                        .catch(error => {
                            document.getElementById('logArea').innerHTML = 'خطا در بارگذاری گزارشات';
                        });
                }
                
                // بارگذاری اولیه
                loadLogs();
                
                // بروزرسانی خودکار هر 30 ثانیه
                setInterval(loadLogs, 30000);
            </script>
        </body>
        </html>
        <?php
    }
    
    private function showConfigPage() {
        if ($_POST) {
            $newConfig = [
                'sql_server' => [
                    'server' => $_POST['sql_server'] ?? '',
                    'database' => $_POST['sql_database'] ?? '',
                    'username' => $_POST['sql_username'] ?? '',
                    'password' => $_POST['sql_password'] ?? '',
                    'port' => $_POST['sql_port'] ?? '1433',
                    'connection_method' => $_POST['connection_method'] ?? 'odbc'
                ],
                'cloud' => [
                    'host' => $_POST['cloud_host'] ?? '',
                    'database' => $_POST['cloud_database'] ?? '',
                    'username' => $_POST['cloud_username'] ?? '',
                    'password' => $_POST['cloud_password'] ?? '',
                    'port' => $_POST['cloud_port'] ?? '3306'
                ],
                'settings' => $this->config['settings']
            ];
            
            $this->saveConfig($newConfig);
            $message = "✅ تنظیمات با موفقیت ذخیره شد!";
        }
        ?>
        <!DOCTYPE html>
        <html dir="rtl" lang="fa">
        <head>
            <meta charset="UTF-8">
            <title>تنظیمات اتصال</title>
            <link rel="stylesheet" href="data:text/css;base64,<?= base64_encode($this->getCSS()) ?>">
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>⚙️ تنظیمات اتصال</h1>
                </div>
                
                <div class="nav-tabs">
                    <a href="/" class="nav-tab">🏠 داشبورد</a>
                    <a href="/config" class="nav-tab active">⚙️ تنظیمات</a>
                    <a href="/test" class="nav-tab">🔧 تست اتصال</a>
                    <a href="/logs" class="nav-tab">📄 گزارشات</a>
                    <a href="/install" class="nav-tab">🛠️ راهنمای نصب</a>
                </div>
                
                <?php if (isset($message)): ?>
                <div class="card" style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724;">
                    <?= $message ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" class="card">
                    <h3>🗄️ تنظیمات SQL Server</h3>
                    
                    <div class="form-group">
                        <label>روش اتصال:</label>
                        <select name="connection_method" onchange="toggleConnectionMethod(this.value)">
                            <option value="odbc" <?= ($this->config['sql_server']['connection_method'] ?? 'odbc') === 'odbc' ? 'selected' : '' ?>>
                                ODBC (توصیه شده)
                            </option>
                            <option value="com" <?= ($this->config['sql_server']['connection_method'] ?? 'odbc') === 'com' ? 'selected' : '' ?>>
                                COM Object (پیشرفته)
                            </option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>آدرس سرور:</label>
                        <input type="text" name="sql_server" value="<?= htmlspecialchars($this->config['sql_server']['server']) ?>" placeholder="localhost یا .\SQLEXPRESS" required>
                        <small style="color: #666;">مثال: localhost، .\SQLEXPRESS، 192.168.1.100</small>
                    </div>
                    
                    <div class="form-group">
                        <label>نام دیتابیس:</label>
                        <input type="text" name="sql_database" value="<?= htmlspecialchars($this->config['sql_server']['database']) ?>" placeholder="نام دیتابیس شما" required>
                    </div>
                    
                    <div class="form-group">
                        <label>نام کاربری:</label>
                        <input type="text" name="sql_username" value="<?= htmlspecialchars($this->config['sql_server']['username']) ?>" placeholder="sa یا نام کاربری دیگر">
                        <small style="color: #666;">برای Windows Authentication خالی بگذارید</small>
                    </div>
                    
                    <div class="form-group">
                        <label>رمز عبور:</label>
                        <input type="password" name="sql_password" value="<?= htmlspecialchars($this->config['sql_server']['password']) ?>" placeholder="رمز عبور دیتابیس">
                    </div>
                    
                    <div class="form-group">
                        <label>پورت:</label>
                        <input type="text" name="sql_port" value="<?= htmlspecialchars($this->config['sql_server']['port']) ?>" placeholder="1433">
                    </div>
                    
                    <hr style="margin: 30px 0;">
                    
                    <h3>☁️ تنظیمات Cloud Database (MySQL)</h3>
                    
                    <div class="form-group">
                        <label>آدرس VPS:</label>
                        <input type="text" name="cloud_host" value="<?= htmlspecialchars($this->config['cloud']['host']) ?>" placeholder="IP address یا domain VPS شما">
                    </div>
                    
                    <div class="form-group">
                        <label>نام دیتابیس:</label>
                        <input type="text" name="cloud_database" value="<?= htmlspecialchars($this->config['cloud']['database']) ?>" placeholder="reports_database">
                    </div>
                    
                    <div class="form-group">
                        <label>نام کاربری:</label>
                        <input type="text" name="cloud_username" value="<?= htmlspecialchars($this->config['cloud']['username']) ?>" placeholder="sync_user">
                    </div>
                    
                    <div class="form-group">
                        <label>رمز عبور:</label>
                        <input type="password" name="cloud_password" value="<?= htmlspecialchars($this->config['cloud']['password']) ?>" placeholder="رمز عبور MySQL">
                    </div>
                    
                    <div class="form-group">
                        <label>پورت:</label>
                        <input type="text" name="cloud_port" value="<?= htmlspecialchars($this->config['cloud']['port']) ?>" placeholder="3306">
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <button type="submit" class="btn">💾 ذخیره تنظیمات</button>
                        <a href="/test" class="btn" style="background: #28a745;">🔧 تست اتصال</a>
                    </div>
                </form>
            </div>
            
            <script>
                function toggleConnectionMethod(method) {
                    // می‌توانید بر اساس روش انتخابی، راهنمایی‌های مختلفی نمایش دهید
                    console.log('Selected method:', method);
                }
            </script>
        </body>
        </html>
        <?php
    }
    
    // باقی متدها...
    private function getRecentLogs() {
        $logs = [];
        
        if (file_exists($this->logFile)) {
            $content = file_get_contents($this->logFile);
            $logs = explode("\n", $content);
            $logs = array_filter($logs);
            $logs = array_slice($logs, -50);
        }
        
        return [
            'success' => true,
            'logs' => $logs
        ];
    }
    
    private function showTestPage() {
        $connectionTest = $this->testConnections();
        $driverInfo = $this->checkDrivers();
        ?>
        <!DOCTYPE html>
        <html dir="rtl" lang="fa">
        <head>
            <meta charset="UTF-8">
            <title>تست اتصالات</title>
            <link rel="stylesheet" href="data:text/css;base64,<?= base64_encode($this->getCSS()) ?>">
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🔧 تست اتصالات</h1>
                </div>
                
                <div class="nav-tabs">
                    <a href="/" class="nav-tab">🏠 داشبورد</a>
                    <a href="/config" class="nav-tab">⚙️ تنظیمات</a>
                    <a href="/test" class="nav-tab active">🔧 تست اتصال</a>
                    <a href="/logs" class="nav-tab">📄 گزارشات</a>
                    <a href="/install" class="nav-tab">🛠️ راهنمای نصب</a>
                </div>
                
                <div class="card">
                    <h3>📊 نتایج تست اتصال</h3>
                    <div class="log-area" style="height: auto; color: #333; background: #f8f9fa;">
                        <?php foreach ($connectionTest['messages'] as $message): ?>
                            <div style="padding: 8px 0; border-bottom: 1px solid #eee;">
                                <?= htmlspecialchars($message) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top: 15px;">
                        <button class="btn" onclick="window.location.reload()">🔄 تست مجدد</button>
                    </div>
                </div>
                
                <div class="card">
                    <h3>🖥️ اطلاعات سیستم</h3>
                    <div class="log-area" style="height: auto; color: #333; background: #f8f9fa;">
                        <div><strong>PHP Version:</strong> <?= $driverInfo['drivers']['php_version'] ?></div>
                        <div><strong>Operating System:</strong> <?= $driverInfo['drivers']['windows_version'] ?></div>
                        <div><strong>PDO Drivers:</strong> <?= implode(', ', $driverInfo['drivers']['pdo_drivers']) ?></div>
                        <div><strong>ODBC Available:</strong> <?= $driverInfo['drivers']['odbc_available'] ? '✅ Yes' : '❌ No' ?></div>
                        <div><strong>COM Available:</strong> <?= $driverInfo['drivers']['com_available'] ? '✅ Yes' : '❌ No' ?></div>
                        <div><strong>MySQL Available:</strong> <?= $driverInfo['drivers']['pdo_mysql'] ? '✅ Yes' : '❌ No' ?></div>
                    </div>
                </div>
                
                <div class="card">
                    <h3>🛠️ پیشنهادات رفع مشکل</h3>
                    
                    <?php if (!$connectionTest['sql_server']): ?>
                    <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #ffc107;">
                        <h4>❌ مشکل SQL Server</h4>
                        <ul>
                            <li>SQL Server Service فعال باشد</li>
                            <li>TCP/IP Protocol فعال باشد</li>
                            <li>Authentication Mode درست تنظیم شده باشد</li>
                            <li>Firewall اجازه دسترسی دهد</li>
                        </ul>
                        <a href="/install" class="btn" style="background: #ffc107; color: #212529;">📚 راهنمای کامل</a>
                    </div>
                    <?php else: ?>
                    <div style="background: #d4edda; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #28a745;">
                        <h4>✅ SQL Server اتصال موفق</h4>
                        <p>اتصال به SQL Server برقرار است و می‌توانید از همگام‌سازی استفاده کنید.</p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!$driverInfo['drivers']['odbc_available'] && !$driverInfo['drivers']['com_available']): ?>
                    <div style="background: #f8d7da; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #dc3545;">
                        <h4>⚠️ هیچ روش اتصالی موجود نیست</h4>
                        <p>نه ODBC و نه COM در دسترس نیست. لطفاً یکی از این دو را فعال کنید.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    private function showLogsPage() {
        $logs = $this->getRecentLogs();
        ?>
        <!DOCTYPE html>
        <html dir="rtl" lang="fa">
        <head>
            <meta charset="UTF-8">
            <title>گزارشات سیستم</title>
            <link rel="stylesheet" href="data:text/css;base64,<?= base64_encode($this->getCSS()) ?>">
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>📄 گزارشات سیستم</h1>
                </div>
                
                <div class="nav-tabs">
                    <a href="/" class="nav-tab">🏠 داشبورد</a>
                    <a href="/config" class="nav-tab">⚙️ تنظیمات</a>
                    <a href="/test" class="nav-tab">🔧 تست اتصال</a>
                    <a href="/logs" class="nav-tab active">📄 گزارشات</a>
                    <a href="/install" class="nav-tab">🛠️ راهنمای نصب</a>
                </div>
                
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3>📊 آخرین فعالیت‌ها</h3>
                        <button class="btn" onclick="window.location.reload()">🔄 بروزرسانی</button>
                    </div>
                    
                    <div class="log-area">
                        <?php if ($logs['success'] && !empty($logs['logs'])): ?>
                            <?php foreach (array_reverse($logs['logs']) as $log): ?>
                                <div style="margin-bottom: 5px;"><?= htmlspecialchars($log) ?></div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="color: #888;">هیچ گزارشی موجود نیست</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    private function show404() {
        http_response_code(404);
        ?>
        <!DOCTYPE html>
        <html dir="rtl" lang="fa">
        <head>
            <meta charset="UTF-8">
            <title>صفحه یافت نشد</title>
            <link rel="stylesheet" href="data:text/css;base64,<?= base64_encode($this->getCSS()) ?>">
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>❌ صفحه یافت نشد</h1>
                    <p>صفحه مورد نظر شما یافت نشد.</p>
                    <a href="/" class="btn">🏠 بازگشت به داشبورد</a>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    private function getCSS() {
        return "
        @import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap');
        
        * { box-sizing: border-box; }
        body { 
            font-family: 'Vazirmatn', Tahoma, Arial, sans-serif; 
            margin: 0; padding: 0; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; font-weight: 400;
        }
        .container { 
            max-width: 1200px; margin: 0 auto; padding: 20px; 
        }
        .header { 
            background: rgba(255,255,255,0.95); 
            backdrop-filter: blur(10px);
            border-radius: 15px; padding: 30px; 
            margin-bottom: 20px; text-align: center;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
        }
        .header h1 { 
            margin: 0; color: #333; font-size: 2.5em; 
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        .header p { color: #666; margin: 10px 0; font-size: 1.1em; }
        
        .nav-tabs { 
            display: flex; gap: 10px; margin-bottom: 20px; 
            justify-content: center; flex-wrap: wrap;
        }
        .nav-tab { 
            background: rgba(255,255,255,0.9); 
            border: none; padding: 12px 24px; 
            border-radius: 25px; cursor: pointer;
            text-decoration: none; color: #333;
            font-weight: bold; transition: all 0.3s;
        }
        .nav-tab:hover, .nav-tab.active { 
            background: #fff; 
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .card { 
            background: rgba(255,255,255,0.95); 
            backdrop-filter: blur(10px);
            border-radius: 15px; padding: 25px; margin: 15px 0;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255,255,255,0.18);
        }
        
        .status-grid { 
            display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 15px; margin-bottom: 20px; 
        }
        .status-card { 
            padding: 20px; border-radius: 10px; text-align: center;
            color: white; font-weight: bold;
        }
        .status-success { background: linear-gradient(45deg, #56ab2f, #a8e6cf); }
        .status-error { background: linear-gradient(45deg, #ff416c, #ff4b2b); }
        .status-warning { background: linear-gradient(45deg, #f7971e, #ffd200); }
        
        .btn { 
            background: linear-gradient(45deg, #667eea, #764ba2); 
            color: white; border: none; 
            padding: 12px 24px; border-radius: 25px; 
            cursor: pointer; font-size: 16px; font-weight: bold;
            transition: all 0.3s; margin: 5px;
            text-decoration: none; display: inline-block;
        }
        .btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .btn:disabled { 
            opacity: 0.6; cursor: not-allowed; 
            transform: none; box-shadow: none;
        }
        
        .form-group { margin: 15px 0; }
        .form-group label { 
            display: block; margin-bottom: 8px; 
            font-weight: bold; color: #333;
        }
        .form-group input, .form-group select { 
            width: 100%; padding: 12px; border: 2px solid #ddd; 
            border-radius: 8px; font-size: 16px;
            transition: border-color 0.3s;
        }
        .form-group input:focus, .form-group select:focus { 
            outline: none; border-color: #667eea; 
        }
        .form-group small { 
            display: block; margin-top: 5px; 
            color: #666; font-size: 14px;
        }
        
        .log-area { 
            background: #1e1e1e; color: #00ff00; 
            padding: 20px; border-radius: 10px; 
            font-family: 'Courier New', monospace; 
            height: 300px; overflow-y: auto;
            font-size: 14px; line-height: 1.4;
        }
        
        .loading { 
            display: inline-block; width: 20px; height: 20px; 
            border: 3px solid rgba(255,255,255,.3); 
            border-radius: 50%; border-top-color: #fff; 
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        
        .table-list { max-height: 400px; overflow-y: auto; }
        .table-item { 
            display: flex; justify-content: space-between; 
            align-items: center; padding: 15px; 
            border: 2px solid #eee; border-radius: 12px; 
            margin: 10px 0; background: linear-gradient(145deg, #ffffff, #f0f0f0);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .table-item:hover { 
            background: linear-gradient(145deg, #f8f9ff, #e6f0ff); 
            border-color: #667eea; transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.2);
        }
        
        .search-filter-section {
            background: rgba(255,255,255,0.95);
            padding: 20px; border-radius: 15px; margin-bottom: 20px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
        }
        
        .search-box {
            position: relative; margin-bottom: 15px;
        }
        
        .search-input {
            width: 100%; padding: 15px 50px 15px 20px;
            border: 2px solid #ddd; border-radius: 25px;
            font-size: 16px; font-family: 'Vazirmatn';
            transition: all 0.3s ease; background: #fff;
        }
        
        .search-input:focus {
            outline: none; border-color: #667eea;
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.2);
        }
        
        .search-icon {
            position: absolute; left: 15px; top: 50%;
            transform: translateY(-50%); color: #666;
            font-size: 18px; pointer-events: none;
        }
        
        .filter-tags {
            display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 15px;
        }
        
        .filter-tag {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white; padding: 8px 15px; border-radius: 20px;
            font-size: 14px; font-weight: 500;
            border: none; cursor: pointer; transition: all 0.3s;
        }
        
        .filter-tag:hover {
            transform: scale(1.05); box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .filter-tag.active {
            background: linear-gradient(135deg, #43a047, #66bb6a);
        }
        
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px; margin-bottom: 20px;
        }
        
        .stat-card {
            background: linear-gradient(145deg, #ffffff, #f0f8ff);
            padding: 20px; border-radius: 15px; text-align: center;
            border: 2px solid #e3f2fd; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
        }
        
        .stat-number {
            font-size: 2em; font-weight: 700; 
            color: #667eea; margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666; font-weight: 500;
        }
        
        .table-details {
            display: none; margin-top: 10px; 
            padding: 15px; background: #f8f9ff;
            border-radius: 8px; border-right: 4px solid #667eea;
        }
        
        .table-meta {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px; font-size: 14px; color: #666;
        }
        
        .sync-progress {
            background: #f5f5f5; border-radius: 10px; 
            height: 8px; margin: 10px 0; overflow: hidden;
        }
        
        .sync-progress-bar {
            background: linear-gradient(90deg, #667eea, #764ba2);
            height: 100%; width: 0%; transition: width 0.5s ease;
        }
        
        .realtime-indicator {
            display: inline-block; width: 10px; height: 10px;
            border-radius: 50%; margin-left: 8px;
            animation: pulse 2s infinite;
        }
        
        .realtime-indicator.online {
            background: #4caf50; box-shadow: 0 0 10px #4caf50;
        }
        
        .realtime-indicator.offline {
            background: #f44336; box-shadow: 0 0 10px #f44336;
        }
        
        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.1); }
            100% { opacity: 1; transform: scale(1); }
        }
        
        ol { padding-right: 20px; }
        ul { padding-right: 20px; }
        li { margin: 8px 0; text-align: right; }
        
        @media (max-width: 768px) {
            .nav-tabs { flex-wrap: wrap; }
            .status-grid { grid-template-columns: 1fr; }
            .container { padding: 10px; }
            .header h1 { font-size: 1.8em; }
        }
        ";
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}\n";
        
        if (!file_exists('logs')) {
            mkdir('logs', 0777, true);
        }
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        if (php_sapi_name() === 'cli') {
            echo $logEntry;
        }
    }
}

// اجرای سرویس
if (php_sapi_name() === 'cli') {
    echo "🚀 Starting PHP Sync Service (ODBC Compatible) for Windows 7...\n";
    echo "Run: php -S localhost:8000 sync-service-odbc.php\n";
    echo "Web interface: http://localhost:8000\n\n";
    echo "Available endpoints:\n";
    echo "  /         - داشبورد اصلی\n";
    echo "  /config   - تنظیمات اتصال\n";
    echo "  /test     - تست اتصالات\n";
    echo "  /logs     - مشاهده گزارشات\n";
    echo "  /install  - راهنمای نصب\n\n";
} else {
    $service = new PHPSyncServiceODBC();
    $service->startWebInterface();
}
?>