# 🛠️ راهنمای فعال‌سازی COM Extension در Windows 7

## مشکل: اتصال به SQL Server قطع است
**علت:** COM Extension در PHP 7.4 غیرفعال است

## ✅ راه‌حل سریع: اجرای فایل کمکی

برای راه‌اندازی آسان و حل مشکلات، فایل `activate-com.bat` را اجرا کنید. این فایل:
- وضعیت COM Extension را بررسی می‌کند
- راهنمایی برای فعال‌سازی ارائه می‌دهد
- اتصال SQL Server را تست می‌کند
- سرویس‌های Windows مربوطه را بررسی می‌کند

```cmd
# اجرای فایل کمکی (از همین پوشه)
activate-com.bat
```

## ✅ راه‌حل گام به گام دستی:

### مرحله 1: پیدا کردن فایل php.ini
```cmd
# در Command Prompt اجرا کنید:
php --ini

# یا:
php -r "echo php_ini_loaded_file();"
```

### مرحله 2: ویرایش php.ini
1. فایل php.ini را با Notepad باز کنید
2. این خط را پیدا کنید:
   ```ini
   ;extension=com_dotnet
   ```
3. semicolon را حذف کنید:
   ```ini
   extension=com_dotnet
   ```

### مرحله 3: ذخیره و راه‌اندازی مجدد
1. فایل php.ini را ذخیره کنید
2. Command Prompt را ببندید
3. مجدداً باز کنید و تست کنید:
   ```cmd
   php -m | findstr -i com
   ```

### مرحله 4: تست COM Extension
```cmd
php -r "echo class_exists('COM') ? 'COM Available' : 'COM Not Found';"
```

## 🔍 عیب‌یابی مشکلات پیشرفته:

### مشکل 1: فایل DLL موجود نیست
- PHP extension directory را بررسی کنید:
  ```cmd
  php -r "echo PHP_EXTENSION_DIR;"
  ```
- فایل `php_com_dotnet.dll` باید در این پوشه باشد
- اگر نیست، از نسخه‌ای از PHP 7.4 برای Windows کپی کنید

### مشکل 2: SQL Server Browser فعال نیست
- برای نمونه‌های Named Instance، SQL Browser ضروری است
- در Command Prompt با حق Administrator:
  ```cmd
  sc start SQLBrowser
  sc config SQLBrowser start= auto
  ```

### مشکل 3: Firewall مسدود می‌کند
- باز کردن پورت‌های SQL Server:
  ```cmd
  netsh advfirewall firewall add rule name="SQL Server" dir=in action=allow protocol=TCP localport=1433
  netsh advfirewall firewall add rule name="SQL Browser" dir=in action=allow protocol=UDP localport=1434
  ```

## ⚡ راه حل جایگزین:
اگر COM Extension فعال نشد، از Simple Dashboard استفاده کنید:
```
http://localhost:8080/windows-simple.php
```
