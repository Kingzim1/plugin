<?php

class EthereumPlugin
{
    private string $rpcUrl;
    private ?string $apiKey;
    private array $headers;
    private int $requestId = 0;

    public function __construct(string $rpcUrl, ?string $apiKey = null)
    {
        if (!filter_var($rpcUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Invalid RPC URL provided');
        }
        $this->rpcUrl = $rpcUrl;
        $this->apiKey = $apiKey;
        $this->headers = $apiKey ? ['Authorization: Bearer ' . $apiKey] : [];
    }

    private function request(string $method, array $params = []): mixed
    {
        $payload = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => $this->requestId++
        ];

        $ch = curl_init($this->rpcUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json'], $this->headers),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('RPC connection failed: ' . $error);
        }
        
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new RuntimeException('RPC request failed with HTTP code: ' . $httpCode);
        }

        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Invalid JSON response: ' . json_last_error_msg());
        }

        if (isset($data['error'])) {
            throw new RuntimeException('RPC error: ' . ($data['error']['message'] ?? 'Unknown error'));
        }

        return $data['result'] ?? null;
    }

    public function getBlockNumber(): int
    {
        $result = $this->request('eth_blockNumber');
        return $result ? $this->hexToInt($result) : 0;
    }

    public function getBlockByNumber(int|string $blockNumber = 'latest', bool $fullTransactions = false): array
    {
        $block = $this->request('eth_getBlockByNumber', [
            $this->formatBlockNumber($blockNumber),
            $fullTransactions
        ]);
        return $block ? $this->formatBlock($block) : [];
    }

    public function getBlockByHash(string $blockHash, bool $fullTransactions = false): array
    {
        $block = $this->request('eth_getBlockByHash', [$blockHash, $fullTransactions]);
        return $block ? $this->formatBlock($block) : [];
    }

    public function getTransaction(string $txHash): array
    {
        $tx = $this->request('eth_getTransactionByHash', [$txHash]);
        return $tx ? $this->formatTransaction($tx) : [];
    }

    public function getTransactionReceipt(string $txHash): array
    {
        $receipt = $this->request('eth_getTransactionReceipt', [$txHash]);
        return $receipt ? $this->formatReceipt($receipt) : [];
    }

    public function getBalance(string $address): string
    {
        $balance = $this->request('eth_getBalance', [$this->validateAddress($address), 'latest']);
        return $balance ? $this->weiToEther($balance) : '0';
    }

    public function getCode(string $address): string
    {
        return $this->request('eth_getCode', [$this->validateAddress($address), 'latest']);
    }

    public function getStorageAt(string $address, string $position, string $block = 'latest'): string
    {
        return $this->request('eth_getStorageAt', [$this->validateAddress($address), $position, $block]);
    }

    public function getGasPrice(): string
    {
        $gasPrice = $this->request('eth_gasPrice');
        return $gasPrice ? $this->weiToGwei($gasPrice) : '0';
    }

    public function estimateGas(array $transaction): int
    {
        $result = $this->request('eth_estimateGas', $transaction);
        return $result ? $this->hexToInt($result) : 0;
    }

    public function getTransactionCount(string $address, string $block = 'latest'): int
    {
        $count = $this->request('eth_getTransactionCount', [$this->validateAddress($address), $block]);
        return $count ? $this->hexToInt($count) : 0;
    }

    public function call(array $transaction, string $block = 'latest'): string
    {
        return $this->request('eth_call', [$transaction, $block]);
    }

    public function chainId(): int
    {
        $id = $this->request('eth_chainId');
        return $id ? $this->hexToInt($id) : 0;
    }

    public function getAccounts(): array
    {
        return $this->request('eth_accounts') ?: [];
    }

    public function submitTransaction(string $signedTx): string
    {
        return $this->request('eth_sendRawTransaction', [$signedTx]);
    }

    public function getFeeHistory(int $blockCount, string $block = 'latest'): array
    {
        return $this->request('eth_feeHistory', [
            '0x' . dechex($blockCount),
            $block,
            []
        ]);
    }

    public function getMaxPriorityFeePerGas(): string
    {
        $feeHistory = $this->getFeeHistory(1, 'latest');
        $baseFee = $feeHistory['baseFeePerGas'][0] ?? '0x0';
        $nextBaseFee = $feeHistory['baseFeePerGas'][1] ?? '0x0';
        $priorityFee = bcdiv($this->hexToInt($nextBaseFee) - $this->hexToInt($baseFee), '2', 0);
        return (string)$priorityFee;
    }

    public function getBlockReceipts(string $blockNumber): array
    {
        return $this->request('eth_getBlockReceipts', [$this->formatBlockNumber($blockNumber)]);
    }

    public function getLogs(array $filter): array
    {
        return $this->request('eth_getLogs', [$filter]) ?: [];
    }

    public function newFilter(array $filter): string
    {
        return $this->request('eth_newFilter', [$filter]);
    }

    public function getFilterChanges(string $filterId): array
    {
        return $this->request('eth_getFilterChanges', [$filterId]) ?: [];
    }

    public function uninstallFilter(string $filterId): bool
    {
        return $this->request('eth_uninstallFilter', [$filterId]) === true;
    }

    private function validateAddress(string $address): string
    {
        if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $address)) {
            throw new InvalidArgumentException('Invalid Ethereum address format: must be 0x followed by 40 hex characters');
        }
        return strtolower($address);
    }

    private function hexToInt(string $hex): int
    {
        return hexdec(strtolower($hex));
    }

    private function formatBlockNumber(int|string $blockNumber): string
    {
        return is_int($blockNumber) ? '0x' . dechex($blockNumber) : $blockNumber;
    }

    private function weiToEther(string $wei): string
    {
        $wei = $this->hexToInt($wei);
        return bcdiv((string)$wei, '1000000000000000000', 18);
    }

    private function weiToGwei(string $wei): string
    {
        $wei = $this->hexToInt($wei);
        return bcdiv((string)$wei, '1000000000', 9);
    }

    private function weiToEtherRaw(string $wei): string
    {
        $wei = $this->hexToInt($wei);
        return bcdiv((string)$wei, '1000000000000000000', 18);
    }

    private function formatBlock(array $block): array
    {
        return [
            'number' => $this->hexToInt($block['number'] ?? '0x0'),
            'hash' => $block['hash'] ?? '',
            'parent_hash' => $block['parentHash'] ?? '',
            'nonce' => $block['nonce'] ?? '',
            'sha3_uncles' => $block['sha3Uncles'] ?? '',
            'author' => $block['author'] ?? $block['miner'] ?? '',
            'miner' => $block['miner'] ?? $block['author'] ?? '',
            'timestamp' => $this->hexToInt($block['timestamp'] ?? '0x0'),
            'difficulty' => $this->hexToInt($block['difficulty'] ?? '0x0'),
            'total_difficulty' => $this->hexToInt($block['totalDifficulty'] ?? '0x0'),
            'extra_data' => $block['extraData'] ?? '',
            'size' => $this->hexToInt($block['size'] ?? '0x0'),
            'gas_limit' => $this->hexToInt($block['gasLimit'] ?? '0x0'),
            'gas_used' => $this->hexToInt($block['gasUsed'] ?? '0x0'),
            'base_fee_per_gas' => isset($block['baseFeePerGas']) ? $this->weiToGwei($block['baseFeePerGas']) : null,
            'transactions_root' => $block['transactionsRoot'] ?? '',
            'state_root' => $block['stateRoot'] ?? '',
            'receipts_root' => $block['receiptsRoot'] ?? '',
            'transactions' => $block['transactions'] ?? []
        ];
    }

    private function formatTransaction(array $tx): array
    {
        return [
            'hash' => $tx['hash'] ?? '',
            'nonce' => $this->hexToInt($tx['nonce'] ?? '0x0'),
            'block_hash' => $tx['blockHash'] ?? '',
            'block_number' => isset($tx['blockNumber']) ? $this->hexToInt($tx['blockNumber']) : null,
            'transaction_index' => $this->hexToInt($tx['transactionIndex'] ?? '0x0'),
            'from' => $tx['from'] ?? '',
            'to' => $tx['to'] ?? '',
            'value_eth' => $this->weiToEtherRaw($tx['value'] ?? '0x0'),
            'value_wei' => $tx['value'] ?? '0x0',
            'gas' => $this->hexToInt($tx['gas'] ?? '0x0'),
            'gas_price_gwei' => isset($tx['gasPrice']) ? $this->weiToGwei($tx['gasPrice']) : null,
            'max_fee_per_gas_gwei' => isset($tx['maxFeePerGas']) ? $this->weiToGwei($tx['maxFeePerGas']) : null,
            'max_priority_fee_per_gas_gwei' => isset($tx['maxPriorityFeePerGas']) ? $this->weiToGwei($tx['maxPriorityFeePerGas']) : null,
            'input' => $tx['input'] ?? '',
            'type' => $this->hexToInt($tx['type'] ?? '0x0')
        ];
    }

    private function formatReceipt(array $receipt): array
    {
        return [
            'transaction_hash' => $receipt['transactionHash'] ?? '',
            'transaction_index' => $this->hexToInt($receipt['transactionIndex'] ?? '0x0'),
            'block_number' => isset($receipt['blockNumber']) ? $this->hexToInt($receipt['blockNumber']) : null,
            'block_hash' => $receipt['blockHash'] ?? '',
            'from' => $receipt['from'] ?? '',
            'to' => $receipt['to'] ?? null,
            'status' => $this->getTxStatus($receipt['status'] ?? null),
            'cumulative_gas_used' => $this->hexToInt($receipt['cumulativeGasUsed'] ?? '0x0'),
            'gas_used' => $this->hexToInt($receipt['gasUsed'] ?? '0x0'),
            'contract_address' => $receipt['contractAddress'] ?? null,
            'logs' => $this->formatLogs($receipt['logs'] ?? []),
            'logs_bloom' => $receipt['logsBloom'] ?? ''
        ];
    }

    private function getTxStatus(string $status): string
    {
        if ($status === null) return 'pending';
        return $this->hexToInt($status) === 1 ? 'success' : 'failed';
    }

    private function formatLogs(array $logs): array
    {
        return array_map(function($log) {
            return [
                'removed' => $log['removed'] ?? false,
                'log_index' => $this->hexToInt($log['logIndex'] ?? '0x0'),
                'transaction_hash' => $log['transactionHash'] ?? '',
                'transaction_index' => $this->hexToInt($log['transactionIndex'] ?? '0x0'),
                'block_number' => isset($log['blockNumber']) ? $this->hexToInt($log['blockNumber']) : null,
                'block_hash' => $log['blockHash'] ?? '',
                'address' => $log['address'] ?? '',
                'data' => $log['data'] ?? '',
                'topics' => $log['topics'] ?? []
            ];
        }, $logs);
    }
}