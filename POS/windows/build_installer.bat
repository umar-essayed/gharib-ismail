@echo off
setlocal

set "ISCC=C:\Program Files (x86)\Inno Setup 6\ISCC.exe"
if not exist "%ISCC%" set "ISCC=C:\Program Files\Inno Setup 6\ISCC.exe"
set "ISS_FILE=%~dp0POSG_Setup.iss"

if not exist "%ISCC%" (
  echo Inno Setup Compiler not found:
  echo - C:\Program Files (x86)\Inno Setup 6\ISCC.exe
  echo - C:\Program Files\Inno Setup 6\ISCC.exe
  echo.
  echo Install Inno Setup 6 first, then run this script again.
  exit /b 1
)

"%ISCC%" "%ISS_FILE%"
if errorlevel 1 (
  echo Build failed.
  exit /b 1
)

echo Build completed successfully.
echo Output EXE: %~dp0build\POSG_Installer.exe
exit /b 0
