#!/bin/bash
# Fleetbase Platform Setup Script for Linux/Mac
# This script automates the initial setup of the Stalabard DAO Marketplace

set -e

SKIP_DOCKER=false
DEV_MODE=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --skip-docker)
            SKIP_DOCKER=true
            shift
            ;;
        --dev-mode)
            DEV_MODE=true
            shift
            ;;
        *)
            echo "Unknown option: $1"
            exit 1
            ;;
    esac
done

echo "=================================================="
echo "  Stalabard DAO Marketplace - Platform Setup"
echo "=================================================="
echo ""

check_docker_installed() {
    if command -v docker &> /dev/null; then
        echo "[OK] Docker is installed: $(docker --version)"
        return 0
    else
        echo "[ERROR] Docker is not installed"
        echo "Please install Docker from https://docs.docker.com/get-docker/"
        return 1
    fi
}

check_docker_running() {
    if docker ps &> /dev/null; then
        echo "[OK] Docker daemon is running"
        return 0
    else
        echo "[ERROR] Docker daemon is not running"
        echo "Please start Docker"
        return 1
    fi
}

check_port_available() {
    local port=$1
    if lsof -Pi :$port -sTCP:LISTEN -t >/dev/null 2>&1; then
        echo "[WARNING] Port $port is already in use"
        return 1
    else
        echo "[OK] Port $port is available"
        return 0
    fi
}

initialize_environment() {
    echo ""
    echo "Initializing environment..."
    
    if [ ! -f "fleetbase/.env" ]; then
        cp .env.example fleetbase/.env
        echo "[OK] Created fleetbase/.env from template"
    else
        echo "[OK] fleetbase/.env already exists"
    fi
    
    mkdir -p fleetbase/storage
    echo "[OK] Created fleetbase/storage directory"
    
    mkdir -p fleetbase/extensions
    echo "[OK] Created fleetbase/extensions directory"
}

start_fleetbase_containers() {
    echo ""
    echo "Starting Fleetbase containers..."
    
    docker-compose up -d
    echo "[OK] Containers started successfully"
    
    echo ""
    echo "Waiting for services to be ready..."
    sleep 10
    
    max_attempts=30
    attempt=0
    ready=false
    
    while [ $ready = false ] && [ $attempt -lt $max_attempts ]; do
        attempt=$((attempt + 1))
        if curl -s -f http://localhost:8000/health > /dev/null 2>&1; then
            ready=true
            echo "[OK] Fleetbase API is ready!"
        else
            echo -n "."
            sleep 2
        fi
    done
    
    if [ $ready = false ]; then
        echo ""
        echo "[WARNING] API health check timeout. Check logs with: docker-compose logs fleetbase-api"
    fi
}

show_status() {
    echo ""
    echo "=================================================="
    echo "  Container Status"
    echo "=================================================="
    docker-compose ps
    
    echo ""
    echo "=================================================="
    echo "  Access Information"
    echo "=================================================="
    echo "Fleetbase Console: http://localhost:8000/console"
    echo "API Endpoint:      http://localhost:8000/api/v1"
    echo "MySQL:             localhost:3306"
    echo "Redis:             localhost:6379"
    
    echo ""
    echo "=================================================="
    echo "  Next Steps"
    echo "=================================================="
    echo "1. Access Fleetbase Console at http://localhost:8000/console"
    echo "2. Complete initial setup wizard"
    echo "3. Install extensions: Storefront, FleetOps, Pallet"
    echo "4. Create Network: 'Stalabard DAO Marketplace'"
    echo "5. Copy Network Key to fleetbase/.env as FLEETBASE_API_KEY"
    echo ""
}

if [ "$SKIP_DOCKER" = false ]; then
    check_docker_installed || exit 1
    check_docker_running || exit 1
    
    echo ""
    echo "Checking port availability..."
    ports_ok=true
    check_port_available 80 || ports_ok=false
    check_port_available 3306 || ports_ok=false
    check_port_available 6379 || ports_ok=false
    check_port_available 8000 || ports_ok=false
    
    if [ "$ports_ok" = false ] && [ "$DEV_MODE" = false ]; then
        echo ""
        echo "[ERROR] Some required ports are in use. Stop conflicting services or use --dev-mode to continue anyway."
        exit 1
    fi
fi

initialize_environment
start_fleetbase_containers
show_status

echo ""
echo "[SUCCESS] Fleetbase platform setup complete!"
