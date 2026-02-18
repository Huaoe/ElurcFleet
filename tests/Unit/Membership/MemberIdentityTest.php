<?php

namespace Tests\Unit\Membership;

use Tests\TestCase;
use Fleetbase\Membership\Models\MemberIdentity;
use Fleetbase\Membership\Models\MemberProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class MemberIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_member_identity_with_valid_data()
    {
        $memberIdentity = MemberIdentity::create([
            'uuid' => Str::uuid(),
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'membership_status' => MemberIdentity::STATUS_PENDING,
            'metadata' => ['network' => 'mainnet-beta']
        ]);

        $this->assertDatabaseHas('member_identities', [
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'membership_status' => 'pending'
        ]);

        $this->assertEquals('pending', $memberIdentity->membership_status);
        $this->assertIsArray($memberIdentity->metadata);
    }

    public function test_wallet_address_must_be_unique()
    {
        $walletAddress = '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU';

        MemberIdentity::create([
            'uuid' => Str::uuid(),
            'wallet_address' => $walletAddress,
            'membership_status' => MemberIdentity::STATUS_PENDING
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        MemberIdentity::create([
            'uuid' => Str::uuid(),
            'wallet_address' => $walletAddress,
            'membership_status' => MemberIdentity::STATUS_PENDING
        ]);
    }

    public function test_membership_status_enum_values()
    {
        $this->assertEquals('pending', MemberIdentity::STATUS_PENDING);
        $this->assertEquals('verified', MemberIdentity::STATUS_VERIFIED);
        $this->assertEquals('suspended', MemberIdentity::STATUS_SUSPENDED);
        $this->assertEquals('revoked', MemberIdentity::STATUS_REVOKED);
    }

    public function test_has_one_member_profile_relationship()
    {
        $memberIdentity = MemberIdentity::create([
            'uuid' => Str::uuid(),
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'membership_status' => MemberIdentity::STATUS_VERIFIED
        ]);

        $profile = MemberProfile::create([
            'uuid' => Str::uuid(),
            'member_identity_uuid' => $memberIdentity->uuid,
            'display_name' => 'TestUser'
        ]);

        $this->assertInstanceOf(MemberProfile::class, $memberIdentity->profile);
        $this->assertEquals($profile->uuid, $memberIdentity->profile->uuid);
    }

    public function test_verified_at_is_cast_to_datetime()
    {
        $memberIdentity = MemberIdentity::create([
            'uuid' => Str::uuid(),
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'membership_status' => MemberIdentity::STATUS_VERIFIED,
            'verified_at' => now()
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $memberIdentity->verified_at);
    }

    public function test_metadata_is_cast_to_array()
    {
        $metadata = ['network' => 'mainnet-beta', 'attempts' => 3];

        $memberIdentity = MemberIdentity::create([
            'uuid' => Str::uuid(),
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'membership_status' => MemberIdentity::STATUS_PENDING,
            'metadata' => $metadata
        ]);

        $this->assertIsArray($memberIdentity->metadata);
        $this->assertEquals('mainnet-beta', $memberIdentity->metadata['network']);
        $this->assertEquals(3, $memberIdentity->metadata['attempts']);
    }

    public function test_is_pending_method()
    {
        $memberIdentity = MemberIdentity::create([
            'uuid' => Str::uuid(),
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'membership_status' => MemberIdentity::STATUS_PENDING
        ]);

        $this->assertTrue($memberIdentity->isPending());
        $this->assertFalse($memberIdentity->isVerified());
    }

    public function test_is_verified_method()
    {
        $memberIdentity = MemberIdentity::create([
            'uuid' => Str::uuid(),
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'membership_status' => MemberIdentity::STATUS_VERIFIED
        ]);

        $this->assertTrue($memberIdentity->isVerified());
        $this->assertFalse($memberIdentity->isPending());
    }

    public function test_mark_as_verified_method()
    {
        $memberIdentity = MemberIdentity::create([
            'uuid' => Str::uuid(),
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'membership_status' => MemberIdentity::STATUS_PENDING
        ]);

        $this->assertTrue($memberIdentity->isPending());

        $memberIdentity->markAsVerified();

        $this->assertTrue($memberIdentity->isVerified());
        $this->assertNotNull($memberIdentity->verified_at);
        $this->assertNotNull($memberIdentity->last_verified_at);
    }

    public function test_update_last_verified_method()
    {
        $memberIdentity = MemberIdentity::create([
            'uuid' => Str::uuid(),
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'membership_status' => MemberIdentity::STATUS_VERIFIED,
            'verified_at' => now()->subDays(7)
        ]);

        $originalVerifiedAt = $memberIdentity->verified_at;
        
        sleep(1);
        $memberIdentity->updateLastVerified();

        $this->assertEquals($originalVerifiedAt, $memberIdentity->verified_at);
        $this->assertNotNull($memberIdentity->last_verified_at);
        $this->assertTrue($memberIdentity->last_verified_at->greaterThan($originalVerifiedAt));
    }

    public function test_soft_deletes_work()
    {
        $memberIdentity = MemberIdentity::create([
            'uuid' => Str::uuid(),
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'membership_status' => MemberIdentity::STATUS_VERIFIED
        ]);

        $uuid = $memberIdentity->uuid;
        $memberIdentity->delete();

        $this->assertSoftDeleted('member_identities', ['uuid' => $uuid]);
        $this->assertNull(MemberIdentity::find($uuid));
        $this->assertNotNull(MemberIdentity::withTrashed()->find($uuid));
    }
}
