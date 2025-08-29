# 🚨 GitHub Repository Transfer Guide

## مشکل: اکانت GitHub معلق است

متأسفانه در هنگام push کردن فایل‌ها، خطای زیر رخ داد:
```
remote: Your account is suspended. Please visit https://support.github.com
```

## 📋 راه‌حل‌های جایگزین:

### 1️⃣ حل مشکل GitHub (توصیه شده)
- به https://support.github.com مراجعه کنید
- درخواست رفع تعلیق اکانت دهید
- پس از حل مشکل، دستور زیر را اجرا کنید:

```bash
cd /Users/macminim4/MyApp/Saba-Rep/server-win
git push -u origin main
```

### 2️⃣ استفاده از GitHub دیگر
اگر اکانت دیگری دارید:

```bash
# تغییر remote URL:
git remote set-url origin https://github.com/NEW_USERNAME/saba-smart-report.git
git push -u origin main
```

### 3️⃣ استفاده از پلتفرم‌های دیگر

#### GitLab:
```bash
git remote set-url origin https://gitlab.com/USERNAME/saba-smart-report.git
git push -u origin main
```

#### Bitbucket:
```bash
git remote set-url origin https://bitbucket.org/USERNAME/saba-smart-report.git
git push -u origin main
```

### 4️⃣ ایجاد Archive برای انتقال دستی
```bash
# ایجاد فایل ZIP:
tar -czf saba-smart-report.tar.gz .

# یا در Windows:
# فولدر را به صورت ZIP فشرده کنید
```

## 📁 وضعیت فعلی پروژه

✅ تمام فایل‌های پروژه آماده شده‌اند:
- **47 فایل** commit شده
- **12,705 خط کد** اضافه شده
- **README.md** کامل نوشته شده
- **Git repository** آماده push

## 📊 لیست فایل‌های اصلی:

### 🖥️ رابط کاربری:
- `windows.php` - داشبورد کامل
- `windows-simple.php` - نسخه سازگار Windows 7
- `settings.php` - صفحه تنظیمات
- `windows-diagnostic.php` - ابزار تشخیص

### 🔧 ابزارهای توسعه:
- `sql-debug.php` - تشخیص SQL Server
- `quick-test.php` - تست سریع سیستم
- `sync-service-odbc.php` - کد مرجع

### 📋 فایل‌های Batch:
- `simple-start.bat` - شروع آسان
- `start-windows.bat` - منوی کامل
- `activate-com.bat` - فعال‌سازی COM
- `debug-sql.bat` - تشخیص SQL

### 📚 مستندات:
- `README.md` - راهنمای اصلی
- `COM-ACTIVATION-GUIDE.md` - راهنمای COM
- `SOLUTION-GUIDE.md` - حل مشکلات
- `README-Windows-Final.md` - مستندات کامل

## 🎯 مرحله بعدی:

1. **حل مشکل GitHub** (اولویت اول)
2. **Push فایل‌ها** پس از حل مشکل
3. **آماده‌سازی Release** اول پروژه

---

**نکته:** تمام فایل‌ها آماده هستند و Git repository کاملاً پیکربندی شده. فقط نیاز به حل مشکل GitHub دارید.

**📞 پشتیبانی GitHub:** https://support.github.com
