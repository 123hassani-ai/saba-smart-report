@echo off
chcp 65001 > nul
title Windows SQL Server Diagnostic Tool

echo ================================================
echo    Windows SQL Server Diagnostic Tool
echo    برای تشخیص مشکلات SQL Server در ویندوز
echo ================================================
echo.

:: Check Administrator privileges
net session >nul 2>&1
if %errorLevel% == 0 (
    echo ✅ Running with Administrator privileges.
) else (
    echo ⚠️ Warning: Not running as Administrator.
    echo    Some diagnostic tests may fail.
    echo    Please restart as Administrator for complete diagnostics.
    echo.
)

:: Check Windows version
echo Checking Windows version:
ver
echo.

:: Check PHP version and extensions
echo Checking PHP configuration:
php -r "echo 'PHP Version: ' . PHP_VERSION . PHP_EOL;" 2>nul
php -r "echo 'PHP Extensions: ' . implode(', ', get_loaded_extensions()) . PHP_EOL;" 2>nul
echo.

:: Check SQL Server services
echo Checking SQL Server services:
echo.
sc query MSSQLSERVER > nul 2>&1
if %errorlevel% equ 0 (
    echo SQL Server (MSSQLSERVER):
    sc query MSSQLSERVER | findstr STATE
) else (
    echo SQL Server (Named Instances):
    sc query | findstr "SQL" | findstr "RUNNING"
)

echo.
echo SQL Server Browser (required for named instances):
sc query SQLBrowser | findstr STATE
echo.

:: Check network connectivity
echo Checking SQL Server connectivity:
set /p server=Enter SQL Server name (default: localhost\SQLEXPRESS): 
if "%server%"=="" set server=localhost\SQLEXPRESS

echo.
echo Testing ping to %server%:
for /f "tokens=1 delims=\" %%a in ("%server%") do set servername=%%a
ping -n 2 %servername% | findstr "bytes"
echo.

:: Check SQL Server port
echo Checking SQL Server port (default 1433):
netstat -an | findstr :1433
echo.

:: Check firewall status
echo Checking Windows Firewall status:
netsh advfirewall show allprofiles state
echo.

:: Check PHP COM extension
echo Checking COM extension:
php -r "echo 'COM Extension: ' . (class_exists('COM') ? 'ENABLED' : 'DISABLED') . PHP_EOL;" 2>nul
echo.

:: Check PDO drivers
echo Checking PDO drivers:
php -r "echo 'PDO Drivers: ' . implode(', ', PDO::getAvailableDrivers()) . PHP_EOL;" 2>nul
echo.

:: Display connection guidance
echo ================================================
echo    Connection Troubleshooting Guide
echo ================================================
echo.
echo 1. If SQL Server service is not running:
echo    - Start SQL Server service: net start MSSQLSERVER
echo    - Or for named instance: net start MSSQL$INSTANCENAME
echo.
echo 2. If SQL Browser is not running (needed for named instances):
echo    - Start service: net start SQLBrowser
echo    - Make it automatic: sc config SQLBrowser start= auto
echo.
echo 3. If firewall is blocking connection:
echo    - Open SQL ports: 
echo      netsh advfirewall firewall add rule name="SQL Server" ^
echo      dir=in action=allow protocol=TCP localport=1433
echo.
echo 4. If COM extension is disabled:
echo    - Edit php.ini and uncomment: extension=com_dotnet
echo    - Or run: activate-com.bat
echo.
echo 5. Test connection using our tools:
echo    - activate-com.bat
echo    - test-windows.bat
echo    - windows-simple.php (browser)
echo.

echo ================================================
echo    Diagnostic Summary
echo ================================================
echo.
php -r "
try {
    // Check config file
    if (!file_exists('config.json') && !file_exists('config-windows.json')) {
        echo '❌ No configuration file found' . PHP_EOL;
        exit;
    }
    
    // Load config
    if (file_exists('config-windows.json')) {
        echo '✅ Found Windows config file' . PHP_EOL;
        \$config = json_decode(file_get_contents('config-windows.json'), true);
    } else {
        echo '✅ Found general config file' . PHP_EOL;
        \$config = json_decode(file_get_contents('config.json'), true);
    }
    
    // Check config structure
    if (!isset(\$config['sql_server'])) {
        echo '❌ SQL Server config section missing' . PHP_EOL;
        exit;
    }
    
    // Display config details
    \$sql = \$config['sql_server'];
    echo 'Server: ' . \$sql['server'] . PHP_EOL;
    echo 'Database: ' . \$sql['database'] . PHP_EOL;
    echo 'Connection Method: ' . (\$sql['connection_method'] ?? 'Not specified') . PHP_EOL;
    
    // Check COM availability
    if ((\$sql['connection_method'] ?? '') === 'com') {
        if (!class_exists('COM')) {
            echo '❌ COM extension required but not loaded' . PHP_EOL;
        } else {
            echo '✅ COM extension loaded' . PHP_EOL;
        }
    }
    
    echo PHP_EOL . 'Diagnostic complete.' . PHP_EOL;
    
} catch (Exception \$e) {
    echo '❌ Error during diagnostics: ' . \$e->getMessage() . PHP_EOL;
}
"

echo.
echo Press any key to exit...
pause > nul
