@echo off
title Rohith Portfolio Server
cd /d "%~dp0"
echo Starting Rohith Portfolio & Admin Server...
echo Public Site: http://localhost:3000/
echo Admin Panel: http://localhost:3000/admin
echo.
node server/index.js
pause
