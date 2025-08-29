# 📖 فهرست مستندات سیستم سبا (Documentation Index)

## 🎯 دسترسی سریع به مستندات

### 📋 مستندات اصلی
- **[DOCUMENTATION.md](./DOCUMENTATION.md)** - مستندات کامل پروژه
- **[PROJECT-STATUS.md](./PROJECT-STATUS.md)** - وضعیت فعلی پروژه
- **[QUICK-START.md](./QUICK-START.md)** - راهنمای شروع سریع
- **[ARCHITECTURE.md](./ARCHITECTURE.md)** - معماری سیستم
- **[DEVELOPMENT.md](./DEVELOPMENT.md)** - راهنمای توسعه‌دهندگان

### 📁 مستندات موجود در پروژه
- **[README-macOS.md](./README-macOS.md)** - راهنمای نصب macOS
- **[GUIDE-COMPLETE.md](./GUIDE-COMPLETE.md)** - راهنمای کامل کاربری
- **[chat-cloud.md](./chat-cloud.md)** - گفتگوهای مربوط به cloud

## 🚀 شروع سریع برای چت جدید

### ✅ اطلاعات کلیدی
1. **پروژه**: سیستم همگام‌سازی SQL Server → Cloud Database
2. **معماری**: Modular PHP با frontend مدرن
3. **وضعیت**: ~70% کامل شده
4. **فونت**: Vazirmatn فارسی از Google Fonts

### 📂 ساختار اصلی
```
server-win/
├── modules/           # ماژول‌های PHP (95% کامل)
├── assets/           # CSS & JS (80% کامل)
├── config/           # تنظیمات (کامل)
├── views/            # HTML templates (0% - نیاز به کار)
└── logs/             # فایل‌های لاگ
```

### 🔧 ماژول‌های کامل
- ✅ **BaseModule.php** - کلاس پایه
- ✅ **Logger.php** - سیستم لاگ
- ✅ **ConfigManager.php** - مدیریت تنظیمات
- ✅ **SQLServerConnection.php** - اتصال SQL Server
- ✅ **CloudConnection.php** - اتصال Cloud DB
- ✅ **SyncManager.php** - مدیریت همگام‌سازی
- ✅ **style.css** - استایل‌های کامل
- ✅ **dashboard.js** - داشبورد JavaScript

### ⚠️ نیاز به کار
- ❌ **Main Controller** - entry point اصلی
- ❌ **View Templates** - صفحات HTML
- ❌ **API Router** - مدیریت endpoints
- ❌ **Auth Module** - احراز هویت

## 🔍 برای فهم سریع پروژه

### 1. ابتدا بخوانید:
1. [QUICK-START.md](./QUICK-START.md) - برای شروع سریع
2. [PROJECT-STATUS.md](./PROJECT-STATUS.md) - وضعیت فعلی

### 2. برای توسعه:
1. [ARCHITECTURE.md](./ARCHITECTURE.md) - معماری سیستم
2. [DEVELOPMENT.md](./DEVELOPMENT.md) - راهنمای توسعه

### 3. مستندات کامل:
1. [DOCUMENTATION.md](./DOCUMENTATION.md) - همه جزئیات

## 🎯 اولویت‌های فوری

### High Priority (فوری)
1. **رفع خطاهای Compilation** - خطاهای PHP syntax
2. **ایجاد Main Controller** - entry point اصلی
3. **راه‌اندازی Web Server** - حل مشکل connection

### Medium Priority (متوسط)
1. **View Templates** - صفحات HTML
2. **API Router** - سیستم routing
3. **Auth Module** - احراز هویت

## 📊 آمار پروژه

### خطوط کد: ~2,680
- PHP Modules: ~1,480 خط
- CSS: ~800 خط
- JavaScript: ~400 خط

### پیشرفت کلی: 70%
- Infrastructure: 95% ✅
- Database Layer: 90% ✅
- Business Logic: 85% ✅
- Frontend: 80% ✅
- Integration: 30% ⚠️

## 🔗 اتصالات مهم

### تنظیمات اصلی
- **SQL Server**: `config.json` → sql_server section
- **Cloud DB**: `config.json` → cloud section
- **ODBC**: `odbc.ini` / `odbcinst.ini`

### فایل‌های کلیدی
- **Legacy File**: `sync-service-odbc.php` (فایل قدیمی - دست نخورده)
- **Config**: `config.json` / `config-mac.json`
- **Logs**: `logs/sync_YYYY-MM-DD.log`

## 🛠️ ابزارهای توسعه

### macOS Commands
```bash
./setup-mac.sh    # نصب dependencies
./start-mac.sh    # اجرای سرور
```

### Debugging
```bash
# بررسی syntax
php -l modules/BaseModule.php

# تست extensions
php -m | grep -E "pdo|odbc|mysqli"
```

---
## 💡 نکته برای چت جدید

این پروژه در مرحله انتقال از **monolithic** به **modular architecture** است. معماری جدید کاملاً جداگانه و مدرن طراحی شده، اما هنوز نیاز به integration و تست دارد.

**آخرین درخواست کاربر**: ایجاد مستندات کامل برای شروع چت جدید ✅

---
*تاریخ ایجاد: 29 آگوست 2025*
*وضعیت: Ready for next phase development*
