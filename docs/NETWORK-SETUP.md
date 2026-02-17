# Fleetbase Network Creation Guide

This guide provides detailed instructions for creating the "Stalabard DAO Marketplace" network in Fleetbase.

## Prerequisites

- Fleetbase platform installed and running
- Storefront extension installed and activated
- Access to Fleetbase Console
- Admin account with network creation permissions

## Network Architecture Overview

### What is a Fleetbase Network?

A **Network** in Fleetbase represents a marketplace or platform that connects multiple stores (sellers) with customers. For Stalabard DAO Marketplace:

- **Network Name**: Stalabard DAO Marketplace
- **Network Owner**: Platform operator (DAO)
- **Network Type**: Multi-vendor marketplace
- **Stores**: Independent sellers operate within the network
- **Governance**: DAO-controlled via NFT membership

### Network vs Store

| Aspect | Network | Store |
|--------|---------|-------|
| **Purpose** | Marketplace platform | Individual seller |
| **Visibility** | All products across stores | Only store's products |
| **Management** | Platform operator (DAO) | Store owner (seller) |
| **API Key** | Network Key (for browsing) | Store Key (for management) |
| **Access** | Public (members only) | Private (store owner) |

## Network Creation Methods

### Method 1: Via Fleetbase Console (Recommended)

#### Step 1: Access Networks Section

1. Navigate to http://localhost:8000/console
2. Log in with admin credentials
3. Click **Storefront** in the left sidebar
4. Click **Networks** submenu

#### Step 2: Create New Network

1. Click **Create Network** button (top-right)
2. Fill in the network details form:

**Basic Information:**
- **Name**: `Stalabard DAO Marketplace`
- **Description**: `Multi-vendor marketplace for Stalabard DAO members. Sellers must hold DAO NFT badges to create stores. Buyers must verify membership to make purchases.`
- **Slug**: `stalabard-dao` (auto-generated, can customize)

**Settings:**
- **Currency**: `USD`
- **Timezone**: Select your timezone (e.g., `America/New_York`, `Europe/London`)
- **Country**: Select primary country
- **Language**: `English`

**Advanced Settings:**
- **Public**: ✅ Enabled (marketplace is public to members)
- **Enable Store Registration**: ✅ Enabled (sellers can request to join)
- **Require Store Approval**: ✅ Enabled (DAO approves new stores)
- **Enable Customer Registration**: ✅ Enabled (members can create accounts)

3. Click **Save** button

#### Step 3: Configure Network Settings

After creation, configure additional settings:

1. Click on the newly created network
2. Navigate to **Settings** tab

**General Settings:**
- **Logo**: Upload network logo (optional)
- **Banner**: Upload banner image (optional)
- **Contact Email**: Set support email
- **Contact Phone**: Set support phone

**Business Settings:**
- **Tax Configuration**: Configure tax rules
- **Shipping Zones**: Define delivery areas
- **Payment Methods**: Will be configured in later stories

**Membership Settings (Custom):**
- **DAO Address**: `D6d8TZrNFwg3QG97JLLfTjgdJequ84wMhQ8f12UW56Rq`
- **NFT Collection**: `3e22667e998143beef529eda8c84ee394a838aa705716c3b6fe0b3d5f913ac4c`
- **Membership Required**: ✅ Enabled

4. Click **Save Settings**

#### Step 4: Generate Network API Key

1. Navigate to **API Keys** tab
2. Click **Generate New Key** button
3. Fill in key details:
   - **Name**: `Storefront App Key`
   - **Description**: `API key for marketplace browsing and customer operations`
   - **Permissions**: Select appropriate permissions:
     - ✅ Read Products
     - ✅ Read Categories
     - ✅ Read Stores
     - ✅ Create Orders (for customers)
     - ✅ Read Orders (own orders only)
4. Click **Generate**
5. **IMPORTANT**: Copy the generated key immediately
6. Save to secure location (password manager, vault)

#### Step 5: Update Environment Configuration

1. Open `fleetbase/.env` file
2. Add/update the following:
   ```bash
   FLEETBASE_API_KEY={your_generated_network_key}
   NETWORK_NAME="Stalabard DAO Marketplace"
   NETWORK_CURRENCY=USD
   ```
3. Save the file
4. Restart Fleetbase:
   ```bash
   docker-compose restart fleetbase-api
   ```

### Method 2: Via API

Use this method for automated deployments or scripting.

#### Step 1: Obtain Admin Token

First, authenticate and get an admin token:

```bash
# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "your_password"
  }'

# Response includes: { "token": "your_admin_token" }
```

#### Step 2: Create Network

```bash
TOKEN="your_admin_token"

curl -X POST http://localhost:8000/api/v1/networks \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Stalabard DAO Marketplace",
    "description": "Multi-vendor marketplace for Stalabard DAO members",
    "currency": "USD",
    "timezone": "UTC",
    "country": "US",
    "language": "en",
    "public": true,
    "enable_store_registration": true,
    "require_store_approval": true,
    "enable_customer_registration": true
  }'
```

#### Step 3: Extract Network ID

From the response, extract the `id` field:

```json
{
  "id": "network_abc123xyz",
  "name": "Stalabard DAO Marketplace",
  "currency": "USD",
  ...
}
```

#### Step 4: Generate API Key

```bash
NETWORK_ID="network_abc123xyz"

curl -X POST http://localhost:8000/api/v1/networks/$NETWORK_ID/keys \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Storefront App Key",
    "description": "API key for marketplace browsing",
    "permissions": ["read:products", "read:stores", "create:orders"]
  }'
```

#### Step 5: Save API Key

Extract the `key` field from response and save to `.env`:

```bash
echo "FLEETBASE_API_KEY={generated_key}" >> fleetbase/.env
docker-compose restart fleetbase-api
```

## Verification

### Via Console

1. Navigate to **Storefront** → **Networks**
2. Verify "Stalabard DAO Marketplace" appears in list
3. Click on network to view details
4. Verify all settings are correct
5. Check that Network ID and API Key are generated

### Via API

```bash
# List all networks
curl http://localhost:8000/api/v1/networks

# Get specific network
curl http://localhost:8000/api/v1/networks/{network_id}

# Verify network key works
curl http://localhost:8000/api/v1/storefront/products \
  -H "Authorization: Bearer {network_key}"
```

### Via Automated Tests

```bash
# Run network creation tests
npm test tests/network-creation.test.js
```

Expected output:
```
✓ Network endpoint should be accessible
✓ Should create network with correct name
✓ Created network should have unique Network ID
✓ Network should have API key generated
✓ Network currency should be configured
✓ Network should be retrievable in list
```

## Network Configuration Details

### Currency Settings

The network uses **USD** as the base currency:

- All product prices stored in USD
- Supports multiple payment methods (configured later)
- Tax calculations in USD
- Order totals in USD

Future enhancement: Multi-currency support can be added.

### Membership Integration

The network integrates with Stalabard DAO:

- **DAO Address**: `D6d8TZrNFwg3QG97JLLfTjgdJequ84wMhQ8f12UW56Rq`
- **NFT Collection**: `3e22667e998143beef529eda8c84ee394a838aa705716c3b6fe0b3d5f913ac4c`
- **Verification**: Solana blockchain via RPC endpoint
- **Access Control**: Membership verified before checkout

### Store Registration Flow

When sellers want to join the network:

1. Seller verifies DAO NFT ownership
2. Seller submits store registration request
3. DAO reviews application (manual or automated)
4. Upon approval, store is created in network
5. Seller receives Store API Key
6. Seller can manage products via Console or API

### Customer Access Flow

When buyers want to shop:

1. Customer connects wallet
2. System verifies DAO NFT ownership
3. Upon verification, customer can browse products
4. Customer can add to cart and checkout
5. Orders routed to appropriate stores

## Troubleshooting

### Network Creation Fails

**Symptoms:**
- Error message when clicking Save
- Network not appearing in list
- Validation errors

**Solutions:**

1. **Check Storefront Extension**
   ```bash
   # Verify Storefront is active
   curl http://localhost:8000/api/v1/extensions | grep storefront
   ```

2. **Check Logs**
   ```bash
   docker-compose logs fleetbase-api | grep -i error
   ```

3. **Verify Database**
   ```bash
   docker-compose exec fleetbase-api php artisan migrate:status
   ```

4. **Clear Cache**
   ```bash
   docker-compose exec fleetbase-api php artisan cache:clear
   docker-compose restart fleetbase-api
   ```

### API Key Generation Fails

**Symptoms:**
- Generate button doesn't work
- No key displayed after generation
- Error message appears

**Solutions:**

1. **Check Permissions**
   - Ensure logged-in user has admin role
   - Verify user can manage API keys

2. **Try Via CLI**
   ```bash
   docker-compose exec fleetbase-api php artisan fleetbase:generate-key \
     --network={network_id} \
     --name="Storefront App Key"
   ```

3. **Check Database**
   ```bash
   docker-compose exec mysql mysql -ufleetbase -pfleetbase_password \
     -e "SELECT * FROM api_keys WHERE network_id='{network_id}';"
   ```

### Network Key Not Working

**Symptoms:**
- API calls with network key return 401/403
- Authentication fails
- "Invalid API key" error

**Solutions:**

1. **Verify Key Format**
   - Key should be alphanumeric string
   - No extra spaces or characters
   - Correct key copied from Console

2. **Check Key Permissions**
   - Navigate to Network → API Keys
   - Verify key has required permissions
   - Ensure key is not disabled/revoked

3. **Test Key**
   ```bash
   curl http://localhost:8000/api/v1/storefront/products \
     -H "Authorization: Bearer {your_key}" \
     -v
   ```

4. **Regenerate Key**
   - Delete old key
   - Generate new key
   - Update `.env` file
   - Restart services

## Post-Creation Checklist

After creating the network, verify:

- [ ] Network "Stalabard DAO Marketplace" exists in Console
- [ ] Network has unique Network ID
- [ ] Network currency is set to USD
- [ ] Network API Key generated successfully
- [ ] API Key saved to `fleetbase/.env` as `FLEETBASE_API_KEY`
- [ ] Environment variables updated with DAO configuration
- [ ] Services restarted after `.env` changes
- [ ] Network accessible via API using Network Key
- [ ] No errors in application logs
- [ ] Automated tests pass (`npm test tests/network-creation.test.js`)

## Next Steps

After successful network creation:

1. ✅ Configure DAO environment variables (see README.md Environment Configuration section)
2. Test network API endpoints
3. Prepare for member authentication extension (Story 1.2)
4. Set up initial store for testing

## Support Resources

- **Fleetbase Networks Documentation**: https://docs.fleetbase.io/storefront/networks
- **API Reference**: https://docs.fleetbase.io/api/networks
- **Multi-vendor Setup**: https://docs.fleetbase.io/guides/multi-vendor
