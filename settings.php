<?php
/**
 * Settings Editor - صفحه ویرایش تنظیمات
 */

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

class SettingsEditor {
    private $configFile = 'config.json';
    private $config = [];
    private $message = '';
    private $messageType = '';
    
    public function __construct() {
        $this->loadConfig();
        $this->handleActions();
    }
    
    private function loadConfig() {
        if (file_exists($this->configFile)) {
            $configData = file_get_contents($this->configFile);
            $this->config = json_decode($configData, true) ?: [];
        } else {
            $this->config = $this->getDefaultConfig();
        }
    }
    
    private function getDefaultConfig() {
        return [
            'sql_server' => [
                'server' => 'localhost\\SQLEXPRESS',
                'database' => 'SabaDB',
                'username' => 'sa',
                'password' => '',
                'port' => '1433',
                'connection_method' => 'com'
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
    
    private function handleActions() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            switch ($action) {
                case 'save':
                    $this->saveConfig();
                    break;
                case 'reset':
                    $this->resetConfig();
                    break;
                case 'test_sql':
                    $this->testSQLConnection();
                    break;
                case 'test_cloud':
                    $this->testCloudConnection();
                    break;
            }
        }
    }
    
    private function saveConfig() {
        try {
            // SQL Server settings
            $this->config['sql_server'] = [
                'server' => $_POST['sql_server'] ?? '',
                'database' => $_POST['sql_database'] ?? '',
                'username' => $_POST['sql_username'] ?? '',
                'password' => $_POST['sql_password'] ?? '',
                'port' => $_POST['sql_port'] ?? '1433',
                'connection_method' => $_POST['sql_method'] ?? 'com'
            ];
            
            // Cloud settings
            $this->config['cloud'] = [
                'host' => $_POST['cloud_host'] ?? '',
                'database' => $_POST['cloud_database'] ?? '',
                'username' => $_POST['cloud_username'] ?? '',
                'password' => $_POST['cloud_password'] ?? '',
                'port' => $_POST['cloud_port'] ?? '3306'
            ];
            
            // General settings
            $this->config['settings'] = [
                'auto_sync_interval' => (int)($_POST['sync_interval'] ?? 300),
                'batch_size' => (int)($_POST['batch_size'] ?? 1000),
                'max_execution_time' => (int)($_POST['max_time'] ?? 300),
                'log_level' => $_POST['log_level'] ?? 'info',
                'timezone' => $_POST['timezone'] ?? 'Asia/Tehran'
            ];
            
            // Dashboard settings
            $this->config['dashboard'] = [
                'items_per_page' => (int)($_POST['items_per_page'] ?? 50),
                'refresh_interval' => (int)($_POST['refresh_interval'] ?? 30),
                'theme' => $_POST['theme'] ?? 'default'
            ];
            
            // Save to file
            $jsonData = json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if (file_put_contents($this->configFile, $jsonData)) {
                $this->message = 'تنظیمات با موفقیت ذخیره شد!';
                $this->messageType = 'success';
            } else {
                $this->message = 'خطا در ذخیره تنظیمات!';
                $this->messageType = 'error';
            }
            
        } catch (Exception $e) {
            $this->message = 'خطا: ' . $e->getMessage();
            $this->messageType = 'error';
        }
    }
    
    private function resetConfig() {
        $this->config = $this->getDefaultConfig();
        $jsonData = json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (file_put_contents($this->configFile, $jsonData)) {
            $this->message = 'تنظیمات به حالت پیش‌فرض بازگردانده شد!';
            $this->messageType = 'success';
        }
    }
    
    private function testSQLConnection() {
        try {
            if (!class_exists('COM')) {
                $this->message = '❌ COM Extension فعال نیست. برای فعال‌سازی COM-ACTIVATION-GUIDE.md را مطالعه کنید.';
                $this->messageType = 'warning';
                return;
            }
            
            $sql = $this->config['sql_server'];
            $conn = new COM("ADODB.Connection");
            $connectionString = "Provider=SQLOLEDB;Server={$sql['server']};Database={$sql['database']};";
            
            if (!empty($sql['username'])) {
                $connectionString .= "UID={$sql['username']};PWD={$sql['password']};";
            } else {
                $connectionString .= "Integrated Security=SSPI;";
            }
            
            $conn->Open($connectionString);
            $conn->Close();
            
            $this->message = '✅ اتصال SQL Server موفقیت‌آمیز بود!';
            $this->messageType = 'success';
            
        } catch (Exception $e) {
            $this->message = '❌ خطا در اتصال SQL Server: ' . $e->getMessage();
            $this->messageType = 'error';
        }
    }
    
    private function testCloudConnection() {
        try {
            $cloud = $this->config['cloud'];
            if (empty($cloud['host'])) {
                $this->message = '⚠️ تنظیمات Cloud Database خالی است!';
                $this->messageType = 'warning';
                return;
            }
            
            $dsn = "mysql:host={$cloud['host']};port={$cloud['port']};dbname={$cloud['database']};charset=utf8mb4";
            $pdo = new PDO($dsn, $cloud['username'], $cloud['password'], [
                PDO::ATTR_TIMEOUT => 5,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            $this->message = '✅ اتصال Cloud Database موفقیت‌آمیز بود!';
            $this->messageType = 'success';
            
        } catch (Exception $e) {
            $this->message = '❌ خطا در اتصال Cloud Database: ' . $e->getMessage();
            $this->messageType = 'error';
        }
    }
    
    public function render() {
        ?>
        <!DOCTYPE html>
        <html lang="fa" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>ویرایش تنظیمات - سیستم گزارشگیری سبا</title>
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
                    max-width: 900px;
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
                    margin-bottom: 30px;
                }
                
                .header h1 {
                    color: white;
                    font-size: 2rem;
                    margin-bottom: 10px;
                }
                
                .message {
                    padding: 15px;
                    border-radius: 10px;
                    margin-bottom: 20px;
                    font-weight: 500;
                }
                
                .message.success {
                    background: rgba(76, 175, 80, 0.2);
                    border: 1px solid rgba(76, 175, 80, 0.5);
                    color: #c8e6c9;
                }
                
                .message.error {
                    background: rgba(244, 67, 54, 0.2);
                    border: 1px solid rgba(244, 67, 54, 0.5);
                    color: #ffcdd2;
                }
                
                .message.warning {
                    background: rgba(255, 152, 0, 0.2);
                    border: 1px solid rgba(255, 152, 0, 0.5);
                    color: #ffe0b2;
                }
                
                .form-section {
                    background: rgba(255, 255, 255, 0.1);
                    padding: 25px;
                    border-radius: 15px;
                    margin-bottom: 20px;
                    border: 1px solid rgba(255, 255, 255, 0.2);
                }
                
                .form-section h3 {
                    color: white;
                    margin-bottom: 20px;
                    font-size: 1.3rem;
                }
                
                .form-group {
                    margin-bottom: 15px;
                }
                
                .form-group label {
                    display: block;
                    color: rgba(255, 255, 255, 0.9);
                    margin-bottom: 5px;
                    font-weight: 500;
                }
                
                .form-group input,
                .form-group select {
                    width: 100%;
                    padding: 10px 15px;
                    background: rgba(255, 255, 255, 0.1);
                    border: 1px solid rgba(255, 255, 255, 0.3);
                    border-radius: 8px;
                    color: white;
                    font-family: 'Vazirmatn', sans-serif;
                }
                
                .form-group input::placeholder {
                    color: rgba(255, 255, 255, 0.6);
                }
                
                .form-group input:focus,
                .form-group select:focus {
                    outline: none;
                    border-color: #4CAF50;
                    background: rgba(255, 255, 255, 0.15);
                }
                
                .form-row {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 15px;
                }
                
                .buttons {
                    display: flex;
                    gap: 10px;
                    justify-content: center;
                    margin-top: 30px;
                    flex-wrap: wrap;
                }
                
                .btn {
                    padding: 12px 25px;
                    border: none;
                    border-radius: 10px;
                    cursor: pointer;
                    font-family: 'Vazirmatn', sans-serif;
                    font-size: 1rem;
                    transition: all 0.3s ease;
                    text-decoration: none;
                    display: inline-block;
                }
                
                .btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                }
                
                .btn-primary {
                    background: linear-gradient(45deg, #4CAF50, #45a049);
                    color: white;
                }
                
                .btn-warning {
                    background: linear-gradient(45deg, #FF9800, #F57C00);
                    color: white;
                }
                
                .btn-danger {
                    background: linear-gradient(45deg, #F44336, #D32F2F);
                    color: white;
                }
                
                .btn-info {
                    background: linear-gradient(45deg, #2196F3, #1976D2);
                    color: white;
                }
                
                .btn-secondary {
                    background: linear-gradient(45deg, #607D8B, #455A64);
                    color: white;
                }
                
                .test-buttons {
                    display: flex;
                    gap: 10px;
                    margin-top: 15px;
                }
                
                .back-link {
                    position: fixed;
                    top: 20px;
                    left: 20px;
                    background: rgba(255, 255, 255, 0.2);
                    color: white;
                    padding: 10px 15px;
                    border-radius: 50px;
                    text-decoration: none;
                    transition: all 0.3s ease;
                }
                
                .back-link:hover {
                    background: rgba(255, 255, 255, 0.3);
                    transform: translateY(-2px);
                }
                
                @media (max-width: 768px) {
                    .form-row {
                        grid-template-columns: 1fr;
                    }
                    
                    .buttons {
                        flex-direction: column;
                        align-items: center;
                    }
                    
                    .test-buttons {
                        flex-direction: column;
                    }
                }
            </style>
        </head>
        <body>
            <a href="javascript:history.back()" class="back-link">← بازگشت</a>
            
            <div class="container">
                <div class="header">
                    <h1>⚙️ ویرایش تنظیمات سیستم</h1>
                    <p style="color: rgba(255,255,255,0.8);">تنظیمات اتصالات و پیکربندی سیستم</p>
                </div>

                <?php if ($this->message): ?>
                    <div class="message <?php echo $this->messageType; ?>">
                        <?php echo htmlspecialchars($this->message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <!-- SQL Server Settings -->
                    <div class="form-section">
                        <h3>🗄️ تنظیمات SQL Server</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>سرور</label>
                                <input type="text" name="sql_server" value="<?php echo htmlspecialchars($this->config['sql_server']['server']); ?>" placeholder="localhost\SQLEXPRESS">
                            </div>
                            <div class="form-group">
                                <label>دیتابیس</label>
                                <input type="text" name="sql_database" value="<?php echo htmlspecialchars($this->config['sql_server']['database']); ?>" placeholder="SabaDB">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>نام کاربری</label>
                                <input type="text" name="sql_username" value="<?php echo htmlspecialchars($this->config['sql_server']['username']); ?>" placeholder="sa">
                            </div>
                            <div class="form-group">
                                <label>رمز عبور</label>
                                <input type="password" name="sql_password" value="<?php echo htmlspecialchars($this->config['sql_server']['password']); ?>" placeholder="رمز عبور">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>پورت</label>
                                <input type="text" name="sql_port" value="<?php echo htmlspecialchars($this->config['sql_server']['port']); ?>" placeholder="1433">
                            </div>
                            <div class="form-group">
                                <label>روش اتصال</label>
                                <select name="sql_method">
                                    <option value="com" <?php echo $this->config['sql_server']['connection_method'] === 'com' ? 'selected' : ''; ?>>COM Object</option>
                                    <option value="sqlsrv" <?php echo $this->config['sql_server']['connection_method'] === 'sqlsrv' ? 'selected' : ''; ?>>SQLSRV</option>
                                </select>
                            </div>
                        </div>
                        <div class="test-buttons">
                            <button type="submit" name="action" value="test_sql" class="btn btn-info">🧪 تست اتصال SQL Server</button>
                        </div>
                    </div>

                    <!-- Cloud Database Settings -->
                    <div class="form-section">
                        <h3>☁️ تنظیمات Cloud Database</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>هاست</label>
                                <input type="text" name="cloud_host" value="<?php echo htmlspecialchars($this->config['cloud']['host']); ?>" placeholder="93.127.180.221">
                            </div>
                            <div class="form-group">
                                <label>دیتابیس</label>
                                <input type="text" name="cloud_database" value="<?php echo htmlspecialchars($this->config['cloud']['database']); ?>" placeholder="reports_database">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>نام کاربری</label>
                                <input type="text" name="cloud_username" value="<?php echo htmlspecialchars($this->config['cloud']['username']); ?>" placeholder="sync_user">
                            </div>
                            <div class="form-group">
                                <label>رمز عبور</label>
                                <input type="password" name="cloud_password" value="<?php echo htmlspecialchars($this->config['cloud']['password']); ?>" placeholder="رمز عبور">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>پورت</label>
                            <input type="text" name="cloud_port" value="<?php echo htmlspecialchars($this->config['cloud']['port']); ?>" placeholder="3306">
                        </div>
                        <div class="test-buttons">
                            <button type="submit" name="action" value="test_cloud" class="btn btn-info">🧪 تست اتصال Cloud</button>
                        </div>
                    </div>

                    <!-- General Settings -->
                    <div class="form-section">
                        <h3>🔧 تنظیمات عمومی</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>فاصله همگام‌سازی (ثانیه)</label>
                                <input type="number" name="sync_interval" value="<?php echo $this->config['settings']['auto_sync_interval']; ?>" min="60" max="3600">
                            </div>
                            <div class="form-group">
                                <label>اندازه دسته (Batch Size)</label>
                                <input type="number" name="batch_size" value="<?php echo $this->config['settings']['batch_size']; ?>" min="100" max="10000">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>حداکثر زمان اجرا (ثانیه)</label>
                                <input type="number" name="max_time" value="<?php echo $this->config['settings']['max_execution_time']; ?>" min="60" max="1800">
                            </div>
                            <div class="form-group">
                                <label>سطح لاگ</label>
                                <select name="log_level">
                                    <option value="debug" <?php echo $this->config['settings']['log_level'] === 'debug' ? 'selected' : ''; ?>>Debug</option>
                                    <option value="info" <?php echo $this->config['settings']['log_level'] === 'info' ? 'selected' : ''; ?>>Info</option>
                                    <option value="warning" <?php echo $this->config['settings']['log_level'] === 'warning' ? 'selected' : ''; ?>>Warning</option>
                                    <option value="error" <?php echo $this->config['settings']['log_level'] === 'error' ? 'selected' : ''; ?>>Error</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>منطقه زمانی</label>
                            <select name="timezone">
                                <option value="Asia/Tehran" <?php echo $this->config['settings']['timezone'] === 'Asia/Tehran' ? 'selected' : ''; ?>>Asia/Tehran</option>
                                <option value="UTC" <?php echo $this->config['settings']['timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                            </select>
                        </div>
                    </div>

                    <!-- Dashboard Settings -->
                    <div class="form-section">
                        <h3>📊 تنظیمات داشبورد</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>آیتم در هر صفحه</label>
                                <input type="number" name="items_per_page" value="<?php echo $this->config['dashboard']['items_per_page']; ?>" min="10" max="200">
                            </div>
                            <div class="form-group">
                                <label>فاصله بروزرسانی (ثانیه)</label>
                                <input type="number" name="refresh_interval" value="<?php echo $this->config['dashboard']['refresh_interval']; ?>" min="5" max="300">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>قالب</label>
                            <select name="theme">
                                <option value="default" <?php echo $this->config['dashboard']['theme'] === 'default' ? 'selected' : ''; ?>>پیش‌فرض</option>
                                <option value="dark" <?php echo $this->config['dashboard']['theme'] === 'dark' ? 'selected' : ''; ?>>تیره</option>
                                <option value="light" <?php echo $this->config['dashboard']['theme'] === 'light' ? 'selected' : ''; ?>>روشن</option>
                            </select>
                        </div>
                    </div>

                    <div class="buttons">
                        <button type="submit" name="action" value="save" class="btn btn-primary">💾 ذخیره تنظیمات</button>
                        <button type="submit" name="action" value="reset" class="btn btn-danger" onclick="return confirm('آیا مطمئن هستید؟')">🔄 بازنشانی</button>
                        <a href="windows-simple.php" class="btn btn-secondary">🏠 بازگشت به داشبورد</a>
                        <a href="COM-ACTIVATION-GUIDE.md" class="btn btn-warning" target="_blank">📖 راهنمای COM</a>
                    </div>
                </form>
            </div>
        </body>
        </html>
        <?php
    }
}

// Run the settings editor
$editor = new SettingsEditor();
$editor->render();
?>
