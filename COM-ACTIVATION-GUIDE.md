# 🛠️ راهنمای فعال‌سازی COM Extension در Windows 7

## مشکل: SQL Server قطع است
**علت:** COM Extension در PHP 7.4 غیرفعال است

## ✅ راه‌حل گام به گام:

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

### مرحله 4: تست
```cmd
php -r "echo class_exists('COM') ? 'COM Available' : 'COM Not Found';"
```

## 🎯 اگر فایل dll موجود نباشد:
- PHP extension directory را بررسی کنید:
  ```cmd
  php -r "echo PHP_EXTENSION_DIR;"
  ```
- فایل `php_com_dotnet.dll` باید در این پوشه باشد
- اگر نیست، آن را از نسخه‌ای از PHP 7.4 برای Windows کپی کنید

## ⚡ راه حل سریع:
اگر COM Extension فعال نشد، از Simple Dashboard استفاده کنید:
```
http://localhost:8080/windows-simple.php
```
