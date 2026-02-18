<?php

namespace Tests\Unit\Membership;

use Fleetbase\Membership\Http\Requests\VerifyMembershipRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class VerifyMembershipRequestTest extends TestCase
{
    public function test_authorize_returns_true()
    {
        $request = new VerifyMembershipRequest();
        
        $this->assertTrue($request->authorize());
    }

    public function test_valid_wallet_address_passes_validation()
    {
        $request = new VerifyMembershipRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'signature' => base64_encode(str_repeat('x', 64)),
            'message' => 'Test message',
        ], $rules);

        $this->assertFalse($validator->fails());
    }

    public function test_invalid_wallet_address_fails_validation()
    {
        $request = new VerifyMembershipRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'wallet_address' => 'invalid-wallet',
            'signature' => base64_encode(str_repeat('x', 64)),
            'message' => 'Test message',
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('wallet_address', $validator->errors()->toArray());
    }

    public function test_missing_wallet_address_fails_validation()
    {
        $request = new VerifyMembershipRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'signature' => base64_encode(str_repeat('x', 64)),
            'message' => 'Test message',
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('wallet_address', $validator->errors()->toArray());
    }

    public function test_missing_signature_fails_validation()
    {
        $request = new VerifyMembershipRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'message' => 'Test message',
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('signature', $validator->errors()->toArray());
    }

    public function test_missing_message_fails_validation()
    {
        $request = new VerifyMembershipRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'signature' => base64_encode(str_repeat('x', 64)),
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('message', $validator->errors()->toArray());
    }

    public function test_custom_error_messages_are_defined()
    {
        $request = new VerifyMembershipRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('wallet_address.required', $messages);
        $this->assertArrayHasKey('wallet_address.regex', $messages);
        $this->assertArrayHasKey('signature.required', $messages);
        $this->assertArrayHasKey('message.required', $messages);
    }

    public function test_short_wallet_address_fails_validation()
    {
        $request = new VerifyMembershipRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkhe',
            'signature' => base64_encode(str_repeat('x', 64)),
            'message' => 'Test message',
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('wallet_address', $validator->errors()->toArray());
    }

    public function test_valid_signature_format_passes_validation()
    {
        $request = new VerifyMembershipRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'signature' => '5iK1p7X1z9w7w8g9b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2g3h4i5j6k7l8m9n0o1p2q3r4s5t6u7v8w9x0y1z2a3b4c5d6e7f8g9h0i1j2k3l4m5',
            'message' => 'Test message',
        ], $rules);

        $this->assertFalse($validator->fails());
    }

    public function test_invalid_signature_format_fails_validation()
    {
        $request = new VerifyMembershipRequest();
        $rules = $request->rules();

        // Too short signature
        $validator = Validator::make([
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'signature' => 'short',
            'message' => 'Test message',
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('signature', $validator->errors()->toArray());
    }

    public function test_signature_with_invalid_characters_fails_validation()
    {
        $request = new VerifyMembershipRequest();
        $rules = $request->rules();

        // Contains invalid character '0' (not in base58)
        $validator = Validator::make([
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'signature' => '5iK1p7X1z9w7w8g9b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2g3h4i5j6k7l8m9n0o1p2q3r4s5t6u7v8w9x0y1z2a3b4c5d6e7f8g9h0i1j2k3l4m0',
            'message' => 'Test message',
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('signature', $validator->errors()->toArray());
    }
}
