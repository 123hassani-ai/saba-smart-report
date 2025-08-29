# 🖥️ راهنمای اجرای برنامه روی macOS

## پیش‌نیازها:
1. **PHP**: نصب شده ✅
2. **Homebrew**: نصب شده ✅ 
3. **ODBC & FreeTDS**: نصب شده ✅

## 🔧 تنظیمات:

### 1️⃣ **ویرایش فایل تنظیمات:**
فایل `config-mac.json` را ویرایش کرده و اطلاعات SQL Server خود را وارد کنید:

```json
{
    "sql_server": {
        "server": "123.123.1.2",
        "database": "نام_دیتابیس_شما",
        "username": "sa",
        "password": "رمز_عبور_شما",
        "port": "1433",
        "connection_method": "odbc"
    }
}
```

### 2️⃣ **ویرایش فایل ODBC:**
فایل `odbc.ini` را ویرایش کنید:

```ini
[ODBC Data Sources]
SQLServer = FreeTDS

[SQLServer]
Driver = /opt/homebrew/lib/libtdsodbc.so
Description = SQL Server via FreeTDS
Server = 123.123.1.2
Port = 1433
Database = نام_دیتابیس_شما
TDS_Version = 8.0
```

## 🚀 **راه‌اندازی:**

### روش آسان:
```bash
./start-mac.sh
```

### روش دستی:
```bash
export ODBCINI="$(pwd)/odbc.ini"
export ODBCSYSINI="$(pwd)"
php -S 0.0.0.0:8000 sync-service-odbc.php
```

## 🌐 **دسترسی:**
- **از مک خودتان**: http://localhost:8000
- **از سایر دستگاه‌ها در شبکه**: http://IP_MAC_SHOMA:8000

## 🔍 **تشخیص عیب:**

### مشکل اتصال ODBC:
```bash
# تست اتصال ODBC
isql -v SQLServer username password
```

### بررسی درایورهای موجود:
```bash
odbcinst -q -d
```

### تست اتصال از PHP:
```bash
php -r "print_r(PDO::getAvailableDrivers());"
```

## 📊 **مزایای اجرا روی macOS:**
- ✅ رابط کاربری بهتر
- ✅ کارایی بالاتر
- ✅ امنیت بیشتر
- ✅ مدیریت آسان‌تر
- ✅ نصب آسان dependencies

## 🔐 **نکات امنیتی:**
1. **فایروال**: پورت 8000 را فقط برای شبکه محلی باز کنید
2. **VPN**: برای دسترسی از بیرون از شبکه
3. **SSL**: برای امنیت بیشتر HTTPS فعال کنید

## ⚡ **بهینه‌سازی کارایی:**
- از **php-fpm** بجای built-in server استفاده کنید
- **Redis** یا **Memcached** برای کش
- **nginx** یا **Apache** در production
