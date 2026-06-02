@echo off
setlocal

set "ROOT_DIR=%~dp0"
set "ELECTRON_BIN=%ROOT_DIR%node_modules\.bin\electron.cmd"

if not exist "%ELECTRON_BIN%" (
  echo Electron dependencies are not installed. Run npm install first. 1>&2
  exit /b 1
)

set ELECTRON_RUN_AS_NODE=
cd /d "%ROOT_DIR%"
"%ELECTRON_BIN%" .
