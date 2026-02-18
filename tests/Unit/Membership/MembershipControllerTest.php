<?php

namespace Tests\Unit\Membership;

use Fleetbase\Membership\Http\Controllers\MembershipController;
use Fleetbase\Membership\Http\Requests\VerifyMembershipRequest;
use Fleetbase\Membership\Models\MemberIdentity;
use Fleetbase\Membership\Models\MemberProfile;
use Fleetbase\Membership\Services\MemberIdentityService;
use Fleetbase\Membership\Services\MembershipVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class MembershipControllerTest extends TestCase
{
    use RefreshDatabase;

    protected MembershipController $controller;
    protected MembershipVerificationService $verificationService;
    protected MemberIdentityService $memberService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->verificationService = $this->createMock(MembershipVerificationService::class);
        $this->memberService = $this->createMock(MemberIdentityService::class);
        
        $this->controller = new MembershipController(
            $this->verificationService,
            $this->memberService
        );

        // Clear cache for replay attack tests
        Cache::flush();
    }

    public function test_verify_returns_success_response_with_valid_data()
    {
        $member = MemberIdentity::factory()->create([
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'membership_status' => 'verified',
        ]);

        $this->verificationService
            ->expects($this->once())
            ->method('verifyMembership')
            ->willReturn([
                'success' => true,
                'member_uuid' => $member->uuid,
                'membership_status' => 'verified',
                'is_new_member' => true,
            ]);

        $request = VerifyMembershipRequest::create('/membership/verify', 'POST', [
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'signature' => '5iK1p7X1z9w7w8g9b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2g3h4i5j6k7l8m9n0o1p2q3r4s5t6u7v8w9x0y1z2a3b4c5d6e7f8g9h0i1j2k3l4m5',
            'message' => 'Verify wallet ownership for Stalabard DAO: ' . Str::random(32) . ':' . time(),
        ]);

        $response = $this->controller->verify($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('token', $data['data']);
    }

    public function test_verify_returns_error_response_on_failure()
    {
        $this->verificationService
            ->expects($this->once())
            ->method('verifyMembership')
            ->willReturn([
                'success' => false,
                'error' => 'No DAO NFT found',
                'error_code' => 'NFT_NOT_FOUND',
            ]);

        $request = VerifyMembershipRequest::create('/membership/verify', 'POST', [
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'signature' => '5iK1p7X1z9w7w8g9b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2g3h4i5j6k7l8m9n0o1p2q3r4s5t6u7v8w9x0y1z2a3b4c5d6e7f8g9h0i1j2k3l4m5',
            'message' => 'Verify wallet ownership for Stalabard DAO: ' . Str::random(32) . ':' . time(),
        ]);

        $response = $this->controller->verify($request);

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
    }

    public function test_token_creation_includes_member_data()
    {
        $member = MemberIdentity::factory()->create([
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'membership_status' => 'verified',
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('createAuthToken');
        $method->setAccessible(true);

        $token = $method->invoke($this->controller, $member);

        $this->assertStringStartsWith('stalabard-membership.', $token);
        
        $parts = explode('.', $token);
        $payload = json_decode(base64_decode($parts[1]), true);
        
        $this->assertEquals($member->uuid, $payload['member_uuid']);
        $this->assertEquals($member->wallet_address, $payload['wallet_address']);
        $this->assertArrayHasKey('expires_at', $payload);
    }

    public function test_token_extraction_validates_expiration()
    {
        $member = MemberIdentity::factory()->create();

        $expiredPayload = [
            'member_uuid' => $member->uuid,
            'wallet_address' => $member->wallet_address,
            'expires_at' => now()->subDay()->timestamp,
        ];

        $expiredToken = 'stalabard-membership.' . base64_encode(json_encode($expiredPayload));

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('extractMemberUuidFromToken');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $expiredToken);

        $this->assertNull($result);
    }

    public function test_error_code_mapping_returns_correct_http_status()
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getHttpStatusFromErrorCode');
        $method->setAccessible(true);

        $this->assertEquals(403, $method->invoke($this->controller, 'INVALID_SIGNATURE'));
        $this->assertEquals(403, $method->invoke($this->controller, 'NFT_NOT_FOUND'));
        $this->assertEquals(403, $method->invoke($this->controller, 'MEMBERSHIP_REVOKED'));
        $this->assertEquals(503, $method->invoke($this->controller, 'CONFIG_MISSING'));
        $this->assertEquals(400, $method->invoke($this->controller, 'UNKNOWN'));
    }

    public function test_replay_attack_with_reused_nonce_returns_403()
    {
        $nonce = Str::random(32);
        $timestamp = time();
        $message = "Verify wallet ownership for Stalabard DAO: {$nonce}:{$timestamp}";

        // First request should pass validation (but fail verification)
        $request1 = VerifyMembershipRequest::create('/membership/verify', 'POST', [
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'signature' => '5iK1p7X1z9w7w8g9b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2g3h4i5j6k7l8m9n0o1p2q3r4s5t6u7v8w9x0y1z2a3b4c5d6e7f8g9h0i1j2k3l4m5',
            'message' => $message,
        ]);

        // Second request with same nonce should fail with 403
        $request2 = VerifyMembershipRequest::create('/membership/verify', 'POST', [
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'signature' => '5iK1p7X1z9w7w8g9b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2g3h4i5j6k7l8m9n0o1p2q3r4s5t6u7v8w9x0y1z2a3b4c5d6e7f8g9h0i1j2k3l4m5',
            'message' => $message,
        ]);

        // First request
        $this->verificationService->method('verifyMembership')->willReturn([
            'success' => false,
            'error' => 'Test',
        ]);

        $response1 = $this->controller->verify($request1);

        // Second request with same nonce
        $response2 = $this->controller->verify($request2);

        $this->assertEquals(403, $response2->getStatusCode());
        $data = json_decode($response2->getContent(), true);
        $this->assertStringContainsString('reused', $data['errors'][0]['detail']);
    }

    public function test_expired_challenge_message_returns_403()
    {
        $nonce = Str::random(32);
        $oldTimestamp = time() - 400; // 6+ minutes ago
        $message = "Verify wallet ownership for Stalabard DAO: {$nonce}:{$oldTimestamp}";

        $request = VerifyMembershipRequest::create('/membership/verify', 'POST', [
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'signature' => '5iK1p7X1z9w7w8g9b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2g3h4i5j6k7l8m9n0o1p2q3r4s5t6u7v8w9x0y1z2a3b4c5d6e7f8g9h0i1j2k3l4m5',
            'message' => $message,
        ]);

        $response = $this->controller->verify($request);

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('expired', $data['errors'][0]['detail']);
    }

    public function test_invalid_challenge_format_returns_403()
    {
        $request = VerifyMembershipRequest::create('/membership/verify', 'POST', [
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'signature' => '5iK1p7X1z9w7w8g9b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2g3h4i5j6k7l8m9n0o1p2q3r4s5t6u7v8w9x0y1z2a3b4c5d6e7f8g9h0i1j2k3l4m5',
            'message' => 'Invalid challenge format without nonce and timestamp',
        ]);

        $response = $this->controller->verify($request);

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Invalid Challenge', $data['errors'][0]['title']);
    }
}
