@echo off
setlocal

set "RULE_NAME=IETI SEVS Port 8000"

net session >nul 2>&1
if errorlevel 1 goto elevate

netsh advfirewall firewall show rule name="%RULE_NAME%" >nul 2>&1
if errorlevel 1 goto add
echo Firewall rule already exists: %RULE_NAME%
goto done

:add
netsh advfirewall firewall add rule name="%RULE_NAME%" dir=in action=allow protocol=TCP localport=8000 profile=any remoteip=localsubnet
if errorlevel 1 goto failed
echo Added firewall rule: %RULE_NAME%
echo Devices on this network can now reach port 8000.
goto done

:failed
echo Failed to add the firewall rule.
goto done

:elevate
echo Requesting administrator privileges...
powershell -NoProfile -Command "Start-Process -FilePath '%~f0' -Verb RunAs"
exit /b 0

:done
pause
