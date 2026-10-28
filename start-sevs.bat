@echo off
title IETI SEVS Launcher
cd /d "C:\Users\grego\Downloads\thesis\nasurada"

echo Starting Laravel server...
start "Laravel Server" cmd /k "php artisan serve --host=0.0.0.0 --port=8000"

echo Waiting for the server to start...
timeout /t 4 /nobreak >nul

start http://localhost:8000/login
exit