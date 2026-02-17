# Stalabard DAO Marketplace - Fleetbase Platform

Multi-vendor marketplace powered by Fleetbase with DAO governance integration.

## Prerequisites

- Docker Desktop (Windows/Mac) or Docker Engine + Docker Compose (Linux)
- Minimum 4GB RAM allocated to Docker
- Ports available: 80, 443, 3306, 6379, 8000

## Quick Start

### 1. Initial Setup

```bash
# Copy environment template
cp .env.example fleetbase/.env

# Generate application key (will be done automatically on first run)
# Start the platform
docker-compose up -d
```

### 2. Verify Installation

```bash
# Check all containers are running
docker-compose ps

# View logs
docker-compose logs -f fleetbase-api

# Health check
curl http://localhost:8000/health
```

Expected response: `{"status": "ok", "timestamp": "..."}`

### 3. Access Fleetbase Console

Open your browser and navigate to:
- **Console URL**: http://localhost:8000/console
- **API URL**: http://localhost:8000/api/v1

Default admin credentials will be created on first run.

### 4. Install Core Extensions

From the Fleetbase Console:

1. Navigate to **Extensions** → **Marketplace**
2. Install the following extensions:
   - **Storefront** (required)
   - **FleetOps** (required)
   - **Pallet** (optional)
3. Activate each extension after installation

### 5. Create Network

1. Navigate to **Storefront** → **Networks**
2. Click **Create Network**
3. Enter details:
   - **Name**: Stalabard DAO Marketplace
   - **Currency**: USD
   - **Description**: Multi-vendor marketplace for Stalabard DAO members
4. Save and copy the **Network Key** to your `.env` file as `FLEETBASE_API_KEY`

## Configuration

### Environment Variables

Key configuration in `fleetbase/.env`:

```bash
# DAO Integration
DAO_ADDRESS=D6d8TZrNFwg3QG97JLLfTjgdJequ84wMhQ8f12UW56Rq
DAO_NFT_COLLECTION=3e22667e998143beef529eda8c84ee394a838aa705716c3b6fe0b3d5f913ac4c

# Solana Blockchain
SOLANA_RPC_URL=https://api.devnet.solana.com  # Use mainnet-beta for production
SOLANA_CONFIRMATION_DEPTH=10
SOLANA_VERIFICATION_TIMEOUT=300

# Fleetbase
FLEETBASE_URL=http://localhost:8000
FLEETBASE_API_KEY={your_network_key_here}
```

### Database

- **Type**: MySQL 8.0
- **Host**: localhost:3306 (from host) or mysql:3306 (from containers)
- **Database**: fleetbase
- **User**: fleetbase
- **Password**: fleetbase_password (change in production!)

### Redis

- **Host**: localhost:6379 (from host) or redis:6379 (from containers)
- **Purpose**: Sessions, cache, job queues

## Directory Structure

```
fleetbase/
├── storage/              # Logs, uploads, cache
├── extensions/           # Installed extensions
│   ├── storefront/
│   ├── fleetops/
│   └── pallet/
└── .env                  # Environment configuration
```

## Troubleshooting

### Containers won't start

```bash
# Check for port conflicts
netstat -ano | findstr :80
netstat -ano | findstr :3306
netstat -ano | findstr :6379

# Increase Docker memory (Docker Desktop → Settings → Resources)
# Minimum: 4GB RAM
```

### Database connection fails

```bash
# Verify MySQL is running
docker-compose ps mysql

# Check logs
docker-compose logs mysql

# Verify credentials in fleetbase/.env match docker-compose.yml
```

### Extensions not appearing

```bash
# Access container
docker-compose exec fleetbase-api bash

# Install extensions manually
php artisan fleetbase:install-extensions

# Clear cache
php artisan cache:clear
php artisan config:clear
```

### Network creation fails

```bash
# Ensure Storefront extension is activated
# Check logs
docker-compose logs fleetbase-api

# Verify database migrations ran
docker-compose exec fleetbase-api php artisan migrate:status
```

## Performance Baselines

Expected response times (local development):

- API health check: < 200ms
- Database query (simple SELECT): < 100ms
- Redis operations: < 10ms

## Security Notes

- **Never commit `.env` files** to version control
- Change default passwords in production
- Use HTTPS in production (configure SSL certificates in nginx)
- Restrict database and Redis to internal network only
- Store production credentials in secure vault

## Next Steps

After successful installation:

1. ✅ Verify all acceptance criteria are met
2. Create member authentication extension (Story 1.2)
3. Set up seller store creation (Story 2.1)
4. Configure product management (Story 3.1)

## Testing

### Obtaining FLEETBASE_TEST_TOKEN

Some tests require an authenticated API token. To generate one:

1. Start Fleetbase containers: `docker-compose up -d`
2. Access Fleetbase Console at http://localhost:8000/console
3. Create an admin account or log in
4. Generate API token via Console (Settings → API Keys) or via CLI:
   ```bash
   docker-compose exec fleetbase-api php artisan tinker
   # Then in tinker:
   $user = \Fleetbase\Models\User::first();
   $token = $user->createToken('test-token');
   echo $token->plainTextToken;
   ```
5. Add token to `.env`:
   ```bash
   FLEETBASE_TEST_TOKEN=your_generated_token_here
   ```

### Running Tests

```bash
# Install dependencies
yarn install

# Run all tests
yarn test

# Run specific test suites
yarn test:platform      # Platform setup tests
yarn test:extensions    # Extension installation tests
yarn test:network       # Network creation tests
yarn test:acceptance    # Acceptance criteria tests
```

## Support

- **Fleetbase Documentation**: https://docs.fleetbase.io
- **Project Architecture**: See `_bmad-output/planning-artifacts/architecture.md`
- **PRD**: See `_bmad-output/planning-artifacts/prd.md`
