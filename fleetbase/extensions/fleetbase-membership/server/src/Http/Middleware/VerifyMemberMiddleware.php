<?php

namespace Fleetbase\Membership\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Fleetbase\Membership\Services\MemberIdentityService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;

class VerifyMemberMiddleware
{
    protected MemberIdentityService $memberService;

    public function __construct(MemberIdentityService $memberService)
    {
        $this->memberService = $memberService;
    }

    /**
     * Handle incoming request
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            // Step 1: Get authenticated user
            $user = $request->user();
            if (!$user) {
                Log::warning('Membership verification failed - no authenticated user', [
                    'route' => $request->path(),
                    'method' => $request->method(),
                    'ip_address' => $request->ip(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->denyAccess('Authentication required', $request, $correlationId);
            }

            // Step 2: Query MemberIdentity by user relationship
            $member = $this->memberService->findByUser($user);

            if (!$member) {
                Log::warning('Member identity not found for authenticated user', [
                    'user_id' => $user->id,
                    'route' => $request->path(),
                    'method' => $request->method(),
                    'ip_address' => $request->ip(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->denyAccess('Verified DAO membership required', $request, $correlationId);
            }

            // Step 3: Check membership status
            if (!$member->isVerified()) {
                Log::warning('User membership not verified', [
                    'user_id' => $user->id,
                    'member_uuid' => $member->uuid,
                    'membership_status' => $member->membership_status,
                    'route' => $request->path(),
                    'method' => $request->method(),
                    'ip_address' => $request->ip(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->denyAccess('Verified DAO membership required', $request, $correlationId);
            }

            // Step 4: Attach member context to request
            $request->attributes->set('member_identity', $member);
            $request->attributes->set('member_profile', $member->profile);

            // Step 5: Log successful verification
            Log::debug('Membership verification passed', [
                'member_uuid' => $member->uuid,
                'user_id' => $user->id,
                'route' => $request->path(),
                'method' => $request->method(),
                'correlation_id' => $correlationId,
            ]);

            // Step 6: Proceed to controller
            return $next($request);

        } catch (QueryException $e) {
            Log::error('Database error during membership verification', [
                'error' => $e->getMessage(),
                'route' => $request->path(),
                'method' => $request->method(),
                'ip_address' => $request->ip(),
                'correlation_id' => $correlationId,
            ]);
            return $this->serviceUnavailable('Membership verification service temporarily unavailable', $request, $correlationId);

        } catch (\Exception $e) {
            Log::error('Unexpected error during membership verification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'route' => $request->path(),
                'method' => $request->method(),
                'ip_address' => $request->ip(),
                'correlation_id' => $correlationId,
            ]);
            return $this->serviceUnavailable('Membership verification service error', $request, $correlationId);
        }
    }

    /**
     * Return 403 Forbidden response
     *
     * @param string $reason
     * @param Request $request
     * @param string $correlationId
     * @return JsonResponse
     */
    protected function denyAccess(string $reason, Request $request, string $correlationId): JsonResponse
    {
        Log::warning('Membership verification failed - access denied', [
            'reason' => $reason,
            'route' => $request->path(),
            'method' => $request->method(),
            'ip_address' => $request->ip(),
            'correlation_id' => $correlationId,
        ]);

        return response()->json([
            'errors' => [
                [
                    'status' => '403',
                    'title' => 'Access Denied',
                    'detail' => $reason,
                    'meta' => [
                        'correlation_id' => $correlationId,
                        'required' => 'Verified DAO membership',
                        'help' => 'Visit /storefront/v1/membership/verify to verify your NFT badge ownership',
                    ],
                ],
            ],
        ], 403);
    }

    /**
     * Return 503 Service Unavailable response
     *
     * @param string $reason
     * @param Request $request
     * @param string $correlationId
     * @return JsonResponse
     */
    protected function serviceUnavailable(string $reason, Request $request, string $correlationId): JsonResponse
    {
        return response()->json([
            'errors' => [
                [
                    'status' => '503',
                    'title' => 'Service Unavailable',
                    'detail' => $reason,
                    'meta' => [
                        'correlation_id' => $correlationId,
                    ],
                ],
            ],
        ], 503)->header('Retry-After', 60);
    }
}
