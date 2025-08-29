@echo off
title Saba System - Simple

echo ================================================
echo    Saba Reporting System - Windows 
echo ================================================

REM Test PHP
echo.
echo Testing PHP...
php --version
if %errorlevel% neq 0 (
    echo ERROR: PHP not found
    pause
    exit /b 1
)

REM Run quick test  
echo.
echo Running system test...
php quick-test.php

echo.
echo ================================================

:MENU
echo.
echo Select an option:
echo [1] Start Simple Dashboard (Windows 7 Compatible)
echo [2] Start Full Dashboard (Requires COM)  
echo [3] Open Configuration
echo [4] Exit
echo.
set /p choice=Enter choice (1-4): 

if "%choice%"=="1" goto SIMPLE
if "%choice%"=="2" goto FULL
if "%choice%"=="3" goto CONFIG
if "%choice%"=="4" goto EXIT

echo Invalid choice
goto MENU

:SIMPLE
echo.
echo Starting simple web dashboard...
echo Open: http://localhost:8080/windows-simple.php
echo.
start "" "http://localhost:8080/windows-simple.php"
php -S localhost:8080 -t .
goto MENU

:FULL
echo.
echo Starting full web dashboard...
echo Open: http://localhost:8080/windows.php
echo.
start "" "http://localhost:8080/windows.php"
php -S localhost:8080 -t .
goto MENU

:CONFIG
echo.
echo Opening config file...
notepad config.json
goto MENU

:EXIT
echo.
echo Goodbye!
pause
exit /b 0
