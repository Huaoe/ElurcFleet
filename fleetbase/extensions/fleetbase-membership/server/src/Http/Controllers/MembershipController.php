<?php

namespace Fleetbase\Membership\Http\Controllers;

use Fleetbase\Http\Controllers\Controller;
use Fleetbase\Membership\Http\Requests\VerifyMembershipRequest;
use Fleetbase\Membership\Http\Resources\MemberIdentityResource;
use Fleetbase\Membership\Http\Resources\MemberProfileResource;
use Fleetbase\Membership\Models\MemberIdentity;
use Fleetbase\Membership\Services\MemberIdentityService;
use Fleetbase\Membership\Services\MembershipVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class MembershipController extends Controller
{
    protected MembershipVerificationService $verificationService;
    protected MemberIdentityService $memberService;

    public function __construct(
        MembershipVerificationService $verificationService,
        MemberIdentityService $memberService
    ) {
        $this->verificationService = $verificationService;
        $this->memberService = $memberService;
    }

    /**
     * Verify wallet ownership and NFT badge
     * POST /storefront/v1/membership/verify
     */
    public function verify(VerifyMembershipRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $validated = $request->validated();

        // Validate challenge nonce/timestamp to prevent replay attacks
        if (!$this->validateChallengeMessage($validated['message'], $correlationId)) {
            return $this->errorResponse(
                'Invalid Challenge',
                'Challenge message expired or reused',
                403,
                $correlationId
            );
        }

        Log::info('Membership verification API request', [
            'wallet_address' => $validated['wallet_address'],
            'correlation_id' => $correlationId,
            'ip_address' => $request->ip(),
        ]);

        $result = $this->verificationService->verifyMembership(
            $validated['wallet_address'],
            $validated['signature'],
            $validated['message']
        );

        if (!$result['success']) {
            Log::warning('Membership verification failed', [
                'wallet_address' => $validated['wallet_address'],
                'error' => $result['error'],
                'error_code' => $result['error_code'] ?? 'UNKNOWN',
                'correlation_id' => $correlationId,
            ]);

            return $this->errorResponse(
                'Verification Failed',
                $result['error'],
                $this->getHttpStatusFromErrorCode($result['error_code'] ?? 'UNKNOWN'),
                $correlationId
            );
        }

        $member = MemberIdentity::with('profile')->where('uuid', $result['member_uuid'])->first();

        if (!$member) {
            Log::error('Member not found after successful verification', [
                'member_uuid' => $result['member_uuid'],
                'correlation_id' => $correlationId,
            ]);

            return $this->errorResponse(
                'Internal Error',
                'Member record not found',
                500,
                $correlationId
            );
        }

        $token = $this->createSanctumToken($member);

        Log::info('Membership verification succeeded', [
            'wallet_address' => $validated['wallet_address'],
            'member_uuid' => $member->uuid,
            'is_new_member' => $result['is_new_member'] ?? false,
            'correlation_id' => $correlationId,
        ]);

        return $this->successResponse([
            'member_uuid' => $member->uuid,
            'membership_status' => $member->membership_status,
            'verified_at' => $member->verified_at?->toIso8601String(),
            'last_verified_at' => $member->last_verified_at?->toIso8601String(),
            'profile' => $member->profile ? new MemberProfileResource($member->profile) : null,
            'token' => $token,
        ], 200, $correlationId);
    }

    /**
     * Validate challenge message format and prevent replay attacks
     */
    protected function validateChallengeMessage(string $message, string $correlationId): bool
    {
        // Expected format: "Verify wallet ownership for Stalabard DAO: {nonce}:{timestamp}"
        if (!preg_match('/Verify wallet ownership for Stalabard DAO: ([a-zA-Z0-9]+):(\d+)/', $message, $matches)) {
            Log::warning('Invalid challenge message format', [
                'correlation_id' => $correlationId,
            ]);
            return false;
        }

        $nonce = $matches[1];
        $timestamp = (int) $matches[2];

        // Check timestamp is within 5 minutes (prevents replay attacks)
        $maxAge = 300; // 5 minutes in seconds
        if ((now()->timestamp - $timestamp) > $maxAge) {
            Log::warning('Challenge message expired', [
                'timestamp' => $timestamp,
                'age_seconds' => now()->timestamp - $timestamp,
                'correlation_id' => $correlationId,
            ]);
            return false;
        }

        // Check nonce hasn't been used before (Redis cache with TTL)
        $nonceKey = 'membership_challenge_nonce:' . $nonce;
        if (Cache::has($nonceKey)) {
            Log::warning('Challenge nonce reused', [
                'nonce' => $nonce,
                'correlation_id' => $correlationId,
            ]);
            return false;
        }

        // Store nonce with 6 hour TTL (longer than challenge validity)
        Cache::put($nonceKey, true, now()->addHours(6));

        return true;
    }

    /**
     * Get current member status
     * GET /storefront/v1/membership/status
     */
    public function status(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $member = $this->getAuthenticatedMember($request);

        if (!$member) {
            return $this->errorResponse(
                'Unauthorized',
                'Authentication required',
                401,
                $correlationId
            );
        }

        Log::info('Member status request', [
            'member_uuid' => $member->uuid,
            'correlation_id' => $correlationId,
        ]);

        return $this->successResponse([
            'member_uuid' => $member->uuid,
            'membership_status' => $member->membership_status,
            'verified_at' => $member->verified_at?->toIso8601String(),
            'last_verified_at' => $member->last_verified_at?->toIso8601String(),
        ], 200, $correlationId);
    }

    /**
     * Get member profile
     * GET /storefront/v1/membership/profile
     */
    public function profile(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $member = $this->getAuthenticatedMember($request);

        if (!$member) {
            return $this->errorResponse(
                'Unauthorized',
                'Authentication required',
                401,
                $correlationId
            );
        }

        $member->load('profile');

        Log::info('Member profile request', [
            'member_uuid' => $member->uuid,
            'correlation_id' => $correlationId,
        ]);

        return $this->successResponse([
            'member_uuid' => $member->uuid,
            'membership_status' => $member->membership_status,
            'profile' => $member->profile ? new MemberProfileResource($member->profile) : null,
        ], 200, $correlationId);
    }

    /**
     * Get authenticated member from request using Sanctum token
     */
    protected function getAuthenticatedMember(Request $request): ?MemberIdentity
    {
        $token = $request->bearerToken();

        if (!$token) {
            return null;
        }

        // Try Sanctum token first
        $sanctumToken = PersonalAccessToken::findToken($token);
        if ($sanctumToken) {
            $memberUuid = $sanctumToken->tokenable_id;
            return MemberIdentity::where('uuid', $memberUuid)->first();
        }

        return null;
    }

    /**
     * Create Sanctum authentication token for member
     */
    protected function createSanctumToken(MemberIdentity $member): string
    {
        $tokenName = config('membership.auth.token_name', 'stalabard-membership');
        $expirationDays = config('membership.auth.token_expiration_days', 7);

        // Create Sanctum token with expiration
        $token = $member->createToken($tokenName, ['membership:read', 'membership:write']);
        
        return $token->plainTextToken;
    }

    /**
     * Format success response
     */
    protected function successResponse(array $data, int $status = 200, string $correlationId = null): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => [
                'correlation_id' => $correlationId ?? Str::uuid()->toString(),
            ],
        ], $status);
    }

    /**
     * Format error response
     */
    protected function errorResponse(string $title, string $detail, int $status = 400, string $correlationId = null): JsonResponse
    {
        return response()->json([
            'errors' => [
                [
                    'status' => (string) $status,
                    'title' => $title,
                    'detail' => $detail,
                    'meta' => [
                        'correlation_id' => $correlationId ?? Str::uuid()->toString(),
                    ],
                ],
            ],
        ], $status);
    }

    /**
     * Map error codes to HTTP status codes
     */
    protected function getHttpStatusFromErrorCode(string $errorCode): int
    {
        return match ($errorCode) {
            'INVALID_SIGNATURE' => 403,
            'NFT_NOT_FOUND' => 403,
            'MEMBERSHIP_REVOKED' => 403,
            'CONFIG_MISSING' => 503,
            default => 400,
        };
    }
}
