<?php
/**
 * Entry Point اصلی سیستم گزارش‌گیری سبا
 * Saba Reporting System Main Entry Point
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 300);

// Load required modules
require_once 'modules/Logger.php';
require_once 'modules/config/ConfigManager.php';
require_once 'modules/database/SQLServerConnection.php';
require_once 'modules/database/CloudConnection.php';
require_once 'modules/sync/SyncManager.php';

class SabaApp {
    
    private $logger;
    private $config;
    private $syncManager;
    
    public function __construct() {
        $this->logger = Logger::getInstance();
        $this->config = new ConfigManager();
        $this->syncManager = new SyncManager();
        
        $this->logger->info('Saba Reporting System Started');
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
            case 'ssms':
                $this->showSSMS();
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
        
        $result = $this->syncManager->syncAllTables();
        
        if ($result['success']) {
            echo "✅ همگام‌سازی با موفقیت انجام شد\n";
            echo "📊 تعداد رکوردها: " . ($result['data']['total_records'] ?? 0) . "\n";
            echo "⏱️ زمان اجرا: " . ($result['data']['execution_time'] ?? 0) . " ثانیه\n";
        } else {
            echo "❌ خطا در همگام‌سازی: " . $result['message'] . "\n";
        }
    }
    
    /**
     * تست اتصالات
     */
    private function testConnections() {
        echo "🔍 تست اتصالات...\n";
        
        // تست SQL Server
        echo "📡 تست اتصال SQL Server: ";
        $sqlConn = new SQLServerConnection();
        if ($sqlConn->testConnection()) {
            echo "✅ موفق\n";
        } else {
            echo "❌ ناموفق\n";
        }
        
        // تست Cloud Database
        echo "☁️ تست اتصال Cloud Database: ";
        $cloudConn = new CloudConnection();
        if ($cloudConn->testConnection()) {
            echo "✅ موفق\n";
        } else {
            echo "❌ ناموفق\n";
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
        echo "📖 راهنمای استفاده از سیستم گزارش‌گیری سبا\n\n";
        echo "دستورات موجود:\n";
        echo "  sync     - انجام همگام‌سازی کامل\n";
        echo "  test     - تست اتصالات\n";
        echo "  config   - نمایش تنظیمات\n";
        echo "  help     - نمایش این راهنما\n\n";
        echo "مثال‌ها:\n";
        echo "  php index.php sync\n";
        echo "  php index.php test\n";
    }
    
    /**
     * نمایش داشبورد وب
     */
    private function showDashboard() {
        $action = $_GET['action'] ?? 'dashboard';
        ?>
        <!DOCTYPE html>
        <html lang="fa" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>سیستم گزارش‌گیری سبا</title>
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/nav.css">
        </head>
        <body>
            <div class="app-container">
                <header class="app-header">
                    <h1>🚀 سیستم گزارش‌گیری سبا</h1>
                    <p>همگام‌سازی SQL Server به Cloud Database</p>
                    <nav class="app-nav">
                        <a href="?action=dashboard" class="nav-link <?php echo $action === 'dashboard' ? 'active' : ''; ?>">داشبورد</a>
                        <a href="?action=ssms" class="nav-link <?php echo $action === 'ssms' ? 'active' : ''; ?>">SQL Server مدیریت</a>
                        <a href="windows.php" class="nav-link">نسخه ویندوز</a>
                    </nav>
                </header>
                
                <main class="dashboard-container" id="dashboard">
                    <div class="loading">
                        <div class="spinner"></div>
                        <p>در حال بارگذاری داشبورد...</p>
                    </div>
                </main>
                
                <footer class="app-footer">
                    <p>نسخه 2.0 - ماژولار | تاریخ: <?php echo date('Y/m/d H:i'); ?></p>
                </footer>
            </div>
            
            <script src="assets/js/dashboard.js"></script>
            <script>
                // Initialize dashboard
                const dashboard = new Dashboard('dashboard');
            </script>
        </body>
        </html>
        <?php
    }
    
    /**
     * نمایش رابط مدیریت SQL Server
     */
    private function showSSMS() {
        // فایل ssms.html را بارگذاری می‌کنیم
        include 'ssms.html';
    }
    
    /**
     * Handle API requests
     */
    private function handleAPI() {
        header('Content-Type: application/json');
        
        $method = $_SERVER['REQUEST_METHOD'];
        $endpoint = $_GET['endpoint'] ?? '';
        
        try {
            switch ($endpoint) {
                case 'stats':
                    $stats = $this->syncManager->getStats();
                    echo json_encode(['success' => true, 'data' => $stats]);
                    break;
                    
                case 'sync':
                    if ($method === 'POST') {
                        $result = $this->syncManager->syncAllTables();
                        echo json_encode($result);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                    }
                    break;
                    
                case 'test':
                    $sqlTest = (new SQLServerConnection())->testConnection();
                    $cloudTest = (new CloudConnection())->testConnection();
                    
                    echo json_encode([
                        'success' => true,
                        'data' => [
                            'sql_server' => $sqlTest,
                            'cloud_db' => $cloudTest
                        ]
                    ]);
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

// Run the application
$app = new SabaApp();
$app->run();
?>
