# 📊 Saba Smart Report System

**سیستم هوشمند گزارشگیری سبا - نسخه ویندوز**

یک سیستم همگام‌سازی داده‌های پیشرفته برای انتقال داده‌ها از SQL Server به Cloud Database با رابط کاربری مدرن.

## 🎯 ویژگی‌های اصلی

- ✅ **همگام‌سازی خودکار** SQL Server به Cloud MySQL
- ✅ **رابط وب مدرن** با طراحی Glass Morphism
- ✅ **پشتیبانی Windows 7/10/11** با COM Extension
- ✅ **ابزارهای تشخیص پیشرفته** برای عیب‌یابی
- ✅ **صفحه تنظیمات کامل** با امکان ویرایش آنلاین
- ✅ **لاگ‌گیری دقیق** و نمایش وضعیت لحظه‌ای
- ✅ **پشتیبانی فونت فارسی** Vazirmatn

## 📋 پیش‌نیازها

### سیستم عامل
- Windows 7, 8, 10, 11 (32-bit یا 64-bit)

### نرم‌افزارها
- **PHP 7.4+** (توصیه: PHP 8.0+)
- **SQL Server** (Express, Standard, Enterprise)
- **MySQL/MariaDB** (برای Cloud Database)

### PHP Extensions مورد نیاز
- `com_dotnet` (برای اتصال SQL Server در ویندوز)
- `pdo_mysql` (برای اتصال Cloud Database)
- `json` (پردازش تنظیمات)
- `curl` (ارتباطات HTTP)

## 🚀 نصب و راه‌اندازی

### گام 1: دانلود پروژه
```bash
git clone https://github.com/123hassani-ai/saba-smart-report.git
cd saba-smart-report
```

### گام 2: راه‌اندازی سریع
```batch
# در Windows Command Prompt:
simple-start.bat
```

### گام 3: راه‌اندازی کامل
```batch
# برای دسترسی به همه امکانات:
start-windows.bat
```

## 📁 ساختار پروژه

```
saba-smart-report/
├── 📱 رابط کاربری
│   ├── windows.php              # داشبورد کامل
│   ├── windows-simple.php       # داشبورد سازگار با Windows 7
│   ├── windows-diagnostic.php   # ابزار تشخیص سیستم
│   └── settings.php            # صفحه ویرایش تنظیمات
│
├── 🔧 ابزارهای تشخیص
│   ├── sql-debug.php           # تشخیص مشکلات SQL Server
│   ├── quick-test.php          # تست سریع سیستم
│   └── sync-service-odbc.php   # نسخه ODBC (مرجع)
│
├── 🎛️ فایل‌های Batch
│   ├── simple-start.bat        # شروع ساده
│   ├── start-windows.bat       # منوی کامل
│   ├── activate-com.bat        # فعال‌سازی COM Extension
│   └── debug-sql.bat          # تشخیص SQL Server
│
├── ⚙️ پیکربندی
│   ├── config.json            # تنظیمات فعال
│   ├── config-windows.json    # تنظیمات پیش‌فرض ویندوز
│   └── config-mac.json        # تنظیمات macOS
│
├── 🔄 ماژول‌های سیستم
│   ├── modules/BaseModule.php  # کلاس پایه
│   ├── modules/Logger.php      # سیستم لاگ
│   └── modules/config/        # مدیریت تنظیمات
│
├── 📋 مستندات
│   ├── README.md              # راهنمای اصلی
│   ├── COM-ACTIVATION-GUIDE.md # راهنمای COM Extension
│   ├── SOLUTION-GUIDE.md      # راهنمای حل مشکلات
│   └── README-Windows-Final.md # مستندات کامل ویندوز
│
└── 📊 لاگ‌ها و فایل‌های موقت
    ├── logs/                  # فایل‌های لاگ
    └── temp/                  # فایل‌های موقت
```

## 🔧 راهنمای استفاده

### 1️⃣ راه‌اندازی سریع (توصیه شده)
```batch
simple-start.bat
# سپس گزینه 1: Start Simple Dashboard
```

### 2️⃣ تشخیص مشکلات
```batch
start-windows.bat
# سپس گزینه 4: SQL Server Debug Tool
```

### 3️⃣ ویرایش تنظیمات
```batch
# از داشبورد وب:
http://localhost:8080/settings.php
```

### 4️⃣ فعال‌سازی COM Extension
```batch
activate-com.bat
```

## ⚙️ پیکربندی

### نمونه فایل config.json
```json
{
    "sql_server": {
        "server": "localhost\\SQLEXPRESS",
        "database": "YourDatabase", 
        "username": "sa",
        "password": "YourPassword",
        "port": "1433",
        "connection_method": "com"
    },
    "cloud": {
        "host": "your-cloud-server.com",
        "database": "reports_database",
        "username": "sync_user", 
        "password": "YourCloudPassword",
        "port": "3306"
    },
    "settings": {
        "auto_sync_interval": 300,
        "batch_size": 1000,
        "max_execution_time": 300,
        "log_level": "info",
        "timezone": "Asia/Tehran"
    }
}
```

## 🐛 عیب‌یابی رایج

### COM Extension فعال نیست
```ini
# در php.ini:
extension=com_dotnet
```

### SQL Server متصل نمی‌شود
1. بررسی سرویس SQL Server
2. فعال‌سازی TCP/IP Protocol
3. بررسی Windows Firewall
4. استفاده از SQL Server Debug Tool

### خطاهای PDO MySQL
```ini
# در php.ini - حذف تکراری:
extension=pdo_mysql
```

## 📊 نمونه‌های کاربردی

### اجرای همگام‌سازی دستی
```php
<?php
require_once 'sync-service-odbc.php';
$sync = new PHPSyncServiceODBC();
$result = $sync->performSync();
?>
```

### دریافت اطلاعات از API
```bash
curl http://localhost:8080/windows.php?action=status
curl http://localhost:8080/windows.php?action=config
```

## 📈 نمونه خروجی

### داشبورد وب
- 🗄️ وضعیت SQL Server (متصل/قطع)
- ☁️ وضعیت Cloud Database (متصل/قطع)
- 📊 آمار جداول و رکوردها
- 🔄 وضعیت آخرین همگام‌سازی

### لاگ‌ها
```
[2025-08-29 09:15:30] [INFO] ✅ SQL Server connected via COM
[2025-08-29 09:15:31] [INFO] ✅ Cloud Database connected successfully
[2025-08-29 09:15:32] [INFO] 🔄 Starting sync process...
[2025-08-29 09:15:35] [INFO] 📊 Synced 1,250 records successfully
```

## 🎨 طراحی UI

### ویژگی‌های طراحی
- **Glass Morphism Effect** - شفافیت و blur background
- **فونت فارسی Vazirmatn** - خوانایی عالی
- **Gradient Backgrounds** - طراحی مدرن
- **Responsive Design** - سازگار با همه اندازه‌ها
- **Dark Theme** - راحتی بیشتر برای چشم

## 🔐 امنیت

### محافظت از داده‌ها
- ✅ ماسک کردن رمز عبور در API
- ✅ عدم نمایش اطلاعات حساس در لاگ
- ✅ استفاده از Windows Authentication (اختیاری)
- ✅ رمزنگاری اتصالات SSL

## 🚀 عملکرد

### بهینه‌سازی‌ها
- **Batch Processing** - پردازش دسته‌ای داده‌ها
- **Connection Pooling** - استفاده بهینه از اتصالات
- **Memory Management** - مدیریت حافظه
- **Error Recovery** - بازیابی خودکار از خطا

## 📞 پشتیبانی

### فایل‌های کمکی
- `COM-ACTIVATION-GUIDE.md` - راهنمای COM Extension
- `SOLUTION-GUIDE.md` - حل مشکلات رایج  
- `README-Windows-Final.md` - مستندات کامل

### ابزارهای تشخیص
- `windows-diagnostic.php` - تشخیص سیستم
- `sql-debug.php` - تشخیص SQL Server
- `activate-com.bat` - فعال‌سازی COM

## 📝 تاریخچه نسخه‌ها

### نسخه 1.2.0 (فعلی)
- ✅ رفع خطای Fatal Error
- ✅ طراحی مدرن UI
- ✅ ابزار تشخیص پیشرفته  
- ✅ صفحه تنظیمات کامل
- ✅ پشتیبانی Windows 7

### نسخه آینده 1.3.0
- 🔄 همگام‌سازی زمان‌بندی شده
- 📊 گزارشات پیشرفته
- 🔔 اطلاع‌رسانی‌های لحظه‌ای
- 🌐 پشتیبانی چند زبانه

## 🤝 مشارکت

برای مشارکت در پروژه:
1. Fork کنید
2. شاخه جدید ایجاد کنید
3. تغییرات خود را commit کنید
4. Pull Request ارسال کنید

## 📄 مجوز

این پروژه تحت مجوز MIT منتشر شده است.

## 👨‍💻 توسعه‌دهندگان

- **Saba Development Team**
- **GitHub:** https://github.com/123hassani-ai

---

**🎯 هدف:** ارائه بهترین تجربه همگام‌سازی داده‌ها در ویندوز

**📧 تماس:** برای پشتیبانی، Issue جدید در GitHub ایجاد کنید.
