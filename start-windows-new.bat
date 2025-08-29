@echo off
chcp 65001 > nul
title Saba Reporting System for Windows

echo ================================================
echo    سیستم گزارش‌گیری سبا برای ویندوز
echo    Saba Reporting System for Windows
echo ================================================
echo.

:: Check PHP availability
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo ❌ PHP not found in PATH!
    echo    Please install PHP or add it to your PATH.
    echo.
    pause
    exit /b 1
)

:: Check PHP version
for /f "tokens=*" %%a in ('php -r "echo PHP_VERSION;"') do set php_version=%%a
echo PHP Version: %php_version%

:: Check COM Extension (critical for Windows)
for /f %%i in ('php -r "echo class_exists('COM') ? 'YES' : 'NO';"') do set COM_STATUS=%%i
echo COM Extension: %COM_STATUS%

if "%COM_STATUS%"=="NO" (
    echo.
    echo ⚠️ COM Extension is not enabled. SQL Server connection may fail.
    echo    Run activate-com.bat to fix this issue.
    echo.
    set /p activate=Run activate-com.bat now? (Y/N): 
    if /i "%activate%"=="Y" (
        call activate-com.bat
        exit /b 0
    )
)

:: Check for config file, copy if needed
if not exist "config.json" (
    if exist "config-windows.json" (
        echo.
        echo Creating config.json from Windows template...
        copy "config-windows.json" "config.json" >nul
        echo ✅ Configuration file created
    ) else (
        echo.
        echo ❌ No configuration files found!
        echo    Please create a config.json file.
        pause
        exit /b 1
    )
)

echo.
echo ================================================
echo.
echo Choose an option:
echo.
echo [1] Start Simple Dashboard (recommended for Windows)
echo [2] Start Full Dashboard (requires COM extension)
echo [3] Test SQL Server Connection
echo [4] Run Diagnostics
echo [5] Edit Configuration
echo [6] Exit
echo.

set /p choice=Enter your choice (1-6): 

if "%choice%"=="1" goto START_SIMPLE
if "%choice%"=="2" goto START_FULL
if "%choice%"=="3" goto TEST_CONNECTION
if "%choice%"=="4" goto RUN_DIAGNOSTICS
if "%choice%"=="5" goto EDIT_CONFIG
if "%choice%"=="6" goto EXIT

echo Invalid choice
goto EXIT

:START_SIMPLE
echo.
echo Starting PHP server with Simple Dashboard...
start http://localhost:8080/windows-simple.php
php -S localhost:8080
goto EXIT

:START_FULL
echo.
echo Starting PHP server with Full Dashboard...
start http://localhost:8080/windows.php
php -S localhost:8080
goto EXIT

:TEST_CONNECTION
echo.
echo Testing SQL Server Connection...
php test-windows.bat
goto EXIT

:RUN_DIAGNOSTICS
echo.
echo Running Windows Diagnostics...
call windows-diagnostic.bat
goto EXIT

:EDIT_CONFIG
echo.
echo Opening configuration file...
notepad config.json
goto EXIT

:EXIT
echo.
echo Goodbye!
exit /b 0
