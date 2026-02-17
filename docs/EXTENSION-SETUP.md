# Fleetbase Extension Installation Guide

This guide provides step-by-step instructions for installing and configuring the core Fleetbase extensions required for the Stalabard DAO Marketplace.

## Required Extensions

1. **Storefront** - Core e-commerce functionality (Products, Orders, Networks, Customers)
2. **FleetOps** - Places (delivery points), order routing, activity flows
3. **Pallet** (Optional) - Inventory management across delivery points

## Prerequisites

- Fleetbase platform installed and running (see INSTALLATION.md)
- Access to Fleetbase Console at http://localhost:8000/console
- Admin account created

## Installation Methods

### Method 1: Via Fleetbase Console (Recommended)

This is the recommended approach as it provides a visual interface and automatic dependency resolution.

#### Step 1: Access Extensions Marketplace

1. Open browser and navigate to: http://localhost:8000/console
2. Log in with admin credentials
3. Click **Extensions** in the left sidebar
4. Click **Marketplace** tab

#### Step 2: Install Storefront Extension

1. Locate **Storefront** extension in the marketplace
2. Click **Install** button
3. Wait for installation progress bar to complete (may take 1-2 minutes)
4. Once installed, click **Activate** button
5. Verify status shows **Active** with green indicator

**Expected Features After Activation:**
- Networks menu appears in sidebar
- Products menu appears in sidebar
- Orders menu appears in sidebar
- Customers menu appears in sidebar

#### Step 3: Install FleetOps Extension

1. Locate **FleetOps** extension in the marketplace
2. Click **Install** button
3. Wait for installation to complete
4. Click **Activate** button
5. Verify status shows **Active**

**Expected Features After Activation:**
- FleetOps menu appears in sidebar
- Places submenu available
- Orders routing submenu available
- Activity flows submenu available

#### Step 4: Install Pallet Extension (Optional)

1. Locate **Pallet** extension in the marketplace
2. Click **Install** button
3. Wait for installation to complete
4. Click **Activate** button
5. Verify status shows **Active**

**Expected Features After Activation:**
- Pallet menu appears in sidebar
- Inventory management features available
- Stock tracking across locations

#### Step 5: Verify Installation

1. Navigate to **Extensions** → **Installed**
2. Confirm all three extensions show **Active** status
3. Check that new menu items appear in sidebar
4. Test accessing each extension's main page

### Method 2: Via Command Line

Use this method if the Console interface is unavailable or for automated deployments.

#### Step 1: Access Fleetbase Container

```bash
docker-compose exec fleetbase-api bash
```

#### Step 2: Install Extensions

```bash
# Install Storefront
php artisan fleetbase:install-extension storefront

# Install FleetOps
php artisan fleetbase:install-extension fleetops

# Install Pallet (optional)
php artisan fleetbase:install-extension pallet
```

#### Step 3: Activate Extensions

```bash
# Activate Storefront
php artisan fleetbase:activate-extension storefront

# Activate FleetOps
php artisan fleetbase:activate-extension fleetops

# Activate Pallet (optional)
php artisan fleetbase:activate-extension pallet
```

#### Step 4: Clear Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

#### Step 5: Restart Services

```bash
# Exit container
exit

# Restart Fleetbase API
docker-compose restart fleetbase-api
```

### Method 3: Manual Installation from Source

Use this method only if extensions are not available in the marketplace.

#### Step 1: Clone Extension Repositories

```bash
# Create extensions directory
mkdir -p fleetbase/extensions

# Clone Storefront
cd fleetbase/extensions
git clone https://github.com/fleetbase/storefront-api.git storefront

# Clone FleetOps
git clone https://github.com/fleetbase/fleetops-api.git fleetops

# Clone Pallet (optional)
git clone https://github.com/fleetbase/pallet-api.git pallet
```

#### Step 2: Install Dependencies

```bash
# Access container
docker-compose exec fleetbase-api bash

# Install Storefront dependencies
cd /var/www/html/extensions/storefront
composer install

# Install FleetOps dependencies
cd /var/www/html/extensions/fleetops
composer install

# Install Pallet dependencies (optional)
cd /var/www/html/extensions/pallet
composer install
```

#### Step 3: Register Extensions

```bash
cd /var/www/html

# Register extensions
php artisan fleetbase:register-extension storefront
php artisan fleetbase:register-extension fleetops
php artisan fleetbase:register-extension pallet

# Activate extensions
php artisan fleetbase:activate-extension storefront
php artisan fleetbase:activate-extension fleetops
php artisan fleetbase:activate-extension pallet
```

#### Step 4: Run Migrations

```bash
# Run extension migrations
php artisan migrate --path=/var/www/html/extensions/storefront/database/migrations
php artisan migrate --path=/var/www/html/extensions/fleetops/database/migrations
php artisan migrate --path=/var/www/html/extensions/pallet/database/migrations
```

#### Step 5: Clear Cache and Restart

```bash
php artisan cache:clear
php artisan config:clear
exit

docker-compose restart fleetbase-api
```

## Verification

### Via Console

1. Navigate to http://localhost:8000/console
2. Check sidebar for new menu items:
   - **Storefront** section with Networks, Products, Orders, Customers
   - **FleetOps** section with Places, Orders, Activity Flows
   - **Pallet** section with Inventory (if installed)
3. Click each menu item to verify pages load without errors

### Via API

```bash
# Get list of installed extensions
curl http://localhost:8000/api/v1/extensions

# Expected response includes:
# {
#   "extensions": [
#     {
#       "name": "storefront",
#       "version": "x.x.x",
#       "active": true
#     },
#     {
#       "name": "fleetops",
#       "version": "x.x.x",
#       "active": true
#     }
#   ]
# }
```

### Via Automated Tests

```bash
# Run extension installation tests
npm test tests/extension-installation.test.js
```

Expected output:
```
✓ Extensions endpoint should be accessible
✓ Storefront extension should be installed
✓ Storefront extension should be activated
✓ Storefront extension directory should exist
✓ FleetOps extension should be installed
✓ FleetOps extension should be activated
✓ FleetOps extension directory should exist
```

## Extension Configuration

### Storefront Extension

After installation, configure Storefront settings:

1. Navigate to **Storefront** → **Settings**
2. Configure:
   - **Default Currency**: USD
   - **Tax Settings**: As per business requirements
   - **Shipping Options**: Configure delivery methods
   - **Payment Gateways**: Will be configured in later stories

### FleetOps Extension

After installation, configure FleetOps settings:

1. Navigate to **FleetOps** → **Settings**
2. Configure:
   - **Default Service Area**: Set geographic boundaries
   - **Order Routing Rules**: Configure automatic routing
   - **Activity Flow Templates**: Set up delivery workflows

### Pallet Extension (Optional)

After installation, configure Pallet settings:

1. Navigate to **Pallet** → **Settings**
2. Configure:
   - **Inventory Tracking**: Enable/disable features
   - **Stock Alerts**: Set low stock thresholds
   - **Multi-location**: Configure warehouse/hub settings

## Troubleshooting

### Extension Not Appearing in Marketplace

**Symptoms:**
- Extension marketplace is empty
- Specific extension not listed

**Solutions:**

1. **Check Internet Connection**
   ```bash
   docker-compose exec fleetbase-api ping -c 3 registry.fleetbase.io
   ```

2. **Clear Extension Cache**
   ```bash
   docker-compose exec fleetbase-api php artisan cache:forget extensions
   docker-compose exec fleetbase-api php artisan cache:forget marketplace
   ```

3. **Manually Refresh Marketplace**
   ```bash
   docker-compose exec fleetbase-api php artisan fleetbase:refresh-marketplace
   ```

### Installation Fails

**Symptoms:**
- Installation progress bar stops
- Error message appears
- Extension status shows "Failed"

**Solutions:**

1. **Check Logs**
   ```bash
   docker-compose logs fleetbase-api | grep -i error
   docker-compose exec fleetbase-api tail -f storage/logs/laravel.log
   ```

2. **Verify Disk Space**
   ```bash
   docker-compose exec fleetbase-api df -h
   ```

3. **Check Permissions**
   ```bash
   docker-compose exec fleetbase-api chown -R www-data:www-data /var/www/html/extensions
   docker-compose exec fleetbase-api chmod -R 775 /var/www/html/extensions
   ```

4. **Retry Installation**
   - Deactivate extension (if partially installed)
   - Uninstall extension
   - Clear cache
   - Reinstall

### Extension Installed But Not Active

**Symptoms:**
- Extension shows in installed list
- Status shows "Inactive" or "Disabled"
- Menu items don't appear

**Solutions:**

1. **Activate via Console**
   - Navigate to Extensions → Installed
   - Click **Activate** button

2. **Activate via CLI**
   ```bash
   docker-compose exec fleetbase-api php artisan fleetbase:activate-extension storefront
   docker-compose exec fleetbase-api php artisan fleetbase:activate-extension fleetops
   ```

3. **Check Dependencies**
   ```bash
   docker-compose exec fleetbase-api php artisan fleetbase:check-dependencies storefront
   ```

### Menu Items Not Appearing

**Symptoms:**
- Extension is active
- Menu items don't show in sidebar
- Console appears unchanged

**Solutions:**

1. **Clear Browser Cache**
   - Hard refresh: Ctrl+Shift+R (Windows/Linux) or Cmd+Shift+R (Mac)
   - Clear browser cache completely
   - Try incognito/private window

2. **Clear Application Cache**
   ```bash
   docker-compose exec fleetbase-api php artisan cache:clear
   docker-compose exec fleetbase-api php artisan config:clear
   docker-compose exec fleetbase-api php artisan view:clear
   ```

3. **Restart Services**
   ```bash
   docker-compose restart fleetbase-api
   ```

4. **Check User Permissions**
   - Ensure logged-in user has admin role
   - Check user permissions include extension access

### Database Migration Errors

**Symptoms:**
- Extension installs but features don't work
- Database errors in logs
- Missing tables

**Solutions:**

1. **Run Migrations Manually**
   ```bash
   docker-compose exec fleetbase-api php artisan migrate
   ```

2. **Check Migration Status**
   ```bash
   docker-compose exec fleetbase-api php artisan migrate:status
   ```

3. **Rollback and Retry**
   ```bash
   docker-compose exec fleetbase-api php artisan migrate:rollback --step=1
   docker-compose exec fleetbase-api php artisan migrate
   ```

### API Endpoints Not Working

**Symptoms:**
- Extension active in Console
- API calls return 404 or 500 errors
- Routes not registered

**Solutions:**

1. **Clear Route Cache**
   ```bash
   docker-compose exec fleetbase-api php artisan route:clear
   docker-compose exec fleetbase-api php artisan route:cache
   ```

2. **Verify Routes**
   ```bash
   docker-compose exec fleetbase-api php artisan route:list | grep storefront
   docker-compose exec fleetbase-api php artisan route:list | grep fleetops
   ```

3. **Restart Application**
   ```bash
   docker-compose restart fleetbase-api
   ```

## Post-Installation Checklist

After installing all extensions, verify:

- [ ] Storefront extension shows **Active** status
- [ ] FleetOps extension shows **Active** status
- [ ] Pallet extension shows **Active** status (if installed)
- [ ] Storefront menu items visible in Console sidebar
- [ ] FleetOps menu items visible in Console sidebar
- [ ] Pallet menu items visible in Console sidebar (if installed)
- [ ] API endpoints respond correctly (run `npm test tests/extension-installation.test.js`)
- [ ] No errors in application logs
- [ ] Database migrations completed successfully
- [ ] Extension directories exist in `fleetbase/extensions/`

## Next Steps

After successful extension installation:

1. ✅ Proceed to Network Creation (see docs/NETWORK-SETUP.md)
2. Configure extension settings as needed
3. Set up initial data (products, places, etc.)
4. Test extension functionality

## Support Resources

- **Fleetbase Extensions Documentation**: https://docs.fleetbase.io/extensions
- **Storefront Extension**: https://github.com/fleetbase/storefront-api
- **FleetOps Extension**: https://github.com/fleetbase/fleetops-api
- **Pallet Extension**: https://github.com/fleetbase/pallet-api
