<?php

namespace Fleetbase\Membership\Services;

use Fleetbase\Membership\Models\MemberIdentity;
use Illuminate\Support\Facades\Log;

class MemberIdentityService
{
    /**
     * Find member by wallet address
     *
     * @param string $walletAddress
     * @return MemberIdentity|null
     */
    public function findByWalletAddress(string $walletAddress): ?MemberIdentity
    {
        return MemberIdentity::where('wallet_address', $walletAddress)->first();
    }

    /**
     * Create member identity from verification
     *
     * @param string $walletAddress
     * @param string $nftTokenAccount
     * @param array $metadata
     * @return MemberIdentity
     */
    public function createFromVerification(
        string $walletAddress,
        string $nftTokenAccount,
        array $metadata = []
    ): MemberIdentity {
        $correlationId = uuid();

        Log::info('Creating MemberIdentity from verification', [
            'wallet_address' => $walletAddress,
            'nft_token_account' => $nftTokenAccount,
            'correlation_id' => $correlationId
        ]);

        $identity = MemberIdentity::create([
            'wallet_address' => $walletAddress,
            'membership_status' => MemberIdentity::STATUS_VERIFIED,
            'verified_at' => now(),
            'last_verified_at' => now(),
            'nft_token_account' => $nftTokenAccount,
            'metadata' => $metadata
        ]);

        Log::info('MemberIdentity created successfully', [
            'member_uuid' => $identity->uuid,
            'wallet_address' => $walletAddress,
            'correlation_id' => $correlationId
        ]);

        return $identity;
    }

    /**
     * Update verification timestamps for existing identity
     *
     * @param MemberIdentity $identity
     * @param string $nftTokenAccount
     * @return MemberIdentity
     */
    public function updateVerificationStatus(
        MemberIdentity $identity,
        string $nftTokenAccount
    ): MemberIdentity {
        $correlationId = uuid();

        Log::info('Updating MemberIdentity verification status', [
            'member_uuid' => $identity->uuid,
            'wallet_address' => $identity->wallet_address,
            'correlation_id' => $correlationId
        ]);

        $identity->update([
            'membership_status' => MemberIdentity::STATUS_VERIFIED,
            'last_verified_at' => now(),
            'nft_token_account' => $nftTokenAccount
        ]);

        Log::info('MemberIdentity verification status updated', [
            'member_uuid' => $identity->uuid,
            'wallet_address' => $identity->wallet_address,
            'correlation_id' => $correlationId
        ]);

        return $identity;
    }

    /**
     * Check if member is verified
     *
     * @param string $walletAddress
     * @return bool
     */
    public function isVerified(string $walletAddress): bool
    {
        $identity = $this->findByWalletAddress($walletAddress);
        return $identity && $identity->isVerified();
    }

    /**
     * Check if member is pending
     *
     * @param string $walletAddress
     * @return bool
     */
    public function isPending(string $walletAddress): bool
    {
        $identity = $this->findByWalletAddress($walletAddress);
        return $identity && $identity->isPending();
    }

    /**
     * Check if member is suspended
     *
     * @param string $walletAddress
     * @return bool
     */
    public function isSuspended(string $walletAddress): bool
    {
        $identity = $this->findByWalletAddress($walletAddress);
        return $identity && $identity->isSuspended();
    }

    /**
     * Check if member is revoked
     *
     * @param string $walletAddress
     * @return bool
     */
    public function isRevoked(string $walletAddress): bool
    {
        $identity = $this->findByWalletAddress($walletAddress);
        return $identity && $identity->isRevoked();
    }

    /**
     * Get member identity or null if not found
     *
     * @param string $walletAddress
     * @return array|null
     */
    public function getMemberData(string $walletAddress): ?array
    {
        $identity = $this->findByWalletAddress($walletAddress);

        if (!$identity) {
            return null;
        }

        return [
            'uuid' => $identity->uuid,
            'wallet_address' => $identity->wallet_address,
            'membership_status' => $identity->membership_status,
            'verified_at' => $identity->verified_at?->toIso8601String(),
            'last_verified_at' => $identity->last_verified_at?->toIso8601String(),
            'nft_token_account' => $identity->nft_token_account,
            'metadata' => $identity->metadata
        ];
    }

    /**
     * Suspend a member's identity
     *
     * @param string $walletAddress
     * @param string $reason
     * @return MemberIdentity|null
     */
    public function suspendMember(string $walletAddress, string $reason = ''): ?MemberIdentity
    {
        $identity = $this->findByWalletAddress($walletAddress);

        if (!$identity) {
            Log::warning('Cannot suspend - member not found', [
                'wallet_address' => $walletAddress
            ]);
            return null;
        }

        $identity->update([
            'membership_status' => MemberIdentity::STATUS_SUSPENDED,
            'metadata' => array_merge($identity->metadata ?? [], [
                'suspended_at' => now()->toIso8601String(),
                'suspension_reason' => $reason
            ])
        ]);

        Log::info('Member suspended', [
            'member_uuid' => $identity->uuid,
            'wallet_address' => $walletAddress,
            'reason' => $reason
        ]);

        return $identity;
    }

    /**
     * Revoke a member's identity
     *
     * @param string $walletAddress
     * @param string $reason
     * @return MemberIdentity|null
     */
    public function revokeMember(string $walletAddress, string $reason = ''): ?MemberIdentity
    {
        $identity = $this->findByWalletAddress($walletAddress);

        if (!$identity) {
            Log::warning('Cannot revoke - member not found', [
                'wallet_address' => $walletAddress
            ]);
            return null;
        }

        $identity->update([
            'membership_status' => MemberIdentity::STATUS_REVOKED,
            'metadata' => array_merge($identity->metadata ?? [], [
                'revoked_at' => now()->toIso8601String(),
                'revocation_reason' => $reason
            ])
        ]);

        Log::info('Member revoked', [
            'member_uuid' => $identity->uuid,
            'wallet_address' => $walletAddress,
            'reason' => $reason
        ]);

        return $identity;
    }

    /**
     * Reactivate a suspended member
     *
     * @param string $walletAddress
     * @return MemberIdentity|null
     */
    public function reactivateMember(string $walletAddress): ?MemberIdentity
    {
        $identity = $this->findByWalletAddress($walletAddress);

        if (!$identity) {
            Log::warning('Cannot reactivate - member not found', [
                'wallet_address' => $walletAddress
            ]);
            return null;
        }

        if (!$identity->isSuspended()) {
            Log::warning('Cannot reactivate - member is not suspended', [
                'member_uuid' => $identity->uuid,
                'wallet_address' => $walletAddress,
                'current_status' => $identity->membership_status
            ]);
            return null;
        }

        $identity->update([
            'membership_status' => MemberIdentity::STATUS_VERIFIED,
            'metadata' => array_merge($identity->metadata ?? [], [
                'reactivated_at' => now()->toIso8601String()
            ])
        ]);

        Log::info('Member reactivated', [
            'member_uuid' => $identity->uuid,
            'wallet_address' => $walletAddress
        ]);

        return $identity;
    }
}
