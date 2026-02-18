<?php

namespace Tests\Unit\Membership;

use Tests\TestCase;
use Fleetbase\Membership\Models\MemberIdentity;
use Fleetbase\Membership\Models\MemberProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class MemberProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function createMemberIdentity()
    {
        return MemberIdentity::create([
            'uuid' => Str::uuid(),
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'membership_status' => MemberIdentity::STATUS_VERIFIED
        ]);
    }

    public function test_can_create_member_profile_with_valid_data()
    {
        $memberIdentity = $this->createMemberIdentity();

        $profile = MemberProfile::create([
            'uuid' => Str::uuid(),
            'member_identity_uuid' => $memberIdentity->uuid,
            'display_name' => 'TestUser',
            'avatar_url' => 'https://example.com/avatar.jpg',
            'bio' => 'Test bio'
        ]);

        $this->assertDatabaseHas('member_profiles', [
            'member_identity_uuid' => $memberIdentity->uuid,
            'display_name' => 'TestUser'
        ]);

        $this->assertEquals('TestUser', $profile->display_name);
    }

    public function test_display_name_must_be_unique()
    {
        $memberIdentity1 = $this->createMemberIdentity();
        $memberIdentity2 = MemberIdentity::create([
            'uuid' => Str::uuid(),
            'wallet_address' => 'AnotherWallet123456789',
            'membership_status' => MemberIdentity::STATUS_VERIFIED
        ]);

        MemberProfile::create([
            'uuid' => Str::uuid(),
            'member_identity_uuid' => $memberIdentity1->uuid,
            'display_name' => 'UniqueUser'
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        MemberProfile::create([
            'uuid' => Str::uuid(),
            'member_identity_uuid' => $memberIdentity2->uuid,
            'display_name' => 'UniqueUser'
        ]);
    }

    public function test_belongs_to_member_identity_relationship()
    {
        $memberIdentity = $this->createMemberIdentity();

        $profile = MemberProfile::create([
            'uuid' => Str::uuid(),
            'member_identity_uuid' => $memberIdentity->uuid,
            'display_name' => 'TestUser'
        ]);

        $this->assertInstanceOf(MemberIdentity::class, $profile->memberIdentity);
        $this->assertEquals($memberIdentity->uuid, $profile->memberIdentity->uuid);
    }

    public function test_display_name_max_length_enforced()
    {
        $memberIdentity = $this->createMemberIdentity();
        $longName = str_repeat('a', 100);

        $profile = MemberProfile::create([
            'uuid' => Str::uuid(),
            'member_identity_uuid' => $memberIdentity->uuid,
            'display_name' => $longName
        ]);

        $this->assertEquals(50, strlen($profile->display_name));
    }

    public function test_bio_max_length_enforced()
    {
        $memberIdentity = $this->createMemberIdentity();
        $longBio = str_repeat('a', 1000);

        $profile = MemberProfile::create([
            'uuid' => Str::uuid(),
            'member_identity_uuid' => $memberIdentity->uuid,
            'display_name' => 'TestUser',
            'bio' => $longBio
        ]);

        $this->assertEquals(500, strlen($profile->bio));
    }

    public function test_display_name_defaults_to_anonymous_when_null()
    {
        $memberIdentity = $this->createMemberIdentity();

        $profile = new MemberProfile([
            'uuid' => Str::uuid(),
            'member_identity_uuid' => $memberIdentity->uuid
        ]);

        $this->assertEquals('Anonymous Member', $profile->display_name);
    }

    public function test_metadata_is_cast_to_array()
    {
        $memberIdentity = $this->createMemberIdentity();
        $metadata = ['preferences' => ['theme' => 'dark']];

        $profile = MemberProfile::create([
            'uuid' => Str::uuid(),
            'member_identity_uuid' => $memberIdentity->uuid,
            'display_name' => 'TestUser',
            'metadata' => $metadata
        ]);

        $this->assertIsArray($profile->metadata);
        $this->assertEquals('dark', $profile->metadata['preferences']['theme']);
    }

    public function test_avatar_url_can_be_null()
    {
        $memberIdentity = $this->createMemberIdentity();

        $profile = MemberProfile::create([
            'uuid' => Str::uuid(),
            'member_identity_uuid' => $memberIdentity->uuid,
            'display_name' => 'TestUser',
            'avatar_url' => null
        ]);

        $this->assertNull($profile->avatar_url);
    }

    public function test_bio_can_be_null()
    {
        $memberIdentity = $this->createMemberIdentity();

        $profile = MemberProfile::create([
            'uuid' => Str::uuid(),
            'member_identity_uuid' => $memberIdentity->uuid,
            'display_name' => 'TestUser',
            'bio' => null
        ]);

        $this->assertNull($profile->bio);
    }

    public function test_cascade_delete_when_member_identity_deleted()
    {
        $memberIdentity = $this->createMemberIdentity();

        $profile = MemberProfile::create([
            'uuid' => Str::uuid(),
            'member_identity_uuid' => $memberIdentity->uuid,
            'display_name' => 'TestUser'
        ]);

        $profileUuid = $profile->uuid;
        $memberIdentity->delete();

        $this->assertNull(MemberProfile::find($profileUuid));
    }

    public function test_soft_deletes_work()
    {
        $memberIdentity = $this->createMemberIdentity();

        $profile = MemberProfile::create([
            'uuid' => Str::uuid(),
            'member_identity_uuid' => $memberIdentity->uuid,
            'display_name' => 'TestUser'
        ]);

        $uuid = $profile->uuid;
        $profile->delete();

        $this->assertSoftDeleted('member_profiles', ['uuid' => $uuid]);
        $this->assertNull(MemberProfile::find($uuid));
        $this->assertNotNull(MemberProfile::withTrashed()->find($uuid));
    }
}
