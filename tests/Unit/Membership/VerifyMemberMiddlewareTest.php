<?php

namespace Tests\Unit\Membership;

use Closure;
use Fleetbase\Membership\Http\Middleware\VerifyMemberMiddleware;
use Fleetbase\Membership\Models\MemberIdentity;
use Fleetbase\Membership\Models\MemberProfile;
use Fleetbase\Membership\Services\MemberIdentityService;
use Fleetbase\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class VerifyMemberMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected MemberIdentityService $memberService;
    protected VerifyMemberMiddleware $middleware;
    protected User $user;
    protected MemberIdentity $memberIdentity;
    protected MemberProfile $memberProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->memberService = $this->createMock(MemberIdentityService::class);
        $this->middleware = new VerifyMemberMiddleware($this->memberService);

        // Create test user
        $this->user = new User([
            'uuid' => 'user-test-uuid-123',
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $this->user->exists = true;

        // Create test member identity
        $this->memberIdentity = new MemberIdentity([
            'uuid' => 'member-test-uuid-456',
            'user_uuid' => $this->user->uuid,
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'membership_status' => MemberIdentity::STATUS_VERIFIED,
            'verified_at' => now(),
            'last_verified_at' => now(),
        ]);
        $this->memberIdentity->exists = true;

        // Create test member profile
        $this->memberProfile = new MemberProfile([
            'uuid' => 'profile-test-uuid-789',
            'member_identity_uuid' => $this->memberIdentity->uuid,
            'display_name' => 'TestMember',
        ]);
        $this->memberProfile->exists = true;

        // Set up profile relationship
        $this->memberIdentity->setRelation('profile', $this->memberProfile);
    }

    /**
     * Create a mock request with authenticated user
     */
    protected function createAuthenticatedRequest(): Request
    {
        $request = Request::create('/storefront/v1/membership/status', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });
        return $request;
    }

    /**
     * Create a mock request without authenticated user
     */
    protected function createUnauthenticatedRequest(): Request
    {
        $request = Request::create('/storefront/v1/membership/status', 'GET');
        $request->setUserResolver(function () {
            return null;
        });
        return $request;
    }

    /**
     * Create a mock next closure that returns success
     */
    protected function createNextClosure(): Closure
    {
        return function ($request) {
            return response()->json(['success' => true]);
        };
    }

    public function test_verified_member_passes_through_middleware()
    {
        $request = $this->createAuthenticatedRequest();

        $this->memberService
            ->expects($this->once())
            ->method('findByUser')
            ->with($this->user)
            ->willReturn($this->memberIdentity);

        $response = $this->middleware->handle($request, $this->createNextClosure());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['success' => true], json_decode($response->getContent(), true));
    }

    public function test_member_context_is_available_in_request()
    {
        $request = $this->createAuthenticatedRequest();

        $this->memberService
            ->expects($this->once())
            ->method('findByUser')
            ->with($this->user)
            ->willReturn($this->memberIdentity);

        $capturedRequest = null;
        $next = function ($req) use (&$capturedRequest) {
            $capturedRequest = $req;
            return response()->json(['success' => true]);
        };

        $this->middleware->handle($request, $next);

        $this->assertNotNull($capturedRequest);
        $this->assertEquals($this->memberIdentity, $capturedRequest->attributes->get('member_identity'));
        $this->assertEquals($this->memberProfile, $capturedRequest->attributes->get('member_profile'));
    }

    public function test_non_verified_member_is_blocked()
    {
        $request = $this->createAuthenticatedRequest();

        $pendingMember = new MemberIdentity([
            'uuid' => 'member-pending-uuid',
            'user_uuid' => $this->user->uuid,
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'membership_status' => MemberIdentity::STATUS_PENDING,
        ]);
        $pendingMember->exists = true;

        $this->memberService
            ->expects($this->once())
            ->method('findByUser')
            ->with($this->user)
            ->willReturn($pendingMember);

        $response = $this->middleware->handle($request, $this->createNextClosure());

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertEquals('Verified DAO membership required', $data['errors'][0]['detail']);
    }

    public function test_unauthenticated_request_is_blocked()
    {
        $request = $this->createUnauthenticatedRequest();

        $response = $this->middleware->handle($request, $this->createNextClosure());

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertEquals('Authentication required', $data['errors'][0]['detail']);
    }

    public function test_member_not_found_is_blocked()
    {
        $request = $this->createAuthenticatedRequest();

        $this->memberService
            ->expects($this->once())
            ->method('findByUser')
            ->with($this->user)
            ->willReturn(null);

        $response = $this->middleware->handle($request, $this->createNextClosure());

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertEquals('Verified DAO membership required', $data['errors'][0]['detail']);
    }

    public function test_suspended_member_is_blocked()
    {
        $request = $this->createAuthenticatedRequest();

        $suspendedMember = new MemberIdentity([
            'uuid' => 'member-suspended-uuid',
            'user_uuid' => $this->user->uuid,
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'membership_status' => MemberIdentity::STATUS_SUSPENDED,
        ]);
        $suspendedMember->exists = true;

        $this->memberService
            ->expects($this->once())
            ->method('findByUser')
            ->with($this->user)
            ->willReturn($suspendedMember);

        $response = $this->middleware->handle($request, $this->createNextClosure());

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertEquals('Verified DAO membership required', $data['errors'][0]['detail']);
    }

    public function test_revoked_member_is_blocked()
    {
        $request = $this->createAuthenticatedRequest();

        $revokedMember = new MemberIdentity([
            'uuid' => 'member-revoked-uuid',
            'user_uuid' => $this->user->uuid,
            'wallet_address' => '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU',
            'membership_status' => MemberIdentity::STATUS_REVOKED,
        ]);
        $revokedMember->exists = true;

        $this->memberService
            ->expects($this->once())
            ->method('findByUser')
            ->with($this->user)
            ->willReturn($revokedMember);

        $response = $this->middleware->handle($request, $this->createNextClosure());

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertEquals('Verified DAO membership required', $data['errors'][0]['detail']);
    }

    public function test_database_error_returns_503_with_retry_after()
    {
        $request = $this->createAuthenticatedRequest();

        $this->memberService
            ->expects($this->once())
            ->method('findByUser')
            ->with($this->user)
            ->willThrowException(new QueryException(
                'sqlsrv',
                'SELECT * FROM member_identities',
                [],
                new \Exception('Connection refused')
            ));

        $response = $this->middleware->handle($request, $this->createNextClosure());

        $this->assertEquals(503, $response->getStatusCode());
        $this->assertEquals(60, $response->headers->get('Retry-After'));
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertEquals('Service Unavailable', $data['errors'][0]['title']);
    }

    public function test_unexpected_error_returns_503_with_retry_after()
    {
        $request = $this->createAuthenticatedRequest();

        $this->memberService
            ->expects($this->once())
            ->method('findByUser')
            ->with($this->user)
            ->willThrowException(new \Exception('Unexpected error'));

        $response = $this->middleware->handle($request, $this->createNextClosure());

        $this->assertEquals(503, $response->getStatusCode());
        $this->assertEquals(60, $response->headers->get('Retry-After'));
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertEquals('Service Unavailable', $data['errors'][0]['title']);
    }

    public function test_error_response_includes_correlation_id()
    {
        $request = $this->createUnauthenticatedRequest();

        $response = $this->middleware->handle($request, $this->createNextClosure());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('meta', $data['errors'][0]);
        $this->assertArrayHasKey('correlation_id', $data['errors'][0]['meta']);
        $this->assertNotEmpty($data['errors'][0]['meta']['correlation_id']);
    }

    public function test_error_response_includes_help_message()
    {
        $request = $this->createUnauthenticatedRequest();

        $response = $this->middleware->handle($request, $this->createNextClosure());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('meta', $data['errors'][0]);
        $this->assertArrayHasKey('help', $data['errors'][0]['meta']);
        $this->assertStringContainsString('/storefront/v1/membership/verify', $data['errors'][0]['meta']['help']);
    }

    public function test_successful_request_includes_correlation_id_in_logs()
    {
        $request = $this->createAuthenticatedRequest();
        $request->headers->set('X-Correlation-ID', 'test-correlation-id-123');

        $this->memberService
            ->expects($this->once())
            ->method('findByUser')
            ->with($this->user)
            ->willReturn($this->memberIdentity);

        $response = $this->middleware->handle($request, $this->createNextClosure());

        $this->assertEquals(200, $response->getStatusCode());
    }
}
