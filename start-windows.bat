@echo off
chcp 65001 > nul
title Saba Reportingecho [1] 🌐 Simple Dashboard (Windows 7 Compatible)
echo [2] 🚀 Full Dashboard (Requires COM Extension)
echo [3] 🔧 Windows Diagnostic Tool  
echo [4] 🔍 SQL Server Debug Tool
echo [5] ⚡ Quick Connection Test
echo [6] 📊 System Status
echo [7] 📝 Edit Configuration
echo [8] 🛠️ Activate COM Extension
echo [9] 🚪 Exit
echo.
set /p choice=👉 Enter your choice (1-9): 

if "%choice%"=="1" goto SIMPLE
if "%choice%"=="2" goto FULL
if "%choice%"=="3" goto DIAG
if "%choice%"=="4" goto SQL_DEBUG
if "%choice%"=="5" goto TEST
if "%choice%"=="6" goto STATUS
if "%choice%"=="7" goto CONFIG
if "%choice%"=="8" goto ACTIVATE_COM
if "%choice%"=="9" goto EXITecho.
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
echo [1] 🌐 Simple Dashboard (Windows 7 Compatible)
echo [2] 🚀 Full Dashboard (Requires COM Extension)
echo [3] 🔧 Windows Diagnostic Tool  
echo [4] ⚡ Quick Connection Test
echo [5] 📊 System Status
echo [6] 📝 Edit Configuration
echo [7] �️ Activate COM Extension
echo [8] �🚪 Exit
echo.
set /p choice=👉 Enter your choice (1-8): 

if "%choice%"=="1" goto SIMPLE
if "%choice%"=="2" goto FULL
if "%choice%"=="3" goto DIAG
if "%choice%"=="4" goto TEST
if "%choice%"=="5" goto STATUS
if "%choice%"=="6" goto CONFIG
if "%choice%"=="7" goto ACTIVATE_COM
if "%choice%"=="8" goto EXIT

echo ❌ Invalid choice. Try again...
timeout /t 2 > nul
goto MENU

:SIMPLE
cls
echo.
echo 🌐 Starting Simple Dashboard (Windows 7 Compatible)...
echo.
echo 🔗 Dashboard URL: http://localhost:8080/windows-simple.php
echo.
echo ⚠️  Press Ctrl+C to stop server
echo.

REM Start browser first
start "" "http://localhost:8080/windows-simple.php"

REM Wait a moment
timeout /t 2 > nul

REM Start PHP server
php -S localhost:8080 -t .

REM When server stops, return to menu
echo.
echo Server stopped. Returning to menu...
timeout /t 3 > nul
goto MENU

:FULL
cls
echo.
echo 🚀 Starting Full Dashboard...
echo.
echo 🔗 Dashboard URL: http://localhost:8080/windows.php
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

:SQL_DEBUG
cls
echo.
echo 🔍 SQL Server Debug Tool...
echo.
call debug-sql.bat
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
echo 📝 Configuration Options...
echo.
echo [1] Open Settings Editor (Web Interface)
echo [2] Edit config.json in Notepad
echo [3] Reset to defaults
echo [4] Back to main menu
echo.
set /p configChoice=Choose option (1-4): 

if "%configChoice%"=="1" goto CONFIG_WEB
if "%configChoice%"=="2" goto CONFIG_NOTEPAD  
if "%configChoice%"=="3" goto CONFIG_RESET
if "%configChoice%"=="4" goto MENU

echo Invalid choice
timeout /t 2 > nul
goto CONFIG

:CONFIG_WEB
cls
echo.
echo 🌐 Opening Settings Editor...
echo.
start "" "http://localhost:8080/settings.php"
php -S localhost:8080 -t . > nul 2>&1 &
echo Settings editor opened in browser
echo.
pause
goto MENU

:CONFIG_NOTEPAD
cls
echo.
echo 📝 Opening Configuration in Notepad...
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
goto MENU

:CONFIG_RESET
cls
echo.
echo 🔄 Resetting configuration to defaults...
echo.
if exist "config-windows.json" (
    copy "config-windows.json" "config.json"
    echo ✅ Configuration reset to defaults
) else (
    echo ⚠️ Default config template not found
)
echo.
pause
goto MENU

:ACTIVATE_COM
cls
echo.
echo 🛠️ COM Extension Activation...
echo.
call activate-com.bat
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
