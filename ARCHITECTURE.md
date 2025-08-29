# 🏗️ راهنمای معماری سیستم (Architecture Guide)

## 📐 نگاه کلی معماری

سیستم با الگوی **Modular Monolith** طراحی شده که هر بخش مسئولیت مشخص و جداگانه‌ای دارد.

```
┌─────────────────────────────────────────────────────────┐
│                    Frontend Layer                        │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐      │
│  │ Dashboard   │  │ Config UI   │  │ Sync UI     │      │
│  │ (JS/CSS)    │  │ (HTML)      │  │ (HTML)      │      │
│  └─────────────┘  └─────────────┘  └─────────────┘      │
└─────────────────────────────────────────────────────────┘
                            │
                     ┌─────────────┐
                     │ API Router  │
                     │ (Planning)  │
                     └─────────────┘
                            │
┌─────────────────────────────────────────────────────────┐
│                 Business Logic Layer                     │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐      │
│  │ SyncManager │  │ AuthManager │  │ Dashboard   │      │
│  │             │  │ (Planning)  │  │ Manager     │      │
│  └─────────────┘  └─────────────┘  └─────────────┘      │
└─────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────┐
│                   Data Access Layer                      │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐      │
│  │ SQL Server  │  │ Cloud DB    │  │ Config      │      │
│  │ Connection  │  │ Connection  │  │ Manager     │      │
│  └─────────────┘  └─────────────┘  └─────────────┘      │
└─────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────┐
│                Infrastructure Layer                      │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐      │
│  │ BaseModule  │  │ Logger      │  │ Validator   │      │
│  │ (Abstract)  │  │ (Singleton) │  │ (Utility)   │      │
│  └─────────────┘  └─────────────┘  └─────────────┘      │
└─────────────────────────────────────────────────────────┘
```

## 🔧 Design Patterns استفاده شده

### 1. Abstract Factory Pattern
**BaseModule.php** - کلاس پایه برای همه ماژول‌ها
```php
abstract class BaseModule {
    protected $config;
    protected $logger;
    
    abstract public function initialize();
    abstract public function validate($data);
}
```

### 2. Singleton Pattern  
**Logger.php** - یک instance واحد در کل برنامه
```php
class Logger {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
```

### 3. Strategy Pattern
**Database Connections** - انتخاب نوع اتصال بر اساس سیستم‌عامل
```php
class SQLServerConnection {
    public function connect() {
        if ($this->isWindows()) {
            return $this->connectViaCOM();
        } else {
            return $this->connectViaODBC();
        }
    }
}
```

### 4. Observer Pattern (در Dashboard)
**dashboard.js** - پایش تغییرات real-time
```javascript
class Dashboard {
    constructor() {
        this.observers = [];
        this.startRealTimeUpdates();
    }
    
    notifyObservers(data) {
        this.observers.forEach(callback => callback(data));
    }
}
```

## 📦 وابستگی‌ها (Dependencies)

### PHP Dependencies
```
BaseModule
├── Logger (Singleton)
├── ConfigManager
└── Validator (Static Methods)

SyncManager
├── BaseModule
├── SQLServerConnection
├── CloudConnection
└── Logger

DatabaseConnections
├── BaseModule  
├── PDO/ODBC
└── Logger
```

### Frontend Dependencies
```
Dashboard (JS)
├── Chart.js (External)
├── Fetch API (Native)
└── CSS Variables

Style (CSS)  
├── Google Fonts (Vazirmatn)
├── CSS Grid/Flexbox
└── CSS Custom Properties
```

## 🔄 Data Flow

### 1. Sync Process Flow
```
[User Request] → [SyncManager] → [Source DB] → [Transform] → [Target DB] → [Log/Report]
      │              │              │             │             │            │
      │              ▼              ▼             ▼             ▼            ▼
   [Dashboard]  [Progress Track] [SQL Server] [Data Clean] [Cloud DB]   [History]
```

### 2. Configuration Flow
```
[JSON File] → [ConfigManager] → [Validation] → [Module Config] → [Runtime Use]
     │             │               │               │               │
     ▼             ▼               ▼               ▼               ▼
[File System] [Load & Parse] [Type Check]   [Memory Store]   [Active Config]
```

### 3. Logging Flow
```
[Module Event] → [Logger] → [Format] → [File Write] → [Console Display]
      │            │          │           │              │
      ▼            ▼          ▼           ▼              ▼
[Error/Info]  [Singleton] [Timestamp] [Daily Log]   [Colored Output]
```

## 🛡️ Security Architecture

### 1. Input Validation Layers
```
User Input → Frontend Validation → Server Validation → Database Sanitization
```

### 2. Database Security
- **SQL Server**: Windows Authentication یا SQL Authentication
- **Cloud DB**: Username/Password با SSL
- **ODBC**: Encrypted connections

### 3. File System Security  
- Config files در دایرکتری محافظت شده
- Log files با دسترسی محدود
- Temp files با پاک‌سازی خودکار

## 🔀 Extension Points

### 1. افزودن Database جدید
```php
class OracleConnection extends BaseModule implements DatabaseInterface {
    public function connect() { /* Implementation */ }
    public function query($sql) { /* Implementation */ }
    public function getTables() { /* Implementation */ }
}
```

### 2. افزودن Sync Strategy جدید
```php
class IncrementalSyncStrategy implements SyncStrategyInterface {
    public function sync($table, $options) { /* Implementation */ }
}
```

### 3. افزودن Authentication Method
```php
class LDAPAuth extends BaseModule implements AuthInterface {
    public function authenticate($username, $password) { /* Implementation */ }
}
```

## 📊 Performance Considerations

### 1. Database Performance
- **Connection Pooling**: برای حجم بالای درخواست‌ها
- **Batch Processing**: درج داده‌ها به صورت دسته‌ای
- **Indexing**: Index های مناسب بر روی جداول

### 2. Memory Management
- **Streaming**: پردازش داده‌های حجیم به صورت Stream
- **Garbage Collection**: پاک‌سازی متغیرهای غیرضروری
- **Buffer Management**: مدیریت حافظه Buffer

### 3. Caching Strategy
```php
class CacheManager {
    private static $cache = [];
    
    public static function remember($key, $callback, $ttl = 3600) {
        if (!isset(self::$cache[$key]) || self::$cache[$key]['expires'] < time()) {
            self::$cache[$key] = [
                'data' => $callback(),
                'expires' => time() + $ttl
            ];
        }
        return self::$cache[$key]['data'];
    }
}
```

## 🔍 Monitoring & Observability

### 1. Logging Levels
- **ERROR**: خطاهای سیستمی
- **WARNING**: هشدارهای قابل نادیده‌گیری  
- **INFO**: اطلاعات عمومی
- **DEBUG**: اطلاعات توسعه‌دهنده

### 2. Metrics Collection
- **Sync Performance**: زمان هر sync operation
- **Database Performance**: زمان query ها  
- **Error Rates**: نرخ خطاها بر واحد زمان
- **Resource Usage**: استفاده از CPU و Memory

### 3. Health Checks
```php
class HealthCheck {
    public function checkDatabaseConnections() { /* Implementation */ }
    public function checkDiskSpace() { /* Implementation */ }
    public function checkMemoryUsage() { /* Implementation */ }
}
```

## 🚀 Scalability Considerations

### 1. Horizontal Scaling
- **Multi-Instance**: اجرای چند instance برای load balancing
- **Queue System**: استفاده از Redis یا RabbitMQ برای job queue

### 2. Vertical Scaling  
- **Resource Optimization**: بهینه‌سازی استفاده از CPU و Memory
- **Database Optimization**: Query optimization و indexing

### 3. Data Partitioning
- **Table Partitioning**: تقسیم جداول بزرگ
- **Date-based Sharding**: تقسیم بر اساس تاریخ

---
*این معماری برای پشتیبانی از رشد آینده پروژه طراحی شده است.*
