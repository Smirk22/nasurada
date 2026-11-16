@echo off
setlocal
cd /d "%~dp0"

set "TASK_NAME=IETI SEVS Daily Backup"
set "BACKUP_SCRIPT=%~dp0backup-sevs.bat"

echo Creating a Windows Task Scheduler entry that runs a backup every day at 11:00 PM...

schtasks /Create /SC DAILY /ST 23:00 /TN "%TASK_NAME%" /TR "\"%BACKUP_SCRIPT%\"" /F

if errorlevel 1 (
    echo.
    echo Could not create the scheduled task.
    echo Open Task Scheduler and add it manually, or run this file as Administrator.
    pause
    exit /b 1
)

echo.
echo Done. The task "%TASK_NAME%" will run:
echo    "%BACKUP_SCRIPT%"
echo.
echo To test it now:  schtasks /Run /TN "%TASK_NAME%"
echo To remove it:    schtasks /Delete /TN "%TASK_NAME%" /F
echo.
pause
