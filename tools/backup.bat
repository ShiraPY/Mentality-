@echo off
REM ============================================================
REM  MENTALITY - BACKUP
REM  Double-click this file. It saves a full copy of the database
REM  (tables, data, views, procedures, functions, triggers) into the
REM  "backups" folder, named with today's date and time.
REM ============================================================
set MYSQLBIN=C:\xampp\mysql\bin
set DBNAME=mentality_db
set BACKUPDIR=%~dp0..\backups

if not exist "%BACKUPDIR%" mkdir "%BACKUPDIR%"

for /f %%I in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd_HHmmss"') do set STAMP=%%I
set OUTFILE=%BACKUPDIR%\mentality_%STAMP%.sql

"%MYSQLBIN%\mysqldump.exe" -u root --routines --triggers --single-transaction --result-file="%OUTFILE%" %DBNAME%

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo BACKUP FAILED. Is MySQL running in the XAMPP Control Panel? Is the path above correct?
) else (
    echo.
    echo Backup saved to: %OUTFILE%
)
echo.
pause
