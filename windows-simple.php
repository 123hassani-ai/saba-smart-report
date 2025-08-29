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
    private $sqlConn = null;
    
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
            case 'sqlserver':
                $this->handleSQLServerAPI();
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
                
                /* Tab styles */
                .tabs {
                    display: flex;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
                    margin-bottom: 20px;
                }
                
                .tab {
                    padding: 10px 20px;
                    color: rgba(255, 255, 255, 0.7);
                    cursor: pointer;
                    transition: all 0.3s ease;
                    border-bottom: 2px solid transparent;
                    background: rgba(255, 255, 255, 0.05);
                    margin-left: 5px;
                    border-radius: 8px 8px 0 0;
                }
                
                .tab:hover {
                    color: white;
                    background-color: rgba(255, 255, 255, 0.1);
                }
                
                .tab.active {
                    color: white !important;
                    border-bottom: 2px solid #4CAF50 !important;
                    background-color: rgba(76, 175, 80, 0.2) !important;
                }
                
                /* Tab content styles - Enhanced */
                .tab-content {
                    display: none !important;
                    opacity: 0;
                    visibility: hidden;
                    position: relative;
                }
                
                .tab-content.active {
                    display: block !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                    animation: fadeIn 0.3s ease-in;
                }
                
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                
                /* SQL Server specific styles */
                .sql-server-container {
                    background: rgba(255, 255, 255, 0.15);
                    border-radius: 10px;
                    padding: 20px;
                    margin-top: 20px;
                    backdrop-filter: blur(5px);
                    border: 1px solid rgba(255, 255, 255, 0.18);
                }
                
                .table-controls {
                    display: flex;
                    gap: 10px;
                    margin-bottom: 15px;
                    flex-wrap: wrap;
                }
                
                .table-select, .table-search {
                    padding: 8px 15px;
                    border-radius: 8px;
                    border: 1px solid rgba(255, 255, 255, 0.2);
                    background-color: rgba(255, 255, 255, 0.1);
                    color: white;
                    font-family: 'Vazirmatn', sans-serif;
                    min-width: 180px;
                }
                
                .table-search {
                    flex-grow: 1;
                    min-width: 200px;
                }
                
                .table-select option {
                    background-color: #333;
                    color: white;
                }
                
                .table-search::placeholder {
                    color: rgba(255, 255, 255, 0.6);
                }
                
                .btn-mini {
                    padding: 8px 12px;
                    font-size: 0.9rem;
                }
                
                .pagination {
                    display: flex;
                    justify-content: center;
                    gap: 10px;
                    margin: 15px 0;
                }
                
                /* Sync options */
                .sync-options {
                    margin-top: 20px;
                    padding: 15px;
                    border-radius: 10px;
                    background-color: rgba(255, 255, 255, 0.05);
                }
                
                .sync-options h4 {
                    color: white;
                    margin-top: 0;
                    margin-bottom: 10px;
                }
                
                .sync-actions {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 10px;
                    align-items: center;
                    margin-bottom: 10px;
                }
                
                .sync-actions label {
                    display: flex;
                    align-items: center;
                    gap: 5px;
                    color: rgba(255, 255, 255, 0.9);
                }
                
                .sync-interval {
                    padding: 5px 10px;
                    border-radius: 5px;
                    border: 1px solid rgba(255, 255, 255, 0.2);
                    background-color: rgba(255, 255, 255, 0.1);
                    color: white;
                    font-family: 'Vazirmatn', sans-serif;
                }
                
                .sync-status {
                    font-size: 0.9rem;
                    color: rgba(255, 255, 255, 0.7);
                    margin-top: 10px;
                }
                
                /* Responsive design */
                @media (max-width: 768px) {
                    .table-controls {
                        flex-direction: column;
                    }
                    
                    .table-select, .table-search {
                        width: 100%;
                        min-width: auto;
                    }
                    
                    .sync-actions {
                        flex-direction: column;
                        align-items: flex-start;
                    }
                    
                    .tabs {
                        flex-wrap: wrap;
                    }
                    
                    .tab {
                        margin-bottom: 5px;
                    }
                }
            </style>
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
                
                /* Tab styles */
                .tabs {
                    display: flex;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
                    margin-bottom: 20px;
                }
                
                .tab {
                    padding: 10px 20px;
                    color: rgba(255, 255, 255, 0.7);
                    cursor: pointer;
                    transition: all 0.3s ease;
                    border-bottom: 2px solid transparent;
                    background: rgba(255, 255, 255, 0.05);
                    margin-left: 5px;
                    border-radius: 8px 8px 0 0;
                }
                
                .tab:hover {
                    color: white;
                    background-color: rgba(255, 255, 255, 0.1);
                }
                
                .tab.active {
                    color: white !important;
                    border-bottom: 2px solid #4CAF50 !important;
                    background-color: rgba(76, 175, 80, 0.2) !important;
                }
                
                .tab-content {
                    display: none !important;
                }
                
                .tab-content.active {
                    display: block !important;
                }
                
                /* Table controls */
                .table-controls {
                    display: flex;
                    gap: 10px;
                    margin-bottom: 15px;
                }
                
                .table-select {
                    padding: 8px 15px;
                    border-radius: 8px;
                    border: 1px solid rgba(255, 255, 255, 0.2);
                    background-color: rgba(255, 255, 255, 0.1);
                    color: white;
                    font-family: 'Vazirmatn', sans-serif;
                    min-width: 180px;
                }
                
                .table-search {
                    padding: 8px 15px;
                    border-radius: 8px;
                    border: 1px solid rgba(255, 255, 255, 0.2);
                    background-color: rgba(255, 255, 255, 0.1);
                    color: white;
                    font-family: 'Vazirmatn', sans-serif;
                    flex-grow: 1;
                }
                
                .btn-mini {
                    padding: 8px 12px;
                    font-size: 0.9rem;
                }
                
                .pagination {
                    display: flex;
                    justify-content: center;
                    gap: 10px;
                    margin: 15px 0;
                }
                
                /* Sync options */
                .sync-options {
                    margin-top: 20px;
                    padding: 15px;
                    border-radius: 10px;
                    background-color: rgba(255, 255, 255, 0.05);
                }
                
                .sync-options h4 {
                    color: white;
                    margin-top: 0;
                    margin-bottom: 10px;
                }
                
                .sync-actions {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 10px;
                    align-items: center;
                    margin-bottom: 10px;
                }
                
                .sync-actions label {
                    display: flex;
                    align-items: center;
                    gap: 5px;
                    color: rgba(255, 255, 255, 0.9);
                }
                
                .sync-interval {
                    padding: 5px 10px;
                    border-radius: 5px;
                    border: 1px solid rgba(255, 255, 255, 0.2);
                    background-color: rgba(255, 255, 255, 0.1);
                    color: white;
                    font-family: 'Vazirmatn', sans-serif;
                }
                
                .sync-status {
                    font-size: 0.9rem;
                    color: rgba(255, 255, 255, 0.7);
                    margin-top: 10px;
                }
            </style>
        </head>
        <body>
                        <div class="container">
                <div class="header">
                    <h1>سیستم گزارش‌گیری سبا</h1>
                    <p>نسخه ساده و بهینه شده برای ویندوز</p>
                </div>
                
                <?php if (!empty($this->errors)): ?>
                <div class="error-list">
                    <ul>
                        <?php foreach ($this->errors as $error): ?>
                        <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <div class="tabs">
                    <div class="tab active" data-tab="dashboard">داشبورد</div>
                    <div class="tab" data-tab="sql-server">پایگاه داده SQL Server</div>
                    <div class="tab" data-tab="cloud">سرور ابری</div>
                </div>
                
                <div id="dashboard-tab" class="tab-content active">
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
                
                <div id="sql-server-tab" class="tab-content">
                    <div class="card status-good">
                        <div class="card-title">🗄️ مدیریت جداول SQL Server</div>
                        <div class="card-content">
                            <div class="table-controls">
                                <select id="table-selector" class="table-select">
                                    <option value="">انتخاب جدول...</option>
                                </select>
                                <input type="text" id="table-search" placeholder="جستجو در داده‌ها..." class="table-search">
                                <button id="refresh-tables" class="btn btn-mini">🔄</button>
                                <button id="export-table" class="btn btn-mini">📥</button>
                            </div>
                            <div id="sql-server-container" class="sql-server-container">
                                <!-- محتوای جدول اینجا قرار می‌گیرد -->
                            </div>
                            <div id="table-pagination" class="pagination"></div>
                            <div id="sync-options" class="sync-options">
                                <h4>گزینه‌های همگام‌سازی</h4>
                                <div class="sync-actions">
                                    <button id="sync-selected" class="btn btn-mini">همگام‌سازی انتخاب شده‌ها</button>
                                    <button id="sync-all" class="btn btn-mini">همگام‌سازی همه</button>
                                    <label><input type="checkbox" id="auto-sync"> همگام‌سازی خودکار</label>
                                    <select id="sync-interval" class="sync-interval">
                                        <option value="300">هر 5 دقیقه</option>
                                        <option value="600">هر 10 دقیقه</option>
                                        <option value="1800">هر 30 دقیقه</option>
                                        <option value="3600">هر ساعت</option>
                                    </select>
                                </div>
                                <div id="sync-status" class="sync-status"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="cloud-tab" class="tab-content">
                    <div class="card">
                        <div class="card-title">☁️ سرور ابری</div>
                        <div class="card-content">
                            <p>این بخش برای اتصال به سرور ابری و مدیریت همگام‌سازی استفاده می‌شود.</p>
                            <div class="cloud-status">
                                <h4>وضعیت اتصال</h4>
                                <div id="cloud-connection-status">درحال بررسی...</div>
                            </div>
                            <div class="cloud-sync">
                                <h4>آخرین همگام‌سازی</h4>
                                <div id="last-sync-time">-</div>
                                <button id="manual-sync" class="btn">همگام‌سازی دستی</button>
                            </div>
                            <div class="cloud-logs">
                                <h4>گزارش‌های اخیر</h4>
                                <div id="sync-logs" class="sync-logs-container">
                                    <div class="no-logs">هنوز گزارشی ثبت نشده است</div>
                                </div>
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
                // JavaScript compatible with Windows 7 / IE8+ and older browsers
                function addEvent(element, event, handler) {
                    if (element.addEventListener) {
                        element.addEventListener(event, handler, false);
                    } else if (element.attachEvent) {
                        element.attachEvent('on' + event, handler);
                    }
                }
                
                function addClass(element, className) {
                    if (element.className.indexOf(className) === -1) {
                        element.className += (element.className ? ' ' : '') + className;
                    }
                }
                
                function removeClass(element, className) {
                    element.className = element.className.replace(new RegExp('(?:^|\\s)' + className + '(?!\\S)', 'g'), '');
                }
                
                function hasClass(element, className) {
                    return element.className.indexOf(className) !== -1;
                }
                
                function domReady(callback) {
                    if (document.readyState === 'complete' || document.readyState === 'interactive') {
                        callback();
                    } else if (document.addEventListener) {
                        document.addEventListener('DOMContentLoaded', callback);
                    } else {
                        document.attachEvent('onreadystatechange', function() {
                            if (document.readyState === 'complete') {
                                callback();
                            }
                        });
                    }
                }
                
                domReady(function() {
                    console.log('DOM loaded, initializing tabs (Windows 7 compatible)...');
                    
                    // Get all tabs and tab contents using older method
                    var tabs = [];
                    var tabContents = [];
                    var allElements = document.getElementsByTagName('*');
                    
                    // Find tabs manually (compatible with old browsers)
                    for (var i = 0; i < allElements.length; i++) {
                        if (allElements[i].className && allElements[i].className.indexOf('tab ') !== -1) {
                            tabs.push(allElements[i]);
                        }
                        if (allElements[i].className && allElements[i].className.indexOf('tab-content') !== -1) {
                            tabContents.push(allElements[i]);
                        }
                    }
                    
                    console.log('Found ' + tabs.length + ' tabs and ' + tabContents.length + ' tab contents');
                    
                    // Debug: log all tab content elements
                    for (var j = 0; j < tabContents.length; j++) {
                        console.log('Tab content ' + j + ':', tabContents[j].id, 'Classes:', tabContents[j].className);
                    }
                    
                    // Add click handlers to tabs
                    for (var k = 0; k < tabs.length; k++) {
                        (function(tab) {
                            addEvent(tab, 'click', function() {
                                var tabId = tab.getAttribute('data-tab');
                                console.log('Tab clicked:', tabId);
                                
                                // Remove active class from all tabs
                                for (var m = 0; m < tabs.length; m++) {
                                    removeClass(tabs[m], 'active');
                                }
                                
                                // Add active class to clicked tab
                                addClass(tab, 'active');
                                
                                // Hide all tab contents and show selected one
                                for (var n = 0; n < tabContents.length; n++) {
                                    removeClass(tabContents[n], 'active');
                                    console.log('Removed active from:', tabContents[n].id);
                                    
                                    if (tabContents[n].id === tabId + '-tab') {
                                        addClass(tabContents[n], 'active');
                                        console.log('Activated tab content:', tabContents[n].id);
                                        console.log('New classes:', tabContents[n].className);
                                    }
                                }
                            });
                        })(tabs[k]);
                    }
                    
                    // Initialize table selector for SQL Server tab
                    var tableSelector = document.getElementById('table-selector');
                    if (tableSelector) {
                        console.log('Table selector found, loading tables...');
                        loadSQLServerTables();
                    }
                    
                    // Table search functionality - compatible version
                    var tableSearch = document.getElementById('table-search');
                    if (tableSearch) {
                        addEvent(tableSearch, 'keyup', function() {
                            filterTableData(this.value);
                        });
                    }
                    
                    // Refresh tables button - compatible version
                    var refreshBtn = document.getElementById('refresh-tables');
                    if (refreshBtn) {
                        addEvent(refreshBtn, 'click', function() {
                            loadSQLServerTables();
                        });
                    }
                    
                    // Export table button - compatible version
                    var exportBtn = document.getElementById('export-table');
                    if (exportBtn) {
                        addEvent(exportBtn, 'click', function() {
                            exportTableToCSV();
                        });
                    }
                    
                    // Sync buttons - compatible version
                    var syncSelected = document.getElementById('sync-selected');
                    if (syncSelected) {
                        addEvent(syncSelected, 'click', function() {
                            alert('همگام‌سازی انتخاب شده‌ها در حال توسعه است');
                        });
                    }
                    
                    var syncAll = document.getElementById('sync-all');
                    if (syncAll) {
                        addEvent(syncAll, 'click', function() {
                            alert('همگام‌سازی همه در حال توسعه است');
                        });
                    }
                });
                
                // XMLHttpRequest compatible function for Windows 7
                function createXHR() {
                    if (window.XMLHttpRequest) {
                        return new XMLHttpRequest();
                    } else if (window.ActiveXObject) {
                        try {
                            return new ActiveXObject('Msxml2.XMLHTTP');
                        } catch (e) {
                            try {
                                return new ActiveXObject('Microsoft.XMLHTTP');
                            } catch (e) {}
                        }
                    }
                    return null;
                }
                
                // Load SQL Server tables - Windows 7 compatible
                function loadSQLServerTables() {
                    var tableSelector = document.getElementById('table-selector');
                    var container = document.getElementById('sql-server-container');
                    
                    if (!tableSelector || !container) return;
                    
                    container.innerHTML = '<div style="text-align: center; padding: 20px; color: rgba(255,255,255,0.7);">در حال بارگذاری جداول...</div>';
                    
                    var xhr = createXHR();
                    if (!xhr) {
                        container.innerHTML = '<div style="text-align: center; padding: 20px; color: #ffcdd2;">خطا: مرورگر از AJAX پشتیبانی نمی‌کند</div>';
                        return;
                    }
                    
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4) {
                            if (xhr.status === 200) {
                                try {
                                    var data = JSON.parse(xhr.responseText);
                                    console.log('Tables response:', data);
                                    
                                    if (data.success && data.tables) {
                                        tableSelector.innerHTML = '<option value="">انتخاب جدول...</option>';
                                        
                                        for (var i = 0; i < data.tables.length; i++) {
                                            var option = document.createElement('option');
                                            option.value = data.tables[i];
                                            option.text = data.tables[i];
                                            tableSelector.appendChild(option);
                                        }
                                        
                                        container.innerHTML = '<div style="text-align: center; padding: 20px; color: rgba(255,255,255,0.7);">لطفاً یک جدول را انتخاب کنید</div>';
                                        
                                        // Add table selector event - compatible version
                                        addEvent(tableSelector, 'change', function() {
                                            if (this.value) {
                                                loadTableData(this.value);
                                            }
                                        });
                                    } else {
                                        container.innerHTML = '<div style="text-align: center; padding: 20px; color: #ffcdd2;">خطا: ' + (data.error || 'نتوانستیم جداول را بارگذاری کنیم') + '</div>';
                                    }
                                } catch (e) {
                                    console.error('Error parsing JSON:', e);
                                    container.innerHTML = '<div style="text-align: center; padding: 20px; color: #ffcdd2;">خطا در تجزیه پاسخ سرور</div>';
                                }
                            } else {
                                container.innerHTML = '<div style="text-align: center; padding: 20px; color: #ffcdd2;">خطا در اتصال به پایگاه داده</div>';
                            }
                        }
                    };
                    
                    xhr.open('GET', 'windows-simple.php?action=sqlserver&query=tables', true);
                    xhr.send();
                }
                
                // Load table data - Windows 7 compatible
                function loadTableData(tableName) {
                    var container = document.getElementById('sql-server-container');
                    if (!container) return;
                    
                    container.innerHTML = '<div style="text-align: center; padding: 20px; color: rgba(255,255,255,0.7);">در حال بارگذاری داده‌های جدول...</div>';
                    
                    var xhr = createXHR();
                    if (!xhr) {
                        container.innerHTML = '<div style="text-align: center; padding: 20px; color: #ffcdd2;">خطا: مرورگر از AJAX پشتیبانی نمی‌کند</div>';
                        return;
                    }
                    
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4) {
                            if (xhr.status === 200) {
                                try {
                                    var data = JSON.parse(xhr.responseText);
                                    console.log('Table data response:', data);
                                    
                                    if (data.success) {
                                        displayTableData(data.columns || [], data.data || []);
                                    } else {
                                        container.innerHTML = '<div style="text-align: center; padding: 20px; color: #ffcdd2;">خطا: ' + (data.error || 'نتوانستیم داده‌های جدول را بارگذاری کنیم') + '</div>';
                                    }
                                } catch (e) {
                                    console.error('Error parsing JSON:', e);
                                    container.innerHTML = '<div style="text-align: center; padding: 20px; color: #ffcdd2;">خطا در تجزیه پاسخ سرور</div>';
                                }
                            } else {
                                container.innerHTML = '<div style="text-align: center; padding: 20px; color: #ffcdd2;">خطا در اتصال به پایگاه داده</div>';
                            }
                        }
                    };
                    
                    var url = 'windows-simple.php?action=sqlserver&query=tableData&table=' + encodeURIComponent(tableName);
                    xhr.open('GET', url, true);
                    xhr.send();
                }
                
                // Display table data - Windows 7 compatible
                function displayTableData(columns, data) {
                    var container = document.getElementById('sql-server-container');
                    if (!container) return;
                    
                    if (columns.length === 0) {
                        container.innerHTML = '<div style="text-align: center; padding: 20px; color: rgba(255,255,255,0.7);">جدول خالی است یا ستون‌ها یافت نشد</div>';
                        return;
                    }
                    
                    var html = '<div class="table-container" style="padding: 20px; max-height: 70vh; overflow: auto;">';
                    html += '<div class="table-controls" style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">';
                    html += '<input type="text" id="tableSearch" placeholder="جستجو..." style="padding: 8px 12px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: #fff; border-radius: 4px; margin-bottom: 10px;">';
                    html += '<button type="button" onclick="exportTableToCSV()" style="padding: 8px 16px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; margin-bottom: 10px;">خروجی CSV</button>';
                    html += '</div>';
                    
                    html += '<table class="sql-table" style="width: 100%; border-collapse: collapse; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);" id="dataTable">';
                    html += '<thead style="background: rgba(255,255,255,0.1);"><tr>';
                    
                    // Add column headers - Windows 7 compatible
                    for (var i = 0; i < columns.length; i++) {
                        html += '<th style="padding: 12px; text-align: right; border-bottom: 1px solid rgba(255,255,255,0.2); font-weight: bold;">' + columns[i] + '</th>';
                    }
                    html += '</tr></thead><tbody id="tableBody">';
                    
                    // Add data rows - Windows 7 compatible
                    for (var i = 0; i < data.length; i++) {
                        html += '<tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">';
                        for (var j = 0; j < columns.length; j++) {
                            var value = data[i][columns[j]];
                            if (value === null || value === undefined) {
                                value = '';
                            }
                            html += '<td style="padding: 12px; text-align: right; border-bottom: 1px solid rgba(255,255,255,0.1);">' + String(value) + '</td>';
                        }
                        html += '</tr>';
                    }
                    
                    html += '</tbody></table>';
                    html += '<div class="table-info" style="margin-top: 15px; text-align: center; color: rgba(255,255,255,0.7);">';
                    html += 'تعداد کل رکوردها: ' + data.length;
                    html += '</div></div>';
                    
                    container.innerHTML = html;
                    
                    // Add search functionality - Windows 7 compatible
                    var searchInput = document.getElementById('tableSearch');
                    if (searchInput) {
                        addEvent(searchInput, 'keyup', function() {
                            filterTableData(this.value);
                        });
                    }
                }
                
                // Filter table data - Windows 7 compatible
                function filterTableData(query) {
                    console.log('Filtering with query:', query);
                    var table = document.getElementById('dataTable');
                    var tbody = document.getElementById('tableBody');
                    if (!table || !tbody) return;
                    
                    var rows = tbody.getElementsByTagName('tr');
                    var visibleCount = 0;
                    
                    for (var i = 0; i < rows.length; i++) {
                        var row = rows[i];
                        var cells = row.getElementsByTagName('td');
                        var match = false;
                        
                        if (!query || query.length === 0) {
                            match = true;
                        } else {
                            for (var j = 0; j < cells.length; j++) {
                                var cellText = cells[j].innerHTML || cells[j].textContent || cells[j].innerText;
                                if (cellText && cellText.toLowerCase().indexOf(query.toLowerCase()) > -1) {
                                    match = true;
                                    break;
                                }
                            }
                        }
                        
                        if (match) {
                            row.style.display = '';
                            visibleCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    }
                    
                    // Update info
                    var info = document.getElementsByClassName('table-info')[0];
                    if (info) {
                        if (query && query.length > 0) {
                            info.innerHTML = 'نمایش ' + visibleCount + ' رکورد از کل رکوردها';
                        } else {
                            info.innerHTML = 'تعداد کل رکوردها: ' + rows.length;
                        }
                    }
                }
                
                // Export to CSV - Windows 7 compatible
                function exportTableToCSV() {
                    var table = document.getElementById('dataTable');
                    if (!table) {
                        alert('جدولی برای صدور یافت نشد');
                        return;
                    }
                    
                    var csv = [];
                    var rows = table.getElementsByTagName('tr');
                    
                    for (var i = 0; i < rows.length; i++) {
                        var row = rows[i];
                        var cols = row.getElementsByTagName('td');
                        if (cols.length === 0) {
                            cols = row.getElementsByTagName('th');
                        }
                        
                        var csvRow = [];
                        for (var j = 0; j < cols.length; j++) {
                            var cellText = cols[j].innerHTML || cols[j].textContent || cols[j].innerText;
                            cellText = cellText.replace(/"/g, '""'); // Escape quotes
                            csvRow.push('"' + cellText + '"');
                        }
                        csv.push(csvRow.join(','));
                    }
                    
                    var csvContent = csv.join('\n');
                    
                    // For Windows 7 IE compatibility
                    if (window.navigator && window.navigator.msSaveOrOpenBlob) {
                        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                        navigator.msSaveOrOpenBlob(blob, 'table-export.csv');
                    } else {
                        // Fallback for other browsers
                        var hiddenElement = document.createElement('a');
                        hiddenElement.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvContent);
                        hiddenElement.target = '_blank';
                        hiddenElement.download = 'table-export.csv';
                        hiddenElement.click();
                    }
                }
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
    
    /**
     * SQL Server API برای جدول های دینامیک
     */
    private function handleSQLServerAPI() {
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => false,
            'error' => null,
            'data' => null
        ];
        
        // بررسی تنظیمات SQL Server
        if (!isset($this->config['sql_server'])) {
            $response['error'] = 'SQL Server configuration not found';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            return;
        }
        
        try {
            // اتصال به SQL Server
            $this->connectToSQLServer();
            
            if (!$this->sqlConn) {
                throw new Exception('Could not establish SQL Server connection');
            }
            
            $query = $_GET['query'] ?? '';
            $table = $_GET['table'] ?? '';
            
            switch ($query) {
                case 'tables':
                    $response['tables'] = $this->getTables();
                    $response['success'] = true;
                    break;
                    
                case 'tableData':
                    if (empty($table)) {
                        throw new Exception('Table name is required');
                    }
                    
                    $response['columns'] = $this->getTableColumns($table);
                    $response['data'] = $this->getTableData($table);
                    $response['success'] = true;
                    break;
                    
                default:
                    throw new Exception('Invalid query parameter');
            }
            
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * اتصال به SQL Server
     */
    private function connectToSQLServer() {
        if ($this->sqlConn) {
            return; // Already connected
        }
        
        $sql = $this->config['sql_server'];
        
        // استفاده از COM Extension اگر در دسترس باشد
        if (class_exists('COM')) {
            try {
                $conn = new COM('ADODB.Connection');
                $connectionString = 'Provider=SQLOLEDB;Server=' . $sql['server'] . ';Database=' . $sql['database'] . ';';
                
                if (!empty($sql['username'])) {
                    $connectionString .= 'User ID=' . $sql['username'] . ';Password=' . $sql['password'] . ';';
                } else {
                    $connectionString .= 'Integrated Security=SSPI;';
                }
                
                $conn->Open($connectionString);
                $this->sqlConn = $conn;
                return;
            } catch (Exception $e) {
                // Fall back to other methods
            }
        }
        
        // استفاده از SQLSRV Extension اگر در دسترس باشد
        if (extension_loaded('sqlsrv')) {
            try {
                $serverName = $sql['server'];
                $connectionInfo = [
                    "Database" => $sql['database']
                ];
                
                if (!empty($sql['username'])) {
                    $connectionInfo["UID"] = $sql['username'];
                    $connectionInfo["PWD"] = $sql['password'];
                }
                
                $conn = sqlsrv_connect($serverName, $connectionInfo);
                if ($conn) {
                    $this->sqlConn = $conn;
                    return;
                }
            } catch (Exception $e) {
                // Continue to other methods
            }
        }
        
        // استفاده از PDO اگر در دسترس باشد
        if (class_exists('PDO')) {
            try {
                $port = $sql['port'] ?? '1433';
                $dsn = "sqlsrv:Server={$sql['server']},{$port};Database={$sql['database']}";
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ];
                
                if (!empty($sql['username'])) {
                    $this->sqlConn = new PDO($dsn, $sql['username'], $sql['password'], $options);
                } else {
                    $this->sqlConn = new PDO($dsn, null, null, $options);
                }
                return;
            } catch (Exception $e) {
                // Throw exception as this is our last attempt
                throw new Exception('SQL Server connection failed: ' . $e->getMessage());
            }
        }
        
        throw new Exception('No compatible SQL Server driver available');
    }
    
    /**
     * دریافت لیست جداول
     */
    private function getTables() {
        $tables = [];
        
        if ($this->sqlConn instanceof COM) {
            $rs = $this->sqlConn->Execute("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME");
            
            if (!$rs->EOF) {
                while (!$rs->EOF) {
                    $tables[] = $rs->Fields("TABLE_NAME")->Value;
                    $rs->MoveNext();
                }
            }
        } else if (function_exists('sqlsrv_query') && !($this->sqlConn instanceof PDO)) {
            $result = sqlsrv_query($this->sqlConn, "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME");
            
            while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
                $tables[] = $row['TABLE_NAME'];
            }
        } else {
            $stmt = $this->sqlConn->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME");
            while ($row = $stmt->fetch()) {
                $tables[] = $row['TABLE_NAME'];
            }
        }
        
        return $tables;
    }
    
    /**
     * دریافت ستون‌های جدول
     */
    private function getTableColumns($table) {
        $columns = [];
        $table = $this->sanitizeTableName($table);
        
        if ($this->sqlConn instanceof COM) {
            $rs = $this->sqlConn->Execute("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table' ORDER BY ORDINAL_POSITION");
            
            if (!$rs->EOF) {
                while (!$rs->EOF) {
                    $columns[] = $rs->Fields("COLUMN_NAME")->Value;
                    $rs->MoveNext();
                }
            }
        } else if (function_exists('sqlsrv_query') && !($this->sqlConn instanceof PDO)) {
            $result = sqlsrv_query($this->sqlConn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table' ORDER BY ORDINAL_POSITION");
            
            while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
                $columns[] = $row['COLUMN_NAME'];
            }
        } else {
            $stmt = $this->sqlConn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table' ORDER BY ORDINAL_POSITION");
            while ($row = $stmt->fetch()) {
                $columns[] = $row['COLUMN_NAME'];
            }
        }
        
        return $columns;
    }
    
    /**
     * دریافت داده‌های جدول
     */
    private function getTableData($table) {
        $data = [];
        $table = $this->sanitizeTableName($table);
        $limit = 1000; // محدودیت تعداد رکوردها برای امنیت
        
        if ($this->sqlConn instanceof COM) {
            $rs = $this->sqlConn->Execute("SELECT TOP $limit * FROM [$table]");
            
            if (!$rs->EOF) {
                $fields = [];
                for ($i = 0; $i < $rs->Fields->Count; $i++) {
                    $fields[] = $rs->Fields($i)->Name;
                }
                
                while (!$rs->EOF) {
                    $row = [];
                    foreach ($fields as $field) {
                        $value = $rs->Fields($field)->Value;
                        $row[$field] = $value === null ? null : (string)$value;
                    }
                    $data[] = $row;
                    $rs->MoveNext();
                }
            }
        } else if (function_exists('sqlsrv_query') && !($this->sqlConn instanceof PDO)) {
            $result = sqlsrv_query($this->sqlConn, "SELECT TOP $limit * FROM [$table]");
            
            while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
                // Convert all values to strings
                foreach ($row as $key => $value) {
                    if ($value instanceof DateTime) {
                        $row[$key] = $value->format('Y-m-d H:i:s');
                    } else if ($value === null) {
                        $row[$key] = null;
                    } else {
                        $row[$key] = (string)$value;
                    }
                }
                $data[] = $row;
            }
        } else {
            $stmt = $this->sqlConn->query("SELECT TOP $limit * FROM [$table]");
            while ($row = $stmt->fetch()) {
                // Convert all values to strings
                foreach ($row as $key => $value) {
                    if ($value === null) {
                        $row[$key] = null;
                    } else {
                        $row[$key] = (string)$value;
                    }
                }
                $data[] = $row;
            }
        }
        
        return $data;
    }
    
    /**
     * Sanitize table name for SQL queries
     */
    private function sanitizeTableName($table) {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    }
}

// Run the application
$app = new SimpleWindowsApp();
$app->run();
?>
