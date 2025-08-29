<?php
/**
 * Windows 7 Compatible Version - Simple Dashboard
 * سازگار با PHP 7.4 و بدون نیاز به COM Extension
 */

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 1);

// Suppress PDO MySQL warning
if (!defined('PDO_MYSQL_LOADED')) {
    define('PDO_MYSQL_LOADED', true);
}

class SimpleWindowsApp {
    private $config;
    private $errors = [];
    
    public function __construct() {
        $this->loadConfig();
    }
    
    private function loadConfig() {
        if (!file_exists('config.json')) {
            $this->errors[] = "Config file not found";
            return;
        }
        
        $configData = file_get_contents('config.json');
        $this->config = json_decode($configData, true);
        
        if (!$this->config) {
            $this->errors[] = "Invalid config JSON";
        }
    }
    
    public function run() {
        $action = $_GET['action'] ?? 'dashboard';
        
        switch ($action) {
            case 'test':
                $this->testConnections();
                break;
            case 'config':
                $this->showConfig();
                break;
            default:
                $this->showDashboard();
        }
    }
    
    private function showDashboard() {
        ?>
        <!DOCTYPE html>
        <html lang="fa" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>سیستم گزارشگیری سبا - ویندوز</title>
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100;200;300;400;500;600;700;800;900&display=swap');
                
                * {
                    box-sizing: border-box;
                    margin: 0;
                    padding: 0;
                }
                
                body {
                    font-family: 'Vazirmatn', sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    padding: 20px;
                }
                
                .container {
                    max-width: 1200px;
                    margin: 0 auto;
                    background: rgba(255, 255, 255, 0.15);
                    backdrop-filter: blur(20px);
                    border-radius: 20px;
                    padding: 30px;
                    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
                    border: 1px solid rgba(255, 255, 255, 0.18);
                }
                
                .header {
                    text-align: center;
                    margin-bottom: 40px;
                }
                
                .header h1 {
                    color: white;
                    font-size: 2.5rem;
                    margin-bottom: 10px;
                    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
                }
                
                .header p {
                    color: rgba(255, 255, 255, 0.8);
                    font-size: 1.1rem;
                }
                
                .cards {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                    gap: 20px;
                    margin-bottom: 30px;
                }
                
                .card {
                    background: rgba(255, 255, 255, 0.1);
                    padding: 25px;
                    border-radius: 15px;
                    border: 1px solid rgba(255, 255, 255, 0.2);
                    transition: transform 0.3s ease;
                }
                
                .card:hover {
                    transform: translateY(-5px);
                }
                
                .card-title {
                    color: white;
                    font-weight: 600;
                    margin-bottom: 15px;
                    font-size: 1.3rem;
                }
                
                .card-content {
                    color: rgba(255, 255, 255, 0.9);
                    line-height: 1.6;
                }
                
                .status-good { border-right: 5px solid #4CAF50; }
                .status-warning { border-right: 5px solid #FF9800; }
                .status-error { border-right: 5px solid #F44336; }
                
                .actions {
                    display: flex;
                    gap: 15px;
                    justify-content: center;
                    flex-wrap: wrap;
                    margin-top: 30px;
                }
                
                .btn {
                    background: linear-gradient(45deg, #4CAF50, #45a049);
                    color: white;
                    padding: 12px 25px;
                    border: none;
                    border-radius: 10px;
                    cursor: pointer;
                    font-family: 'Vazirmatn', sans-serif;
                    font-size: 1rem;
                    text-decoration: none;
                    display: inline-block;
                    transition: all 0.3s ease;
                }
                
                .btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                }
                
                .btn-warning {
                    background: linear-gradient(45deg, #FF9800, #F57C00);
                }
                
                .btn-info {
                    background: linear-gradient(45deg, #2196F3, #1976D2);
                }
                
                .system-info {
                    background: rgba(0, 0, 0, 0.2);
                    padding: 15px;
                    border-radius: 10px;
                    margin-top: 15px;
                    font-family: 'Courier New', monospace;
                    font-size: 0.9rem;
                }
                
                .error-list {
                    background: rgba(244, 67, 54, 0.2);
                    border: 1px solid rgba(244, 67, 54, 0.5);
                    padding: 15px;
                    border-radius: 10px;
                    margin: 15px 0;
                }
                
                .error-list ul {
                    list-style: none;
                    padding-left: 0;
                }
                
                .error-list li {
                    color: #ffebee;
                    margin: 5px 0;
                }
                
                .error-list li:before {
                    content: "❌ ";
                    margin-left: 10px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🔧 سیستم گزارشگیری سبا</h1>
                    <p>نسخه سازگار با ویندوز 7 - PHP <?php echo PHP_VERSION; ?></p>
                </div>

                <?php if (!empty($this->errors)): ?>
                    <div class="error-list">
                        <h3>⚠️ مشکلات شناسایی شده:</h3>
                        <ul>
                            <?php foreach ($this->errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="cards">
                    <div class="card <?php echo $this->getSystemStatus(); ?>">
                        <div class="card-title">🖥️ وضعیت سیستم</div>
                        <div class="card-content">
                            <strong>PHP:</strong> <?php echo PHP_VERSION; ?><br>
                            <strong>OS:</strong> <?php echo PHP_OS; ?><br>
                            <strong>Server:</strong> <?php echo php_sapi_name(); ?><br>
                            
                            <div class="system-info">
                                Extensions:<br>
                                • COM: <?php echo class_exists('COM') ? '✅' : '❌'; ?><br>
                                • PDO: <?php echo class_exists('PDO') ? '✅' : '❌'; ?><br>
                                • MySQL: <?php echo $this->checkMySQLExtension(); ?><br>
                                • JSON: <?php echo function_exists('json_encode') ? '✅' : '❌'; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card <?php echo $this->getSQLServerStatus(); ?>">
                        <div class="card-title">🗄️ SQL Server</div>
                        <div class="card-content">
                            <?php if ($this->config): ?>
                                <strong>Server:</strong> <?php echo htmlspecialchars($this->config['sql_server']['server']); ?><br>
                                <strong>Database:</strong> <?php echo htmlspecialchars($this->config['sql_server']['database']); ?><br>
                                <strong>User:</strong> <?php echo htmlspecialchars($this->config['sql_server']['username']); ?><br>
                                
                                <div class="system-info">
                                    <?php if (class_exists('COM')): ?>
                                        Status: COM Extension Available ✅
                                    <?php else: ?>
                                        Status: COM Extension Missing ❌<br>
                                        <small>برای اتصال به SQL Server باید COM extension فعال باشد</small>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span style="color: #ffcdd2;">پیکربندی یافت نشد</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card <?php echo $this->getCloudStatus(); ?>">
                        <div class="card-title">☁️ Cloud Database</div>
                        <div class="card-content">
                            <?php if ($this->config && isset($this->config['cloud'])): ?>
                                <strong>Host:</strong> <?php echo htmlspecialchars($this->config['cloud']['host']); ?><br>
                                <strong>Database:</strong> <?php echo htmlspecialchars($this->config['cloud']['database']); ?><br>
                                <strong>Port:</strong> <?php echo htmlspecialchars($this->config['cloud']['port']); ?><br>
                                
                                <div class="system-info">
                                    MySQL Extension: <?php echo $this->checkMySQLExtension(); ?>
                                </div>
                            <?php else: ?>
                                <span style="color: #ffcdd2;">تنظیمات Cloud یافت نشد</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card status-good">
                        <div class="card-title">📋 اطلاعات فایل‌ها</div>
                        <div class="card-content">
                            <div class="system-info">
                                config.json: <?php echo file_exists('config.json') ? '✅' : '❌'; ?><br>
                                windows.php: <?php echo file_exists('windows.php') ? '✅' : '❌'; ?><br>
                                logs/: <?php echo is_dir('logs') ? '✅' : '❌'; ?><br>
                                modules/: <?php echo is_dir('modules') ? '✅' : '❌'; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="actions">
                    <a href="?action=test" class="btn btn-info">🧪 تست اتصالات</a>
                    <a href="settings.php" class="btn btn-warning">⚙️ ویرایش تنظیمات</a>
                    <a href="windows-diagnostic.php" class="btn">🔧 ابزار تشخیص</a>
                    <a href="COM-ACTIVATION-GUIDE.md" class="btn btn-info" target="_blank">📖 راهنمای COM</a>
                    <button class="btn" onclick="location.reload()">🔄 بروزرسانی</button>
                </div>
            </div>

            <script>
                // Auto refresh every 30 seconds
                setTimeout(() => location.reload(), 30000);
            </script>
        </body>
        </html>
        <?php
    }
    
    private function testConnections() {
        header('Content-Type: application/json; charset=utf-8');
        
        $results = [
            'system' => [
                'php_version' => PHP_VERSION,
                'os' => PHP_OS,
                'com_available' => class_exists('COM'),
                'pdo_available' => class_exists('PDO'),
                'mysql_available' => $this->checkMySQLExtension() === '✅'
            ],
            'sql_server' => [
                'configured' => isset($this->config['sql_server']),
                'com_extension' => class_exists('COM'),
                'connection_status' => 'unavailable'
            ],
            'cloud' => [
                'configured' => isset($this->config['cloud']),
                'mysql_extension' => $this->checkMySQLExtension() === '✅',
                'connection_status' => 'not_tested'
            ]
        ];
        
        // Test Cloud MySQL if possible
        if ($results['cloud']['configured'] && $results['cloud']['mysql_extension']) {
            try {
                $cloud = $this->config['cloud'];
                $dsn = "mysql:host={$cloud['host']};port={$cloud['port']};dbname={$cloud['database']};charset=utf8mb4";
                $pdo = new PDO($dsn, $cloud['username'], $cloud['password'], [
                    PDO::ATTR_TIMEOUT => 5,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                $results['cloud']['connection_status'] = 'connected';
                $pdo = null;
            } catch (Exception $e) {
                $results['cloud']['connection_status'] = 'failed';
                $results['cloud']['error'] = $e->getMessage();
            }
        }
        
        echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    private function showConfig() {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!$this->config) {
            echo json_encode(['error' => 'Config not loaded']);
            return;
        }
        
        // Mask passwords
        $safeConfig = $this->config;
        if (isset($safeConfig['sql_server']['password'])) {
            $safeConfig['sql_server']['password'] = str_repeat('*', strlen($safeConfig['sql_server']['password']));
        }
        if (isset($safeConfig['cloud']['password'])) {
            $safeConfig['cloud']['password'] = str_repeat('*', strlen($safeConfig['cloud']['password']));
        }
        
        echo json_encode($safeConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    private function getSystemStatus() {
        $phpOk = version_compare(PHP_VERSION, '7.0', '>=');
        $pdoOk = class_exists('PDO');
        
        if ($phpOk && $pdoOk) {
            return 'status-good';
        } elseif ($phpOk) {
            return 'status-warning';
        } else {
            return 'status-error';
        }
    }
    
    private function getSQLServerStatus() {
        if (!isset($this->config['sql_server'])) {
            return 'status-error';
        }
        
        if (class_exists('COM')) {
            return 'status-good';
        } else {
            return 'status-warning';
        }
    }
    
    private function getCloudStatus() {
        if (!isset($this->config['cloud'])) {
            return 'status-error';
        }
        
        if ($this->checkMySQLExtension() === '✅') {
            return 'status-good';
        } else {
            return 'status-warning';
        }
    }
    
    private function checkMySQLExtension() {
        if (class_exists('PDO')) {
            $drivers = PDO::getAvailableDrivers();
            return in_array('mysql', $drivers) ? '✅' : '❌';
        }
        return '❌';
    }
}

// Run the application
$app = new SimpleWindowsApp();
$app->run();
?>
