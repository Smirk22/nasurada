@echo off
cd /d "%~dp0"
php artisan backup:run
if errorlevel 1 (
    echo.
    echo The backup failed. See the message above.
    pause
    exit /b 1
)
exit /b 0
