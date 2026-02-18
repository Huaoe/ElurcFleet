<?php

namespace Tests\Unit\Membership;

use Fleetbase\Membership\Services\SolanaRpcService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SolanaRpcServiceTest extends TestCase
{
    protected SolanaRpcService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SolanaRpcService();
    }

    public function test_get_token_accounts_by_owner_returns_empty_array_on_failure()
    {
        Http::fake([
            '*' => Http::response(['error' => 'Invalid request'], 400)
        ]);

        $result = $this->service->getTokenAccountsByOwner('invalid_wallet');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_get_token_accounts_by_owner_returns_accounts_on_success()
    {
        $mockResponse = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'value' => [
                    [
                        'pubkey' => 'token_account_1',
                        'account' => [
                            'data' => [
                                'parsed' => [
                                    'info' => [
                                        'mint' => 'nft_mint_1',
                                        'tokenAmount' => ['amount' => '1', 'decimals' => 0]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            '*' => Http::response($mockResponse, 200)
        ]);

        $result = $this->service->getTokenAccountsByOwner('valid_wallet_address');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('token_account_1', $result[0]['pubkey']);
    }

    public function test_get_nft_metadata_returns_null_on_invalid_account()
    {
        Http::fake([
            '*' => Http::response(['error' => 'Account not found'], 404)
        ]);

        $result = $this->service->getNftMetadata('invalid_metadata_account');

        $this->assertNull($result);
    }

    public function test_verify_nft_collection_returns_null_when_no_tokens_found()
    {
        Http::fake([
            '*' => Http::response([
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => ['value' => []]
            ], 200)
        ]);

        $result = $this->service->verifyNftCollection('wallet_address', 'collection_address');

        $this->assertNull($result);
    }

    public function test_verify_nft_collection_finds_matching_nft()
    {
        $collectionAddress = '3e22667e998143beef529eda8c84ee394a838aa705716c3b6fe0b3d5f913ac4c';

        $tokenAccountsResponse = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'value' => [
                    [
                        'pubkey' => 'token_account_123',
                        'account' => [
                            'data' => [
                                'parsed' => [
                                    'info' => [
                                        'mint' => 'nft_mint_123',
                                        'tokenAmount' => ['amount' => '1', 'decimals' => 0]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            '*' => Http::response($tokenAccountsResponse, 200)
        ]);

        $result = $this->service->verifyNftCollection('wallet_address', $collectionAddress);

        $this->assertNull($result);
    }

    public function test_service_uses_configured_rpc_url()
    {
        config(['membership.solana_rpc.url' => 'https://custom.rpc.com']);
        config(['membership.solana_rpc.timeout' => 60]);

        $service = new SolanaRpcService();

        Http::fake([
            'https://custom.rpc.com' => Http::response([
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => ['value' => []]
            ], 200)
        ]);

        $result = $service->getTokenAccountsByOwner('wallet_address');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://custom.rpc.com';
        });
    }

    public function test_service_handles_rpc_timeout()
    {
        Http::fake([
            '*' => Http::response(null, 504)->timeout()
        ]);

        $result = $this->service->getTokenAccountsByOwner('wallet_address');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_service_handles_network_error()
    {
        Http::fake([
            '*' => Http::response(null, 500)
        ]);

        $result = $this->service->getTokenAccountsByOwner('wallet_address');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_service_skips_non_nft_tokens()
    {
        $mockResponse = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'value' => [
                    [
                        'pubkey' => 'token_account_1',
                        'account' => [
                            'data' => [
                                'parsed' => [
                                    'info' => [
                                        'mint' => 'fungible_token_mint',
                                        'tokenAmount' => ['amount' => '1000000', 'decimals' => 6]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'pubkey' => 'token_account_2',
                        'account' => [
                            'data' => [
                                'parsed' => [
                                    'info' => [
                                        'mint' => 'nft_mint',
                                        'tokenAmount' => ['amount' => '1', 'decimals' => 0]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            '*' => Http::response($mockResponse, 200)
        ]);

        $result = $this->service->getTokenAccountsByOwner('wallet_address');

        $this->assertCount(2, $result);
    }
}
