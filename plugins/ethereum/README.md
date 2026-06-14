# Ethereum Plugin for PHP Crypto Exchange

A lightweight PHP plugin for interacting with the Ethereum blockchain via JSON-RPC. Supports multiple networks including Ethereum mainnet, Polygon, BSC, and testnets.

## Installation

```
plugins/ethereum/
├── EthereumPlugin.php      # Core RPC client
├── EthereumWallet.php      # Transaction signing
├── exchange_functions.php  # Helper functions
├── config.php             # Network and token configuration template
├── README.md
└── examples/
    └── exchange_integration.php
```

## Features

- Block and transaction queries
- Balance and nonce lookup
- Gas price estimation
- Event/log monitoring for deposits
- Transaction signing and submission
- Multi-network support (Ethereum, Polygon, BSC)
- EIP-1559 fee calculation

## Usage

```php
<?php
require_once 'plugins/ethereum/EthereumPlugin.php';
require_once 'plugins/ethereum/EthereumWallet.php';

// Initialize plugin with RPC URL (Infura, Alchemy, or own node)
$plugin = new EthereumPlugin('https://mainnet.infura.io/v3/YOUR_API_KEY', 'YOUR_API_KEY');

// Get latest block number
$blockNumber = $plugin->getBlockNumber();

// Get block details
$block = $plugin->getBlockByNumber('latest');

// Get transaction
$tx = $plugin->getTransaction('0x...');

// Get transaction receipt
$receipt = $plugin->getTransactionReceipt('0x...');

// Get ETH balance
$balance = $plugin->getBalance('0x...');

// Query events/logs
$logs = $plugin->getLogs([
    'fromBlock' => '0x1',
    'toBlock' => 'latest',
    'address' => '0x...',
    'topics' => ['0x...']
]);
```

## Wallet Usage

```php
<?php
// Create wallet with private key
$wallet = new EthereumWallet('YOUR_PRIVATE_KEY', 'https://mainnet.infura.io/v3/YOUR_API_KEY');

// Get address
$address = $wallet->getAddress();

// Get balance
$balance = $wallet->getBalance();

// Send transaction
$txHash = $wallet->sendTransaction(
    '0xRecipientAddress',
    '0.1', // ETH amount
    null,  // optional data
    21000 // gas limit
);
```

## Requirements

- PHP 8.0+
- ext-curl
- ext-bcmath (for arbitrary precision math)
- ext-sodium (recommended, or ext-openssl)
- GMP or BCMath extension

## Supported Networks

- Mainnet (chainId: 1)
- Goerli (chainId: 5)
- Sepolia (chainId: 11155111)
- Polygon (chainId: 137)
- BSC (chainId: 56)

## RPC Providers

- Infura: `https://mainnet.infura.io/v3/YOUR_PROJECT_ID`
- Alchemy: `https://eth-mainnet.alchemyapi.io/v2/YOUR_API_KEY`
- QuickNode: `https://YOUR_ENDPOINT.quiknode.pro/YOUR_API_KEY`
- Chainstack: `https://YOUR_ENDPOINT.rpc.chainstack.com/YOUR_API_KEY`
- Your own node: `http://localhost:8545`