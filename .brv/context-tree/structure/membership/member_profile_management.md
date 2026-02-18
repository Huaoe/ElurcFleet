## Raw Concept
**Task:**
Implement member profile auto-creation and management

**Changes:**
- Added profile auto-creation in MembershipVerificationService
- Implemented MemberProfileService for profile updates
- Added UpdateMemberProfileRequest validation
- Created PATCH /storefront/v1/membership/profile route
- Expanded integration tests for profile management

**Files:**
- fleetbase/extensions/fleetbase-membership/server/src/Services/MembershipVerificationService.php
- fleetbase/extensions/fleetbase-membership/server/src/Services/MemberProfileService.php
- fleetbase/extensions/fleetbase-membership/server/src/Http/Controllers/MembershipController.php
- fleetbase/extensions/fleetbase-membership/server/src/Http/Requests/UpdateMemberProfileRequest.php
- tests/Integration/Membership/VerificationApiTest.php

**Flow:**
verifyMembership -> ensureMemberProfile (auto-create if missing) -> updateProfile (PATCH request) -> validation -> persistence

**Timestamp:** 2026-02-17

## Narrative
### Structure
Profile management is split between auto-creation during verification in MembershipVerificationService and explicit updates via MemberProfileService. The MembershipController handles the API surface.

### Dependencies
Uses Laravel Sanctum for authentication, MemberProfile model for persistence, and UpdateMemberProfileRequest for validation rules.

### Features
Profiles are created automatically on first verification with a default display name (first 8 chars of wallet). Updates are idempotent and support display_name, avatar_url, and bio. Access is restricted via fleetbase.auth+verify.member middleware.

### Rules
Rule 1: Display name max 50 chars, alphanumeric/spaces/hyphens/underscores only.
Rule 2: Avatar URL must be valid https? URL, max 500 chars.
Rule 3: Bio max 500 chars.
Rule 4: Profile creation is idempotent during verification.

### Examples
PATCH /storefront/v1/membership/profile
{
  "display_name": "CryptoWanderer",
  "bio": "Exploring the decentralized fleet."
}
