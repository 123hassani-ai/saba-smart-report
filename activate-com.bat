@echo off
chcp 65001 > nul
title COM Extension Activation Helper - SQL Server Connection Tool

echo ================================================
echo    COM Extension Activation Helper
echo    SQL Server Connection Tool for Windows
echo ================================================
echo.

echo Checking PHP configuration...
php -r "echo 'PHP Version: ' . PHP_VERSION . PHP_EOL;" 2>nul
php -r "echo 'PHP INI File: ' . php_ini_loaded_file() . PHP_EOL;" 2>nul
php -r "echo 'Extension Dir: ' . PHP_EXTENSION_DIR . PHP_EOL;" 2>nul
echo.

echo.
echo Checking COM Extension...
for /f %%i in ('php -r "echo class_exists('COM') ? 'YES' : 'NO';" 2^>nul') do set COM_STATUS=%%i
echo COM Available: %COM_STATUS%

echo.
echo Checking SQL Server drivers...
php -r "echo 'PDO drivers: ' . implode(', ', PDO::getAvailableDrivers()) . PHP_EOL;" 2>nul
php -r "echo 'SQLSRV extension: ' . (extension_loaded('sqlsrv') ? 'YES' : 'NO') . PHP_EOL;" 2>nul
php -r "echo 'PDO_SQLSRV extension: ' . (extension_loaded('pdo_sqlsrv') ? 'YES' : 'NO') . PHP_EOL;" 2>nul

echo.
echo Checking DLL files...
for /f %%i in ('php -r "echo PHP_EXTENSION_DIR;" 2^>nul') do set EXT_DIR=%%i
if exist "%EXT_DIR%\php_com_dotnet.dll" (
    echo php_com_dotnet.dll: EXISTS
) else (
    echo php_com_dotnet.dll: NOT FOUND
)

echo.
echo ================================================
echo.

if "%COM_STATUS%"=="YES" (
    echo ✅ COM Extension is ALREADY ACTIVE!
    echo    Your SQL Server connection should work now.
    echo.
    echo    If still having issues:
    echo    - Check SQL Server is running
    echo    - Verify connection credentials
    echo    - Check Windows Firewall
    echo    - Ensure SQL Server Browser service is running
) else (
    echo ❌ COM Extension is NOT active
    echo.
    echo To activate COM Extension:
    echo 1. Open php.ini file
    echo 2. Find: ;extension=com_dotnet
    echo 3. Remove semicolon: extension=com_dotnet
    echo 4. Save and restart Command Prompt
)

echo.
echo ================================================

echo.
echo Menu Options:
echo [1] Open php.ini file
echo [2] Test SQL Server connection
echo [3] Check Windows services
echo [4] Diagnostic details
echo [5] Exit
echo.
set /p choice=Enter your choice (1-5): 

if "%choice%"=="1" goto OPEN_INI
if "%choice%"=="2" goto TEST_CONNECTION
if "%choice%"=="3" goto CHECK_SERVICES
if "%choice%"=="4" goto DIAGNOSTICS
if "%choice%"=="5" goto EXIT

echo Invalid choice
goto EXIT

:OPEN_INI
echo Opening php.ini...
for /f %%i in ('php -r "echo php_ini_loaded_file();" 2^>nul') do set INI_PATH=%%i
if exist "%INI_PATH%" (
    notepad "%INI_PATH%"
) else (
    echo php.ini file not found: %INI_PATH%
)
echo.
echo Press any key to return to the menu...
pause > nul
cls
goto :EOF

:TEST_CONNECTION
echo.
echo Testing SQL Server connection...
php -r "
try {
    if (!class_exists('COM')) {
        echo '❌ COM Extension not available' . PHP_EOL;
        exit;
    }
    
    echo '✅ COM Extension available' . PHP_EOL;
    
    // Load config
    if (!file_exists('config.json')) {
        echo '❌ config.json not found' . PHP_EOL;
        exit;
    }
    
    \$config = json_decode(file_get_contents('config.json'), true);
    if (!isset(\$config['sql_server'])) {
        echo '❌ SQL Server config not found' . PHP_EOL;
        exit;
    }
    
    \$sql = \$config['sql_server'];
    echo 'Server: ' . \$sql['server'] . PHP_EOL;
    echo 'Database: ' . \$sql['database'] . PHP_EOL;
    
    // Test connection
    \$conn = new COM('ADODB.Connection');
    \$connectionString = 'Provider=SQLOLEDB;Server=' . \$sql['server'] . ';Database=' . \$sql['database'] . ';';
    
    if (!empty(\$sql['username'])) {
        \$connectionString .= 'UID=' . \$sql['username'] . ';PWD=' . \$sql['password'] . ';';
    } else {
        \$connectionString .= 'Integrated Security=SSPI;';
    }
    
    echo 'Connection String: ' . \$connectionString . PHP_EOL;
    echo 'Attempting connection...' . PHP_EOL;
    
    \$conn->Open(\$connectionString);
    echo '🎉 SQL Server connection SUCCESS!' . PHP_EOL;
    \$conn->Close();
    
} catch (Exception \$e) {
    echo '❌ Connection failed: ' . \$e->getMessage() . PHP_EOL;
}
" 2>nul

echo.
echo Press any key to return to the menu...
pause > nul
cls
goto :EOF

:CHECK_SERVICES
echo.
echo Checking SQL Server related services...
echo.
echo SQL Server Services:
sc query MSSQLSERVER | findstr "STATE"
sc query SQLSERVERAGENT | findstr "STATE" 
sc query SQLBrowser | findstr "STATE"
echo.
echo SQL Server Browser is essential for named instances!
echo If service shows STOPPED, run this command as Administrator:
echo sc start SQLBrowser
echo.
echo Press any key to return to the menu...
pause > nul
cls
goto :EOF

:DIAGNOSTICS
echo.
echo ================================================
echo    Windows Diagnostic Information
echo ================================================
echo.
echo Checking System Environment:
systeminfo | findstr /B /C:"OS Name" /C:"OS Version" /C:"System Type"
echo.
echo Network Configuration:
ipconfig | findstr /B /C:"   IPv4 Address" /C:"   Default Gateway" /C:"   DNS Servers"
echo.
echo Firewall Status:
netsh advfirewall show allprofiles state
echo.
echo Checking SQL Server TCP Ports:
netstat -ano | findstr ":1433"
echo.
echo Run these commands if SQL Server port is blocked:
echo netsh advfirewall firewall add rule name="SQL Server" dir=in action=allow protocol=TCP localport=1433
echo netsh advfirewall firewall add rule name="SQL Browser" dir=in action=allow protocol=UDP localport=1434
echo.
echo Press any key to return to the menu...
pause > nul
cls
goto :EOF

:EXIT
echo.
echo Goodbye!
exit /b 0
