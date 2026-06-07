@echo off
setlocal

set "XAMPP_PATH=%~1"
set "APP_PATH=%~2"
set "DB_PORT=%~3"
set "DB_USER=%~4"
set "DB_PASS=%~5"

if "%XAMPP_PATH%"=="" exit /b 1
if "%APP_PATH%"=="" exit /b 1
if "%DB_PORT%"=="" set "DB_PORT=3306"
if "%DB_USER%"=="" set "DB_USER=root"

set "MYSQL_EXE=%XAMPP_PATH%\mysql\bin\mysql.exe"
set "SQL_FILE=%APP_PATH%\database\full_install.sql"
set "PS_UPDATE=%~dp0update_config.ps1"
set "CFG_FILE=%APP_PATH%\config\database.php"

if not exist "%MYSQL_EXE%" (
  echo [POSG Installer] mysql.exe not found: %MYSQL_EXE%
  exit /b 1
)

if not exist "%SQL_FILE%" (
  echo [POSG Installer] SQL file not found: %SQL_FILE%
  exit /b 1
)

if "%DB_PASS%"=="" (
  "%MYSQL_EXE%" --default-character-set=utf8mb4 -h127.0.0.1 -P%DB_PORT% -u%DB_USER% < "%SQL_FILE%"
) else (
  set "MYSQL_PWD=%DB_PASS%"
  "%MYSQL_EXE%" --default-character-set=utf8mb4 -h127.0.0.1 -P%DB_PORT% -u%DB_USER% < "%SQL_FILE%"
  set "MYSQL_PWD="
)

if errorlevel 1 (
  echo [POSG Installer] Database import failed.
  exit /b 1
)

powershell -NoProfile -ExecutionPolicy Bypass -File "%PS_UPDATE%" ^
  -ConfigPath "%CFG_FILE%" ^
  -Host "127.0.0.1" ^
  -Port "%DB_PORT%" ^
  -Database "posg" ^
  -Username "%DB_USER%" ^
  -Password "%DB_PASS%"

if errorlevel 1 (
  echo [POSG Installer] Database config update failed.
  exit /b 1
)

if not exist "%APP_PATH%\storage\logs" mkdir "%APP_PATH%\storage\logs" >nul 2>nul
if not exist "%APP_PATH%\public\uploads" mkdir "%APP_PATH%\public\uploads" >nul 2>nul

echo [POSG Installer] Completed successfully.
exit /b 0
