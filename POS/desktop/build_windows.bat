@echo off
echo === GharibPOS Windows Local Packager ===

set "DESKTOP_DIR=%~dp0"
set "DIST_DIR=%DESKTOP_DIR%dist"
set "APP_OUT_DIR=%DIST_DIR%\GharibPOS-win32-x64"
set "APP_DIR=%APP_OUT_DIR%\resources\app"

echo Cleaning previous builds...
if exist "%APP_OUT_DIR%" rmdir /s /q "%APP_OUT_DIR%"
mkdir "%APP_OUT_DIR%"

echo Copying Electron binaries from node_modules...
if not exist "%DESKTOP_DIR%node_modules\electron\dist" (
    echo Error: node_modules/electron/dist not found. Please run "npm install" first.
    exit /b 1
)
xcopy /e /i /y "%DESKTOP_DIR%node_modules\electron\dist" "%APP_OUT_DIR%"

echo Renaming executable...
rename "%APP_OUT_DIR%\electron.exe" "GharibPOS.exe"

echo Generating and applying custom icon...
node "%DESKTOP_DIR%apply_icon.js"

echo Packaging application files...
mkdir "%APP_DIR%"
copy "%DESKTOP_DIR%main.js" "%APP_DIR%\"
copy "%DESKTOP_DIR%preload.js" "%APP_DIR%\"
copy "%DESKTOP_DIR%package.json" "%APP_DIR%\"
copy "%DESKTOP_DIR%loading_php.html" "%APP_DIR%\"
if exist "%DESKTOP_DIR%icon.png" (
    copy "%DESKTOP_DIR%icon.png" "%APP_DIR%\"
)

echo Packaging PHP backend application...
set "RESOURCES_DIR=%APP_OUT_DIR%\resources"
xcopy /e /i /y "%DESKTOP_DIR%..\app" "%RESOURCES_DIR%\app"
xcopy /e /i /y "%DESKTOP_DIR%..\config" "%RESOURCES_DIR%\config"
xcopy /e /i /y "%DESKTOP_DIR%..\database" "%RESOURCES_DIR%\database"
xcopy /e /i /y "%DESKTOP_DIR%..\db" "%RESOURCES_DIR%\db"
xcopy /e /i /y "%DESKTOP_DIR%..\public" "%RESOURCES_DIR%\public"
xcopy /e /i /y "%DESKTOP_DIR%..\routes" "%RESOURCES_DIR%\routes"
xcopy /e /i /y "%DESKTOP_DIR%..\storage" "%RESOURCES_DIR%\storage"
copy "%DESKTOP_DIR%..\bootstrap.php" "%RESOURCES_DIR%\"

rem Clean up large backup files to keep the build size small
if exist "%RESOURCES_DIR%\storage\backups" (
    del /q /f "%RESOURCES_DIR%\storage\backups\*.zip" >nul 2>&1
)

echo === Build Completed Successfully! ===
echo Output folder: %APP_OUT_DIR%
pause
