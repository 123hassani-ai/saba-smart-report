# 🔧 راهنمای توسعه‌دهندگان (Developer Guide)

## 🚀 شروع توسعه

### Pre-requisites
```bash
# macOS
brew install php unixodbc freetds
brew install mysql-client

# Ubuntu/Debian
sudo apt-get install php php-odbc php-pdo php-mysql unixodbc freetds-bin

# Windows
# Install XAMPP یا WampServer
# Install ODBC drivers for SQL Server
```

### Development Environment Setup
```bash
# Clone repository
git clone <repository-url>
cd server-win

# Install development dependencies  
composer install --dev  # اگر composer.json داشته باشیم

# Setup environment
cp config-example.json config.json
# Edit config.json with your settings

# Make scripts executable (macOS/Linux)
chmod +x setup-mac.sh start-mac.sh

# Run setup
./setup-mac.sh
```

## 📁 ساختار کد

### Naming Conventions
- **Classes**: PascalCase (`BaseModule`, `SyncManager`)
- **Methods**: camelCase (`getSyncHistory`, `validateConfig`)  
- **Properties**: camelCase (`$connectionString`, `$batchSize`)
- **Constants**: UPPER_SNAKE_CASE (`MAX_BATCH_SIZE`, `DEFAULT_TIMEOUT`)
- **Files**: PascalCase for classes (`SyncManager.php`)

### File Organization
```
modules/
├── [Feature]/           # Group by feature
│   ├── Manager.php     # Main logic class
│   ├── Interface.php   # Contract definition
│   └── Exception.php   # Custom exceptions
├── shared/             # Shared utilities
│   ├── BaseModule.php  
│   └── Logger.php
└── contracts/          # Interfaces و Abstracts
    └── DatabaseInterface.php
```

## 🏗️ افزودن ماژول جدید

### 1. ایجاد کلاس پایه
```php
<?php
// modules/feature/FeatureManager.php

require_once __DIR__ . '/../BaseModule.php';

class FeatureManager extends BaseModule {
    
    public function __construct() {
        parent::__construct();
        $this->logger->info('FeatureManager initialized');
    }
    
    public function initialize(): bool {
        // Initialization logic
        return true;
    }
    
    public function validate($data): array {
        // Validation logic
        return ['valid' => true, 'errors' => []];
    }
    
    // Feature-specific methods
    public function processFeature($params) {
        try {
            $this->logger->info('Processing feature', $params);
            
            // Implementation
            
            return $this->success('Feature processed successfully');
            
        } catch (Exception $e) {
            $this->logger->error('Feature processing failed', ['error' => $e->getMessage()]);
            return $this->error('Feature processing failed: ' . $e->getMessage());
        }
    }
}
```

### 2. تعریف Interface (اختیاری)
```php
<?php
// modules/contracts/FeatureInterface.php

interface FeatureInterface {
    public function processFeature($params);
    public function getFeatureStatus();
}
```

### 3. Custom Exception (اختیاری)
```php
<?php
// modules/feature/FeatureException.php

class FeatureException extends Exception {
    
    public function __construct($message = "", $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        
        // Log exception
        Logger::getInstance()->error('FeatureException', [
            'message' => $message,
            'code' => $code,
            'trace' => $this->getTraceAsString()
        ]);
    }
}
```

## 🔌 اضافه کردن Database جدید

### 1. Database Connection Class
```php
<?php
// modules/database/PostgreSQLConnection.php

require_once __DIR__ . '/../BaseModule.php';

class PostgreSQLConnection extends BaseModule {
    
    private $connection;
    
    public function __construct() {
        parent::__construct();
    }
    
    public function connect(): bool {
        try {
            $config = $this->config['postgresql'];
            
            $dsn = sprintf(
                "pgsql:host=%s;port=%s;dbname=%s",
                $config['host'],
                $config['port'],
                $config['database']
            );
            
            $this->connection = new PDO($dsn, $config['username'], $config['password']);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $this->logger->info('PostgreSQL connection established');
            return true;
            
        } catch (PDOException $e) {
            $this->logger->error('PostgreSQL connection failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    public function getTables(): array {
        $sql = "SELECT tablename FROM pg_tables WHERE schemaname = 'public'";
        $stmt = $this->connection->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // سایر متدهای مورد نیاز...
}
```

### 2. بروزرسانی ConfigManager
```php
// در modules/config/ConfigManager.php

protected function getDefaultConfig(): array {
    return [
        // ... existing configs
        'postgresql' => [
            'host' => 'localhost',
            'port' => '5432', 
            'database' => '',
            'username' => '',
            'password' => '',
            'schema' => 'public'
        ]
    ];
}

protected function validatePostgreSQLConfig($config): array {
    $errors = [];
    
    if (empty($config['host'])) {
        $errors[] = 'PostgreSQL host is required';
    }
    
    if (empty($config['database'])) {
        $errors[] = 'PostgreSQL database name is required';
    }
    
    return $errors;
}
```

## 🌐 اضافه کردن API Endpoint

### 1. Route Handler
```php
<?php
// modules/api/FeatureController.php

require_once __DIR__ . '/../BaseModule.php';
require_once __DIR__ . '/../feature/FeatureManager.php';

class FeatureController extends BaseModule {
    
    private $featureManager;
    
    public function __construct() {
        parent::__construct();
        $this->featureManager = new FeatureManager();
    }
    
    public function handleRequest($method, $path, $params = []) {
        switch ($method) {
            case 'GET':
                return $this->handleGet($path, $params);
            case 'POST':
                return $this->handlePost($path, $params);
            default:
                return $this->error('Method not allowed', 405);
        }
    }
    
    private function handleGet($path, $params) {
        switch ($path) {
            case '/api/feature/status':
                return $this->getStatus();
            case '/api/feature/list':
                return $this->getList($params);
            default:
                return $this->error('Endpoint not found', 404);
        }
    }
    
    private function handlePost($path, $params) {
        switch ($path) {
            case '/api/feature/process':
                return $this->processFeature($params);
            default:
                return $this->error('Endpoint not found', 404);
        }
    }
    
    private function getStatus() {
        $status = $this->featureManager->getStatus();
        return $this->success($status);
    }
    
    private function processFeature($params) {
        $validation = $this->validate($params);
        if (!$validation['valid']) {
            return $this->error('Validation failed', 400, $validation['errors']);
        }
        
        return $this->featureManager->processFeature($params);
    }
}
```

### 2. Main Router Update
```php
// در فایل اصلی (index.php یا router.php)

require_once 'modules/api/FeatureController.php';

class Router {
    
    private $controllers = [];
    
    public function __construct() {
        $this->controllers['feature'] = new FeatureController();
        // ... other controllers
    }
    
    public function route($request) {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Route to appropriate controller
        if (strpos($path, '/api/feature') === 0) {
            return $this->controllers['feature']->handleRequest($method, $path, $request);
        }
        
        // ... other routes
    }
}
```

## 🎨 اضافه کردن Frontend Component

### 1. JavaScript Module
```javascript
// assets/js/modules/FeatureModule.js

class FeatureModule {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.apiBase = '/api/feature';
        this.init();
    }
    
    async init() {
        await this.loadStatus();
        this.bindEvents();
        this.startPolling();
    }
    
    async loadStatus() {
        try {
            const response = await fetch(`${this.apiBase}/status`);
            const data = await response.json();
            this.renderStatus(data);
        } catch (error) {
            console.error('Failed to load feature status:', error);
        }
    }
    
    renderStatus(data) {
        this.container.innerHTML = `
            <div class="feature-status">
                <h3>Feature Status</h3>
                <div class="status-info">
                    ${this.formatStatusData(data)}
                </div>
            </div>
        `;
    }
    
    bindEvents() {
        // Event listeners
        this.container.addEventListener('click', (e) => {
            if (e.target.classList.contains('process-btn')) {
                this.processFeature();
            }
        });
    }
    
    async processFeature() {
        // Implementation
    }
    
    startPolling() {
        setInterval(() => {
            this.loadStatus();
        }, 30000); // هر 30 ثانیه
    }
}

// Export for use in other modules
window.FeatureModule = FeatureModule;
```

### 2. CSS Styles
```css
/* assets/css/modules/feature.css */

.feature-status {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    padding: var(--spacing-lg);
    margin-bottom: var(--spacing-md);
    box-shadow: var(--card-shadow);
}

.feature-status h3 {
    color: var(--text-primary);
    margin-bottom: var(--spacing-md);
    font-weight: 600;
}

.status-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-md);
}

.status-item {
    background: var(--surface-bg);
    padding: var(--spacing-md);
    border-radius: var(--border-radius-sm);
    text-align: center;
}

.process-btn {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: var(--spacing-sm) var(--spacing-lg);
    border-radius: var(--border-radius-sm);
    cursor: pointer;
    transition: var(--transition-fast);
}

.process-btn:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
}
```

## 🧪 Testing

### 1. Unit Test Example
```php
<?php
// tests/FeatureManagerTest.php

require_once __DIR__ . '/../modules/feature/FeatureManager.php';

class FeatureManagerTest extends PHPUnit\Framework\TestCase {
    
    private $featureManager;
    
    protected function setUp(): void {
        $this->featureManager = new FeatureManager();
    }
    
    public function testProcessFeatureSuccess() {
        $params = ['key' => 'value'];
        $result = $this->featureManager->processFeature($params);
        
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['data']);
    }
    
    public function testProcessFeatureValidation() {
        $params = []; // Invalid params
        $result = $this->featureManager->processFeature($params);
        
        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['errors']);
    }
}
```

### 2. Integration Test
```php
<?php
// tests/APIIntegrationTest.php

class APIIntegrationTest extends PHPUnit\Framework\TestCase {
    
    public function testFeatureAPIEndpoint() {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'http://localhost:8000/api/feature/status',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json']
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        $this->assertEquals(200, $httpCode);
        
        $data = json_decode($response, true);
        $this->assertTrue($data['success']);
    }
}
```

## 📝 Debugging

### 1. Debug Mode
```php
// در config.json
{
    "settings": {
        "debug": true,
        "log_level": "debug"
    }
}
```

### 2. Debug Helper Functions
```php
<?php
// modules/shared/DebugHelper.php

class DebugHelper {
    
    public static function dump($variable, $label = '') {
        if (!self::isDebugMode()) return;
        
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        $file = basename($trace['file']);
        $line = $trace['line'];
        
        echo "\n[DEBUG] {$file}:{$line}";
        if ($label) echo " - {$label}";
        echo "\n" . print_r($variable, true) . "\n";
    }
    
    public static function isDebugMode(): bool {
        $config = ConfigManager::getInstance()->getConfig();
        return $config['settings']['debug'] ?? false;
    }
    
    public static function logQuery($sql, $params = []) {
        if (!self::isDebugMode()) return;
        
        Logger::getInstance()->debug('SQL Query', [
            'sql' => $sql,
            'params' => $params,
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ]);
    }
}
```

## 🚀 Performance Best Practices

### 1. Database Optimization
```php
// Use prepared statements
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);

// Batch operations
$stmt = $pdo->prepare("INSERT INTO table (col1, col2) VALUES (?, ?)");
foreach ($data as $row) {
    $stmt->execute([$row['col1'], $row['col2']]);
}
```

### 2. Memory Management
```php
// Free memory after processing large datasets
unset($largeArray);
gc_collect_cycles();

// Use generators for large data sets
function processLargeDataset() {
    $handle = fopen('large-file.csv', 'r');
    while (($line = fgets($handle)) !== false) {
        yield $line;
    }
    fclose($handle);
}
```

### 3. Caching Strategy
```php
// Simple in-memory cache
class SimpleCache {
    private static $cache = [];
    
    public static function remember($key, $callback, $ttl = 3600) {
        $cacheKey = md5($key);
        
        if (isset(self::$cache[$cacheKey]) && 
            self::$cache[$cacheKey]['expires'] > time()) {
            return self::$cache[$cacheKey]['data'];
        }
        
        $data = $callback();
        self::$cache[$cacheKey] = [
            'data' => $data,
            'expires' => time() + $ttl
        ];
        
        return $data;
    }
}
```

---
*این راهنما پوشش کاملی از فرآیند توسعه در پروژه ارائه می‌دهد.*
