<?php

require_once __DIR__ . '/EthereumPlugin.php';

class EthereumWallet
{
    private string $privateKey;
    private int $chainId;
    private EthereumPlugin $plugin;

    public function __construct(string $privateKey, string $rpcUrl, int $chainId = 1)
    {
        if (!preg_match('/^[a-fA-F0-9]{64}$/', $privateKey)) {
            throw new InvalidArgumentException('Invalid private key: must be 64 hex characters');
        }
        
        $this->privateKey = $privateKey;
        $this->chainId = $chainId;
        $this->plugin = new EthereumPlugin($rpcUrl);
    }

    public function getAddress(): string
    {
        return $this->privateKeyToAddress($this->privateKey);
    }

    private function privateKeyToAddress(string $privateKey): string
    {
        $privBytes = hex2bin($privateKey);
        
        if (extension_loaded('sodium')) {
            $keypair = sodium_crypto_sign_seed_keypair($privBytes);
            $pubKey = sodium_crypto_sign_publickey($keypair);
        } else {
            $ctx = stream_context_create(['secp256k1' => []]);
            $pubKey = '';
        }
        
        $hash = Keccak256::hash(substr($pubKey, 1), true);
        return '0x' . strtolower(substr(bin2hex($hash), -40));
    }

    public function getBalance(): string
    {
        return $this->plugin->getBalance($this->getAddress());
    }

    public function getNonce(): int
    {
        return $this->plugin->getTransactionCount($this->getAddress());
    }

    public function sendTransaction(string $to, string $valueEth, ?string $data = null, int $gasLimit = 21000): string
    {
        $nonce = $this->getNonce();
        $gasPrice = $this->plugin->getGasPrice();
        
        $tx = [
            'nonce' => '0x' . dechex($nonce),
            'gasPrice' => $this->gweiToHex($gasPrice),
            'gasLimit' => '0x' . dechex($gasLimit),
            'to' => $this->validateAddress($to),
            'value' => $this->etherToWei($valueEth),
            'data' => $data ?? '0x'
        ];

        return $this->signAndSend($tx);
    }

    private function signAndSend(array $tx): string
    {
        $signedTx = $this->signTransaction($tx);
        return $this->plugin->submitTransaction($signedTx);
    }

    private function signTransaction(array $transaction): string
    {
        $rlpData = $this->encodeTransaction($transaction);
        $hash = Keccak256::hash($rlpData, true);
        
        if (extension_loaded('sodium')) {
            $keypair = sodium_crypto_sign_seed_keypair(hex2bin($this->privateKey));
            $signature = sodium_crypto_sign_detached($hash, sodium_crypto_sign_secretkey($keypair));
            
            $r = bin2hex(substr($signature, 0, 32));
            $s = bin2hex(substr($signature, 32, 32));
            $v = dechex($this->chainId * 2 + 35);
            
            return '0x' . $v . str_pad($r, 64, '0', STR_PAD_LEFT) . str_pad($s, 64, '0', STR_PAD_LEFT) . ltrim($rlpData, '0x');
        }
        
        return '';
    }

    private function encodeTransaction(array $tx): string
    {
        $items = [
            $tx['nonce'],
            $tx['gasPrice'],
            $tx['gasLimit'],
            $tx['to'],
            $tx['value'],
            $tx['data']
        ];
        return RLP::encode($items);
    }

    private function validateAddress(string $address): string
    {
        if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $address)) {
            throw new InvalidArgumentException('Invalid Ethereum address format');
        }
        return strtolower($address);
    }

    private function gweiToHex(string $gwei): string
    {
        $wei = bcmul($gwei, '1000000000', 0);
        return '0x' . dechex((int)$wei);
    }

    private function etherToWei(string $ether): string
    {
        $wei = bcmul($ether, '1000000000000000000', 0);
        return '0x' . dechex((int)$wei);
    }
}

class Keccak256
{
    public static function hash(string $data, bool $raw = false): string
    {
        if (extension_loaded('sodium')) {
            $hash = sodium_crypto_generichash($data, '', 32);
        } else {
            $hash = hash('sha3-256', $data, true);
        }
        return $raw ? $hash : '0x' . bin2hex($hash);
    }
}

class RLP
{
    public static function encode(array $items): string
    {
        $encoded = '';
        foreach ($items as $item) {
            $item = ltrim($item, '0x');
            $len = strlen($item);
            
            if ($len === 1 && hexdec($item) < 128) {
                $encoded .= $item;
            } elseif ($len <= 55) {
                $encoded .= chr(128 + $len) . $item;
            } else {
                $lenBytes = $len <= 255 ? chr($len) : chr(floor($len / 256)) . chr($len % 256);
                $encoded .= chr(183 + strlen($lenBytes)) . $lenBytes . $item;
            }
        }
        return '0x' . bin2hex($encoded);
    }
}

