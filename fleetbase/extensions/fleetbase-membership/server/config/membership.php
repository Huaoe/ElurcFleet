<?php

return [
    'name' => 'Membership',
    'version' => '0.1.0',
    'description' => 'NFT-based membership verification and profile management',

    'dao_nft_collection' => env('DAO_NFT_COLLECTION'),

    'solana_rpc' => [
        'url' => env('SOLANA_RPC_URL', 'https://api.mainnet-beta.solana.com'),
        'timeout' => env('SOLANA_RPC_TIMEOUT', 30),
    ],

    'membership_statuses' => [
        'pending' => 'Pending Verification',
        'verified' => 'Verified Member',
        'suspended' => 'Suspended',
        'revoked' => 'Revoked'
    ],

    'validation' => [
        'display_name_max_length' => 50,
        'bio_max_length' => 500,
    ],

    'blockchain' => [
        'network' => env('SOLANA_NETWORK', 'mainnet-beta'),
        'rpc_endpoint' => env('SOLANA_RPC_ENDPOINT', 'https://api.mainnet-beta.solana.com'),
    ],
];
