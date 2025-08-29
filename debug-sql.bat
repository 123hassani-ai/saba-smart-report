@echo off
chcp 65001 > nul
title SQL Server Debug Tool

echo ================================================
echo    🔍 SQL Server Connection Debug Tool
echo ================================================
echo.

echo Choose debug method:
echo [1] Web Interface (Recommended)
echo [2] Command Line
echo [3] Exit
echo.
set /p choice=Enter choice (1-3): 

if "%choice%"=="1" goto WEB_DEBUG
if "%choice%"=="2" goto CMD_DEBUG
if "%choice%"=="3" goto EXIT

echo Invalid choice
goto EXIT

:WEB_DEBUG
cls
echo.
echo 🌐 Starting SQL Server Debug in Web Browser...
echo.
echo This will:
echo - Check COM Extension status
echo - Validate configuration  
echo - Test SQL Server connection
echo - Generate detailed logs
echo.
echo 🔗 Debug URL: http://localhost:8080/sql-debug.php
echo.

REM Start browser first
start "" "http://localhost:8080/sql-debug.php"

REM Wait a moment
timeout /t 2 > nul

REM Start PHP server
echo Starting debug server...
php -S localhost:8080 -t .

echo.
echo Debug completed. Check the logs/ folder for detailed logs.
pause
goto EXIT

:CMD_DEBUG
cls
echo.
echo 🖥️ Running SQL Server Debug in Command Line...
echo.

php sql-debug.php

echo.
echo Debug completed. Check the logs/ folder for detailed logs.
pause
goto EXIT

:EXIT
echo.
echo Goodbye!
pause
