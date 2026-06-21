<?php

$HmvcConfig = $HmvcConfig ?? [];

$HmvcConfig['eth'] = [
    'networks' => [
        'ethereum_mainnet' => [
            'rpc_url' => 'https://mainnet.infura.io/v3/YOUR_PROJECT_ID',
            'chain_id' => 1,
            'currency' => 'ETH',
            'min_confirmations' => 12
        ],
        'ethereum_goerli' => [
            'rpc_url' => 'https://goerli.infura.io/v3/YOUR_PROJECT_ID',
            'chain_id' => 5,
            'currency' => 'ETH',
            'min_confirmations' => 1
        ],
        'polygon' => [
            'rpc_url' => 'https://polygon-mainnet.infura.io/v3/YOUR_PROJECT_ID',
            'chain_id' => 137,
            'currency' => 'MATIC',
            'min_confirmations' => 10
        ],
        'bsc' => [
            'rpc_url' => 'https://bsc-dataseed.binance.org/',
            'chain_id' => 56,
            'currency' => 'BNB',
            'min_confirmations' => 15
        ]
    ],
    
    'tokens' => [
        'usdt_ethereum' => [
            'contract_address' => '0xdAC17F958D2ee523a22062C9bFcda47062d86dF3',
            'decimals' => 6,
            'symbol' => 'USDT'
        ],
        'usdc_ethereum' => [
            'contract_address' => '0xA0b86a33E6441E6A3d8d4dF1d7dE8b7D0a8F0C6',
            'decimals' => 6,
            'symbol' => 'USDC'
        ]
    ],
    
    'hot_wallet_address' => '0x...',
    'hot_wallet_private_key' => '...'
];

return $HmvcConfig;