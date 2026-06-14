<?php

require_once __DIR__ . '/EthereumPlugin.php';

function monitorAddress(EthereumPlugin $eth, string $address): array
{
    return [
        'address' => $address,
        'balance_eth' => $eth->getBalance($address),
        'nonce' => $eth->getTransactionCount($address)
    ];
}

function getDepositInfo(EthereumPlugin $eth, string $txHash): array
{
    $tx = $eth->getTransaction($txHash);
    $receipt = $eth->getTransactionReceipt($txHash);
    
    return [
        'transaction' => $tx,
        'receipt' => $receipt,
        'confirmations' => $receipt ? ($eth->getBlockNumber() - $receipt['block_number']) : null
    ];
}

function trackContractEvents(EthereumPlugin $eth, string $contractAddress, string $fromBlock = 'latest'): array
{
    return $eth->getLogs([
        'fromBlock' => $fromBlock,
        'toBlock' => 'latest',
        'address' => $contractAddress
    ]);
}

function isTransactionSuccessful(array $receipt): bool
{
    return isset($receipt['status']) && $receipt['status'] === 'success';
}

function getTransactionFee(array $receipt, string $gasPriceGwei): float
{
    $feeWei = $receipt['gas_used'] * $gasPriceGwei * 1000000000;
    return $feeWei / 1000000000000000000;
}