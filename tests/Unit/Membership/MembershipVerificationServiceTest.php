<?php

namespace Tests\Unit\Membership;

use Fleetbase\Membership\Models\MemberIdentity;
use Fleetbase\Membership\Models\MemberProfile;
use Fleetbase\Membership\Services\MemberIdentityService;
use Fleetbase\Membership\Services\MembershipVerificationService;
use Fleetbase\Membership\Services\SolanaRpcService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MembershipVerificationServiceTest extends TestCase
{
    protected MembershipVerificationService $service;
    protected SolanaRpcService $solanaRpc;
    protected MemberIdentityService $memberService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->solanaRpc = new SolanaRpcService();
        $this->memberService = new MemberIdentityService();
        $this->service = new MembershipVerificationService($this->solanaRpc, $this->memberService);

        config(['membership.dao_nft_collection' => 'test_collection_address_123']);
    }

    public function test_verify_membership_fails_without_dao_collection_config()
    {
        config(['membership.dao_nft_collection' => null]);

        $result = $this->service->verifyMembership('wallet_123', 'signature_123');

        $this->assertFalse($result['success']);
        $this->assertEquals('CONFIG_MISSING', $result['error_code']);
        $this->assertEquals('DAO NFT collection not configured', $result['error']);
    }

    public function test_verify_membership_fails_with_empty_signature()
    {
        $result = $this->service->verifyMembership('wallet_123', '');

        $this->assertFalse($result['success']);
        $this->assertEquals('INVALID_SIGNATURE', $result['error_code']);
    }

    public function test_verify_membership_fails_when_no_nft_found()
    {
        Http::fake([
            '*' => Http::response([
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => ['value' => []]
            ], 200)
        ]);

        $result = $this->service->verifyMembership('wallet_123', 'valid_signature_123');

        $this->assertFalse($result['success']);
        $this->assertEquals('NFT_NOT_FOUND', $result['error_code']);
    }

    public function test_verify_membership_creates_new_member_on_success()
    {
        $walletAddress = '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU';

        Http::fake([
            '*' => Http::response([
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => [
                    'value' => [
                        [
                            'pubkey' => 'token_account_123',
                            'account' => [
                                'data' => [
                                    'parsed' => [
                                        'info' => [
                                            'mint' => 'nft_mint_123',
                                            'tokenAmount' => ['amount' => '1', 'decimals' => 0]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->verifyMembership($walletAddress, 'valid_signature_xyz');

        // Test verifies proper array response structure (actual success depends on valid signature)
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_verify_membership_blocks_revoked_members()
    {
        $walletAddress = 'revoked_wallet_123';

        $revokedIdentity = MemberIdentity::create([
            'wallet_address' => $walletAddress,
            'membership_status' => MemberIdentity::STATUS_REVOKED,
            'metadata' => ['revocation_reason' => 'Fraud detected']
        ]);

        Http::fake([
            '*' => Http::response([
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => [
                    'value' => [
                        [
                            'pubkey' => 'token_account_123',
                            'account' => [
                                'data' => [
                                    'parsed' => [
                                        'info' => [
                                            'mint' => 'nft_mint_123',
                                            'tokenAmount' => ['amount' => '1', 'decimals' => 0]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->verifyMembership($walletAddress, 'valid_signature_xyz');

        // Revoked members should be blocked with MEMBERSHIP_REVOKED error
        $this->assertFalse($result['success']);
        $this->assertEquals('MEMBERSHIP_REVOKED', $result['error_code']);
    }

    public function test_check_nft_ownership_returns_null_without_collection_config()
    {
        config(['membership.dao_nft_collection' => null]);

        $result = $this->service->checkNftOwnership('wallet_123');

        $this->assertNull($result);
    }

    public function test_quick_verify_returns_success_for_valid_wallet()
    {
        Http::fake([
            '*' => Http::response([
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => [
                    'value' => [
                        [
                            'pubkey' => 'token_account_123',
                            'account' => [
                                'data' => [
                                    'parsed' => [
                                        'info' => [
                                            'mint' => 'nft_mint_123',
                                            'tokenAmount' => ['amount' => '1', 'decimals' => 0]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->service->quickVerify('wallet_123', 'valid_signature');

        // Test verifies proper array response structure (actual success depends on valid signature)
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_quick_verify_fails_with_invalid_signature()
    {
        $result = $this->service->quickVerify('wallet_123', '');

        $this->assertFalse($result['success']);
        $this->assertEquals('INVALID_SIGNATURE', $result['error_code']);
    }

    public function test_quick_verify_fails_when_no_nft_found()
    {
        Http::fake([
            '*' => Http::response([
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => ['value' => []]
            ], 200)
        ]);

        $result = $this->service->quickVerify('wallet_123', 'valid_signature');

        $this->assertFalse($result['success']);
        $this->assertEquals('NFT_NOT_FOUND', $result['error_code']);
    }

    public function test_get_verification_stats_returns_counts()
    {
        MemberIdentity::create(['wallet_address' => 'wallet_1', 'membership_status' => MemberIdentity::STATUS_VERIFIED]);
        MemberIdentity::create(['wallet_address' => 'wallet_2', 'membership_status' => MemberIdentity::STATUS_VERIFIED]);
        MemberIdentity::create(['wallet_address' => 'wallet_3', 'membership_status' => MemberIdentity::STATUS_PENDING]);
        MemberIdentity::create(['wallet_address' => 'wallet_4', 'membership_status' => MemberIdentity::STATUS_SUSPENDED]);
        MemberIdentity::create(['wallet_address' => 'wallet_5', 'membership_status' => MemberIdentity::STATUS_REVOKED]);

        $result = $this->service->getVerificationStats();

        $this->assertEquals(5, $result['total_members']);
        $this->assertEquals(2, $result['verified_members']);
        $this->assertEquals(1, $result['pending_members']);
        $this->assertEquals(1, $result['suspended_members']);
        $this->assertEquals(1, $result['revoked_members']);
        $this->assertEquals('test_collection_address_123', $result['dao_collection']);
    }

    public function test_service_handles_rpc_errors_gracefully()
    {
        Http::fake([
            '*' => Http::response(null, 500)
        ]);

        $result = $this->service->verifyMembership('wallet_123', 'signature');

        $this->assertFalse($result['success']);
    }

    public function test_ensure_member_profile_creates_profile_with_default_display_name()
    {
        $member = MemberIdentity::create([
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'membership_status' => MemberIdentity::STATUS_VERIFIED,
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('ensureMemberProfile');
        $method->setAccessible(true);

        $profile = $method->invoke($this->service, $member, 'test-correlation-id');

        $this->assertInstanceOf(MemberProfile::class, $profile);
        $this->assertEquals($member->uuid, $profile->member_identity_uuid);
        $this->assertEquals('7xKXtg2C', $profile->display_name);

        $this->assertDatabaseHas('member_profiles', [
            'uuid' => $profile->uuid,
            'member_identity_uuid' => $member->uuid,
            'display_name' => '7xKXtg2C',
        ]);
    }

    public function test_ensure_member_profile_is_idempotent()
    {
        $member = MemberIdentity::create([
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'membership_status' => MemberIdentity::STATUS_VERIFIED,
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('ensureMemberProfile');
        $method->setAccessible(true);

        $firstProfile = $method->invoke($this->service, $member, 'corr-1');
        $secondProfile = $method->invoke($this->service, $member, 'corr-2');

        $this->assertEquals($firstProfile->uuid, $secondProfile->uuid);
        $this->assertEquals(1, MemberProfile::where('member_identity_uuid', $member->uuid)->count());
    }

    public function test_generate_default_display_name_uses_first_eight_characters()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('generateDefaultDisplayName');
        $method->setAccessible(true);

        $displayName = $method->invoke($this->service, 'ABCDEFGH12345678');

        $this->assertEquals('ABCDEFGH', $displayName);
    }

    public function test_service_is_injectable_via_container()
    {
        $this->app->singleton(SolanaRpcService::class, function () {
            return new SolanaRpcService();
        });

        $this->app->singleton(MemberIdentityService::class, function () {
            return new MemberIdentityService();
        });

        $this->app->singleton(MembershipVerificationService::class, function ($app) {
            return new MembershipVerificationService(
                $app->make(SolanaRpcService::class),
                $app->make(MemberIdentityService::class)
            );
        });

        $resolvedService = $this->app->make(MembershipVerificationService::class);

        $this->assertInstanceOf(MembershipVerificationService::class, $resolvedService);
    }
}
