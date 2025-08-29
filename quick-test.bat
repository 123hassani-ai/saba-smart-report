@echo off
echo Saba System - Quick Test
echo =========================

REM Change to script directory
cd /d "%~dp0"
echo Current directory: %cd%
echo.

REM Test PHP
echo Testing PHP...
php --version
if %errorlevel% neq 0 (
    echo ERROR: PHP not found
    pause
    exit /b 1
)
echo PHP: OK
echo.

REM Check main file
if exist "windows.php" (
    echo windows.php: Found
) else (
    echo ERROR: windows.php not found
    pause
    exit /b 1
)

REM Test PHP syntax
echo Testing PHP syntax...
php -l windows.php
if %errorlevel% neq 0 (
    echo ERROR: PHP syntax error
    pause
    exit /b 1
)
echo Syntax: OK
echo.

REM Run help command
echo Running help command...
php windows.php help

echo.
echo Test completed successfully!
pause
