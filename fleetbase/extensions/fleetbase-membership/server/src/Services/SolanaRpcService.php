<?php

namespace Fleetbase\Membership\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SolanaRpcService
{
    protected string $rpcUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->rpcUrl = config('membership.solana_rpc.url', 'https://api.mainnet-beta.solana.com');
        $this->timeout = config('membership.solana_rpc.timeout', 30);
    }

    /**
     * Get all token accounts owned by wallet
     *
     * @param string $walletAddress
     * @return array
     */
    public function getTokenAccountsByOwner(string $walletAddress): array
    {
        $correlationId = uuid();

        try {
            $response = Http::timeout($this->timeout)
                ->post($this->rpcUrl, [
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'method' => 'getTokenAccountsByOwner',
                    'params' => [
                        $walletAddress,
                        [
                            'programId' => 'TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA'
                        ],
                        [
                            'encoding' => 'jsonParsed'
                        ]
                    ]
                ]);

            if ($response->failed()) {
                Log::error('Solana RPC getTokenAccountsByOwner failed', [
                    'wallet_address' => $walletAddress,
                    'status' => $response->status(),
                    'correlation_id' => $correlationId,
                    'rpc_endpoint' => $this->rpcUrl
                ]);
                return [];
            }

            $data = $response->json();

            if (isset($data['error'])) {
                Log::error('Solana RPC returned error', [
                    'wallet_address' => $walletAddress,
                    'error' => $data['error'],
                    'correlation_id' => $correlationId,
                    'rpc_endpoint' => $this->rpcUrl
                ]);
                return [];
            }

            return $data['result']['value'] ?? [];
        } catch (\Exception $e) {
            Log::error('Solana RPC request exception', [
                'wallet_address' => $walletAddress,
                'error_message' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'rpc_endpoint' => $this->rpcUrl
            ]);
            return [];
        }
    }

    /**
     * Get NFT metadata account info
     *
     * @param string $metadataAccount
     * @return array|null
     */
    public function getNftMetadata(string $metadataAccount): ?array
    {
        $correlationId = uuid();

        try {
            $response = Http::timeout($this->timeout)
                ->post($this->rpcUrl, [
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'method' => 'getAccountInfo',
                    'params' => [
                        $metadataAccount,
                        ['encoding' => 'base64']
                    ]
                ]);

            if ($response->failed()) {
                Log::error('Solana RPC getAccountInfo failed', [
                    'metadata_account' => $metadataAccount,
                    'status' => $response->status(),
                    'correlation_id' => $correlationId,
                    'rpc_endpoint' => $this->rpcUrl
                ]);
                return null;
            }

            $data = $response->json();

            if (isset($data['error'])) {
                Log::error('Solana RPC returned error', [
                    'metadata_account' => $metadataAccount,
                    'error' => $data['error'],
                    'correlation_id' => $correlationId,
                    'rpc_endpoint' => $this->rpcUrl
                ]);
                return null;
            }

            $accountInfo = $data['result']['value'] ?? null;

            if (!$accountInfo || !isset($accountInfo['data'][0])) {
                return null;
            }

            return $this->parseMetaplexMetadata($accountInfo['data'][0]);
        } catch (\Exception $e) {
            Log::error('Solana RPC getAccountInfo exception', [
                'metadata_account' => $metadataAccount,
                'error_message' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'rpc_endpoint' => $this->rpcUrl
            ]);
            return null;
        }
    }

    /**
     * Verify NFT belongs to specific collection by checking mint accounts
     *
     * @param string $walletAddress
     * @param string $collectionAddress
     * @return array|null Returns NFT data if found in collection, null otherwise
     */
    public function verifyNftCollection(string $walletAddress, string $collectionAddress): ?array
    {
        $correlationId = uuid();

        Log::info('Starting NFT collection verification', [
            'wallet_address' => $walletAddress,
            'collection_address' => $collectionAddress,
            'correlation_id' => $correlationId
        ]);

        $tokenAccounts = $this->getTokenAccountsByOwner($walletAddress);

        if (empty($tokenAccounts)) {
            Log::info('No token accounts found for wallet', [
                'wallet_address' => $walletAddress,
                'correlation_id' => $correlationId
            ]);
            return null;
        }

        foreach ($tokenAccounts as $account) {
            $parsedData = $account['account']['data']['parsed'] ?? null;

            if (!$parsedData) {
                continue;
            }

            $info = $parsedData['info'] ?? null;

            if (!$info) {
                continue;
            }

            $mint = $info['mint'] ?? null;
            $tokenAmount = $info['tokenAmount'] ?? null;

            if (!$mint || !$tokenAmount) {
                continue;
            }

            $amount = $tokenAmount['amount'] ?? '0';
            $decimals = $tokenAmount['decimals'] ?? 0;

            if ($decimals !== 0 || $amount !== '1') {
                continue;
            }

            $metadata = $this->getNftMetadata($mint);

            if (!$metadata) {
                continue;
            }

            $nftCollection = $metadata['collection']['key'] ?? null;
            $collectionVerified = $metadata['collection']['verified'] ?? false;

            if ($nftCollection === $collectionAddress && $collectionVerified) {
                Log::info('DAO NFT found for wallet', [
                    'wallet_address' => $walletAddress,
                    'nft_mint' => $mint,
                    'collection_address' => $collectionAddress,
                    'correlation_id' => $correlationId
                ]);

                return [
                    'mint' => $mint,
                    'metadata' => $metadata,
                    'token_account' => $account['pubkey'] ?? null
                ];
            }
        }

        Log::info('No matching DAO NFT found for wallet', [
            'wallet_address' => $walletAddress,
            'collection_address' => $collectionAddress,
            'token_accounts_checked' => count($tokenAccounts),
            'correlation_id' => $correlationId
        ]);

        return null;
    }

    /**
     * Parse Metaplex metadata from base64 encoded data
     *
     * @param string $base64Data
     * @return array|null
     */
    protected function parseMetaplexMetadata(string $base64Data): ?array
    {
        try {
            $decoded = base64_decode($base64Data);

            if (!$decoded) {
                return null;
            }

            $metadata = unpack('C4discard/a32name/a10symbol/a200uri/C2seller_fee/C1has_creators/C1creator_count', $decoded);

            if (!$metadata) {
                return null;
            }

            $name = rtrim($metadata['name'], "\x00");
            $symbol = rtrim($metadata['symbol'], "\x00");
            $uri = rtrim($metadata['uri'], "\x00");

            $result = [
                'name' => $name,
                'symbol' => $symbol,
                'uri' => $uri,
                'sellerFeeBasisPoints' => ($metadata['seller_fee1'] << 8) | $metadata['seller_fee2'],
                'collection' => null
            ];

            $offset = 4 + 32 + 10 + 200 + 2 + 1;

            if ($metadata['has_creators'] && $metadata['creator_count'] > 0) {
                $offset += 1 + ($metadata['creator_count'] * 34);
            }

            if (strlen($decoded) > $offset + 2) {
                $hasCollection = unpack('C', substr($decoded, $offset, 1))[1];
                if ($hasCollection) {
                    $collectionData = unpack('a32key/Cverified', substr($decoded, $offset + 1, 33));
                    $result['collection'] = [
                        'key' => rtrim($collectionData['key'], "\x00"),
                        'verified' => (bool) $collectionData['verified']
                    ];
                }
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to parse Metaplex metadata', [
                'error_message' => $e->getMessage()
            ]);
            return null;
        }
    }
}
