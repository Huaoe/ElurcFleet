# Story 1.2: Membership Extension Scaffold & Database Models

Status: done

## Story

As a developer,
I want to create the Membership Extension with database models,
So that member identity and profile data can be persisted.

## Acceptance Criteria

**Given** Fleetbase is installed
**When** I scaffold the Membership Extension using Fleetbase CLI
**Then** Extension directory structure is created at fleetbase-membership/
**And** ServiceProvider is registered with Fleetbase
**And** Extension is activated in Fleetbase Console

**Given** the extension scaffold exists
**When** I create the MemberIdentity Eloquent model
**Then** Model has fields: uuid, wallet_address (unique indexed), membership_status (enum), verified_at, nft_token_account, last_verified_at, metadata (JSON)
**And** Model defines belongsTo(User) relationship (optional)
**And** Model defines hasOne(MemberProfile) relationship

**Given** the MemberIdentity model exists
**When** I create the MemberProfile Eloquent model
**Then** Model has fields: uuid, member_identity_uuid, display_name, avatar_url, bio, metadata (JSON)
**And** Model defines belongsTo(MemberIdentity) relationship

**Given** both models are defined
**When** I create and run database migrations
**Then** member_identities table is created with proper indexes
**And** member_profiles table is created with foreign key to member_identities
**And** Migrations run successfully without errors

## Tasks / Subtasks

- [x] Scaffold Membership Extension (AC: 1)
  - [x] Use Fleetbase CLI to create extension directory structure
  - [x] Create MembershipServiceProvider.php
  - [x] Register ServiceProvider in extension config
  - [x] Create composer.json for extension dependencies
  - [x] Activate extension in Fleetbase Console

- [x] Create MemberIdentity Eloquent Model (AC: 2)
  - [x] Create Models/MemberIdentity.php file
  - [x] Define table name, fillable fields, casts
  - [x] Add wallet_address unique constraint
  - [x] Define membership_status enum values
  - [x] Define belongsTo(User) relationship (optional)
  - [x] Define hasOne(MemberProfile) relationship

- [x] Create MemberProfile Eloquent Model (AC: 3)
  - [x] Create Models/MemberProfile.php file
  - [x] Define table name, fillable fields, casts
  - [x] Define belongsTo(MemberIdentity) relationship
  - [x] Add validation rules for display_name, avatar_url

- [x] Create and Run Database Migrations (AC: 4)
  - [x] Create migration for member_identities table
  - [x] Create migration for member_profiles table
  - [x] Add indexes: wallet_address (unique), membership_status, member_identity_uuid
  - [x] Add foreign key constraints
  - [x] Test migration up/down
  - [x] Verify tables created in database

## Dev Notes

### Technical Stack Requirements

**Platform:**
- Fleetbase (PHP/Laravel-based extension system)
- Laravel 10.x (Fleetbase's underlying framework)
- Eloquent ORM for database models
- PostgreSQL or MySQL database (from Story 1.1 setup)

**Extension Architecture:**
- Fleetbase extension pattern: Extension → ServiceProvider → Models → Services → Routes
- PSR-4 autoloading for extension classes
- Composer for PHP dependency management

[Source: `@architecture.md#2.1 Layered Fleetbase Architecture`, `@architecture.md#3.1 Custom Extensions`]

### Fleetbase Extension Architecture Pattern

**Extension Directory Structure:**
```
fleetbase-membership/
├── server/
│   ├── src/
│   │   ├── Models/
│   │   │   ├── MemberIdentity.php
│   │   │   └── MemberProfile.php
│   │   ├── Services/
│   │   │   ├── MembershipVerificationService.php (Story 1.3)
│   │   │   └── MemberIdentityService.php (Story 1.3)
│   │   ├── Http/
│   │   │   ├── Controllers/ (Story 1.4)
│   │   │   ├── Middleware/ (Story 1.5)
│   │   │   └── Resources/
│   │   └── Providers/
│   │       └── MembershipServiceProvider.php
│   ├── migrations/
│   │   ├── 2026_02_17_000001_create_member_identities_table.php
│   │   └── 2026_02_17_000002_create_member_profiles_table.php
│   ├── config/
│   │   └── membership.php
│   └── composer.json
├── addon/ (Ember.js frontend - optional for MVP)
└── package.json
```

**ServiceProvider Pattern:**
- Registers extension with Fleetbase core
- Boots models, services, routes, middleware
- Publishes migrations and config files
- Defines extension namespace and autoloading

[Source: `@architecture.md#3.3 Extension Naming and Structure`]

### MemberIdentity Model Specifications

**Purpose:** Store NFT-based membership verification state and wallet linkage

**Eloquent Model Definition:**
```php
namespace Fleetbase\Membership\Models;

use Fleetbase\Models\Model;
use Fleetbase\Models\User;

class MemberIdentity extends Model
{
    protected $table = 'member_identities';
    
    protected $fillable = [
        'uuid',
        'wallet_address',
        'membership_status',
        'verified_at',
        'nft_token_account',
        'last_verified_at',
        'metadata'
    ];
    
    protected $casts = [
        'verified_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'metadata' => 'array'
    ];
    
    // Enum values for membership_status
    const STATUS_PENDING = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_REVOKED = 'revoked';
    
    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_uuid');
    }
    
    public function profile()
    {
        return $this->hasOne(MemberProfile::class, 'member_identity_uuid', 'uuid');
    }
}
```

**Key Fields:**
- `uuid`: Primary identifier (Fleetbase uses UUIDs)
- `wallet_address`: Solana wallet address (unique indexed)
- `membership_status`: Enum (pending/verified/suspended/revoked)
- `verified_at`: Timestamp of initial verification
- `nft_token_account`: Solana NFT token account address
- `last_verified_at`: Timestamp of most recent verification
- `metadata`: JSON field for extensibility (blockchain network, verification attempts, etc.)

**Validation Rules:**
- wallet_address: unique, required, format validation (Solana base58)
- membership_status: enum validation
- nft_token_account: format validation (Solana token account)

[Source: `@architecture.md#3.1 Custom Extensions A) Membership Extension`, `@epics.md Story 1.2`]

### MemberProfile Model Specifications

**Purpose:** User-facing profile with display name, avatar, bio for marketplace interactions

**Eloquent Model Definition:**
```php
namespace Fleetbase\Membership\Models;

use Fleetbase\Models\Model;

class MemberProfile extends Model
{
    protected $table = 'member_profiles';
    
    protected $fillable = [
        'uuid',
        'member_identity_uuid',
        'display_name',
        'avatar_url',
        'bio',
        'metadata'
    ];
    
    protected $casts = [
        'metadata' => 'array'
    ];
    
    // Relationships
    public function memberIdentity()
    {
        return $this->belongsTo(MemberIdentity::class, 'member_identity_uuid', 'uuid');
    }
    
    public function store()
    {
        // Will be defined when Store relationship is established in Story 2.1
        return $this->belongsTo(\Fleetbase\Storefront\Models\Store::class, 'store_uuid', 'uuid');
    }
}
```

**Key Fields:**
- `uuid`: Primary identifier
- `member_identity_uuid`: Foreign key to member_identities
- `display_name`: User's display name (max 50 chars)
- `avatar_url`: URL to profile avatar image
- `bio`: Short bio text (max 500 chars)
- `metadata`: JSON field for additional profile data

**Validation Rules:**
- member_identity_uuid: required, exists in member_identities
- display_name: required, max 50 chars, unique
- avatar_url: nullable, valid URL format
- bio: nullable, max 500 chars

[Source: `@architecture.md#3.1 Custom Extensions A) Membership Extension`, `@epics.md Story 1.2`, `@epics.md Story 1.6`]

### Database Migration Specifications

**Migration 1: member_identities Table**

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('member_identities', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('user_uuid')->nullable()->index();
            $table->string('wallet_address')->unique()->index();
            $table->enum('membership_status', ['pending', 'verified', 'suspended', 'revoked'])
                  ->default('pending')
                  ->index();
            $table->timestamp('verified_at')->nullable();
            $table->string('nft_token_account')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key to Fleetbase users table (optional)
            $table->foreign('user_uuid')
                  ->references('uuid')
                  ->on('users')
                  ->onDelete('set null');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('member_identities');
    }
};
```

**Migration 2: member_profiles Table**

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('member_profiles', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('member_identity_uuid')->index();
            $table->uuid('store_uuid')->nullable()->index();
            $table->string('display_name', 50)->unique();
            $table->string('avatar_url')->nullable();
            $table->text('bio')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key to member_identities
            $table->foreign('member_identity_uuid')
                  ->references('uuid')
                  ->on('member_identities')
                  ->onDelete('cascade');
            
            // Foreign key to stores (will be used in Story 2.1)
            // Commented out for now since stores table may not exist yet
            // $table->foreign('store_uuid')
            //       ->references('uuid')
            //       ->on('stores')
            //       ->onDelete('set null');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('member_profiles');
    }
};
```

**Index Strategy:**
- Primary: uuid (both tables)
- Unique: wallet_address (member_identities), display_name (member_profiles)
- Indexed: membership_status (filtering), member_identity_uuid (joins), store_uuid (joins)
- Foreign Keys: user_uuid, member_identity_uuid (cascade delete on identity removal)

[Source: `@architecture.md#3.1 Custom Extensions`, `@architecture.md#9.3 Performance`]

### Fleetbase CLI Scaffolding Commands

**Create Extension Scaffold:**
```bash
# From Fleetbase root directory
php artisan fleetbase:create-extension membership

# Or manually create directory structure
mkdir -p extensions/fleetbase-membership/server/src/{Models,Services,Http/{Controllers,Middleware,Resources},Providers}
mkdir -p extensions/fleetbase-membership/server/{migrations,config}
```

**ServiceProvider Registration:**
- Create `MembershipServiceProvider.php` extending `\Fleetbase\Providers\CoreServiceProvider`
- Register in `config/fleetbase.php` extensions array
- Boot models, routes, migrations, config

**Extension Activation:**
- Access Fleetbase Console at http://localhost/console
- Navigate to Extensions → Available Extensions
- Find "Membership" extension
- Click "Activate" button
- Verify activation in Extensions → Installed

[Source: Story 1.1 learnings, Fleetbase extension documentation patterns]

### Cross-Extension Relationships (Future Stories)

**Relationships to be established later:**
- `MemberProfile` → Storefront `Customer` (1:1 via customer_uuid) - Story 2.1
- `MemberProfile` → Storefront `Store` (1:1 via store_uuid for sellers) - Story 2.1
- `MemberIdentity` → DAO Governance `ProductGovernance` (via MemberProfile) - Story 3.1

**Note for Developers:**
- Keep relationships loosely coupled initially
- Use UUIDs for foreign keys (Fleetbase standard)
- Add foreign key constraints only after related tables exist
- Use nullable foreign keys where relationships are optional

[Source: `@architecture.md#3.4 Cross-Extension Relationships`]

### Testing Requirements

**Unit Tests:**
- Model creation with valid data
- Model validation rules (wallet_address format, display_name length)
- Relationship queries (MemberIdentity → MemberProfile)
- Enum validation for membership_status

**Migration Tests:**
- Migrations run without errors
- Tables created with correct schema
- Indexes created properly
- Foreign key constraints enforced
- Migration rollback works correctly

**Integration Tests (with Story 1.1 setup):**
- Extension activates in Fleetbase Console
- Models are autoloaded via ServiceProvider
- Database connection from Story 1.1 works with new tables

**Test Data:**
```php
// Example test fixture
$memberIdentity = MemberIdentity::create([
    'uuid' => \Illuminate\Support\Str::uuid(),
    'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
    'membership_status' => MemberIdentity::STATUS_VERIFIED,
    'verified_at' => now(),
    'nft_token_account' => 'TokenAccount123...',
    'metadata' => ['network' => 'mainnet-beta']
]);

$memberProfile = MemberProfile::create([
    'uuid' => \Illuminate\Support\Str::uuid(),
    'member_identity_uuid' => $memberIdentity->uuid,
    'display_name' => 'TestUser',
    'avatar_url' => 'https://example.com/avatar.jpg',
    'bio' => 'Test bio'
]);
```

[Source: `@architecture.md#10.4 Release Gate for MVP`]

### Security Considerations

**Data Protection:**
- Never expose wallet private keys (only store public wallet address)
- Metadata field should not contain sensitive information
- Use Laravel's mass assignment protection (fillable array)
- Validate all input data via Form Requests (Story 1.4)

**Database Security:**
- Use parameterized queries (Eloquent ORM handles this)
- Apply unique constraints at database level (not just application)
- Use soft deletes for audit trail preservation
- Ensure foreign key constraints maintain referential integrity

**Access Control:**
- Models should not be directly accessible via API without middleware
- Middleware validation will be added in Story 1.5
- Profile updates must validate ownership (Story 1.6)

[Source: `@architecture.md#9.1 Security`, `@prd.md#6.1 Security`]

### Common Setup Issues and Solutions

**Issue**: Extension not appearing in Console
- **Solution**: Clear Fleetbase cache (`php artisan cache:clear`), verify ServiceProvider registration in config/fleetbase.php

**Issue**: Migration fails with "table already exists"
- **Solution**: Check migration status (`php artisan migrate:status`), rollback if needed, ensure no duplicate migration files

**Issue**: Foreign key constraint fails
- **Solution**: Ensure referenced table exists first, use nullable for optional relationships, check UUID format consistency

**Issue**: Eloquent relationships return null
- **Solution**: Verify foreign key field names match relationship definitions, use `with()` for eager loading, check data exists in both tables

**Issue**: Composer autoload not finding model classes
- **Solution**: Run `composer dump-autoload` after creating new classes, verify PSR-4 namespace in composer.json

[Source: Story 1.1 learnings on Docker and database setup]

### Project Structure Notes

**Alignment with Fleetbase Architecture:**
- Extension follows Fleetbase's modular extension pattern
- Uses Fleetbase's UUID-based primary keys (not auto-increment)
- Extends `Fleetbase\Models\Model` base class
- Follows Laravel 10.x conventions (Fleetbase's framework)

**File Organization:**
- Server-side code in `server/src/` directory
- Migrations in `server/migrations/` directory
- Config in `server/config/` directory
- Follows PSR-4 autoloading standards

**Namespace Convention:**
- `Fleetbase\Membership\Models\`
- `Fleetbase\Membership\Services\`
- `Fleetbase\Membership\Http\Controllers\`

[Source: `@architecture.md#3.3 Extension Naming and Structure`, Story 1.1 file structure]

### References

- **Architecture Section 3.1**: Membership Extension specification [`@architecture.md#3.1 Custom Extensions A) Membership Extension`]
- **Architecture Section 3.3**: Extension naming and structure patterns [`@architecture.md#3.3 Extension Naming and Structure`]
- **PRD Section 4.2**: Custom extension entities [`@prd.md#4.2 Custom Extension Entities`]
- **Epic 1 Story 1.2**: Detailed acceptance criteria [`@epics.md Story 1.2`]
- **Story 1.1**: Platform setup and Docker configuration [`@1-1-fleetbase-platform-setup-network-creation.md`]
- **Architecture Section 9**: Security and performance requirements [`@architecture.md#9 Non-Functional Architecture Controls`]

## Dev Agent Record

### Agent Model Used

{{agent_model_name_version}}

### Debug Log References

### Completion Notes List

### File List

**Extension Scaffold (Task 1):**
- Created complete directory structure following Fleetbase extension pattern
- Implemented MembershipServiceProvider extending CoreServiceProvider
- Created composer.json with PSR-4 autoloading for Fleetbase\Membership namespace
- Created membership.php config file with status definitions and validation rules
- Created placeholder routes/api.php for future API endpoints (Story 1.4)
- Created package.json for extension metadata

**MemberIdentity Model (Task 2):**
- Implemented full Eloquent model with UUID primary key and HasUuid trait
- Defined all required fields: uuid, user_uuid, wallet_address, membership_status, verified_at, nft_token_account, last_verified_at, metadata
- Implemented status enum constants: STATUS_PENDING, STATUS_VERIFIED, STATUS_SUSPENDED, STATUS_REVOKED
- Added belongsTo(User) relationship (nullable foreign key)
- Added hasOne(MemberProfile) relationship
- Implemented helper methods: isPending(), isVerified(), isSuspended(), isRevoked(), markAsVerified(), updateLastVerified()
- Applied SoftDeletes trait for audit trail preservation
- Cast verified_at and last_verified_at to datetime, metadata to array

**MemberProfile Model (Task 3):**
- Implemented full Eloquent model with UUID primary key and HasUuid trait
- Defined all required fields: uuid, member_identity_uuid, store_uuid, display_name, avatar_url, bio, metadata
- Added belongsTo(MemberIdentity) relationship with cascade delete
- Added belongsTo(Store) relationship (for future Story 2.1)
- Implemented display_name mutator to enforce 50 char max length
- Implemented bio mutator to enforce 500 char max length
- Implemented display_name accessor to default to "Anonymous Member" when null
- Applied SoftDeletes trait
- Cast metadata to array

**Database Migrations (Task 4):**
- Created 2026_02_17_000001_create_member_identities_table.php migration
  - UUID primary key
  - user_uuid nullable foreign key to users table with SET NULL on delete
  - wallet_address unique indexed string
  - membership_status enum with index (pending, verified, suspended, revoked)
  - verified_at, last_verified_at nullable timestamps
  - nft_token_account nullable string
  - metadata JSON field
  - timestamps and soft deletes
- Created 2026_02_17_000002_create_member_profiles_table.php migration
  - UUID primary key
  - member_identity_uuid foreign key with CASCADE on delete
  - store_uuid nullable (for future Story 2.1)
  - display_name unique string(50)
  - avatar_url nullable string
  - bio nullable text
  - metadata JSON field
  - timestamps and soft deletes

**Testing (Comprehensive Test Suite):**
- Created MemberIdentityTest.php with 12 unit tests covering:
  - Model creation with valid data
  - Wallet address uniqueness constraint
  - Enum status values
  - MemberProfile relationship
  - DateTime casting for verified_at
  - Array casting for metadata
  - Status helper methods (isPending, isVerified)
  - markAsVerified() method
  - updateLastVerified() method
  - Soft delete functionality
- Created MemberProfileTest.php with 11 unit tests covering:
  - Model creation with valid data
  - Display name uniqueness constraint
  - MemberIdentity relationship
  - Display name max length enforcement (50 chars)
  - Bio max length enforcement (500 chars)
  - Display name default value ("Anonymous Member")
  - Metadata array casting
  - Nullable avatar_url and bio
  - Cascade delete when MemberIdentity deleted
  - Soft delete functionality
- Created MigrationTest.php with 7 integration tests covering:
  - Table existence verification
  - Column existence verification for both tables
  - Unique index on wallet_address
  - Unique index on display_name
  - Migration rollback functionality
  - Foreign key constraint verification

**Documentation:**
- Created comprehensive README.md with installation, usage examples, and future roadmap

### File List

**Extension Structure:**
- fleetbase/extensions/fleetbase-membership/server/composer.json
- fleetbase/extensions/fleetbase-membership/server/config/membership.php
- fleetbase/extensions/fleetbase-membership/server/src/Providers/MembershipServiceProvider.php
- fleetbase/extensions/fleetbase-membership/server/src/Models/MemberIdentity.php
- fleetbase/extensions/fleetbase-membership/server/src/Models/MemberProfile.php
- fleetbase/extensions/fleetbase-membership/server/migrations/2026_02_17_000001_create_member_identities_table.php
- fleetbase/extensions/fleetbase-membership/server/migrations/2026_02_17_000002_create_member_profiles_table.php
- fleetbase/extensions/fleetbase-membership/server/routes/api.php
- fleetbase/extensions/fleetbase-membership/package.json
- fleetbase/extensions/fleetbase-membership/README.md

**Test Files:**
- tests/Unit/Membership/MemberIdentityTest.php
- tests/Unit/Membership/MemberProfileTest.php
- tests/Integration/Membership/MigrationTest.php

**Project Files:**
- _bmad-output/implementation-artifacts/sprint-status.yaml (updated status to in-progress, then review)
- _bmad-output/implementation-artifacts/1-2-membership-extension-scaffold-database-models.md (this file)
