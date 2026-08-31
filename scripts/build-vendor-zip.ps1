#Requires -Version 5.1
<#
.SYNOPSIS
  Build vendor.zip locally for manual upload via cPanel File Manager (no terminal on server).

.USAGE
  cd c:\xampp\htdocs\ispbilling
  .\scripts\build-vendor-zip.ps1

  Then upload vendor.zip via cPanel File Manager to your app root and Extract.
  Or split for slow FTP: the GitHub "Upload vendor (FTP chunks)" workflow is preferred.
#>
$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot\..

Write-Host 'Running composer install --no-dev ...'
composer install --no-dev --optimize-autoloader --no-interaction

if (Test-Path vendor.zip) { Remove-Item vendor.zip -Force }

Write-Host 'Creating vendor.zip ...'
Compress-Archive -Path vendor -DestinationPath vendor.zip -CompressionLevel Optimal

$size = [math]::Round((Get-Item vendor.zip).Length / 1MB, 1)
Write-Host "Done: vendor.zip ($size MB)"
Write-Host 'Upload vendor.zip via cPanel File Manager → your app folder → Extract'
