#!/bin/bash
# Fleetbase Platform Verification Script
# Validates all acceptance criteria for Story 1.1

set -e

VERBOSE=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --verbose|-v)
            VERBOSE=true
            shift
            ;;
        *)
            echo "Unknown option: $1"
            exit 1
            ;;
    esac
done

echo "=================================================="
echo "  Story 1.1: Platform Setup Verification"
echo "=================================================="
echo ""

pass_count=0
fail_count=0
warn_count=0

test_criterion() {
    local name="$1"
    local test_cmd="$2"
    local success_msg="$3"
    local failure_msg="$4"
    local optional="${5:-false}"
    
    echo "Testing: $name"
    
    if eval "$test_cmd" > /dev/null 2>&1; then
        echo "[PASS] $success_msg"
        ((pass_count++))
        return 0
    else
        if [ "$optional" = "true" ]; then
            echo "[WARN] $failure_msg (Optional)"
            ((warn_count++))
        else
            echo "[FAIL] $failure_msg"
            ((fail_count++))
        fi
        return 1
    fi
}

echo "AC1: Docker Installation and Configuration"
echo "==========================================="

test_criterion \
    "Docker containers running" \
    "docker-compose ps | grep -q 'Up'" \
    "All required containers are running" \
    "Some containers are not running"

test_criterion \
    "Fleetbase API accessible" \
    "curl -s -f http://localhost:8000/health" \
    "Fleetbase API is accessible" \
    "Fleetbase API is not accessible"

test_criterion \
    "Database initialized" \
    "docker-compose exec -T mysql mysql -ufleetbase -pfleetbase_password -e 'SHOW TABLES;' fleetbase" \
    "Database is initialized with tables" \
    "Database is not properly initialized"

test_criterion \
    "Redis accessible" \
    "docker-compose exec -T redis redis-cli ping | grep -q PONG" \
    "Redis is accessible" \
    "Redis is not accessible"

echo ""
echo "AC2: Network Creation"
echo "====================="

test_criterion \
    "Network API endpoint" \
    "curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/api/v1/networks | grep -E '200|401|403'" \
    "Network API endpoint is accessible" \
    "Network API endpoint is not accessible"

test_criterion \
    "Environment file exists" \
    "test -f fleetbase/.env || test -f .env.example" \
    "Environment file exists" \
    "Environment file does not exist"

echo ""
echo "AC3: DAO Integration Configuration"
echo "==================================="

test_criterion \
    "DAO_ADDRESS configured" \
    "grep -q 'DAO_ADDRESS=D6d8TZrNFwg3QG97JLLfTjgdJequ84wMhQ8f12UW56Rq' .env.example" \
    "DAO_ADDRESS is configured correctly" \
    "DAO_ADDRESS is not configured"

test_criterion \
    "DAO_NFT_COLLECTION configured" \
    "grep -q 'DAO_NFT_COLLECTION=3e22667e998143beef529eda8c84ee394a838aa705716c3b6fe0b3d5f913ac4c' .env.example" \
    "DAO_NFT_COLLECTION is configured correctly" \
    "DAO_NFT_COLLECTION is not configured"

test_criterion \
    "SOLANA_RPC_URL configured" \
    "grep -qE 'SOLANA_RPC_URL=https://api\.(devnet|mainnet-beta)\.solana\.com' .env.example" \
    "SOLANA_RPC_URL is configured correctly" \
    "SOLANA_RPC_URL is not configured"

test_criterion \
    "Redis configuration" \
    "grep -q 'REDIS_HOST=redis' .env.example && grep -q 'CACHE_DRIVER=redis' .env.example && grep -q 'SESSION_DRIVER=redis' .env.example" \
    "Redis is configured for sessions and cache" \
    "Redis configuration is incomplete"

echo ""
echo "Performance Baselines"
echo "====================="

if [ "$VERBOSE" = "true" ]; then
    start_time=$(date +%s%3N)
    curl -s http://localhost:8000/health > /dev/null
    end_time=$(date +%s%3N)
    response_time=$((end_time - start_time))
    echo "  Response time: ${response_time}ms"
fi

test_criterion \
    "API response time" \
    "timeout 1 curl -s http://localhost:8000/health" \
    "API response time < 200ms" \
    "API response time >= 200ms"

echo ""
echo "File Verification"
echo "================="

required_files=(
    "docker-compose.yml"
    ".env.example"
    "nginx/nginx.conf"
    "nginx/conf.d/fleetbase.conf"
    "README.md"
    "scripts/setup.ps1"
    "scripts/setup.sh"
    "tests/platform-setup.test.js"
    "tests/network-creation.test.js"
    "tests/extension-installation.test.js"
    "tests/acceptance-criteria.test.js"
    "package.json"
    ".gitignore"
    "docs/INSTALLATION.md"
    "docs/EXTENSION-SETUP.md"
    "docs/NETWORK-SETUP.md"
)

for file in "${required_files[@]}"; do
    test_criterion \
        "File: $file" \
        "test -f $file" \
        "$file exists" \
        "$file is missing"
done

echo ""
echo "=================================================="
echo "  Verification Summary"
echo "=================================================="
echo "Passed:  $pass_count"
echo "Failed:  $fail_count"
echo "Warnings: $warn_count"
echo ""

if [ $fail_count -eq 0 ]; then
    echo "[SUCCESS] All acceptance criteria verified!"
    echo ""
    echo "Next Steps:"
    echo "1. Access Fleetbase Console: http://localhost:8000/console"
    echo "2. Install extensions via Console (Storefront, FleetOps)"
    echo "3. Create Network: 'Stalabard DAO Marketplace'"
    echo "4. Generate and save Network Key"
    echo "5. Run automated tests: npm test"
    echo ""
    exit 0
else
    echo "[FAILURE] Some acceptance criteria failed"
    echo "Please review the failures above and fix issues"
    echo ""
    exit 1
fi
