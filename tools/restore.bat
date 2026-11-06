@echo off
REM ============================================================
REM  MENTALITY - RESTORE
REM  Brings the database back to the state saved in a backup file.
REM  WARNING: everything currently in the database is replaced.
REM ============================================================
set MYSQLBIN=C:\xampp\mysql\bin
set DBNAME=mentality_db
set BACKUPDIR=%~dp0..\backups

echo Backups available:
dir /b "%BACKUPDIR%\*.sql"
echo.
set /p FILE=Type the backup file name to restore (example: mentality_20261023_141500.sql): 

if not exist "%BACKUPDIR%\%FILE%" (
    echo.
    echo File not found: %BACKUPDIR%\%FILE%
    pause
    exit /b 1
)

set /p SURE=This will REPLACE the current database. Type YES to continue: 
if /I not "%SURE%"=="YES" (
    echo Cancelled.
    pause
    exit /b 0
)

"%MYSQLBIN%\mysql.exe" -u root %DBNAME% < "%BACKUPDIR%\%FILE%"

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo RESTORE FAILED. Make sure the database "%DBNAME%" exists and MySQL is running.
) else (
    echo.
    echo Restore complete.
)
echo.
pause
