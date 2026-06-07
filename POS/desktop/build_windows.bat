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

echo === Build Completed Successfully! ===
echo Output folder: %APP_OUT_DIR%
pause
