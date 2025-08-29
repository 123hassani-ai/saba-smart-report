@echo off
echo Starting troubleshooting...

REM Test PHP installation
echo.
echo Testing PHP...
php --version
if %errorlevel% neq 0 (
    echo ERROR: PHP not found in PATH
    echo Please install PHP or add it to system PATH
    pause
    exit /b 1
)

REM Test current directory
echo.
echo Current directory: %cd%
echo Checking files...
if exist "windows.php" (
    echo OK: windows.php found
) else (
    echo ERROR: windows.php not found
)

if exist "config.json" (
    echo OK: config.json found
) else (
    echo ERROR: config.json not found
)

if exist "modules" (
    echo OK: modules directory found
) else (
    echo ERROR: modules directory not found
)

REM Test PHP syntax
echo.
echo Testing PHP syntax...
php -l windows.php
if %errorlevel% neq 0 (
    echo ERROR: PHP syntax error
    pause
    exit /b 1
)

echo.
echo All checks passed. Starting application...
echo.

REM Run help command
php windows.php help

echo.
echo Press any key to continue...
pause
