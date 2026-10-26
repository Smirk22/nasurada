@echo off
title IETI SEVS Launcher
cd /d "C:\Users\grego\Downloads\thesis\nasurada"

echo Starting Laravel server...
start "Laravel Server" cmd /k "php artisan serve"

echo Waiting for the server to start...
timeout /t 5 /nobreak >nul

start http://localhost:8000/login
exit