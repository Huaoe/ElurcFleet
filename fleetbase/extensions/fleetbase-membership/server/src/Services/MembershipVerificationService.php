<?php

namespace Fleetbase\Membership\Services;

use Fleetbase\Membership\Models\MemberIdentity;
use Illuminate\Support\Facades\Log;

class MembershipVerificationService
{
    protected SolanaRpcService $solanaRpc;
    protected MemberIdentityService $memberService;
    protected string $daoNftCollection;

    public function __construct(
        SolanaRpcService $solanaRpc,
        MemberIdentityService $memberService
    ) {
        $this->solanaRpc = $solanaRpc;
        $this->memberService = $memberService;
        $this->daoNftCollection = config('membership.dao_nft_collection');
    }

    /**
     * Verify membership via wallet signature and NFT ownership
     *
     * @param string $walletAddress
     * @param string $signature
     * @param string $message
     * @return array
     */
    public function verifyMembership(string $walletAddress, string $signature, string $message = ''): array
    {
        $correlationId = uuid();

        Log::info('Starting membership verification', [
            'wallet_address' => $walletAddress,
            'correlation_id' => $correlationId,
            'has_signature' => !empty($signature),
            'has_message' => !empty($message)
        ]);

        if (empty($this->daoNftCollection)) {
            Log::error('DAO NFT collection address not configured', [
                'wallet_address' => $walletAddress,
                'correlation_id' => $correlationId
            ]);

            return [
                'success' => false,
                'error' => 'DAO NFT collection not configured',
                'error_code' => 'CONFIG_MISSING'
            ];
        }

        if (!$this->validateSignature($walletAddress, $signature, $message)) {
            Log::warning('Signature validation failed', [
                'wallet_address' => $walletAddress,
                'correlation_id' => $correlationId
            ]);

            return [
                'success' => false,
                'error' => 'Invalid signature',
                'error_code' => 'INVALID_SIGNATURE'
            ];
        }

        Log::info('Signature validated successfully', [
            'wallet_address' => $walletAddress,
            'correlation_id' => $correlationId
        ]);

        $nftData = $this->solanaRpc->verifyNftCollection($walletAddress, $this->daoNftCollection);

        if (!$nftData) {
            Log::warning('NFT ownership verification failed', [
                'wallet_address' => $walletAddress,
                'collection_address' => $this->daoNftCollection,
                'correlation_id' => $correlationId
            ]);

            return [
                'success' => false,
                'error' => 'No DAO NFT found for this wallet',
                'error_code' => 'NFT_NOT_FOUND'
            ];
        }

        Log::info('NFT ownership verified', [
            'wallet_address' => $walletAddress,
            'nft_mint' => $nftData['mint'],
            'correlation_id' => $correlationId
        ]);

        $existingIdentity = $this->memberService->findByWalletAddress($walletAddress);

        if ($existingIdentity) {
            if ($existingIdentity->isRevoked()) {
                Log::warning('Verification attempted for revoked member', [
                    'wallet_address' => $walletAddress,
                    'member_uuid' => $existingIdentity->uuid,
                    'correlation_id' => $correlationId
                ]);

                return [
                    'success' => false,
                    'error' => 'Membership has been revoked',
                    'error_code' => 'MEMBERSHIP_REVOKED',
                    'member_uuid' => $existingIdentity->uuid
                ];
            }

            $identity = $this->memberService->updateVerificationStatus(
                $existingIdentity,
                $nftData['token_account'] ?? $nftData['mint']
            );

            Log::info('Existing member re-verified', [
                'member_uuid' => $identity->uuid,
                'wallet_address' => $walletAddress,
                'correlation_id' => $correlationId
            ]);

            return [
                'success' => true,
                'member_uuid' => $identity->uuid,
                'membership_status' => $identity->membership_status,
                'is_new_member' => false,
                'verified_at' => $identity->verified_at?->toIso8601String(),
                'last_verified_at' => $identity->last_verified_at?->toIso8601String(),
                'nft_token_account' => $identity->nft_token_account,
                'wallet_address' => $identity->wallet_address
            ];
        }

        $identity = $this->memberService->createFromVerification(
            $walletAddress,
            $nftData['token_account'] ?? $nftData['mint'],
            [
                'nft_metadata' => $nftData['metadata'] ?? null,
                'verification_correlation_id' => $correlationId
            ]
        );

        Log::info('New member verified and created', [
            'member_uuid' => $identity->uuid,
            'wallet_address' => $walletAddress,
            'correlation_id' => $correlationId
        ]);

        return [
            'success' => true,
            'member_uuid' => $identity->uuid,
            'membership_status' => $identity->membership_status,
            'is_new_member' => true,
            'verified_at' => $identity->verified_at?->toIso8601String(),
            'last_verified_at' => $identity->last_verified_at?->toIso8601String(),
            'nft_token_account' => $identity->nft_token_account,
            'wallet_address' => $identity->wallet_address
        ];
    }

    /**
     * Validate wallet signature proves ownership using ed25519
     *
     * @param string $walletAddress
     * @param string $signature
     * @param string $message
     * @return bool
     */
    protected function validateSignature(string $walletAddress, string $signature, string $message = ''): bool
    {
        if (empty($signature)) {
            return false;
        }

        if (empty($message)) {
            $message = 'Verify DAO membership for ' . $walletAddress;
        }

        try {
            // Decode base58 wallet address to get public key bytes
            $publicKeyBytes = $this->decodeBase58($walletAddress);
            
            if (strlen($publicKeyBytes) !== 32) {
                Log::warning('Invalid public key length', [
                    'wallet_address' => $walletAddress,
                    'key_length' => strlen($publicKeyBytes)
                ]);
                return false;
            }

            // Decode base64 signature
            $signatureBytes = base64_decode($signature, true);
            
            if ($signatureBytes === false || strlen($signatureBytes) !== 64) {
                Log::warning('Invalid signature format', [
                    'wallet_address' => $walletAddress,
                    'signature_length' => strlen($signatureBytes ?: '')
                ]);
                return false;
            }

            // Solana messages are typically prefixed with "\x19Solana Signed Message:\n" + length + message
            // or just the raw message bytes depending on the signing method
            // For Phantom/standard Solana wallets, the message is UTF-8 encoded
            $messageBytes = $message;

            // Use sodium for ed25519 signature verification
            if (extension_loaded('sodium')) {
                $result = sodium_crypto_sign_verify_detached($signatureBytes, $messageBytes, $publicKeyBytes);
                
                Log::info('Signature validation result', [
                    'wallet_address' => $walletAddress,
                    'valid' => $result,
                    'using' => 'sodium_native'
                ]);
                
                return $result;
            }
            
            // Fallback to sodium_compat if native extension not available
            if (class_exists('ParagonIE_Sodium_Compat')) {
                $result = ParagonIE_Sodium_Compat::crypto_sign_verify_detached($signatureBytes, $messageBytes, $publicKeyBytes);
                
                Log::info('Signature validation result', [
                    'wallet_address' => $walletAddress,
                    'valid' => $result,
                    'using' => 'sodium_compat'
                ]);
                
                return $result;
            }

            Log::error('No ed25519 verification library available', [
                'wallet_address' => $walletAddress
            ]);
            
            return false;
            
        } catch (\Exception $e) {
            Log::error('Signature validation error', [
                'wallet_address' => $walletAddress,
                'error_message' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if wallet has DAO NFT (shortcut method)
     *
     * @param string $walletAddress
     * @return array|null
     */
    public function checkNftOwnership(string $walletAddress): ?array
    {
        if (empty($this->daoNftCollection)) {
            Log::error('DAO NFT collection address not configured', [
                'wallet_address' => $walletAddress
            ]);
            return null;
        }

        return $this->solanaRpc->verifyNftCollection($walletAddress, $this->daoNftCollection);
    }

    /**
     * Quick verification check without creating/updating records
     *
     * @param string $walletAddress
     * @param string $signature
     * @param string $message
     * @return array
     */
    public function quickVerify(string $walletAddress, string $signature, string $message = ''): array
    {
        $correlationId = uuid();

        Log::info('Starting quick verification', [
            'wallet_address' => $walletAddress,
            'correlation_id' => $correlationId
        ]);

        if (!$this->validateSignature($walletAddress, $signature, $message)) {
            return [
                'success' => false,
                'error' => 'Invalid signature',
                'error_code' => 'INVALID_SIGNATURE'
            ];
        }

        $nftData = $this->checkNftOwnership($walletAddress);

        if (!$nftData) {
            return [
                'success' => false,
                'error' => 'No DAO NFT found',
                'error_code' => 'NFT_NOT_FOUND'
            ];
        }

        return [
            'success' => true,
            'wallet_address' => $walletAddress,
            'nft_mint' => $nftData['mint'],
            'correlation_id' => $correlationId
        ];
    }

    /**
     * Decode base58 encoded string (Solana address format)
     *
     * @param string $base58
     * @return string
     */
    protected function decodeBase58(string $base58): string
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $base = strlen($alphabet);
        $decoded = 0;
        $multiplier = 1;

        for ($i = strlen($base58) - 1; $i >= 0; $i--) {
            $index = strpos($alphabet, $base58[$i]);
            if ($index === false) {
                throw new \InvalidArgumentException('Invalid base58 character');
            }
            $decoded += $index * $multiplier;
            $multiplier *= $base;
        }

        $result = '';
        while ($decoded > 0) {
            $result = chr($decoded % 256) . $result;
            $decoded = intdiv($decoded, 256);
        }

        for ($i = 0; $i < strlen($base58) && $base58[$i] === '1'; $i++) {
            $result = "\x00" . $result;
        }

        return $result;
    }

    /**
     * Get verification statistics
     *
     * @return array
     */
    public function getVerificationStats(): array
    {
        return [
            'total_members' => MemberIdentity::count(),
            'verified_members' => MemberIdentity::where('membership_status', MemberIdentity::STATUS_VERIFIED)->count(),
            'pending_members' => MemberIdentity::where('membership_status', MemberIdentity::STATUS_PENDING)->count(),
            'suspended_members' => MemberIdentity::where('membership_status', MemberIdentity::STATUS_SUSPENDED)->count(),
            'revoked_members' => MemberIdentity::where('membership_status', MemberIdentity::STATUS_REVOKED)->count(),
            'dao_collection' => $this->daoNftCollection
        ];
    }
}
