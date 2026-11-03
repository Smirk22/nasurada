@echo off
setlocal
title IETI SEVS Launcher
cd /d "%~dp0"

set "APP_URL=http://localhost:8000/login"
set "PORT=8000"

netstat -ano | findstr /R /C:":%PORT% .*LISTENING" >nul
if %errorlevel%==0 (
    echo IETI SEVS is already running. Opening the system...
    start "" "%APP_URL%"
    exit /b 0
)

echo Starting IETI SEVS...
echo.

start "IETI SEVS Server" /D "%~dp0" cmd /k "php artisan serve --host=0.0.0.0 --port=%PORT%"

echo Waiting for the system to start...
powershell -NoProfile -Command "for ($i = 0; $i -lt 30; $i++) { try { $response = Invoke-WebRequest -Uri '%APP_URL%' -UseBasicParsing -TimeoutSec 1; if ($response.StatusCode -ge 200) { exit 0 } } catch {} Start-Sleep -Milliseconds 500 }; exit 1"

if errorlevel 1 (
    echo.
    echo The server did not start in time. Check the server window for errors.
    pause
    exit /b 1
)

start "" "%APP_URL%"
exit /b 0
