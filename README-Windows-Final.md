# 🚀 Saba Reporting System - Windows Edition

## 🎯 خلاصه مشکلات حل شده

### ✅ مشکل 1: خطای Fatal Error
**مشکل:** `Call to a member function query() on null`
**حل:** اضافه کردن بررسی null برای اتصال SQL Server

### ✅ مشکل 2: رابط کاربری ضعیف
**مشکل:** ظاهر نامناسب و طراحی قدیمی
**حل:** طراحی مدرن با CSS پیشرفته و فونت فارسی

### 🔧 مشکل 3: اتصال SQL Server (در حال حل)
**مشکل:** مشکلات اتصال به SQL Server در Windows
**حل:** ابزار تشخیص و پیکربندی بهینه

---

## 🛠️ نصب و راه‌اندازی

### پیش‌نیازها
1. **PHP 8.0+** با extensions زیر:
   - `php_com_dotnet.dll` (برای COM Object)
   - `php_pdo_mysql.dll` (برای MySQL)
   - `php_pdo_sqlsrv.dll` (اختیاری)

2. **SQL Server** (LocalDB, Express، یا Full)
3. **Windows 10/11**

### مراحل نصب

#### 1️⃣ راه‌اندازی سریع
```batch
# دانلود و اجرا
start-windows.bat
```

#### 2️⃣ تنظیم دستی

**الف) فعال‌سازی COM Extension**
```ini
# در php.ini
extension=php_com_dotnet.dll
```

**ب) پیکربندی SQL Server**
```json
{
    "sql_server": {
        "server": "localhost\\SQLEXPRESS",
        "database": "SabaDB",
        "username": "",
        "password": "",
        "connection_method": "com",
        "integrated_security": true
    }
}
```

---

## 🔧 ابزار تشخیص

### استفاده از Diagnostic Tool
```
http://localhost:8080/windows-diagnostic.php
```

**ویژگی‌های تشخیص:**
- ✅ بررسی COM Extension
- ✅ تست اتصال SQL Server  
- ✅ بررسی درایورهای PDO
- ✅ اطلاعات سیستم

---

## 📋 منوی اصلی (start-windows.bat)

### گزینه‌های در دسترس:
1. **🌐 Start Web Dashboard** - اجرای وب‌سایت
2. **🔧 Run Windows Diagnostic** - تشخیص مشکلات
3. **⚡ Quick Connection Test** - تست سریع اتصال
4. **📊 View System Status** - وضعیت سیستم
5. **📝 Edit Configuration** - ویرایش تنظیمات
6. **🚪 Exit** - خروج

---

## 🐛 رفع مشکلات رایج

### مشکل: COM Extension فعال نیست
```ini
# در php.ini
extension_dir = "C:\php\ext"
extension=php_com_dotnet.dll
```

### مشکل: SQL Server در دسترس نیست
```bash
# بررسی سرویس SQL Server
services.msc → SQL Server (SQLEXPRESS)
```

### مشکل: خطای اتصال
1. بررسی TCP/IP Protocol در SQL Server Configuration Manager
2. فعال‌سازی SQL Server Browser
3. بررسی Windows Firewall

---

## 📁 ساختار پروژه

```
server-win/
├── windows.php              # نقطه ورود اصلی
├── windows-diagnostic.php   # ابزار تشخیص
├── start-windows.bat       # منوی راه‌اندازی
├── config-windows.json     # تنظیمات ویندوز
├── config.json            # تنظیمات فعال
├── modules/               # ماژول‌های سیستم
│   ├── BaseModule.php    
│   ├── config/
│   ├── database/
│   └── sync/
├── logs/                 # فایل‌های لاگ
├── temp/                 # فایل‌های موقت
└── views/               # قالب‌ها
```

---

## 🎨 ویژگی‌های رابط کاربری

### طراحی مدرن
- ✨ Glass Morphism Effect
- 🎨 Gradient Backgrounds  
- 🔤 فونت فارسی Vazirmatn
- 📱 Responsive Design
- 🌙 Dark Theme

### کارت‌های اطلاعاتی
- 🗄️ وضعیت SQL Server
- ☁️ اتصال Cloud MySQL
- 🔄 آخرین همگام‌سازی
- 📊 آمار عملکرد

---

## ⚡ API Endpoints

### دریافت تنظیمات
```
GET /windows.php?action=config
```

### بررسی وضعیت
```  
GET /windows.php?action=status
```

### تست اتصال
```
GET /windows.php?action=test
```

---

## 📝 لاگ‌ها و خطایابی

### فایل‌های لاگ
```
logs/sync_YYYY-MM-DD.log
```

### سطح لاگ
- `ERROR`: خطاهای حیاتی
- `WARNING`: هشدارها  
- `INFO`: اطلاعات عمومی
- `DEBUG`: جزئیات تکنیکی

---

## 🔒 امنیت

### محافظت از رمز عبور
- ماسک کردن رمز در API
- عدم ذخیره plain text
- استفاده از Windows Authentication

### دسترسی‌ها
- محدودیت IP محلی
- احراز هویت Windows
- Encrypted connections

---

## 🚀 بهینه‌سازی عملکرد

### تنظیمات PHP
```ini
memory_limit = 512M
max_execution_time = 300
post_max_size = 100M
upload_max_filesize = 100M
```

### تنظیمات SQL Server
```sql
-- فعال‌سازی TCP/IP
EXEC sp_configure 'remote access', 1;
RECONFIGURE;
```

---

## 📞 پشتیبانی

### مشکلات رایج
1. **COM Extension** - بررسی php.ini
2. **SQL Server Connection** - استفاده از Diagnostic Tool
3. **Permission Errors** - اجرا به عنوان Administrator

### ابزارهای کمکی
- `windows-diagnostic.php` - تشخیص خودکار
- `config-windows.json` - تنظیمات پیش‌فرض
- `start-windows.bat` - منوی تعاملی

---

## 🔄 بروزرسانی

### نسخه فعلی: 1.2.0
- ✅ رفع خطای Fatal Error
- ✅ طراحی مدرن UI  
- ✅ ابزار تشخیص Windows
- ✅ منوی تعاملی Batch

### آینده (v1.3.0)
- 🔄 Automatic Sync Scheduling
- 📊 Advanced Reporting
- 🔔 Real-time Notifications

---

**تهیه شده توسط:** Saba Development Team  
**تاریخ:** آذر 1403  
**پلتفرم:** Windows 10/11 + PHP 8.0+
