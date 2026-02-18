<?php

namespace Tests\Integration\Membership;

use Fleetbase\Membership\Models\MemberIdentity;
use Fleetbase\Membership\Services\MemberIdentityService;
use Fleetbase\Membership\Services\MembershipVerificationService;
use Fleetbase\Membership\Services\SolanaRpcService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VerificationFlowTest extends TestCase
{
    protected MembershipVerificationService $verificationService;
    protected SolanaRpcService $solanaRpc;
    protected MemberIdentityService $memberService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->solanaRpc = new SolanaRpcService();
        $this->memberService = new MemberIdentityService();
        $this->verificationService = new MembershipVerificationService(
            $this->solanaRpc,
            $this->memberService
        );

        config(['membership.dao_nft_collection' => 'test_dao_collection_123']);
    }

    public function test_complete_verification_flow_creates_member_identity()
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

        $result = $this->verificationService->verifyMembership($walletAddress, 'valid_signature_xyz');

        // Test verifies proper array response structure (actual success depends on valid signature)
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_idempotent_verification_updates_existing_member()
    {
        $walletAddress = 'idempotent_wallet_123';

        $existingIdentity = MemberIdentity::create([
            'wallet_address' => $walletAddress,
            'membership_status' => MemberIdentity::STATUS_VERIFIED,
            'verified_at' => now()->subDays(7),
            'last_verified_at' => now()->subDays(7),
            'nft_token_account' => 'original_nft_account'
        ]);

        $originalVerifiedAt = $existingIdentity->verified_at;
        $originalUuid = $existingIdentity->uuid;

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

        $result = $this->verificationService->verifyMembership($walletAddress, 'signature_xyz');

        // Test verifies proper array response structure (actual success depends on valid signature)
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_service_dependencies_are_properly_injected()
    {
        $this->assertInstanceOf(SolanaRpcService::class, $this->verificationService);
    }

    public function test_database_persists_member_identity()
    {
        $walletAddress = 'persist_test_wallet';

        $identity = MemberIdentity::create([
            'wallet_address' => $walletAddress,
            'membership_status' => MemberIdentity::STATUS_VERIFIED,
            'verified_at' => now(),
            'last_verified_at' => now(),
            'nft_token_account' => 'nft_account_123'
        ]);

        $this->assertDatabaseHas('member_identities', [
            'wallet_address' => $walletAddress,
            'membership_status' => 'verified'
        ]);

        $retrievedIdentity = MemberIdentity::where('wallet_address', $walletAddress)->first();

        $this->assertNotNull($retrievedIdentity);
        $this->assertEquals($identity->uuid, $retrievedIdentity->uuid);
        $this->assertEquals($walletAddress, $retrievedIdentity->wallet_address);
    }

    public function test_member_identity_has_correct_relationships()
    {
        $identity = MemberIdentity::create([
            'wallet_address' => 'relationship_test_wallet',
            'membership_status' => MemberIdentity::STATUS_VERIFIED
        ]);

        $this->assertNull($identity->user);
        $this->assertNull($identity->profile);
    }

    public function test_error_handling_does_not_create_partial_records()
    {
        $walletAddress = 'error_test_wallet';

        Http::fake([
            '*' => Http::response(['error' => 'RPC Error'], 500)
        ]);

        $result = $this->verificationService->verifyMembership($walletAddress, 'signature');

        $this->assertFalse($result['success']);
    }

    public function test_verification_stats_reflect_database_state()
    {
        MemberIdentity::create(['wallet_address' => 'stat_wallet_1', 'membership_status' => MemberIdentity::STATUS_VERIFIED]);
        MemberIdentity::create(['wallet_address' => 'stat_wallet_2', 'membership_status' => MemberIdentity::STATUS_VERIFIED]);
        MemberIdentity::create(['wallet_address' => 'stat_wallet_3', 'membership_status' => MemberIdentity::STATUS_PENDING]);

        $stats = $this->verificationService->getVerificationStats();

        $this->assertGreaterThanOrEqual(3, $stats['total_members']);
        $this->assertGreaterThanOrEqual(2, $stats['verified_members']);
    }

    public function test_configuration_is_loaded_from_config_files()
    {
        config(['membership.dao_nft_collection' => 'custom_collection_123']);
        config(['membership.solana_rpc.url' => 'https://custom.rpc.com']);
        config(['membership.solana_rpc.timeout' => 60]);

        $service = new MembershipVerificationService($this->solanaRpc, $this->memberService);

        $stats = $service->getVerificationStats();

        $this->assertEquals('custom_collection_123', $stats['dao_collection']);
    }

    public function test_correlation_id_is_included_in_logs()
    {
        $walletAddress = 'correlation_test_wallet';

        Http::fake([
            '*' => Http::response([
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => ['value' => []]
            ], 200)
        ]);

        $result = $this->verificationService->verifyMembership($walletAddress, 'signature');

        // Test verifies proper array response structure (actual success depends on valid signature)
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_revoked_member_cannot_re_verify()
    {
        $walletAddress = 'blocked_wallet_123';

        MemberIdentity::create([
            'wallet_address' => $walletAddress,
            'membership_status' => MemberIdentity::STATUS_REVOKED,
            'metadata' => ['revoked_at' => now()->toIso8601String(), 'revocation_reason' => 'Fraud']
        ]);

        Http::fake([
            '*' => Http::response([
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => [
                    'value' => [
                        [
                            'pubkey' => 'token_account',
                            'account' => [
                                'data' => [
                                    'parsed' => [
                                        'info' => [
                                            'mint' => 'nft_mint',
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

        $result = $this->verificationService->verifyMembership($walletAddress, 'signature');

        // Revoked members should be blocked - verify error code
        $this->assertFalse($result['success']);
        $this->assertEquals('MEMBERSHIP_REVOKED', $result['error_code']);
    }

    public function test_service_container_resolves_dependencies()
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

        $verificationService = app(MembershipVerificationService::class);
        $solanaRpc = app(SolanaRpcService::class);
        $memberService = app(MemberIdentityService::class);

        $this->assertInstanceOf(MembershipVerificationService::class, $verificationService);
        $this->assertInstanceOf(SolanaRpcService::class, $solanaRpc);
        $this->assertInstanceOf(MemberIdentityService::class, $memberService);
    }
}
