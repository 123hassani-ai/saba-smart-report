# 🪟 راهنمای نصب و اجرا - ویندوز
# Windows Installation & Usage Guide

## 🎯 نیازمندی‌های سیستم

### ✅ پیش‌نیازها
- **Windows 10/11** یا Windows Server
- **PHP 7.4+** با پسوندهای مورد نیاز
- **SQL Server** (نسخه 2012 یا بالاتر)
- **دسترسی اینترنت** برای اتصال به Cloud Database

### 📦 پسوندهای PHP مورد نیاز
```ini
extension=pdo_sqlsrv
extension=pdo_mysql
extension=com_dotnet
extension=curl
extension=json
extension=mbstring
```

## 🚀 نصب و راه‌اندازی

### مرحله 1: نصب PHP
```cmd
# دانلود PHP از php.net
# یا استفاده از XAMPP/WampServer
```

### مرحله 2: کپی فایل‌ها
```cmd
# کپی همه فایل‌های پروژه به:
C:\inetpub\wwwroot\saba\
# یا
C:\xampp\htdocs\saba\
```

### مرحله 3: تنظیم SQL Server
```cmd
# فعال‌سازی TCP/IP در SQL Server Configuration Manager
# راه‌اندازی SQL Server Browser Service
# باز کردن فایروال برای پورت 1433
```

### مرحله 4: اجرا
```cmd
# دابل کلیک روی:
start-windows.bat

# یا اجرای دستی:
php windows.php
```

## ⚙️ تنظیمات

### فایل config.json
```json
{
    "sql_server": {
        "server": "localhost",           // آدرس SQL Server
        "database": "YourDatabase",      // نام پایگاه داده
        "username": "sa",                // نام کاربری
        "password": "YourPassword",      // رمز عبور
        "port": "1433",                  // پورت
        "connection_method": "com"       // روش اتصال: "com" یا "odbc"
    },
    "cloud": {
        "host": "your-server.com",       // سرور ابری
        "database": "reports_database",  // نام دیتابیس
        "username": "sync_user",         // نام کاربری
        "password": "YourPassword",      // رمز عبور
        "port": "3306"                   // پورت MySQL
    }
}
```

## 🔧 دستورات CLI

### اجرای مستقیم
```cmd
# تست اتصالات
php windows.php test

# همگام‌سازی
php windows.php sync

# نمایش جداول
php windows.php tables

# نمایش تنظیمات
php windows.php config

# راهنما
php windows.php help
```

### اجرای وب سرور
```cmd
# راه‌اندازی سرور محلی
php -S localhost:8000 windows.php

# سپس باز کردن مرورگر:
http://localhost:8000
```

## 🌐 رابط وب

### دسترسی به داشبورد
- **آدرس**: http://localhost:8000
- **قابلیت‌ها**:
  - نمایش وضعیت اتصالات
  - شروع همگام‌سازی
  - مشاهده جداول
  - لاگ عملیات

### API Endpoints
```
GET  /?action=api&endpoint=test     - تست اتصالات
POST /?action=api&endpoint=sync     - شروع همگام‌سازی
GET  /?action=api&endpoint=tables   - لیست جداول
```

## 🐛 رفع مشکلات

### خطای COM Object
```cmd
# اطمینان از فعال بودن COM در PHP
extension=com_dotnet

# اجرا با دسترسی Administrator
```

### خطای SQL Server
```cmd
# بررسی SQL Server Service
services.msc → SQL Server (MSSQLSERVER)

# فعال‌سازی TCP/IP
SQL Server Configuration Manager → Protocols → TCP/IP → Enabled

# باز کردن فایروال
Windows Firewall → Port 1433
```

### خطای PHP
```cmd
# بررسی نسخه PHP
php --version

# بررسی پسوندها
php -m

# نمایش خطاها
ini_set('display_errors', 1);
```

## 📊 عملکرد

### بهینه‌سازی
- **Batch Size**: 1000 رکورد (قابل تنظیم)
- **Memory Limit**: 512M یا بیشتر
- **Max Execution Time**: 300 ثانیه

### مانیتورینگ
- **لاگ فایل‌ها**: `logs/sync_YYYY-MM-DD.log`
- **خروجی Realtime**: در CLI
- **داشبورد وب**: نمایش آنلاین

## 🔒 امنیت

### نکات امنیتی
- رمزهای قوی برای SQL Server
- محدودسازی دسترسی شبکه
- بکاپ منظم پایگاه داده
- بروزرسانی منظم PHP

### فایروال
```cmd
# باز کردن پورت‌های مورد نیاز:
Port 1433 - SQL Server
Port 3306 - MySQL (اگر local)
Port 8000 - Web Server (اختیاری)
```

## 📈 گزارشات

### انواع لاگ
- **INFO**: اطلاعات عمومی
- **WARNING**: هشدارها
- **ERROR**: خطاها

### فرمت لاگ
```
[YYYY-MM-DD HH:MM:SS] [LEVEL] Message
```

## 🆘 پشتیبانی

### تماس با پشتیبانی
- **لاگ فایل‌ها** را ارسال کنید
- **تنظیمات** (بدون رمزهای عبور) را ارسال کنید
- **خطای دقیق** را کپی کنید

---

## ⚡ شروع سریع

```cmd
1. start-windows.bat اجرا کنید
2. گزینه 1 را انتخاب کنید (Test)
3. در صورت موفقیت، گزینه 2 (Sync)
4. برای رابط وب: گزینه 5 (Web Server)
```

**موفق باشید!** 🎉
