<?php
/**
 * SQL Server Connection Logger & Debugger
 * ابزار تشخیص و لاگ مشکلات اتصال SQL Server
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

class SQLServerDebugger {
    private $config;
    private $logFile;
    
    public function __construct() {
        $this->logFile = 'logs/sql_debug_' . date('Y-m-d_H-i-s') . '.log';
        $this->loadConfig();
        $this->createLogDir();
    }
    
    private function createLogDir() {
        if (!file_exists('logs')) {
            mkdir('logs', 0777, true);
        }
    }
    
    private function loadConfig() {
        if (!file_exists('config.json')) {
            $this->logError("❌ config.json file not found");
            return false;
        }
        
        $configData = file_get_contents('config.json');
        $this->config = json_decode($configData, true);
        
        if (!$this->config) {
            $this->logError("❌ Invalid JSON in config.json");
            return false;
        }
        
        $this->logInfo("✅ Config loaded successfully");
        return true;
    }
    
    private function log($level, $message) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
        
        // Write to file
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Also echo to screen
        echo $logEntry;
    }
    
    private function logInfo($message) {
        $this->log('INFO', $message);
    }
    
    private function logError($message) {
        $this->log('ERROR', $message);
    }
    
    private function logWarning($message) {
        $this->log('WARNING', $message);
    }
    
    public function runDiagnostic() {
        $this->logInfo("🔍 Starting SQL Server Connection Diagnostic");
        $this->logInfo("==========================================");
        
        // Step 1: Check PHP Environment
        $this->checkPHPEnvironment();
        
        // Step 2: Check Extensions
        $this->checkExtensions();
        
        // Step 3: Check Config
        if ($this->config) {
            $this->checkConfig();
            
            // Step 4: Test Connection
            $this->testConnection();
        }
        
        $this->logInfo("==========================================");
        $this->logInfo("🏁 Diagnostic completed. Log saved to: " . $this->logFile);
        
        return $this->logFile;
    }
    
    private function checkPHPEnvironment() {
        $this->logInfo("🖥️ PHP Environment Check:");
        $this->logInfo("  PHP Version: " . PHP_VERSION);
        $this->logInfo("  Operating System: " . PHP_OS);
        $this->logInfo("  Server API: " . php_sapi_name());
        $this->logInfo("  Architecture: " . (PHP_INT_SIZE * 8) . "-bit");
        $this->logInfo("  Max Memory: " . ini_get('memory_limit'));
        $this->logInfo("  Max Execution Time: " . ini_get('max_execution_time'));
        $this->logInfo("  PHP INI File: " . php_ini_loaded_file());
        $this->logInfo("");
    }
    
    private function checkExtensions() {
        $this->logInfo("📦 Extensions Check:");
        
        // COM Extension
        if (class_exists('COM')) {
            $this->logInfo("  ✅ COM Extension: Available");
        } else {
            $this->logError("  ❌ COM Extension: NOT Available");
            $this->logError("     Solution: Enable com_dotnet extension in php.ini");
        }
        
        // PDO Extensions
        if (class_exists('PDO')) {
            $drivers = PDO::getAvailableDrivers();
            $this->logInfo("  ✅ PDO: Available");
            $this->logInfo("  📋 PDO Drivers: " . implode(', ', $drivers));
            
            if (in_array('sqlsrv', $drivers)) {
                $this->logInfo("  ✅ PDO_SQLSRV: Available");
            } else {
                $this->logWarning("  ⚠️ PDO_SQLSRV: Not Available (using COM instead)");
            }
        } else {
            $this->logError("  ❌ PDO: NOT Available");
        }
        
        // Other useful extensions
        $extensions = ['json', 'curl', 'openssl', 'mbstring'];
        foreach ($extensions as $ext) {
            $status = extension_loaded($ext) ? '✅' : '❌';
            $this->logInfo("  $status $ext: " . (extension_loaded($ext) ? 'Available' : 'Not Available'));
        }
        
        $this->logInfo("");
    }
    
    private function checkConfig() {
        $this->logInfo("⚙️ Configuration Check:");
        
        if (!isset($this->config['sql_server'])) {
            $this->logError("  ❌ sql_server section missing in config");
            return;
        }
        
        $sql = $this->config['sql_server'];
        
        $this->logInfo("  📋 SQL Server Configuration:");
        $this->logInfo("    Server: " . ($sql['server'] ?? 'NOT SET'));
        $this->logInfo("    Database: " . ($sql['database'] ?? 'NOT SET'));
        $this->logInfo("    Username: " . ((!empty($sql['username'])) ? $sql['username'] : 'Windows Authentication'));
        $this->logInfo("    Password: " . ((!empty($sql['password'])) ? str_repeat('*', strlen($sql['password'])) : 'NOT SET'));
        $this->logInfo("    Port: " . ($sql['port'] ?? '1433'));
        $this->logInfo("    Connection Method: " . ($sql['connection_method'] ?? 'com'));
        
        // Validate required fields
        if (empty($sql['server'])) {
            $this->logError("  ❌ Server name is required");
        }
        if (empty($sql['database'])) {
            $this->logError("  ❌ Database name is required");
        }
        
        $this->logInfo("");
    }
    
    private function testConnection() {
        $this->logInfo("🔌 Connection Test:");
        
        if (!class_exists('COM')) {
            $this->logError("  ❌ Cannot test connection - COM extension not available");
            return;
        }
        
        if (!isset($this->config['sql_server'])) {
            $this->logError("  ❌ Cannot test connection - SQL Server config missing");
            return;
        }
        
        $sql = $this->config['sql_server'];
        
        try {
            $this->logInfo("  🚀 Creating COM Object...");
            $conn = new COM("ADODB.Connection");
            $this->logInfo("  ✅ COM Object created successfully");
            
            // Build connection string
            $connectionString = "Provider=SQLOLEDB;Server={$sql['server']};Database={$sql['database']};";
            
            if (!empty($sql['username'])) {
                $connectionString .= "UID={$sql['username']};PWD={$sql['password']};";
                $this->logInfo("  🔑 Using SQL Server Authentication");
            } else {
                $connectionString .= "Integrated Security=SSPI;";
                $this->logInfo("  🔑 Using Windows Authentication");
            }
            
            $this->logInfo("  📡 Connection String: " . str_replace($sql['password'] ?? '', '***', $connectionString));
            
            $this->logInfo("  🔗 Attempting to connect...");
            $startTime = microtime(true);
            
            $conn->Open($connectionString);
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logInfo("  🎉 Connection SUCCESS! Duration: {$duration}ms");
            
            // Test a simple query
            $this->logInfo("  📝 Testing simple query...");
            $rs = $conn->Execute("SELECT @@VERSION as version, @@SERVERNAME as server_name, DB_NAME() as database_name");
            
            if (!$rs->EOF) {
                $version = $rs->Fields("version")->Value;
                $serverName = $rs->Fields("server_name")->Value;
                $dbName = $rs->Fields("database_name")->Value;
                
                $this->logInfo("  📊 Query Results:");
                $this->logInfo("    Server Name: $serverName");
                $this->logInfo("    Database: $dbName");
                $this->logInfo("    SQL Server Version: " . substr($version, 0, 100) . "...");
            }
            
            $rs->Close();
            $conn->Close();
            
            $this->logInfo("  ✅ Connection test completed successfully");
            
        } catch (Exception $e) {
            $this->logError("  ❌ Connection FAILED: " . $e->getMessage());
            $this->logError("  🔍 Possible causes:");
            $this->logError("    - SQL Server is not running");
            $this->logError("    - Server name is incorrect");
            $this->logError("    - Database name is incorrect");
            $this->logError("    - Authentication credentials are wrong");
            $this->logError("    - TCP/IP protocol is disabled in SQL Server");
            $this->logError("    - Windows Firewall is blocking the connection");
            $this->logError("    - SQL Server Browser service is not running (for named instances)");
        }
        
        $this->logInfo("");
    }
    
    public function getLogContent() {
        if (file_exists($this->logFile)) {
            return file_get_contents($this->logFile);
        }
        return "Log file not found.";
    }
}

// Check if running from web or command line
$isWeb = isset($_SERVER['REQUEST_METHOD']);

if ($isWeb) {
    // Web interface
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <title>SQL Server Connection Debugger</title>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600&display=swap');
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Vazirmatn', sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh; padding: 20px;
            }
            .container {
                max-width: 1000px; margin: 0 auto;
                background: rgba(255, 255, 255, 0.15);
                backdrop-filter: blur(20px); border-radius: 20px; padding: 30px;
                box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            }
            h1 { color: white; text-align: center; margin-bottom: 30px; }
            .log-container {
                background: rgba(0, 0, 0, 0.8); padding: 20px; border-radius: 15px;
                font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.6;
                max-height: 70vh; overflow-y: auto; color: #00ff00;
            }
            .error { color: #ff6b6b; }
            .warning { color: #ffd93d; }
            .info { color: #74c0fc; }
            .btn {
                background: linear-gradient(45deg, #4CAF50, #45a049);
                color: white; padding: 12px 25px; border: none; border-radius: 10px;
                margin: 10px 5px; cursor: pointer; text-decoration: none; display: inline-block;
            }
            .btn:hover { transform: translateY(-2px); }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔍 SQL Server Connection Debugger</h1>
            
            <?php
            $debugger = new SQLServerDebugger();
            $logFile = $debugger->runDiagnostic();
            $logContent = $debugger->getLogContent();
            ?>
            
            <div class="log-container">
                <?php
                $lines = explode("\n", $logContent);
                foreach ($lines as $line) {
                    $class = '';
                    if (strpos($line, '[ERROR]') !== false) $class = 'error';
                    elseif (strpos($line, '[WARNING]') !== false) $class = 'warning';
                    elseif (strpos($line, '[INFO]') !== false) $class = 'info';
                    
                    echo "<div class='$class'>" . htmlspecialchars($line) . "</div>";
                }
                ?>
            </div>
            
            <div style="text-align: center; margin-top: 20px;">
                <button class="btn" onclick="location.reload()">🔄 Run Again</button>
                <a href="windows-simple.php" class="btn">🏠 Back to Dashboard</a>
                <a href="settings.php" class="btn">⚙️ Edit Settings</a>
            </div>
            
            <p style="color: white; text-align: center; margin-top: 15px;">
                📁 Full log saved to: <?php echo htmlspecialchars($logFile); ?>
            </p>
        </div>
        
        <script>
            // Auto-scroll to bottom
            document.querySelector('.log-container').scrollTop = document.querySelector('.log-container').scrollHeight;
        </script>
    </body>
    </html>
    <?php
} else {
    // Command line interface
    $debugger = new SQLServerDebugger();
    $logFile = $debugger->runDiagnostic();
    echo "\n📁 Full log saved to: $logFile\n";
}
?>
