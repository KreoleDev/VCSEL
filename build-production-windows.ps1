$ErrorActionPreference = "Stop"

$ProjectDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$DistDir = Join-Path $ProjectDir "dist"

Write-Host "============================================================"
Write-Host " Pertech VCSEL Windows Production Build"
Write-Host "============================================================"
Write-Host ""

Set-Location $ProjectDir

if (-not (Test-Path "package.json")) {
    throw "package.json not found. Run this script from the Electron project root folder."
}

if (-not (Test-Path "main.js")) {
    throw "main.js not found. Run this script from the Electron project root folder."
}

if (-not (Get-Command node -ErrorAction SilentlyContinue)) {
    throw "node is not installed."
}

if (-not (Get-Command npm -ErrorAction SilentlyContinue)) {
    throw "npm is not installed."
}

Write-Host "Node: $(node -v)"
Write-Host "npm: $(npm -v)"
Write-Host ""

if (Test-Path $DistDir) {
    Remove-Item $DistDir -Recurse -Force
}

if (-not (Test-Path "node_modules")) {
    Write-Host "node_modules not found. Running npm install..."
    npm install
}

Write-Host "Building Windows installer..."
npm run dist:win

$Installer = Get-ChildItem $DistDir -Filter "*.exe" | Select-Object -First 1
if (-not $Installer) {
    throw "No Windows installer found in dist."
}

Write-Host ""
Write-Host "Windows installer created: $($Installer.FullName)"
