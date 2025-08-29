@echo off
chcp 65001 > nul
title Saba Reporting System - Windows

echo.
echo ================================================
echo    🚀 Saba Reporting System - Windows
echo ================================================
echo.

REM Check PHP
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PHP not found in PATH
    echo Please install PHP and add to system PATH
    pause
    exit /b 1
)

echo ✅ PHP is available
php --version
echo.

REM Create directories
if not exist "logs" mkdir logs
if not exist "temp" mkdir temp
if not exist "config" mkdir config

REM Copy config if needed
if not exist "config.json" (
    if exist "config-windows.json" (
        copy "config-windows.json" "config.json"
        echo ✅ Config copied from template
    ) else (
        echo ❌ No config file found
        echo Creating basic config...
        echo {"sql_server":{"server":"localhost","database":"test"},"cloud":{"host":"localhost"}} > config.json
    )
)

:MENU
cls
echo.
echo ================================================
echo              🎯 MAIN MENU
echo ================================================
echo.
echo [1] 🌐 Start Web Dashboard
echo [2] 🔧 Windows Diagnostic Tool  
echo [3] ⚡ Quick Connection Test
echo [4] 📊 System Status
echo [5] 📝 Edit Configuration
echo [6] 🚪 Exit
echo.
set /p choice=👉 Enter your choice (1-6): 

if "%choice%"=="1" goto WEB
if "%choice%"=="2" goto DIAG
if "%choice%"=="3" goto TEST
if "%choice%"=="4" goto STATUS
if "%choice%"=="5" goto CONFIG
if "%choice%"=="6" goto EXIT

echo ❌ Invalid choice. Try again...
timeout /t 2 > nul
goto MENU

:WEB
cls
echo.
echo 🌐 Starting Web Dashboard...
echo.
echo 🔗 Dashboard URL: http://localhost:8080/windows.php
echo 🔗 Diagnostic URL: http://localhost:8080/windows-diagnostic.php
echo.
echo ⚠️  Press Ctrl+C to stop server
echo.

REM Start browser first
start "" "http://localhost:8080/windows.php"

REM Wait a moment
timeout /t 2 > nul

REM Start PHP server
php -S localhost:8080 -t .

REM When server stops, return to menu
echo.
echo Server stopped. Returning to menu...
timeout /t 3 > nul
goto MENU

:DIAG  
cls
echo.
echo 🔧 Running Diagnostic Tool...
echo.

REM Start diagnostic in background
start "" "http://localhost:8080/windows-diagnostic.php"

REM Start temporary server for diagnostic
echo Starting diagnostic server...
timeout /t 2 > nul
php -S localhost:8080 -t . > nul 2>&1 &

echo ✅ Diagnostic opened in browser
echo.
echo Press any key to return to menu...
pause > nul
goto MENU

:TEST
cls
echo.
echo ⚡ Quick Connection Test
echo ================================================
echo.

echo Testing PHP extensions...
php -r "
echo 'COM Extension: ' . (class_exists('COM') ? '✅ Available' : '❌ Not Available') . PHP_EOL;
echo 'PDO MySQL: ' . (class_exists('PDO') && in_array('mysql', PDO::getAvailableDrivers()) ? '✅ Available' : '❌ Not Available') . PHP_EOL;
echo 'JSON Support: ' . (function_exists('json_encode') ? '✅ Available' : '❌ Not Available') . PHP_EOL;
"

echo.
echo Testing config file...
if exist "config.json" (
    echo ✅ Config file exists
    php -r "
    \$config = json_decode(file_get_contents('config.json'), true);
    if (\$config) {
        echo '✅ Config file is valid JSON' . PHP_EOL;
        echo 'SQL Server: ' . \$config['sql_server']['server'] . PHP_EOL;
        echo 'Database: ' . \$config['sql_server']['database'] . PHP_EOL;
    } else {
        echo '❌ Config file has JSON errors' . PHP_EOL;
    }
    "
) else (
    echo ❌ Config file not found
)

echo.
echo Press any key to return to menu...
pause > nul
goto MENU

:STATUS
cls
echo.
echo 📊 System Status
echo ================================================
echo.

php -r "
echo '=== System Information ===' . PHP_EOL;
echo 'PHP Version: ' . PHP_VERSION . PHP_EOL;
echo 'Operating System: ' . PHP_OS . PHP_EOL;
echo 'Server API: ' . PHP_SAPI . PHP_EOL;
echo PHP_EOL;

echo '=== File Status ===' . PHP_EOL;
echo 'windows.php: ' . (file_exists('windows.php') ? '✅' : '❌') . PHP_EOL;
echo 'config.json: ' . (file_exists('config.json') ? '✅' : '❌') . PHP_EOL;
echo 'logs directory: ' . (is_dir('logs') ? '✅' : '❌') . PHP_EOL;
echo PHP_EOL;

echo '=== Extensions ===' . PHP_EOL;
\$extensions = ['com_dotnet', 'pdo', 'pdo_mysql', 'json', 'curl'];
foreach (\$extensions as \$ext) {
    echo \$ext . ': ' . (extension_loaded(\$ext) ? '✅' : '❌') . PHP_EOL;
}
"

echo.
echo Press any key to return to menu...
pause > nul
goto MENU

:CONFIG
cls
echo.
echo 📝 Opening Configuration File...
echo.

if exist "config.json" (
    echo Opening config.json in Notepad...
    notepad config.json
) else (
    echo Config file not found. Creating new one...
    if exist "config-windows.json" (
        copy "config-windows.json" "config.json"
        echo ✅ Created from template
        notepad config.json
    ) else (
        echo Creating basic config file...
        echo {> config.json
        echo   "sql_server": {>> config.json
        echo     "server": "localhost\\SQLEXPRESS",>> config.json  
        echo     "database": "YourDatabase",>> config.json
        echo     "username": "sa",>> config.json
        echo     "password": "YourPassword">> config.json
        echo   },>> config.json
        echo   "cloud": {>> config.json
        echo     "host": "your-server.com",>> config.json
        echo     "database": "cloud_db">> config.json
        echo   }>> config.json
        echo }>> config.json
        echo ✅ Basic config created
        notepad config.json
    )
)

echo.
echo Press any key to return to menu...
pause > nul
goto MENU

:EXIT
cls
echo.
echo ================================================
echo    👋 Thank you for using Saba System!
echo ================================================
echo.
timeout /t 3 > nul
exit /b 0
