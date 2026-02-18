<?php

namespace Tests\Integration\Membership;

use Fleetbase\Membership\Models\MemberIdentity;
use Fleetbase\Membership\Models\MemberProfile;
use Fleetbase\Membership\Services\MembershipVerificationService;
use Fleetbase\Membership\Services\SolanaRpcService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VerificationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        config(['membership.dao_nft_collection' => 'TestCollectionAddress123']);
        config(['membership.auth.token_expiration' => 60 * 24 * 7]);
        config(['membership.auth.token_name' => 'stalabard-membership']);
    }

    public function test_successful_verification_returns_member_data()
    {
        Http::fake([
            '*' => Http::response([
                'result' => [
                    'value' => [
                        [
                            'account' => [
                                'data' => [
                                    'parsed' => [
                                        'info' => [
                                            'mint' => 'TestNftMint123',
                                            'tokenAmount' => ['amount' => '1']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $walletAddress = '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU';
        $signature = base64_encode(str_repeat('x', 64));
        $message = 'Verify wallet ownership for Stalabard DAO: test-nonce:' . time();

        $response = $this->postJson('/storefront/v1/membership/verify', [
            'wallet_address' => $walletAddress,
            'signature' => $signature,
            'message' => $message,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'member_uuid',
                    'membership_status',
                    'verified_at',
                    'last_verified_at',
                    'token',
                ],
                'meta' => ['correlation_id']
            ]);

        $this->assertDatabaseHas('member_identities', [
            'wallet_address' => $walletAddress,
            'membership_status' => 'verified',
        ]);

        $data = $response->json('data');
        $this->assertEquals('verified', $data['membership_status']);
        $this->assertNotEmpty($data['token']);
        // Sanctum tokens have format: {tokenId}|{hash}
        $this->assertStringContainsString('|', $data['token']);

        $memberUuid = $data['member_uuid'];
        $this->assertDatabaseHas('member_profiles', [
            'member_identity_uuid' => $memberUuid,
            'display_name' => substr($walletAddress, 0, 8),
        ]);
    }

    public function test_verification_failure_returns_403()
    {
        Http::fake([
            '*' => Http::response([
                'result' => [
                    'value' => []
                ]
            ], 200)
        ]);

        $walletAddress = '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU';
        $signature = base64_encode(str_repeat('x', 64));
        $message = 'Verify wallet ownership for Stalabard DAO: test-nonce:' . time();

        $response = $this->postJson('/storefront/v1/membership/verify', [
            'wallet_address' => $walletAddress,
            'signature' => $signature,
            'message' => $message,
        ]);

        $response->assertStatus(403)
            ->assertJsonStructure([
                'errors' => [
                    '*' => [
                        'status',
                        'title',
                        'detail',
                        'meta' => ['correlation_id']
                    ]
                ]
            ]);

        $this->assertDatabaseMissing('member_identities', [
            'wallet_address' => $walletAddress,
        ]);
    }

    public function test_invalid_wallet_address_returns_422()
    {
        $response = $this->postJson('/storefront/v1/membership/verify', [
            'wallet_address' => 'invalid-wallet',
            'signature' => base64_encode(str_repeat('x', 64)),
            'message' => 'Test message',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['wallet_address']);
    }

    public function test_missing_signature_returns_422()
    {
        $response = $this->postJson('/storefront/v1/membership/verify', [
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'message' => 'Test message',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['signature']);
    }

    public function test_missing_message_returns_422()
    {
        $response = $this->postJson('/storefront/v1/membership/verify', [
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'signature' => base64_encode(str_repeat('x', 64)),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_status_endpoint_requires_authentication()
    {
        $response = $this->getJson('/storefront/v1/membership/status');

        $response->assertStatus(401);
    }

    public function test_authenticated_member_can_get_status()
    {
        $member = MemberIdentity::create([
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'membership_status' => 'verified',
            'verified_at' => now(),
            'last_verified_at' => now(),
            'nft_token_account' => 'TestTokenAccount123',
        ]);

        $token = $this->createTestToken($member);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/storefront/v1/membership/status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'member_uuid',
                    'membership_status',
                    'verified_at',
                    'last_verified_at',
                ],
                'meta' => ['correlation_id']
            ]);

        $data = $response->json('data');
        $this->assertEquals($member->uuid, $data['member_uuid']);
        $this->assertEquals('verified', $data['membership_status']);
    }

    public function test_authenticated_member_can_get_profile()
    {
        $member = MemberIdentity::create([
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'membership_status' => 'verified',
            'verified_at' => now(),
            'last_verified_at' => now(),
            'nft_token_account' => 'TestTokenAccount123',
        ]);

        $profile = MemberProfile::create([
            'member_identity_uuid' => $member->uuid,
            'display_name' => 'Test Member',
            'avatar_url' => 'https://example.com/avatar.png',
            'bio' => 'Test bio',
        ]);

        $token = $this->createTestToken($member);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/storefront/v1/membership/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'member_uuid',
                    'membership_status',
                    'profile' => [
                        'uuid',
                        'display_name',
                        'avatar_url',
                        'bio',
                    ],
                ],
                'meta' => ['correlation_id']
            ]);

        $data = $response->json('data');
        $this->assertEquals($member->uuid, $data['member_uuid']);
        $this->assertEquals('Test Member', $data['profile']['display_name']);
        $this->assertArrayNotHasKey('wallet_address', $data['profile']);
    }

    public function test_profile_endpoint_requires_authentication()
    {
        $response = $this->getJson('/storefront/v1/membership/profile');

        $response->assertStatus(401);
    }

    public function test_expired_token_returns_401()
    {
        $member = MemberIdentity::create([
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'membership_status' => 'verified',
            'verified_at' => now(),
            'last_verified_at' => now(),
            'nft_token_account' => 'TestTokenAccount123',
        ]);

        $expiredToken = $this->createExpiredToken($member);

        $response = $this->withHeader('Authorization', 'Bearer ' . $expiredToken)
            ->getJson('/storefront/v1/membership/status');

        $response->assertStatus(401);
    }

    public function test_re_verification_updates_existing_member()
    {
        $walletAddress = '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU';
        
        $existingMember = MemberIdentity::create([
            'wallet_address' => $walletAddress,
            'membership_status' => 'verified',
            'verified_at' => now()->subDays(7),
            'last_verified_at' => now()->subDays(7),
            'nft_token_account' => 'OldTokenAccount123',
        ]);

        MemberProfile::create([
            'member_identity_uuid' => $existingMember->uuid,
            'display_name' => 'ExistingName',
            'avatar_url' => null,
            'bio' => null,
        ]);

        Http::fake([
            '*' => Http::response([
                'result' => [
                    'value' => [
                        [
                            'account' => [
                                'data' => [
                                    'parsed' => [
                                        'info' => [
                                            'mint' => 'TestNftMint123',
                                            'tokenAmount' => ['amount' => '1']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $signature = base64_encode(str_repeat('x', 64));
        $message = 'Verify wallet ownership for Stalabard DAO: test-nonce:' . time();

        $response = $this->postJson('/storefront/v1/membership/verify', [
            'wallet_address' => $walletAddress,
            'signature' => $signature,
            'message' => $message,
        ]);

        $response->assertStatus(200);

        $this->assertEquals(1, MemberIdentity::where('wallet_address', $walletAddress)->count());
        $this->assertEquals(1, MemberProfile::where('member_identity_uuid', $existingMember->uuid)->count());
        
        $updatedMember = MemberIdentity::where('wallet_address', $walletAddress)->first();
        $this->assertEquals($existingMember->uuid, $updatedMember->uuid);
        $this->assertTrue($updatedMember->last_verified_at->greaterThan($existingMember->last_verified_at));
    }

    protected function createTestToken(MemberIdentity $member): string
    {
        $tokenName = config('membership.auth.token_name', 'stalabard-membership');
        $token = $member->createToken($tokenName, ['membership:read', 'membership:write']);
        
        return $token->plainTextToken;
    }

    protected function createExpiredToken(MemberIdentity $member): string
    {
        // Create a token and manually expire it in the database
        $tokenName = config('membership.auth.token_name', 'stalabard-membership');
        $token = $member->createToken($tokenName, ['membership:read', 'membership:write']);
        
        // Update the token's expiration to the past
        \Laravel\Sanctum\PersonalAccessToken::where('tokenable_id', $member->uuid)
            ->update(['expires_at' => now()->subDay()]);
        
        return $token->plainTextToken;
    }
}
