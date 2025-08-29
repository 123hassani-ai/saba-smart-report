# 📚 مستندات سیستم گزارش‌گیری سبا (Saba Reporting System)

## 📖 درباره پروژه
سیستم گزارش‌گیری سبا یک برنامه PHP برای همگام‌سازی داده‌ها بین SQL Server (ویندوز) و پایگاه داده ابری (MySQL/MariaDB) است که به صورت ماژولار طراحی شده و دارای رابط کاربری مدرن با پشتیبانی از فونت فارسی وزیر می‌باشد.

## 🏗️ معماری پروژه

### ساختار فایل‌ها
```
server-win/
├── assets/                     # فایل‌های استاتیک
│   ├── css/
│   │   └── style.css          # استایل‌های اصلی
│   └── js/
│       └── dashboard.js       # جاوا اسکریپت داشبورد
├── config/                    # فایل‌های تنظیمات
├── logs/                      # فایل‌های لاگ
├── modules/                   # ماژول‌های اصلی
│   ├── BaseModule.php         # کلاس پایه
│   ├── Logger.php             # سیستم لاگ
│   ├── auth/                  # احراز هویت
│   ├── config/
│   │   └── ConfigManager.php  # مدیریت تنظیمات
│   ├── database/
│   │   ├── SQLServerConnection.php  # اتصال SQL Server
│   │   └── CloudConnection.php      # اتصال پایگاه داده ابری
│   ├── dashboard/             # ماژول داشبورد
│   └── sync/
│       └── SyncManager.php    # مدیریت همگام‌سازی
├── temp/                      # فایل‌های موقت
├── views/                     # قالب‌های HTML
├── config.json               # تنظیمات اصلی
├── config-mac.json           # تنظیمات macOS
├── odbc.ini                  # تنظیمات ODBC
├── odbcinst.ini              # درایورهای ODBC
├── setup-mac.sh              # اسکریپت نصب macOS
├── start-mac.sh              # اسکریپت اجرا macOS
├── sync-service-odbc.php     # فایل اصلی (قدیمی)
├── README-macOS.md           # راهنمای macOS
└── GUIDE-COMPLETE.md         # راهنمای کامل
```

## 🛠️ ماژول‌ها و کلاس‌ها

### 1. BaseModule.php
کلاس پایه برای همه ماژول‌ها
- **مسئولیت**: پایه مشترک برای همه ماژول‌ها
- **قابلیت‌ها**:
  - بارگذاری تنظیمات
  - سیستم لاگ
  - اعتبارسنجی داده‌ها
  - پاسخ‌های JSON

### 2. Logger.php
سیستم مدیریت لاگ‌ها
- **الگوی طراحی**: Singleton
- **قابلیت‌ها**:
  - ثبت لاگ با سطوح مختلف (INFO, ERROR, WARNING, DEBUG)
  - رنگ‌بندی خروجی در CLI
  - مدیریت فایل‌های لاگ روزانه
  - پاک‌سازی خودکار لاگ‌های قدیمی

### 3. ConfigManager.php
مدیریت تنظیمات برنامه
- **مسئولیت**: بارگذاری، ذخیره و اعتبارسنجی تنظیمات
- **قابلیت‌ها**:
  - پشتیبانی از تنظیمات پیش‌فرض
  - اعتبارسنجی IP، پورت و email
  - بروزرسانی بخش‌های جداگانه تنظیمات

### 4. SQLServerConnection.php
مدیریت اتصال به SQL Server
- **روش‌های اتصال**: ODBC، COM Object
- **پشتیبانی سیستم‌عامل**: Windows، macOS، Linux
- **قابلیت‌ها**:
  - تشخیص خودکار سیستم‌عامل
  - دریافت لیست جداول
  - دریافت داده‌های جدول با pagination
  - تست اتصال

### 5. CloudConnection.php
مدیریت اتصال به پایگاه داده ابری
- **پایگاه داده**: MySQL/MariaDB
- **قابلیت‌ها**:
  - ایجاد جداول خودکار
  - تشخیص نوع داده‌ها
  - درج batch با تراکنش
  - آمارگیری جداول

### 6. SyncManager.php
مدیریت فرآیند همگام‌سازی
- **قابلیت‌ها**:
  - همگام‌سازی تک جدول
  - همگام‌سازی چند جدول
  - همگام‌سازی تدریجی (Incremental)
  - ثبت تاریخچه همگام‌سازی
  - آمار و گزارش‌گیری
  - مانیتورینگ وضعیت

## ⚙️ فایل‌های تنظیمات

### config.json / config-mac.json
```json
{
    "sql_server": {
        "server": "123.123.1.2",
        "database": "DATABASE_NAME",
        "username": "sa",
        "password": "PASSWORD",
        "port": "1433",
        "connection_method": "odbc"
    },
    "cloud": {
        "host": "VPS_IP",
        "database": "reports_database",
        "username": "sync_user",
        "password": "PASSWORD",
        "port": "3306"
    },
    "settings": {
        "auto_sync_interval": 300,
        "batch_size": 1000,
        "max_execution_time": 300,
        "log_level": "info",
        "timezone": "Asia/Tehran"
    },
    "dashboard": {
        "items_per_page": 50,
        "refresh_interval": 30,
        "theme": "default"
    }
}
```

### odbc.ini
```ini
[ODBC Data Sources]
SQLServer = FreeTDS

[SQLServer]
Driver = /opt/homebrew/lib/libtdsodbc.so
Description = SQL Server via FreeTDS
Server = 123.123.1.2
Port = 1433
Database = DATABASE_NAME
TDS_Version = 8.0
```

## 🎨 رابط کاربری

### CSS Framework
- **فونت**: Vazirmatn از Google Fonts
- **طراحی**: Material Design + Glass Morphism
- **ویژگی‌ها**:
  - Responsive design
  - تم تاریک/روشن
  - انیمیشن‌های تعاملی
  - متغیرهای CSS سفارشی

### JavaScript Dashboard
- **معماری**: Class-based modules
- **قابلیت‌ها**:
  - API calls با fetch
  - Real-time updates
  - نمودارهای Chart.js
  - notification system
  - Theme switching

## 📡 API Endpoints (پیشنهادی)

```
GET  /api/dashboard/stats     - آمار داشبورد
GET  /api/config/get          - دریافت تنظیمات
POST /api/config/save         - ذخیره تنظیمات
GET  /api/tables/list         - لیست جداول
POST /api/sync/start          - شروع همگام‌سازی
GET  /api/sync/progress       - پیشرفت همگام‌سازی
GET  /api/sync/history        - تاریخچه همگام‌سازی
GET  /api/logs/recent         - لاگ‌های اخیر
POST /api/test/connection     - تست اتصالات
```

## 🚀 نصب و راه‌اندازی

### پیش‌نیازها
- PHP 7.4+ با پسوندهای PDO، ODBC
- SQL Server با TCP/IP فعال
- MySQL/MariaDB (برای cloud)
- FreeTDS (برای macOS/Linux)

### macOS
```bash
# نصب dependencies
brew install php unixodbc freetds

# راه‌اندازی
./setup-mac.sh
./start-mac.sh
```

### Windows
```cmd
# اطمینان از نصب ODBC drivers
# اجرای فایل PHP
php sync-service-odbc.php
```

## 🔧 توسعه و سفارشی‌سازی

### اضافه کردن ماژول جدید
1. کلاس جدید در `modules/` ایجاد کنید
2. از `BaseModule` ارث‌بری کنید
3. متدهای مورد نیاز را پیاده‌سازی کنید

### اضافه کردن API endpoint
1. در فایل اصلی route جدید اضافه کنید
2. متد handler در ماژول مربوطه ایجاد کنید
3. پاسخ JSON استاندارد برگردانید

## 🐛 رفع مشکلات

### مشکلات رایج
- **خطای ODBC**: بررسی درایورهای نصب شده
- **اتصال SQL Server**: فعال‌سازی TCP/IP
- **خطای مجوزها**: بررسی دسترسی فایل‌ها

### لاگ‌ها
- لاگ‌های روزانه در `logs/sync_YYYY-MM-DD.log`
- سطوح لاگ: ERROR, WARNING, INFO, DEBUG

## 📊 آمار و گزارشات

### قابلیت‌های گزارش‌گیری
- تعداد کل رکوردهای همگام‌سازی شده
- آمار روزانه/هفتگی/ماهانه
- وضعیت جداول
- زمان‌بندی همگام‌سازی
- خطاها و هشدارها

## 🔐 امنیت

### نکات امنیتی
- رمزهای عبور در فایل‌های تنظیمات
- محدودسازی دسترسی شبکه
- اعتبارسنجی ورودی‌ها
- لاگ‌گیری عملیات حساس

## 🌐 پشتیبانی چندزبانه

### زبان‌های پشتیبانی شده
- فارسی (RTL)
- انگلیسی (LTR)

### فرمت‌های تاریخ
- شمسی برای فارسی
- میلادی برای انگلیسی

---

## ℹ️ اطلاعات نسخه
- **نسخه**: 2.0 (Modular)
- **تاریخ**: 29 اگست 2025
- **نویسنده**: Saba Reporting System

این مستندات آخرین وضعیت پروژه تا تاریخ ایجاد را نشان می‌دهد.
