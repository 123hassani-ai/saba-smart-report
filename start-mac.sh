#!/bin/bash

# راه‌اندازی سرور PHP برای استفاده روی macOS
echo "🚀 Starting PHP Sync Service for macOS..."

# تنظیم متغیرهای محیطی برای ODBC
export ODBCINI="$(pwd)/odbc.ini"
export ODBCSYSINI="$(pwd)"

# نمایش اطلاعات اتصال
echo "📍 SQL Server IP: 123.123.1.2"
echo "📂 ODBC Config: $ODBCINI"
echo "🌐 Web Interface: http://localhost:8000"
echo ""

# راه‌اندازی سرور PHP
php -S 0.0.0.0:8000 sync-service-odbc.php
