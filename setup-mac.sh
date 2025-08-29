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
brew install unixodbc freetds php-pdo_dblib

# بررسی وجود extension های مورد نیاز PHP
echo -e "${BLUE}🔍 Checking PHP extensions...${NC}"
if php -m | grep -q "pdo_dblib"; then
    echo -e "${GREEN}✅ PHP PDO DBLib extension found${NC}"
else
    echo -e "${YELLOW}⚠️ PHP PDO DBLib extension not found, trying to install...${NC}"
    brew install php-pdo_dblib
    echo -e "${YELLOW}⚠️ You may need to manually enable pdo_dblib in php.ini${NC}"
fi

# تنظیم فایل های ODBC
echo -e "${BLUE}📝 Setting up ODBC configuration...${NC}"
cat > odbcinst.ini << EOF
[FreeTDS]
Description = FreeTDS Driver
Driver = $(brew --prefix)/lib/libtdsodbc.so
Setup = $(brew --prefix)/lib/libtdsodbc.so
UsageCount = 1
EOF

cat > odbc.ini << EOF
[ODBC Data Sources]
SQLServer = FreeTDS

[SQLServer]
Driver = $(brew --prefix)/lib/libtdsodbc.so
Description = SQL Server via FreeTDS
Server = C123-SRV
Port = 1433
Database = Saba404
TDS_Version = 8.0
Uid = sa
Pwd = 555

[Default]
Driver = $(brew --prefix)/lib/libtdsodbc.so
EOF
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
