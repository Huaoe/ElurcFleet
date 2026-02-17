# Fleetbase Platform Setup Script for Windows
# This script automates the initial setup of the Stalabard DAO Marketplace

param(
    [switch]$SkipDocker,
    [switch]$DevMode
)

$ErrorActionPreference = "Stop"

Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "  Stalabard DAO Marketplace - Platform Setup" -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host ""

function Test-DockerInstalled {
    try {
        $dockerVersion = docker --version
        Write-Host "[OK] Docker is installed: $dockerVersion" -ForegroundColor Green
        return $true
    }
    catch {
        Write-Host "[ERROR] Docker is not installed or not in PATH" -ForegroundColor Red
        Write-Host "Please install Docker Desktop from https://www.docker.com/products/docker-desktop" -ForegroundColor Yellow
        return $false
    }
}

function Test-DockerRunning {
    try {
        docker ps | Out-Null
        Write-Host "[OK] Docker daemon is running" -ForegroundColor Green
        return $true
    }
    catch {
        Write-Host "[ERROR] Docker daemon is not running" -ForegroundColor Red
        Write-Host "Please start Docker Desktop" -ForegroundColor Yellow
        return $false
    }
}

function Test-PortAvailable {
    param([int]$Port)
    
    $connection = Get-NetTCPConnection -LocalPort $Port -ErrorAction SilentlyContinue
    if ($connection) {
        Write-Host "[WARNING] Port $Port is already in use" -ForegroundColor Yellow
        return $false
    }
    Write-Host "[OK] Port $Port is available" -ForegroundColor Green
    return $true
}

function Initialize-Environment {
    Write-Host "`nInitializing environment..." -ForegroundColor Cyan
    
    if (-not (Test-Path "fleetbase\.env")) {
        Copy-Item ".env.example" "fleetbase\.env"
        Write-Host "[OK] Created fleetbase\.env from template" -ForegroundColor Green
    }
    else {
        Write-Host "[OK] fleetbase\.env already exists" -ForegroundColor Green
    }
    
    if (-not (Test-Path "fleetbase\storage")) {
        New-Item -ItemType Directory -Path "fleetbase\storage" -Force | Out-Null
        Write-Host "[OK] Created fleetbase\storage directory" -ForegroundColor Green
    }
    
    if (-not (Test-Path "fleetbase\extensions")) {
        New-Item -ItemType Directory -Path "fleetbase\extensions" -Force | Out-Null
        Write-Host "[OK] Created fleetbase\extensions directory" -ForegroundColor Green
    }
}

function Start-FleetbaseContainers {
    Write-Host "`nStarting Fleetbase containers..." -ForegroundColor Cyan
    
    try {
        docker-compose up -d
        Write-Host "[OK] Containers started successfully" -ForegroundColor Green
        
        Write-Host "`nWaiting for services to be ready..." -ForegroundColor Cyan
        Start-Sleep -Seconds 10
        
        $maxAttempts = 30
        $attempt = 0
        $ready = $false
        
        while (-not $ready -and $attempt -lt $maxAttempts) {
            $attempt++
            try {
                $response = Invoke-WebRequest -Uri "http://localhost:8000/health" -TimeoutSec 2 -ErrorAction SilentlyContinue
                if ($response.StatusCode -eq 200) {
                    $ready = $true
                    Write-Host "[OK] Fleetbase API is ready!" -ForegroundColor Green
                }
            }
            catch {
                Write-Host "." -NoNewline
                Start-Sleep -Seconds 2
            }
        }
        
        if (-not $ready) {
            Write-Host "`n[WARNING] API health check timeout. Check logs with: docker-compose logs fleetbase-api" -ForegroundColor Yellow
        }
    }
    catch {
        Write-Host "[ERROR] Failed to start containers: $_" -ForegroundColor Red
        exit 1
    }
}

function Show-Status {
    Write-Host "`n==================================================" -ForegroundColor Cyan
    Write-Host "  Container Status" -ForegroundColor Cyan
    Write-Host "==================================================" -ForegroundColor Cyan
    docker-compose ps
    
    Write-Host "`n==================================================" -ForegroundColor Cyan
    Write-Host "  Access Information" -ForegroundColor Cyan
    Write-Host "==================================================" -ForegroundColor Cyan
    Write-Host "Fleetbase Console: http://localhost:8000/console" -ForegroundColor Green
    Write-Host "API Endpoint:      http://localhost:8000/api/v1" -ForegroundColor Green
    Write-Host "MySQL:             localhost:3306" -ForegroundColor Green
    Write-Host "Redis:             localhost:6379" -ForegroundColor Green
    
    Write-Host "`n==================================================" -ForegroundColor Cyan
    Write-Host "  Next Steps" -ForegroundColor Cyan
    Write-Host "==================================================" -ForegroundColor Cyan
    Write-Host "1. Access Fleetbase Console at http://localhost:8000/console"
    Write-Host "2. Complete initial setup wizard"
    Write-Host "3. Install extensions: Storefront, FleetOps, Pallet"
    Write-Host "4. Create Network: 'Stalabard DAO Marketplace'"
    Write-Host "5. Copy Network Key to fleetbase\.env as FLEETBASE_API_KEY"
    Write-Host ""
}

if (-not $SkipDocker) {
    if (-not (Test-DockerInstalled)) { exit 1 }
    if (-not (Test-DockerRunning)) { exit 1 }
    
    Write-Host "`nChecking port availability..." -ForegroundColor Cyan
    $portsOk = $true
    $portsOk = (Test-PortAvailable -Port 80) -and $portsOk
    $portsOk = (Test-PortAvailable -Port 3306) -and $portsOk
    $portsOk = (Test-PortAvailable -Port 6379) -and $portsOk
    $portsOk = (Test-PortAvailable -Port 8000) -and $portsOk
    
    if (-not $portsOk -and -not $DevMode) {
        Write-Host "`n[ERROR] Some required ports are in use. Stop conflicting services or use -DevMode to continue anyway." -ForegroundColor Red
        exit 1
    }
}

Initialize-Environment
Start-FleetbaseContainers
Show-Status

Write-Host "`n[SUCCESS] Fleetbase platform setup complete!" -ForegroundColor Green
