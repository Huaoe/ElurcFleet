# Fleetbase Platform Installation Guide

This guide provides detailed instructions for installing and configuring the Fleetbase platform for the Stalabard DAO Marketplace.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Automated Installation](#automated-installation)
3. [Manual Installation](#manual-installation)
4. [Extension Installation](#extension-installation)
5. [Network Creation](#network-creation)
6. [Environment Configuration](#environment-configuration)
7. [Verification](#verification)
8. [Troubleshooting](#troubleshooting)

## Prerequisites

### System Requirements

- **Operating System**: Windows 10/11, macOS 10.15+, or Linux (Ubuntu 20.04+)
- **RAM**: Minimum 4GB (8GB recommended)
- **Disk Space**: 10GB available
- **Docker**: Docker Desktop 4.0+ or Docker Engine 20.10+
- **Docker Compose**: v2.0+

### Required Ports

Ensure the following ports are available:

- `80` - Nginx reverse proxy
- `443` - HTTPS (production)
- `3306` - MySQL database
- `6379` - Redis cache/sessions
- `8000` - Fleetbase API

### Check Port Availability

**Windows (PowerShell):**
```powershell
Get-NetTCPConnection -LocalPort 80,443,3306,6379,8000 -ErrorAction SilentlyContinue
```

**Linux/Mac:**
```bash
lsof -i :80,443,3306,6379,8000
```

## Automated Installation

### Windows

```powershell
# Run setup script
.\scripts\setup.ps1

# With options
.\scripts\setup.ps1 -DevMode  # Skip port checks
```

### Linux/Mac

```bash
# Make script executable
chmod +x scripts/setup.sh

# Run setup
./scripts/setup.sh

# With options
./scripts/setup.sh --dev-mode  # Skip port checks
```

The automated script will:
1. ✅ Verify Docker installation
2. ✅ Check port availability
3. ✅ Create environment files
4. ✅ Start Docker containers
5. ✅ Wait for services to be ready
6. ✅ Display access information

## Manual Installation

### Step 1: Create Environment File

```bash
# Copy template
cp .env.example fleetbase/.env

# Create required directories
mkdir -p fleetbase/storage
mkdir -p fleetbase/extensions
```

### Step 2: Configure Environment Variables

Edit `fleetbase/.env`:

```bash
# Application
APP_NAME="Stalabard DAO Marketplace"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=fleetbase
DB_USERNAME=fleetbase
DB_PASSWORD=fleetbase_password  # CHANGE IN PRODUCTION!

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# DAO Integration
DAO_ADDRESS=D6d8TZrNFwg3QG97JLLfTjgdJequ84wMhQ8f12UW56Rq
DAO_NFT_COLLECTION=3e22667e998143beef529eda8c84ee394a838aa705716c3b6fe0b3d5f913ac4c

# Solana
SOLANA_RPC_URL=https://api.devnet.solana.com
SOLANA_CONFIRMATION_DEPTH=10
SOLANA_VERIFICATION_TIMEOUT=300
```

### Step 3: Pull Docker Images

```bash
docker-compose pull
```

### Step 4: Start Containers

```bash
docker-compose up -d
```

### Step 5: Verify Containers

```bash
# Check status
docker-compose ps

# Expected output:
# NAME                 STATUS
# fleetbase-api        Up
# fleetbase-mysql      Up
# fleetbase-redis      Up
# fleetbase-nginx      Up
```

### Step 6: Check Logs

```bash
# View all logs
docker-compose logs -f

# View specific service
docker-compose logs -f fleetbase-api
```

### Step 7: Initialize Database

```bash
# Access Fleetbase container
docker-compose exec fleetbase-api bash

# Run migrations
php artisan migrate

# Seed initial data (if available)
php artisan db:seed
```

## Extension Installation

### Via Fleetbase Console (Recommended)

1. **Access Console**
   - Navigate to: http://localhost:8000/console
   - Complete initial setup wizard if prompted
   - Create admin account

2. **Navigate to Extensions**
   - Click **Extensions** in sidebar
   - Click **Marketplace** tab

3. **Install Storefront Extension**
   - Find "Storefront" extension
   - Click **Install**
   - Wait for installation to complete
   - Click **Activate**
   - Verify status shows "Active"

4. **Install FleetOps Extension**
   - Find "FleetOps" extension
   - Click **Install**
   - Wait for installation to complete
   - Click **Activate**
   - Verify status shows "Active"

5. **Install Pallet Extension (Optional)**
   - Find "Pallet" extension
   - Click **Install**
   - Click **Activate**

### Via Command Line (Alternative)

```bash
# Access container
docker-compose exec fleetbase-api bash

# Install extensions
php artisan fleetbase:install-extension storefront
php artisan fleetbase:install-extension fleetops
php artisan fleetbase:install-extension pallet

# Activate extensions
php artisan fleetbase:activate-extension storefront
php artisan fleetbase:activate-extension fleetops
php artisan fleetbase:activate-extension pallet

# Clear cache
php artisan cache:clear
php artisan config:clear
```

## Network Creation

### Via Fleetbase Console

1. **Navigate to Networks**
   - Click **Storefront** in sidebar
   - Click **Networks**

2. **Create New Network**
   - Click **Create Network** button
   - Fill in details:
     - **Name**: `Stalabard DAO Marketplace`
     - **Description**: `Multi-vendor marketplace for Stalabard DAO members`
     - **Currency**: `USD`
     - **Timezone**: Select your timezone
   - Click **Save**

3. **Generate Network Key**
   - After creation, click on the network
   - Navigate to **API Keys** tab
   - Click **Generate Key**
   - Copy the generated key
   - **IMPORTANT**: Save this key securely

4. **Update Environment**
   - Edit `fleetbase/.env`
   - Set `FLEETBASE_API_KEY={your_network_key}`
   - Restart containers: `docker-compose restart`

### Via API (Alternative)

```bash
# Get auth token first (from Console)
TOKEN="your_admin_token"

# Create network
curl -X POST http://localhost:8000/api/v1/networks \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Stalabard DAO Marketplace",
    "description": "Multi-vendor marketplace for Stalabard DAO members",
    "currency": "USD"
  }'
```

## Environment Configuration

### DAO Integration Settings

```bash
# Mainnet (Production)
DAO_ADDRESS=D6d8TZrNFwg3QG97JLLfTjgdJequ84wMhQ8f12UW56Rq
DAO_NFT_COLLECTION=3e22667e998143beef529eda8c84ee394a838aa705716c3b6fe0b3d5f913ac4c
SOLANA_RPC_URL=https://api.mainnet-beta.solana.com

# Devnet (Testing)
SOLANA_RPC_URL=https://api.devnet.solana.com
```

### Redis Configuration

```bash
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null  # Set password in production

# Cache
CACHE_DRIVER=redis
CACHE_PREFIX=fleetbase_cache

# Sessions
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Queues
QUEUE_CONNECTION=redis
```

### Database Configuration

```bash
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=fleetbase
DB_USERNAME=fleetbase
DB_PASSWORD=fleetbase_password  # CHANGE IN PRODUCTION!

# Connection pool
DB_POOL_MIN=2
DB_POOL_MAX=10
```

## Verification

### Health Check

```bash
# API health
curl http://localhost:8000/health

# Expected response:
# {"status":"ok","timestamp":"2026-02-17T10:24:00Z"}
```

### Database Connection

```bash
docker-compose exec mysql mysql -ufleetbase -pfleetbase_password -e "SELECT 1;"
```

### Redis Connection

```bash
docker-compose exec redis redis-cli ping
# Expected: PONG
```

### Extension Verification

```bash
# Via API
curl http://localhost:8000/api/v1/extensions

# Via Console
# Navigate to Extensions page and verify all show "Active"
```

### Network Verification

```bash
# Via API (requires auth token)
curl http://localhost:8000/api/v1/networks \
  -H "Authorization: Bearer $TOKEN"

# Via Console
# Navigate to Storefront → Networks
# Verify "Stalabard DAO Marketplace" is listed
```

### Performance Baselines

Run automated tests:

```bash
npm install
npm test
```

Expected performance:
- ✅ API health check: < 200ms
- ✅ Database query: < 100ms
- ✅ Redis operations: < 10ms

## Troubleshooting

### Containers Won't Start

**Issue**: Port conflicts

```bash
# Find process using port
# Windows
netstat -ano | findstr :80

# Linux/Mac
lsof -i :80

# Kill process or change port in docker-compose.yml
```

**Issue**: Insufficient Docker resources

```bash
# Docker Desktop → Settings → Resources
# Increase:
# - Memory: 4GB minimum (8GB recommended)
# - CPUs: 2 minimum (4 recommended)
# - Disk: 10GB minimum
```

### Database Connection Fails

```bash
# Check MySQL is running
docker-compose ps mysql

# Check logs
docker-compose logs mysql

# Verify credentials match in:
# - docker-compose.yml (MYSQL_USER, MYSQL_PASSWORD)
# - fleetbase/.env (DB_USERNAME, DB_PASSWORD)

# Reset database
docker-compose down -v
docker-compose up -d
```

### Extensions Not Appearing

```bash
# Access container
docker-compose exec fleetbase-api bash

# Install extensions manually
php artisan fleetbase:install-extensions

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Restart
exit
docker-compose restart fleetbase-api
```

### Network Creation Fails

**Check Storefront Extension**
```bash
# Verify Storefront is active
curl http://localhost:8000/api/v1/extensions | grep storefront
```

**Check Logs**
```bash
docker-compose logs fleetbase-api | grep -i error
```

**Verify Database Migrations**
```bash
docker-compose exec fleetbase-api php artisan migrate:status
```

### API Returns 500 Errors

```bash
# Check application logs
docker-compose exec fleetbase-api tail -f storage/logs/laravel.log

# Check permissions
docker-compose exec fleetbase-api chown -R www-data:www-data storage
docker-compose exec fleetbase-api chmod -R 775 storage

# Clear cache
docker-compose exec fleetbase-api php artisan cache:clear
docker-compose exec fleetbase-api php artisan config:clear
```

### Redis Connection Issues

```bash
# Test Redis
docker-compose exec redis redis-cli ping

# Check Redis logs
docker-compose logs redis

# Verify REDIS_HOST in fleetbase/.env matches service name in docker-compose.yml
```

## Next Steps

After successful installation:

1. ✅ Verify all acceptance criteria are met
2. ✅ Run automated tests: `npm test`
3. ✅ Access Fleetbase Console: http://localhost:8000/console
4. ✅ Verify Network Key is saved in `.env`
5. ➡️ Proceed to Story 1.2: Member Authentication Extension

## Support Resources

- **Fleetbase Documentation**: https://docs.fleetbase.io
- **Docker Documentation**: https://docs.docker.com
- **Project Architecture**: `_bmad-output/planning-artifacts/architecture.md`
- **PRD**: `_bmad-output/planning-artifacts/prd.md`
