<?php

namespace Tests\Unit\Membership;

use Fleetbase\Membership\Http\Requests\UpdateMemberProfileRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateMemberProfileRequestTest extends TestCase
{
    public function test_authorize_returns_true(): void
    {
        $request = new UpdateMemberProfileRequest();

        $this->assertTrue($request->authorize());
    }

    public function test_valid_payload_passes_validation(): void
    {
        $request = new UpdateMemberProfileRequest();

        $validator = Validator::make([
            'display_name' => 'Member 01',
            'avatar_url' => 'https://example.com/avatar.png',
            'bio' => 'Local producer in ElurcFleet network',
        ], $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_invalid_display_name_fails_validation(): void
    {
        $request = new UpdateMemberProfileRequest();

        $validator = Validator::make([
            'display_name' => 'Invalid@Name!',
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('display_name', $validator->errors()->toArray());
    }

    public function test_display_name_over_max_length_fails_validation(): void
    {
        $request = new UpdateMemberProfileRequest();

        $validator = Validator::make([
            'display_name' => str_repeat('a', 51),
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('display_name', $validator->errors()->toArray());
    }

    public function test_invalid_avatar_url_fails_validation(): void
    {
        $request = new UpdateMemberProfileRequest();

        $validator = Validator::make([
            'avatar_url' => 'not-a-url',
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('avatar_url', $validator->errors()->toArray());
    }

    public function test_avatar_url_requires_http_or_https(): void
    {
        $request = new UpdateMemberProfileRequest();

        $validator = Validator::make([
            'avatar_url' => 'ftp://example.com/avatar.png',
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('avatar_url', $validator->errors()->toArray());
    }

    public function test_bio_over_max_length_fails_validation(): void
    {
        $request = new UpdateMemberProfileRequest();

        $validator = Validator::make([
            'bio' => str_repeat('a', 501),
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('bio', $validator->errors()->toArray());
    }
}
