# Story 1.6: Member Profile Management

Status: in-progress

## Story

As a verified member,
I want to create and update my profile information,
So that other members can identify me in the marketplace.

## Acceptance Criteria

1. **Given** I am a verified member
**When** I first access the platform after verification
**Then** MemberProfile record is automatically created
**And** Profile is linked to my MemberIdentity
**And** Default display_name is set from wallet address (truncated)

2. **Given** my MemberProfile exists
**When** I PATCH /membership/profile with display_name, avatar_url, bio
**Then** Profile fields are validated (display_name max 50 chars, valid URL for avatar)
**And** Profile is updated with new values
**And** Response returns updated profile data

3. **Given** I provide an invalid avatar URL
**When** I attempt to update my profile
**Then** Request returns 422 Validation Error
**And** Error message specifies invalid avatar_url format

4. **Given** another member views my products or orders
**When** They query seller or buyer attribution
**Then** My display_name and avatar_url are visible
**And** My wallet_address is NOT exposed (privacy)

## Tasks / Subtasks

- [ ] Implement Auto-Creation of MemberProfile (AC: 1)
  - [ ] Add profile creation logic to MembershipVerificationService
  - [ ] Create profile automatically after successful verification
  - [ ] Generate default display_name from wallet address (first 8 chars)
  - [ ] Link profile to MemberIdentity via member_identity_uuid
  - [ ] Ensure idempotent operation (don't duplicate profiles)

- [ ] Add updateProfile() Method to MembershipController (AC: 2)
  - [ ] Implement PATCH /membership/profile endpoint
  - [ ] Apply verify.member middleware (requires verified membership)
  - [ ] Extract authenticated member from request
  - [ ] Call MemberProfileService to update profile
  - [ ] Return updated profile data in response

- [ ] Create UpdateMemberProfileRequest Validator (AC: 2, 3)
  - [ ] Create Http/Requests/UpdateMemberProfileRequest.php
  - [ ] Validate display_name (max 50 chars, alphanumeric + spaces)
  - [ ] Validate avatar_url (valid URL format, optional)
  - [ ] Validate bio (max 500 chars, optional)
  - [ ] Return 422 validation errors with clear messages

- [ ] Create MemberProfileService (AC: 2)
  - [ ] Create Services/MemberProfileService.php
  - [ ] Implement updateProfile(member_uuid, data) method
  - [ ] Query MemberProfile by member_identity_uuid
  - [ ] Update profile fields with validated data
  - [ ] Return updated MemberProfile model
  - [ ] Log profile updates with correlation_id

- [ ] Implement Privacy Controls (AC: 4)
  - [ ] Ensure MemberProfileResource excludes wallet_address
  - [ ] Include only display_name, avatar_url, bio in public responses
  - [ ] Add member_uuid for internal references (not wallet address)
  - [ ] Document privacy policy in API responses

- [ ] Add Profile Update Tests (All AC)
  - [ ] Test auto-creation of profile on verification
  - [ ] Test successful profile update with valid data
  - [ ] Test validation errors for invalid display_name
  - [ ] Test validation errors for invalid avatar_url
  - [ ] Test profile privacy (wallet_address not exposed)
  - [ ] Test middleware protection on update endpoint
  - [ ] Test idempotent profile creation

## Dev Notes

### Technical Stack Requirements

**Platform:**
- Fleetbase (PHP/Laravel-based extension system)
- Laravel 10.x HTTP layer (Controllers, Form Requests)
- Eloquent ORM for MemberProfile queries
- Laravel validation rules

**Dependencies from Previous Stories:**
- Story 1.2: MemberProfile model with display_name, avatar_url, bio fields
- Story 1.3: MembershipVerificationService for verification flow
- Story 1.4: MembershipController with profile() endpoint (GET)
- Story 1.5: VerifyMemberMiddleware for route protection

**New Components:**
- MemberProfileService for profile management operations
- UpdateMemberProfileRequest for input validation
- Auto-creation logic in verification flow
- PATCH endpoint for profile updates

[Source: `@architecture.md#3.1 Custom Extensions`, `@epics.md Story 1.6`]

### Auto-Creation of MemberProfile

**Integration Point in Verification Flow:**
```php
// In MembershipVerificationService::verifyMembership()
public function verifyMembership(string $walletAddress, string $signature): array
{
    // ... existing verification logic ...
    
    // After successful verification and MemberIdentity creation/update
    if ($verificationResult['success']) {
        $member = $verificationResult['member'];
        
        // Auto-create profile if it doesn't exist
        $profile = $this->ensureMemberProfile($member);
        
        $verificationResult['profile'] = $profile;
    }
    
    return $verificationResult;
}

protected function ensureMemberProfile(MemberIdentity $member): MemberProfile
{
    // Check if profile already exists
    $profile = MemberProfile::where('member_identity_uuid', $member->uuid)->first();
    
    if ($profile) {
        return $profile; // Idempotent - return existing profile
    }
    
    // Create new profile with defaults
    $profile = MemberProfile::create([
        'uuid' => Str::uuid(),
        'member_identity_uuid' => $member->uuid,
        'display_name' => $this->generateDefaultDisplayName($member->wallet_address),
        'avatar_url' => null,
        'bio' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    Log::info('Member profile auto-created', [
        'member_uuid' => $member->uuid,
        'profile_uuid' => $profile->uuid,
        'display_name' => $profile->display_name,
    ]);
    
    return $profile;
}

protected function generateDefaultDisplayName(string $walletAddress): string
{
    // Truncate wallet address: "7xKXtg2C..." -> "7xKXtg2C"
    return substr($walletAddress, 0, 8);
}
```

**Design Rationale:**
- Profile creation happens automatically during verification (AC#1)
- Idempotent operation prevents duplicate profiles
- Default display_name uses first 8 characters of wallet address
- Profile is immediately available after verification
- No separate profile creation endpoint needed

[Source: `@epics.md Story 1.6 AC#1`, `@1-3-nft-badge-ownership-verification-service.md#MembershipVerificationService Specifications`]

### MemberProfileService Implementation

**Service Class Structure:**
```php
namespace Fleetbase\Membership\Services;

use Fleetbase\Membership\Models\MemberProfile;
use Fleetbase\Membership\Models\MemberIdentity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MemberProfileService
{
    /**
     * Update member profile
     */
    public function updateProfile(string $memberUuid, array $data): MemberProfile
    {
        $correlationId = request()->header('X-Correlation-ID', Str::uuid());
        
        // Find profile by member identity UUID
        $profile = MemberProfile::where('member_identity_uuid', $memberUuid)->firstOrFail();
        
        // Update only provided fields
        if (isset($data['display_name'])) {
            $profile->display_name = $data['display_name'];
        }
        
        if (isset($data['avatar_url'])) {
            $profile->avatar_url = $data['avatar_url'];
        }
        
        if (isset($data['bio'])) {
            $profile->bio = $data['bio'];
        }
        
        $profile->updated_at = now();
        $profile->save();
        
        Log::info('Member profile updated', [
            'member_uuid' => $memberUuid,
            'profile_uuid' => $profile->uuid,
            'updated_fields' => array_keys($data),
            'correlation_id' => $correlationId,
        ]);
        
        return $profile;
    }
    
    /**
     * Get profile by member UUID
     */
    public function getProfile(string $memberUuid): ?MemberProfile
    {
        return MemberProfile::where('member_identity_uuid', $memberUuid)->first();
    }
}
```

**Service Registration:**
```php
// In MembershipServiceProvider
public function register()
{
    $this->app->singleton(MemberProfileService::class, function ($app) {
        return new MemberProfileService();
    });
}
```

[Source: `@architecture.md#5 Service and Event Architecture`, `@1-3-nft-badge-ownership-verification-service.md#Service Design Patterns`]

### Controller Method for Profile Update

**Add to MembershipController:**
```php
use Fleetbase\Membership\Http\Requests\UpdateMemberProfileRequest;
use Fleetbase\Membership\Services\MemberProfileService;

class MembershipController extends Controller
{
    protected MemberProfileService $profileService;
    
    public function __construct(
        MembershipVerificationService $verificationService,
        MemberIdentityService $memberService,
        MemberProfileService $profileService
    ) {
        $this->verificationService = $verificationService;
        $this->memberService = $memberService;
        $this->profileService = $profileService;
    }
    
    /**
     * Update member profile
     * PATCH /storefront/v1/membership/profile
     */
    public function updateProfile(UpdateMemberProfileRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid());
        
        try {
            // Get authenticated member from middleware
            $member = $request->attributes->get('member_identity');
            
            if (!$member) {
                return $this->errorResponse(
                    'Authentication Required',
                    'Member context not found',
                    401,
                    $correlationId
                );
            }
            
            // Update profile
            $profile = $this->profileService->updateProfile(
                $member->uuid,
                $request->validated()
            );
            
            return $this->successResponse([
                'profile' => new MemberProfileResource($profile),
            ], 200, $correlationId);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Profile not found for member', [
                'member_uuid' => $member->uuid ?? null,
                'correlation_id' => $correlationId,
            ]);
            
            return $this->errorResponse(
                'Profile Not Found',
                'Member profile does not exist',
                404,
                $correlationId
            );
            
        } catch (\Exception $e) {
            Log::error('Profile update failed', [
                'error' => $e->getMessage(),
                'member_uuid' => $member->uuid ?? null,
                'correlation_id' => $correlationId,
            ]);
            
            return $this->errorResponse(
                'Update Failed',
                'Failed to update profile',
                500,
                $correlationId
            );
        }
    }
}
```

[Source: `@1-4-member-wallet-connection-verification-api.md#Controller Structure`, `@architecture.md#6.2 API Standards`]

### Form Request Validation

**UpdateMemberProfileRequest:**
```php
namespace Fleetbase\Membership\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }
    
    public function rules(): array
    {
        return [
            'display_name' => [
                'sometimes',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9\s\-\_]+$/', // Alphanumeric, spaces, hyphens, underscores
            ],
            'avatar_url' => [
                'sometimes',
                'nullable',
                'url',
                'max:500',
                'regex:/^https?:\/\//', // Must be http or https
            ],
            'bio' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }
    
    public function messages(): array
    {
        return [
            'display_name.max' => 'Display name must not exceed 50 characters',
            'display_name.regex' => 'Display name can only contain letters, numbers, spaces, hyphens, and underscores',
            'avatar_url.url' => 'Avatar URL must be a valid URL',
            'avatar_url.regex' => 'Avatar URL must use http or https protocol',
            'bio.max' => 'Bio must not exceed 500 characters',
        ];
    }
}
```

**Validation Rules Explained:**
- `display_name`: Max 50 chars, alphanumeric with spaces/hyphens/underscores
- `avatar_url`: Valid URL with http/https protocol, max 500 chars, nullable
- `bio`: Max 500 chars, nullable
- All fields are `sometimes` (optional) - partial updates allowed

[Source: `@architecture.md#6.2 API Standards`, `@1-4-member-wallet-connection-verification-api.md#Form Request Validation`]

### Route Registration

**Add to api.php:**
```php
// Protected routes requiring verified membership
Route::prefix('storefront/v1/membership')
    ->middleware(['fleetbase.auth', 'verify.member'])
    ->group(function () {
        Route::get('status', [MembershipController::class, 'status']);
        Route::get('profile', [MembershipController::class, 'profile']);
        Route::patch('profile', [MembershipController::class, 'updateProfile']); // NEW
    });
```

**Route Design:**
- PATCH /membership/profile requires authentication + verified membership
- Uses verify.member middleware from Story 1.5
- Member context available via request attributes
- Follows RESTful conventions (PATCH for partial update)

[Source: `@1-5-membership-verification-middleware-access-control.md#Route Application Strategy`, `@architecture.md#6.1 Route Groups`]

### Privacy Controls & Data Exposure

**MemberProfileResource (ensure privacy):**
```php
namespace Fleetbase\Membership\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MemberProfileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'display_name' => $this->display_name,
            'avatar_url' => $this->avatar_url,
            'bio' => $this->bio,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            // NEVER expose: wallet_address, member_identity_uuid (internal only)
        ];
    }
}
```

**Privacy Principles:**
- `wallet_address` is NEVER exposed in API responses (AC#4)
- `member_identity_uuid` is internal reference only
- Public fields: display_name, avatar_url, bio, timestamps
- Use `uuid` for profile references in other contexts
- Document privacy policy in API documentation

**Attribution in Other Features:**
```php
// Example: Product listing with seller attribution
{
  "product": {
    "uuid": "product-123",
    "name": "Fresh Tomatoes",
    "seller": {
      "profile_uuid": "profile-456",
      "display_name": "7xKXtg2C",  // Visible
      "avatar_url": "https://...",  // Visible
      // wallet_address NOT included
    }
  }
}
```

[Source: `@epics.md Story 1.6 AC#4`, `@architecture.md#9.1 Security`]

### Response Format Standards

**Success Response (200 OK):**
```json
{
  "data": {
    "profile": {
      "uuid": "profile-uuid",
      "display_name": "CryptoFarmer",
      "avatar_url": "https://example.com/avatar.jpg",
      "bio": "Organic produce from my garden",
      "created_at": "2026-02-17T10:00:00Z",
      "updated_at": "2026-02-17T15:30:00Z"
    }
  },
  "meta": {
    "correlation_id": "req-12345"
  }
}
```

**Validation Error Response (422 Unprocessable Entity):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "display_name": [
      "Display name must not exceed 50 characters"
    ],
    "avatar_url": [
      "Avatar URL must be a valid URL"
    ]
  }
}
```

**Profile Not Found (404 Not Found):**
```json
{
  "errors": [
    {
      "status": "404",
      "title": "Profile Not Found",
      "detail": "Member profile does not exist",
      "meta": {
        "correlation_id": "req-12345"
      }
    }
  ]
}
```

[Source: `@1-4-member-wallet-connection-verification-api.md#Response Format Standards`, `@architecture.md#6.2 API Standards`]

### Error Handling & Logging

**Logging Requirements:**
```php
// Log profile auto-creation
Log::info('Member profile auto-created', [
    'member_uuid' => $member->uuid,
    'profile_uuid' => $profile->uuid,
    'display_name' => $profile->display_name,
    'correlation_id' => $correlationId,
]);

// Log profile updates
Log::info('Member profile updated', [
    'member_uuid' => $member->uuid,
    'profile_uuid' => $profile->uuid,
    'updated_fields' => array_keys($data),
    'correlation_id' => $correlationId,
]);

// Log profile update failures
Log::error('Profile update failed', [
    'error' => $exception->getMessage(),
    'member_uuid' => $member->uuid,
    'correlation_id' => $correlationId,
]);
```

**Error Scenarios:**
1. **Profile Not Found (404)**: Member has no profile (shouldn't happen with auto-creation)
2. **Validation Error (422)**: Invalid input data
3. **Unauthorized (401)**: Not authenticated
4. **Forbidden (403)**: Not verified member (middleware blocks)
5. **Server Error (500)**: Database or unexpected errors

**NFR Compliance:**
- NFR-030: Include correlation_id in all logs
- NFR-033: Use Laravel structured logging
- NFR-004: Include request-level audit metadata

[Source: `@architecture.md#8 Observability and Audit Architecture`, `@1-4-member-wallet-connection-verification-api.md#Error Handling & Logging`]

### Testing Strategy

**Unit Tests:**
```php
// tests/Unit/Membership/MemberProfileServiceTest.php

public function test_update_profile_with_valid_data()
{
    // Create member and profile
    // Call updateProfile() with valid data
    // Assert profile fields updated
    // Assert updated_at changed
}

public function test_update_profile_partial_update()
{
    // Create profile with all fields
    // Update only display_name
    // Assert display_name changed
    // Assert avatar_url and bio unchanged
}

public function test_get_profile_by_member_uuid()
{
    // Create profile
    // Call getProfile()
    // Assert correct profile returned
}
```

**Integration Tests:**
```php
// tests/Integration/Membership/ProfileManagementTest.php

public function test_profile_auto_created_on_verification()
{
    // Mock Solana RPC with valid NFT
    // POST /membership/verify
    // Assert MemberProfile created
    // Assert display_name is truncated wallet address
}

public function test_update_profile_with_valid_data()
{
    // Create verified member with profile
    // Authenticate as member
    // PATCH /membership/profile with new display_name
    // Assert 200 response
    // Assert profile updated in database
}

public function test_update_profile_validation_errors()
{
    // Create verified member
    // Authenticate as member
    // PATCH /membership/profile with display_name > 50 chars
    // Assert 422 validation error
    // Assert error message specifies field
}

public function test_update_profile_requires_authentication()
{
    // PATCH /membership/profile without auth
    // Assert 401 Unauthorized
}

public function test_update_profile_requires_verified_membership()
{
    // Create user without verified membership
    // Authenticate as user
    // PATCH /membership/profile
    // Assert 403 Forbidden (middleware blocks)
}

public function test_profile_privacy_wallet_not_exposed()
{
    // Create verified member with profile
    // GET /membership/profile
    // Assert response includes display_name, avatar_url, bio
    // Assert response does NOT include wallet_address
}

public function test_idempotent_profile_creation()
{
    // Create member with profile
    // Call verification again
    // Assert no duplicate profile created
    // Assert existing profile returned
}
```

[Source: `@architecture.md#10.4 Release Gate for MVP`, `@1-4-member-wallet-connection-verification-api.md#Testing Strategy`]

### Previous Story Intelligence

**From Story 1.4 (Member Wallet Connection & Verification API):**

**Key Learnings:**
- MembershipController already has profile() GET endpoint
- MemberProfileResource already created for serialization
- Custom token authentication system implemented
- Response format follows JSON:API structure
- Correlation IDs used throughout

**Files Already Created:**
- `MembershipController.php` - Add updateProfile() method here
- `MemberProfileResource.php` - Already implements privacy controls
- `api.php` - Add PATCH route to existing route group

**Integration Points:**
- updateProfile() method follows same pattern as verify(), status(), profile()
- Uses same error response helpers
- Uses same middleware stack (fleetbase.auth + verify.member)
- Member context from request attributes (set by middleware)

**From Story 1.5 (Membership Verification Middleware):**

**Key Learnings:**
- verify.member middleware provides member context in request
- Member accessible via `$request->attributes->get('member_identity')`
- Middleware blocks non-verified members with 403
- All protected routes use middleware stack

**What This Story Adds:**
- Auto-creation of MemberProfile during verification
- Profile update endpoint with validation
- MemberProfileService for profile operations
- Privacy controls ensuring wallet_address never exposed

[Source: `@1-4-member-wallet-connection-verification-api.md`, `@1-5-membership-verification-middleware-access-control.md`]

### Preparation for Future Stories

**Epic 2 (Seller Network Participation):**
- Story 2.1 will use MemberProfile.display_name for Store.name
- Store creation requires verified membership and profile
- Profile must exist before store creation

**Epic 3 (Product Catalog Management):**
- Product listings will display seller profile (display_name, avatar_url)
- Seller attribution uses profile_uuid, not wallet_address
- Profile data visible to all marketplace users

**Epic 5-8 (Orders, Payments, Delivery):**
- Order attribution shows buyer/seller profiles
- Profile data used in notifications and UI
- Privacy maintained throughout order lifecycle

[Source: `@architecture.md#3.1 Custom Extensions`, `@epics.md` Epic 2-8]

### Database Schema Considerations

**MemberProfile Table (from Story 1.2):**
```sql
CREATE TABLE member_profiles (
    uuid VARCHAR(36) PRIMARY KEY,
    member_identity_uuid VARCHAR(36) NOT NULL,
    display_name VARCHAR(50) NOT NULL,
    avatar_url VARCHAR(500) NULL,
    bio TEXT NULL,
    store_uuid VARCHAR(36) NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    FOREIGN KEY (member_identity_uuid) REFERENCES member_identities(uuid) ON DELETE CASCADE,
    UNIQUE KEY (member_identity_uuid)
);
```

**Key Constraints:**
- `member_identity_uuid` is UNIQUE (one profile per member)
- `display_name` is NOT NULL (always has default value)
- `avatar_url` and `bio` are nullable (optional fields)
- CASCADE delete ensures profile deleted if member deleted

[Source: `@1-2-membership-extension-scaffold-database-models.md#Database Schema`, `@architecture.md#3.1 Custom Extensions`]

### Project Structure Notes

**Alignment with Fleetbase Architecture:**
- Service layer for business logic (MemberProfileService)
- Controller for HTTP layer (MembershipController)
- Form Request for validation (UpdateMemberProfileRequest)
- Resource for serialization (MemberProfileResource)
- Middleware for authorization (verify.member)

**File Organization:**
```
fleetbase-membership/
├── server/
│   ├── src/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   └── MembershipController.php (UPDATE - add updateProfile)
│   │   │   ├── Requests/
│   │   │   │   ├── VerifyMembershipRequest.php (from Story 1.4)
│   │   │   │   └── UpdateMemberProfileRequest.php (NEW)
│   │   │   ├── Resources/
│   │   │   │   ├── MemberIdentityResource.php (from Story 1.4)
│   │   │   │   └── MemberProfileResource.php (from Story 1.4)
│   │   │   └── Middleware/
│   │   │       └── VerifyMemberMiddleware.php (from Story 1.5)
│   │   ├── Services/
│   │   │   ├── MembershipVerificationService.php (UPDATE - add profile creation)
│   │   │   ├── SolanaRpcService.php (from Story 1.3)
│   │   │   ├── MemberIdentityService.php (from Story 1.3)
│   │   │   └── MemberProfileService.php (NEW)
│   │   ├── Models/ (from Story 1.2)
│   │   │   ├── MemberIdentity.php
│   │   │   └── MemberProfile.php
│   │   └── Providers/
│   │       └── MembershipServiceProvider.php (UPDATE - register service)
│   ├── routes/
│   │   └── api.php (UPDATE - add PATCH route)
```

**Namespace Convention:**
- Services: `Fleetbase\Membership\Services\`
- Requests: `Fleetbase\Membership\Http\Requests\`

[Source: `@architecture.md#3.3 Extension Naming and Structure`, `@1-4-member-wallet-connection-verification-api.md#Project Structure Notes`]

### References

- **Architecture Section 3.1**: Custom Extensions (Membership) [`@architecture.md#3.1 Custom Extensions`]
- **Architecture Section 6.2**: API Standards and response format [`@architecture.md#6.2 API Standards`]
- **Architecture Section 9.1**: Security and privacy requirements [`@architecture.md#9.1 Security`]
- **Epic 1 Story 1.6**: Detailed acceptance criteria [`@epics.md Story 1.6`]
- **Story 1.4**: MembershipController and API patterns [`@1-4-member-wallet-connection-verification-api.md`]
- **Story 1.5**: Middleware for route protection [`@1-5-membership-verification-middleware-access-control.md`]
- **Story 1.3**: Service layer patterns and logging [`@1-3-nft-badge-ownership-verification-service.md`]
- **Story 1.2**: MemberProfile model and database schema [`@1-2-membership-extension-scaffold-database-models.md`]
- **Laravel Form Requests**: Validation documentation
- **Laravel Resources**: API resource transformation

## Dev Agent Record

### Agent Model Used

Cascade (GPT-5)

### Debug Log References

- Implemented profile auto-creation and idempotency in verification flow.
- Added profile update endpoint, validation request, service layer, and route wiring.
- Added/updated unit and integration tests for Story 1.6 acceptance criteria.
- Validation execution blocked locally: `php` CLI not available and Docker engine not running.

### Completion Notes List

- Added `ensureMemberProfile()` and `generateDefaultDisplayName()` to verification service to auto-create profile records linked by `member_identity_uuid`.
- Added `MemberProfileService::updateProfile()` with constrained updatable fields and structured logging with correlation IDs.
- Added `UpdateMemberProfileRequest` with validation for `display_name`, `avatar_url`, and `bio` including custom messages.
- Added `PATCH /storefront/v1/membership/profile` under `fleetbase.auth` + `verify.member` middleware.
- Updated `MembershipController` to support profile update, including 401/404 handling.
- Enforced profile response privacy by excluding wallet address from `MemberProfileResource` output.
- Added new unit tests for profile service and request validation, plus expanded controller/verification/integration coverage.
- Pending: run PHP test suite once runtime is available, then complete checkbox/task finalization and move story to `review`.

### File List

- _bmad-output/implementation-artifacts/sprint-status.yaml
- fleetbase/extensions/fleetbase-membership/server/routes/api.php
- fleetbase/extensions/fleetbase-membership/server/src/Http/Controllers/MembershipController.php
- fleetbase/extensions/fleetbase-membership/server/src/Http/Requests/UpdateMemberProfileRequest.php
- fleetbase/extensions/fleetbase-membership/server/src/Http/Resources/MemberProfileResource.php
- fleetbase/extensions/fleetbase-membership/server/src/Providers/MembershipServiceProvider.php
- fleetbase/extensions/fleetbase-membership/server/src/Services/MemberProfileService.php
- fleetbase/extensions/fleetbase-membership/server/src/Services/MembershipVerificationService.php
- tests/Integration/Membership/MiddlewareProtectionTest.php
- tests/Integration/Membership/VerificationApiTest.php
- tests/Unit/Membership/MemberProfileServiceTest.php
- tests/Unit/Membership/MembershipControllerTest.php
- tests/Unit/Membership/MembershipVerificationServiceTest.php
- tests/Unit/Membership/UpdateMemberProfileRequestTest.php
