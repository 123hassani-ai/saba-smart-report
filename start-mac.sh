#!/bin/bash

# راه‌اندازی سرور PHP برای استفاده روی macOS
echo "🚀 Starting Saba Report System on macOS..."

# مسیر کنونی
CURRENT_DIR=$(pwd)

# تنظیم متغیرهای محیطی برای ODBC
export ODBCINI="${CURRENT_DIR}/odbc.ini"
export ODBCSYSINI="${CURRENT_DIR}"
export FREETDSCONF="${CURRENT_DIR}/freetds.conf"

# بررسی PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP not found. Please install PHP first."
    echo "Run './setup-mac.sh' to install dependencies."
    exit 1
fi

# آدرس سرور
HOST="localhost"
PORT="8000"

# اجرای سرور PHP
echo "✅ Starting PHP server on http://${HOST}:${PORT}"
echo "👉 Open your browser and navigate to http://${HOST}:${PORT}"
echo "👉 Press Ctrl+C to stop the server"
echo ""

php -S ${HOST}:${PORT} -t "$(pwd)"
