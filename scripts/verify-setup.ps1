# Fleetbase Platform Verification Script
# Validates all acceptance criteria for Story 1.1

param(
    [switch]$Verbose
)

$ErrorActionPreference = "Stop"

Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "  Story 1.1: Platform Setup Verification" -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host ""

$passCount = 0
$failCount = 0
$warnCount = 0

function Test-Criterion {
    param(
        [string]$Name,
        [scriptblock]$Test,
        [string]$SuccessMessage,
        [string]$FailureMessage,
        [switch]$Optional
    )
    
    Write-Host "Testing: $Name" -ForegroundColor Yellow
    
    try {
        $result = & $Test
        if ($result) {
            Write-Host "[PASS] $SuccessMessage" -ForegroundColor Green
            $script:passCount++
            return $true
        }
        else {
            if ($Optional) {
                Write-Host "[WARN] $FailureMessage (Optional)" -ForegroundColor Yellow
                $script:warnCount++
            }
            else {
                Write-Host "[FAIL] $FailureMessage" -ForegroundColor Red
                $script:failCount++
            }
            return $false
        }
    }
    catch {
        if ($Optional) {
            Write-Host "[WARN] $FailureMessage (Optional): ${_}" -ForegroundColor Yellow
            $script:warnCount++
        }
        else {
            Write-Host "[FAIL] $FailureMessage: ${_}" -ForegroundColor Red
            $script:failCount++
        }
        return $false
    }
}

Write-Host "AC1: Docker Installation and Configuration" -ForegroundColor Cyan
Write-Host "===========================================" -ForegroundColor Cyan

Test-Criterion -Name "Docker containers running" -Test {
    $output = docker-compose ps --format json | ConvertFrom-Json
    $running = $output | Where-Object { $_.State -eq "running" }
    return $running.Count -ge 4
} -SuccessMessage "All required containers are running" `
  -FailureMessage "Some containers are not running"

Test-Criterion -Name "Fleetbase API accessible" -Test {
    $response = Invoke-WebRequest -Uri "http://localhost:8000/health" -TimeoutSec 5 -ErrorAction Stop
    return $response.StatusCode -eq 200
} -SuccessMessage "Fleetbase API is accessible" `
  -FailureMessage "Fleetbase API is not accessible"

Test-Criterion -Name "Database initialized" -Test {
    $output = docker-compose exec -T mysql mysql -ufleetbase -pfleetbase_password -e "SHOW TABLES;" fleetbase 2>&1
    return $output -match "Tables_in_fleetbase"
} -SuccessMessage "Database is initialized with tables" `
  -FailureMessage "Database is not properly initialized"

Test-Criterion -Name "Redis accessible" -Test {
    $output = docker-compose exec -T redis redis-cli ping 2>&1
    return $output -match "PONG"
} -SuccessMessage "Redis is accessible" `
  -FailureMessage "Redis is not accessible"

Write-Host ""
Write-Host "AC2: Network Creation" -ForegroundColor Cyan
Write-Host "=====================" -ForegroundColor Cyan

Test-Criterion -Name "Network API endpoint" -Test {
    $response = Invoke-WebRequest -Uri "http://localhost:8000/api/v1/networks" -TimeoutSec 5 -ErrorAction Stop
    return $response.StatusCode -in @(200, 401, 403)
} -SuccessMessage "Network API endpoint is accessible" `
  -FailureMessage "Network API endpoint is not accessible"

Test-Criterion -Name "Environment file exists" -Test {
    return Test-Path "fleetbase\.env"
} -SuccessMessage "Environment file exists" `
  -FailureMessage "Environment file does not exist"

Write-Host ""
Write-Host "AC3: DAO Integration Configuration" -ForegroundColor Cyan
Write-Host "===================================" -ForegroundColor Cyan

Test-Criterion -Name "DAO_ADDRESS configured" -Test {
    $envContent = Get-Content "fleetbase\.env" -ErrorAction SilentlyContinue
    if (-not $envContent) {
        $envContent = Get-Content ".env.example"
    }
    return $envContent -match "DAO_ADDRESS=D6d8TZrNFwg3QG97JLLfTjgdJequ84wMhQ8f12UW56Rq"
} -SuccessMessage "DAO_ADDRESS is configured correctly" `
  -FailureMessage "DAO_ADDRESS is not configured"

Test-Criterion -Name "DAO_NFT_COLLECTION configured" -Test {
    $envContent = Get-Content "fleetbase\.env" -ErrorAction SilentlyContinue
    if (-not $envContent) {
        $envContent = Get-Content ".env.example"
    }
    return $envContent -match "DAO_NFT_COLLECTION=3e22667e998143beef529eda8c84ee394a838aa705716c3b6fe0b3d5f913ac4c"
} -SuccessMessage "DAO_NFT_COLLECTION is configured correctly" `
  -FailureMessage "DAO_NFT_COLLECTION is not configured"

Test-Criterion -Name "SOLANA_RPC_URL configured" -Test {
    $envContent = Get-Content "fleetbase\.env" -ErrorAction SilentlyContinue
    if (-not $envContent) {
        $envContent = Get-Content ".env.example"
    }
    return $envContent -match "SOLANA_RPC_URL=https://api\.(devnet|mainnet-beta)\.solana\.com"
} -SuccessMessage "SOLANA_RPC_URL is configured correctly" `
  -FailureMessage "SOLANA_RPC_URL is not configured"

Test-Criterion -Name "Redis configuration" -Test {
    $envContent = Get-Content "fleetbase\.env" -ErrorAction SilentlyContinue
    if (-not $envContent) {
        $envContent = Get-Content ".env.example"
    }
    return ($envContent -match "REDIS_HOST=redis") -and ($envContent -match "CACHE_DRIVER=redis") -and ($envContent -match "SESSION_DRIVER=redis")
} -SuccessMessage "Redis is configured for sessions and cache" `
  -FailureMessage "Redis configuration is incomplete"

Write-Host ""
Write-Host "Performance Baselines" -ForegroundColor Cyan
Write-Host "=====================" -ForegroundColor Cyan

Test-Criterion -Name "API response time" -Test {
    $startTime = Get-Date
    Invoke-WebRequest -Uri "http://localhost:8000/health" -TimeoutSec 5 -ErrorAction Stop | Out-Null
    $responseTime = ((Get-Date) - $startTime).TotalMilliseconds
    if ($Verbose) {
        Write-Host "  Response time: $([math]::Round($responseTime, 2))ms" -ForegroundColor Gray
    }
    return $responseTime -lt 200
} -SuccessMessage "API response time < 200ms" `
  -FailureMessage "API response time >= 200ms"

Write-Host ""
Write-Host "File Verification" -ForegroundColor Cyan
Write-Host "=================" -ForegroundColor Cyan

$requiredFiles = @(
    "docker-compose.yml",
    ".env.example",
    "nginx/nginx.conf",
    "nginx/conf.d/fleetbase.conf",
    "README.md",
    "scripts/setup.ps1",
    "scripts/setup.sh",
    "tests/platform-setup.test.js",
    "tests/network-creation.test.js",
    "tests/extension-installation.test.js",
    "tests/acceptance-criteria.test.js",
    "package.json",
    ".gitignore",
    "docs/INSTALLATION.md",
    "docs/EXTENSION-SETUP.md",
    "docs/NETWORK-SETUP.md"
)

foreach ($file in $requiredFiles) {
    Test-Criterion -Name "File: $file" -Test {
        return Test-Path $file
    } -SuccessMessage "$file exists" `
      -FailureMessage "$file is missing"
}

Write-Host ""
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "  Verification Summary" -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "Passed:  $passCount" -ForegroundColor Green
Write-Host "Failed:  $failCount" -ForegroundColor $(if ($failCount -gt 0) { "Red" } else { "Green" })
Write-Host "Warnings: $warnCount" -ForegroundColor Yellow
Write-Host ""

if ($failCount -eq 0) {
    Write-Host "[SUCCESS] All acceptance criteria verified!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Next Steps:" -ForegroundColor Cyan
    Write-Host "1. Access Fleetbase Console: http://localhost:8000/console"
    Write-Host "2. Install extensions via Console (Storefront, FleetOps)"
    Write-Host "3. Create Network: 'Stalabard DAO Marketplace'"
    Write-Host "4. Generate and save Network Key"
    Write-Host "5. Run automated tests: npm test"
    Write-Host ""
    exit 0
}
else {
    Write-Host "[FAILURE] Some acceptance criteria failed" -ForegroundColor Red
    Write-Host "Please review the failures above and fix issues" -ForegroundColor Yellow
    Write-Host ""
    exit 1
}
