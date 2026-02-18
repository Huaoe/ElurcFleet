<?php

namespace Tests\Unit\Membership;

use Fleetbase\Membership\Models\MemberIdentity;
use Fleetbase\Membership\Services\MemberIdentityService;
use Tests\TestCase;

class MemberIdentityServiceTest extends TestCase
{
    protected MemberIdentityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MemberIdentityService();
    }

    public function test_find_by_wallet_address_returns_null_for_nonexistent_wallet()
    {
        $result = $this->service->findByWalletAddress('nonexistent_wallet_address');

        $this->assertNull($result);
    }

    public function test_find_by_wallet_address_returns_identity_for_existing_wallet()
    {
        $identity = MemberIdentity::create([
            'wallet_address' => 'test_wallet_123',
            'membership_status' => MemberIdentity::STATUS_VERIFIED,
            'verified_at' => now(),
            'nft_token_account' => 'nft_account_123'
        ]);

        $result = $this->service->findByWalletAddress('test_wallet_123');

        $this->assertInstanceOf(MemberIdentity::class, $result);
        $this->assertEquals($identity->uuid, $result->uuid);
        $this->assertEquals('test_wallet_123', $result->wallet_address);
    }

    public function test_create_from_verification_creates_new_identity()
    {
        $result = $this->service->createFromVerification(
            'new_wallet_123',
            'nft_token_123',
            ['nft_name' => 'Test NFT']
        );

        $this->assertInstanceOf(MemberIdentity::class, $result);
        $this->assertEquals('new_wallet_123', $result->wallet_address);
        $this->assertEquals('verified', $result->membership_status);
        $this->assertEquals('nft_token_123', $result->nft_token_account);
        $this->assertNotNull($result->verified_at);
        $this->assertNotNull($result->last_verified_at);
        $this->assertEquals(['nft_name' => 'Test NFT'], $result->metadata);
    }

    public function test_update_verification_status_updates_existing_identity()
    {
        $identity = MemberIdentity::create([
            'wallet_address' => 'test_wallet_456',
            'membership_status' => MemberIdentity::STATUS_PENDING,
            'verified_at' => now()->subDays(7),
            'last_verified_at' => now()->subDays(7),
            'nft_token_account' => 'old_nft_account'
        ]);

        $originalVerifiedAt = $identity->verified_at;

        $result = $this->service->updateVerificationStatus($identity, 'new_nft_account_789');

        $this->assertEquals(MemberIdentity::STATUS_VERIFIED, $result->membership_status);
        $this->assertEquals('new_nft_account_789', $result->nft_token_account);
        $this->assertEquals($originalVerifiedAt, $result->verified_at);
        $this->assertTrue($result->last_verified_at->greaterThan($originalVerifiedAt));
    }

    public function test_is_verified_returns_true_for_verified_member()
    {
        MemberIdentity::create([
            'wallet_address' => 'verified_wallet',
            'membership_status' => MemberIdentity::STATUS_VERIFIED,
            'verified_at' => now()
        ]);

        $result = $this->service->isVerified('verified_wallet');

        $this->assertTrue($result);
    }

    public function test_is_verified_returns_false_for_pending_member()
    {
        MemberIdentity::create([
            'wallet_address' => 'pending_wallet',
            'membership_status' => MemberIdentity::STATUS_PENDING
        ]);

        $result = $this->service->isVerified('pending_wallet');

        $this->assertFalse($result);
    }

    public function test_is_verified_returns_false_for_nonexistent_wallet()
    {
        $result = $this->service->isVerified('nonexistent_wallet');

        $this->assertFalse($result);
    }

    public function test_is_pending_returns_true_for_pending_member()
    {
        MemberIdentity::create([
            'wallet_address' => 'pending_wallet_2',
            'membership_status' => MemberIdentity::STATUS_PENDING
        ]);

        $result = $this->service->isPending('pending_wallet_2');

        $this->assertTrue($result);
    }

    public function test_is_suspended_returns_true_for_suspended_member()
    {
        MemberIdentity::create([
            'wallet_address' => 'suspended_wallet',
            'membership_status' => MemberIdentity::STATUS_SUSPENDED
        ]);

        $result = $this->service->isSuspended('suspended_wallet');

        $this->assertTrue($result);
    }

    public function test_is_revoked_returns_true_for_revoked_member()
    {
        MemberIdentity::create([
            'wallet_address' => 'revoked_wallet',
            'membership_status' => MemberIdentity::STATUS_REVOKED
        ]);

        $result = $this->service->isRevoked('revoked_wallet');

        $this->assertTrue($result);
    }

    public function test_get_member_data_returns_array_for_existing_member()
    {
        $identity = MemberIdentity::create([
            'wallet_address' => 'data_wallet',
            'membership_status' => MemberIdentity::STATUS_VERIFIED,
            'verified_at' => now(),
            'last_verified_at' => now(),
            'nft_token_account' => 'nft_123',
            'metadata' => ['key' => 'value']
        ]);

        $result = $this->service->getMemberData('data_wallet');

        $this->assertIsArray($result);
        $this->assertEquals($identity->uuid, $result['uuid']);
        $this->assertEquals('data_wallet', $result['wallet_address']);
        $this->assertEquals('verified', $result['membership_status']);
        $this->assertEquals('nft_123', $result['nft_token_account']);
        $this->assertEquals(['key' => 'value'], $result['metadata']);
    }

    public function test_get_member_data_returns_null_for_nonexistent_wallet()
    {
        $result = $this->service->getMemberData('nonexistent_wallet');

        $this->assertNull($result);
    }

    public function test_suspend_member_updates_status()
    {
        MemberIdentity::create([
            'wallet_address' => 'suspend_test_wallet',
            'membership_status' => MemberIdentity::STATUS_VERIFIED
        ]);

        $result = $this->service->suspendMember('suspend_test_wallet', 'Violation of terms');

        $this->assertInstanceOf(MemberIdentity::class, $result);
        $this->assertEquals(MemberIdentity::STATUS_SUSPENDED, $result->membership_status);
        $this->assertEquals('Violation of terms', $result->metadata['suspension_reason']);
        $this->assertNotNull($result->metadata['suspended_at']);
    }

    public function test_suspend_member_returns_null_for_nonexistent_wallet()
    {
        $result = $this->service->suspendMember('nonexistent_wallet', 'Reason');

        $this->assertNull($result);
    }

    public function test_revoke_member_updates_status()
    {
        MemberIdentity::create([
            'wallet_address' => 'revoke_test_wallet',
            'membership_status' => MemberIdentity::STATUS_VERIFIED
        ]);

        $result = $this->service->revokeMember('revoke_test_wallet', 'Fraud detected');

        $this->assertInstanceOf(MemberIdentity::class, $result);
        $this->assertEquals(MemberIdentity::STATUS_REVOKED, $result->membership_status);
        $this->assertEquals('Fraud detected', $result->metadata['revocation_reason']);
        $this->assertNotNull($result->metadata['revoked_at']);
    }

    public function test_reactivate_member_restores_verified_status()
    {
        MemberIdentity::create([
            'wallet_address' => 'reactivate_wallet',
            'membership_status' => MemberIdentity::STATUS_SUSPENDED,
            'metadata' => ['suspended_at' => now()->subDays(1)->toIso8601String()]
        ]);

        $result = $this->service->reactivateMember('reactivate_wallet');

        $this->assertInstanceOf(MemberIdentity::class, $result);
        $this->assertEquals(MemberIdentity::STATUS_VERIFIED, $result->membership_status);
        $this->assertNotNull($result->metadata['reactivated_at']);
    }

    public function test_reactivate_member_returns_null_for_non_suspended_member()
    {
        MemberIdentity::create([
            'wallet_address' => 'verified_not_suspended',
            'membership_status' => MemberIdentity::STATUS_VERIFIED
        ]);

        $result = $this->service->reactivateMember('verified_not_suspended');

        $this->assertNull($result);
    }

    public function test_reactivate_member_returns_null_for_nonexistent_wallet()
    {
        $result = $this->service->reactivateMember('nonexistent_wallet');

        $this->assertNull($result);
    }
}
