# 🚀 راهنمای سریع (Quick Start Guide)

## 🔍 OverView سریع پروژه
سیستم همگام‌سازی داده که SQL Server را با پایگاه داده ابری متصل می‌کند. کاملاً ماژولار و با رابط کاربری مدرن.

## ⚡ اجرای سریع

### macOS
```bash
cd /Users/macminim4/MyApp/Saba-Rep/server-win
chmod +x setup-mac.sh start-mac.sh
./start-mac.sh
```

### Windows
```cmd
cd server-win
php sync-service-odbc.php
```

## 📂 کلیدی‌ترین فایل‌ها

### 1. ماژول‌های اصلی
- `modules/BaseModule.php` - پایه همه کلاس‌ها
- `modules/Logger.php` - سیستم لاگ
- `modules/database/SQLServerConnection.php` - اتصال SQL Server  
- `modules/database/CloudConnection.php` - اتصال Cloud DB
- `modules/sync/SyncManager.php` - مدیریت همگام‌سازی

### 2. رابط کاربری
- `assets/css/style.css` - استایل‌های کامل
- `assets/js/dashboard.js` - داشبورد تعاملی

### 3. تنظیمات
- `config.json` (Windows) / `config-mac.json` (macOS)
- `odbc.ini` - تنظیمات ODBC

## 🔧 تنظیمات ضروری

### config.json نمونه
```json
{
    "sql_server": {
        "server": "YOUR_SQL_SERVER_IP",
        "database": "YOUR_DATABASE",
        "username": "sa",
        "password": "YOUR_PASSWORD",
        "port": "1433",
        "connection_method": "odbc"
    },
    "cloud": {
        "host": "YOUR_VPS_IP",
        "database": "reports_database",
        "username": "sync_user", 
        "password": "YOUR_PASSWORD",
        "port": "3306"
    }
}
```

## 🎯 استفاده سریع

### همگام‌سازی یک جدول
```php
$sync = new SyncManager();
$result = $sync->syncTable('table_name');
```

### همگام‌سازی همه جداول
```php
$sync = new SyncManager();
$result = $sync->syncAllTables();
```

### دریافت آمار
```php
$sync = new SyncManager();
$stats = $sync->getStats();
```

## 📊 داشبورد

برای دسترسی به داشبورد:
1. مرورگر را باز کنید
2. به آدرس `http://localhost:8000` بروید
3. وضعیت همگام‌سازی را مشاهده کنید

## 🐛 رفع مشکلات سریع

### خطای اتصال
```bash
# بررسی وضعیت سرویس‌ها
netstat -an | grep :1433  # SQL Server
netstat -an | grep :3306  # MySQL

# تست اتصال
telnet YOUR_SERVER_IP 1433
```

### خطاهای PHP
```bash
# بررسی extensions
php -m | grep -E "pdo|odbc|mysqli"

# بررسی syntax
php -l modules/BaseModule.php
```

## 📁 ساختار کلی

```
server-win/
├── modules/           # ماژول‌های PHP
├── assets/           # CSS & JS
├── config/           # تنظیمات
├── logs/            # فایل‌های لاگ
├── views/           # HTML templates
└── config.json     # تنظیمات اصلی
```

## 💡 نکات مهم

1. **همیشه** قبل از تغییر، backup بگیرید
2. فایل‌های لاگ را برای رفع مشکل بررسی کنید
3. تنظیمات را در `config.json` کنترل کنید
4. برای نصب جدید، اسکریپت‌های setup را اجرا کنید

---
*آخرین بروزرسانی: 29 آگوست 2025*
