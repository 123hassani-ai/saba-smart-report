#!/bin/bash

echo "🚀 Setting up PHP Sync Service for macOS..."
echo "=========================================="

# رنگ‌ها برای خروجی زیبا
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# بررسی نصب بودن Homebrew
if ! command -v brew &> /dev/null; then
    echo -e "${RED}❌ Homebrew is not installed!${NC}"
    echo "Please install Homebrew first: https://brew.sh"
    exit 1
else
    echo -e "${GREEN}✅ Homebrew found${NC}"
fi

# بررسی نصب بودن PHP
if ! command -v php &> /dev/null; then
    echo -e "${YELLOW}⚠️ PHP not found, installing...${NC}"
    brew install php
else
    echo -e "${GREEN}✅ PHP found: $(php -v | head -n1)${NC}"
fi

# نصب dependencies
echo -e "${BLUE}📦 Installing ODBC dependencies...${NC}"
brew install unixodbc freetds

# بررسی وجود تنظیمات
if [ ! -f "config-mac.json" ]; then
    echo -e "${YELLOW}⚠️ Creating default config file...${NC}"
    cat > config-mac.json << EOF
{
    "sql_server": {
        "server": "123.123.1.2",
        "database": "YOUR_DATABASE_NAME",
        "username": "sa",
        "password": "YOUR_PASSWORD",
        "port": "1433",
        "connection_method": "odbc"
    },
    "cloud": {
        "host": "YOUR_VPS_IP",
        "database": "reports_database",
        "username": "sync_user",
        "password": "YOUR_MYSQL_PASSWORD",
        "port": "3306"
    },
    "settings": {
        "auto_sync_interval": 300,
        "batch_size": 1000,
        "max_execution_time": 300,
        "log_level": "info"
    }
}
EOF
    echo -e "${GREEN}✅ Config file created: config-mac.json${NC}"
    echo -e "${YELLOW}⚠️ Please edit config-mac.json with your database details!${NC}"
fi

# تنظیم ODBC
if [ ! -f "odbc.ini" ]; then
    echo -e "${YELLOW}⚠️ Creating ODBC configuration...${NC}"
    cat > odbc.ini << EOF
[ODBC Data Sources]
SQLServer = FreeTDS

[SQLServer]
Driver = /opt/homebrew/lib/libtdsodbc.so
Description = SQL Server via FreeTDS
Server = 123.123.1.2
Port = 1433
Database = YOUR_DATABASE_NAME
TDS_Version = 8.0

[Default]
Driver = /opt/homebrew/lib/libtdsodbc.so
EOF
    echo -e "${GREEN}✅ ODBC configuration created: odbc.ini${NC}"
    echo -e "${YELLOW}⚠️ Please edit odbc.ini with your database details!${NC}"
fi

# ایجاد directories مورد نیاز
mkdir -p logs temp config

# تنظیم مجوزها
chmod +x start-mac.sh

# IP address مک
MAC_IP=$(ifconfig | grep "inet " | grep -v 127.0.0.1 | awk '{print $2}' | head -n1)

echo ""
echo -e "${GREEN}🎉 Setup completed successfully!${NC}"
echo ""
echo -e "${BLUE}📝 Next steps:${NC}"
echo "1. Edit config-mac.json with your SQL Server details"
echo "2. Edit odbc.ini with your database information"  
echo "3. Run: ./start-mac.sh"
echo ""
echo -e "${BLUE}🌐 Access URLs:${NC}"
echo "- Local: http://localhost:8000"
if [ ! -z "$MAC_IP" ]; then
    echo "- Network: http://$MAC_IP:8000"
fi
echo ""
echo -e "${YELLOW}⚠️ Don't forget to configure your firewall to allow port 8000!${NC}"
