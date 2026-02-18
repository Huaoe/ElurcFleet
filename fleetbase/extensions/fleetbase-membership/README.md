# Fleetbase Membership Extension

NFT-based membership verification and profile management extension for Fleetbase.

## Overview

This extension provides NFT-based membership verification for the Stalabard DAO Marketplace, allowing members to verify their Solana NFT ownership and create profiles for marketplace interactions.

## Features

- **MemberIdentity Model**: Stores wallet addresses and NFT verification status
- **MemberProfile Model**: User-facing profiles with display names, avatars, and bios
- **Database Migrations**: Automated table creation with proper indexes and foreign keys
- **Status Management**: Track membership status (pending, verified, suspended, revoked)

## Installation

1. The extension is located in `extensions/fleetbase-membership/`
2. Ensure Fleetbase is running via Docker Compose
3. Run migrations:
   ```bash
   docker exec -it fleetbase-api php artisan migrate --path=extensions/fleetbase-membership/server/migrations
   ```

## Database Schema

### member_identities
- `uuid` (primary key)
- `user_uuid` (nullable, foreign key to users)
- `wallet_address` (unique, indexed)
- `membership_status` (enum: pending, verified, suspended, revoked)
- `verified_at` (timestamp)
- `nft_token_account` (string)
- `last_verified_at` (timestamp)
- `metadata` (JSON)
- Timestamps and soft deletes

### member_profiles
- `uuid` (primary key)
- `member_identity_uuid` (foreign key to member_identities)
- `store_uuid` (nullable, for future store relationship)
- `display_name` (unique, max 50 chars)
- `avatar_url` (nullable)
- `bio` (nullable, max 500 chars)
- `metadata` (JSON)
- Timestamps and soft deletes

## Usage

### Creating a Member Identity

```php
use Fleetbase\Membership\Models\MemberIdentity;
use Illuminate\Support\Str;

$memberIdentity = MemberIdentity::create([
    'uuid' => Str::uuid(),
    'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
    'membership_status' => MemberIdentity::STATUS_PENDING,
    'metadata' => ['network' => 'mainnet-beta']
]);
```

### Creating a Member Profile

```php
use Fleetbase\Membership\Models\MemberProfile;

$profile = MemberProfile::create([
    'uuid' => Str::uuid(),
    'member_identity_uuid' => $memberIdentity->uuid,
    'display_name' => 'CryptoUser123',
    'avatar_url' => 'https://example.com/avatar.jpg',
    'bio' => 'Blockchain enthusiast and marketplace participant'
]);
```

### Verifying a Member

```php
$memberIdentity->markAsVerified();
```

## Testing

Run the test suite:

```bash
# Unit tests
npm test tests/Unit/Membership/

# Integration tests
npm test tests/Integration/Membership/
```

## Configuration

Configuration is available in `server/config/membership.php`:

- Membership status definitions
- Validation rules (display name length, bio length)
- Blockchain network settings

## Future Development

- Story 1.3: NFT verification service
- Story 1.4: Member wallet connection API
- Story 1.5: Membership verification middleware
- Story 1.6: Member profile management endpoints

## License

MIT
