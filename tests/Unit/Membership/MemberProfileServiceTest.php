<?php

namespace Tests\Unit\Membership;

use Fleetbase\Membership\Models\MemberIdentity;
use Fleetbase\Membership\Models\MemberProfile;
use Fleetbase\Membership\Services\MemberProfileService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MemberProfileService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MemberProfileService();
    }

    public function test_update_profile_updates_only_provided_fields(): void
    {
        $member = MemberIdentity::factory()->create();
        $profile = MemberProfile::factory()->create([
            'member_identity_uuid' => $member->uuid,
            'display_name' => 'Before Name',
            'avatar_url' => 'https://example.com/before.png',
            'bio' => 'Before bio',
        ]);

        $updated = $this->service->updateProfile($member->uuid, [
            'display_name' => 'After Name',
        ]);

        $this->assertEquals('After Name', $updated->display_name);
        $this->assertEquals('https://example.com/before.png', $updated->avatar_url);
        $this->assertEquals('Before bio', $updated->bio);

        $this->assertDatabaseHas('member_profiles', [
            'uuid' => $profile->uuid,
            'display_name' => 'After Name',
            'avatar_url' => 'https://example.com/before.png',
        ]);
    }

    public function test_update_profile_allows_nullable_fields(): void
    {
        $member = MemberIdentity::factory()->create();
        $profile = MemberProfile::factory()->create([
            'member_identity_uuid' => $member->uuid,
            'avatar_url' => 'https://example.com/avatar.png',
            'bio' => 'Original bio',
        ]);

        $updated = $this->service->updateProfile($member->uuid, [
            'avatar_url' => null,
            'bio' => null,
        ]);

        $this->assertNull($updated->avatar_url);
        $this->assertNull($updated->bio);

        $this->assertDatabaseHas('member_profiles', [
            'uuid' => $profile->uuid,
            'avatar_url' => null,
            'bio' => null,
        ]);
    }

    public function test_update_profile_throws_not_found_if_profile_missing(): void
    {
        $member = MemberIdentity::factory()->create([
            'uuid' => (string) Str::uuid(),
        ]);

        $this->expectException(ModelNotFoundException::class);

        $this->service->updateProfile($member->uuid, [
            'display_name' => 'No profile member',
        ]);
    }
}
