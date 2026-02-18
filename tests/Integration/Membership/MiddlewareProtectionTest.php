<?php

namespace Tests\Integration\Membership;

use Fleetbase\Membership\Models\MemberIdentity;
use Fleetbase\Membership\Models\MemberProfile;
use Fleetbase\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MiddlewareProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $verifiedUser;
    protected User $nonMemberUser;
    protected MemberIdentity $memberIdentity;
    protected MemberProfile $memberProfile;

    protected function setUp(): void
    {
        parent::setUp();

        // Create verified member user
        $this->verifiedUser = User::factory()->create([
            'uuid' => 'user-verified-uuid',
            'email' => 'verified@example.com',
        ]);

        $this->memberIdentity = MemberIdentity::factory()->create([
            'user_uuid' => $this->verifiedUser->uuid,
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'membership_status' => MemberIdentity::STATUS_VERIFIED,
            'verified_at' => now(),
            'last_verified_at' => now(),
        ]);

        $this->memberProfile = MemberProfile::factory()->create([
            'member_identity_uuid' => $this->memberIdentity->uuid,
            'display_name' => 'VerifiedMember',
        ]);

        // Create non-member user
        $this->nonMemberUser = User::factory()->create([
            'uuid' => 'user-nonmember-uuid',
            'email' => 'nonmember@example.com',
        ]);
    }

    public function test_protected_route_requires_membership()
    {
        $response = $this->actingAs($this->verifiedUser)
            ->getJson('/storefront/v1/membership/status');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'membership_status',
                'wallet_address',
            ],
        ]);
    }

    public function test_protected_route_blocks_non_member()
    {
        $response = $this->actingAs($this->nonMemberUser)
            ->getJson('/storefront/v1/membership/status');

        $response->assertStatus(403);
        $response->assertJson([
            'errors' => [
                [
                    'status' => '403',
                    'title' => 'Access Denied',
                    'detail' => 'Verified DAO membership required',
                ],
            ],
        ]);
    }

    public function test_protected_route_blocks_unauthenticated_user()
    {
        $response = $this->getJson('/storefront/v1/membership/status');

        $response->assertStatus(403);
        $response->assertJson([
            'errors' => [
                [
                    'status' => '403',
                    'title' => 'Access Denied',
                    'detail' => 'Authentication required',
                ],
            ],
        ]);
    }

    public function test_public_verify_endpoint_does_not_require_membership()
    {
        $response = $this->postJson('/storefront/v1/membership/verify', [
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'signature' => 'invalid-signature-for-test',
            'message' => 'test message',
        ]);

        // Should not return 403 (may return 400/422 for invalid signature, but not 403)
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_profile_endpoint_requires_membership()
    {
        $response = $this->actingAs($this->verifiedUser)
            ->getJson('/storefront/v1/membership/profile');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'uuid',
                'display_name',
                'member_identity_uuid',
            ],
        ]);
    }

    public function test_profile_endpoint_blocks_non_member()
    {
        $response = $this->actingAs($this->nonMemberUser)
            ->getJson('/storefront/v1/membership/profile');

        $response->assertStatus(403);
    }

    public function test_error_response_includes_correlation_id()
    {
        $response = $this->actingAs($this->nonMemberUser)
            ->getJson('/storefront/v1/membership/status');

        $response->assertStatus(403);
        $data = $response->json();
        $this->assertArrayHasKey('correlation_id', $data['errors'][0]['meta']);
        $this->assertNotEmpty($data['errors'][0]['meta']['correlation_id']);
    }

    public function test_error_response_includes_help_link()
    {
        $response = $this->actingAs($this->nonMemberUser)
            ->getJson('/storefront/v1/membership/status');

        $response->assertStatus(403);
        $data = $response->json();
        $this->assertArrayHasKey('help', $data['errors'][0]['meta']);
        $this->assertStringContainsString('/storefront/v1/membership/verify', $data['errors'][0]['meta']['help']);
    }

    public function test_pending_member_is_blocked()
    {
        $pendingUser = User::factory()->create([
            'uuid' => 'user-pending-uuid',
            'email' => 'pending@example.com',
        ]);

        MemberIdentity::factory()->create([
            'user_uuid' => $pendingUser->uuid,
            'wallet_address' => '8yLYuh3DX98d08UYKTEqcE6kClifUrA94TZRuJosgAsV',
            'membership_status' => MemberIdentity::STATUS_PENDING,
        ]);

        $response = $this->actingAs($pendingUser)
            ->getJson('/storefront/v1/membership/status');

        $response->assertStatus(403);
        $response->assertJsonPath('errors.0.detail', 'Verified DAO membership required');
    }

    public function test_suspended_member_is_blocked()
    {
        $suspendedUser = User::factory()->create([
            'uuid' => 'user-suspended-uuid',
            'email' => 'suspended@example.com',
        ]);

        MemberIdentity::factory()->create([
            'user_uuid' => $suspendedUser->uuid,
            'wallet_address' => '9zMZui4EY09e19VZLUFrdF7lDmgVsA05TZRuJosgAsW',
            'membership_status' => MemberIdentity::STATUS_SUSPENDED,
        ]);

        $response = $this->actingAs($suspendedUser)
            ->getJson('/storefront/v1/membership/status');

        $response->assertStatus(403);
        $response->assertJsonPath('errors.0.detail', 'Verified DAO membership required');
    }

    public function test_revoked_member_is_blocked()
    {
        $revokedUser = User::factory()->create([
            'uuid' => 'user-revoked-uuid',
            'email' => 'revoked@example.com',
        ]);

        MemberIdentity::factory()->create([
            'user_uuid' => $revokedUser->uuid,
            'wallet_address' => '0aNVfj5FZ10f20WAMVGVse8mEnhWtB16TZRuJosgAsX',
            'membership_status' => MemberIdentity::STATUS_REVOKED,
        ]);

        $response = $this->actingAs($revokedUser)
            ->getJson('/storefront/v1/membership/status');

        $response->assertStatus(403);
        $response->assertJsonPath('errors.0.detail', 'Verified DAO membership required');
    }

    public function test_middleware_is_not_applied_to_verify_endpoint()
    {
        // The verify endpoint should be accessible without any authentication
        $response = $this->postJson('/storefront/v1/membership/verify', [
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'signature' => '5iK1p7X1z9w7w8g9b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2g3h4i5j6k7l8m9n0o1p2q3r4s5t6u7v8w9x0y1z2a3b4c5d6e7f8g9h0i1j2k3l4m5',
            'message' => 'Verify wallet ownership for Stalabard DAO: ' . \Illuminate\Support\Str::random(32) . ':' . time(),
        ]);

        // Should NOT return 403 - the endpoint is public
        // It may return 200 (if verification succeeds) or 400/422 (if signature invalid)
        // But it should NOT be blocked by middleware
        $this->assertNotEquals(403, $response->getStatusCode(), 'Verify endpoint should not require membership');
    }

    public function test_request_macros_work_for_verified_member()
    {
        $response = $this->actingAs($this->verifiedUser)
            ->getJson('/storefront/v1/membership/status');

        $response->assertStatus(200);
    }
}
