(function() {
    'use strict';

    window.EthereumPlugin = {
        formatAddress: function(address) {
            if (!address || address.length < 10) return address;
            return address.substring(0, 6) + '...' + address.substring(address.length - 4);
        },

        formatBalance: function(balance, decimals = 18) {
            if (!balance) return '0.000000';
            const divisor = Math.pow(10, decimals);
            const formatted = parseFloat(balance) / divisor;
            return formatted.toFixed(6);
        },

        formatGwei: function(wei) {
            if (!wei) return '0';
            const gwei = parseInt(wei) / 1000000000;
            return gwei.toFixed(2);
        },

        formatHash: function(hash) {
            if (!hash || hash.length < 12) return hash;
            return hash.substring(0, 10) + '...' + hash.substring(hash.length - 8);
        },

        weiToEth: function(wei) {
            if (!wei) return '0';
            const eth = parseFloat(wei) / 1000000000000000000;
            return eth.toString();
        },

        hexToInt: function(hex) {
            if (!hex) return 0;
            return parseInt(hex.startsWith('0x') ? hex : '0x' + hex, 16);
        },

        intToHex: function(num) {
            if (!num) return '0x0';
            return '0x' + parseInt(num).toString(16);
        },

        async fetchRPC(rpcUrl, method, params = [], apiKey = null) {
            const headers = {
                'Content-Type': 'application/json'
            };
            if (apiKey) {
                headers['Authorization'] = 'Bearer ' + apiKey;
            }

            const response = await fetch(rpcUrl, {
                method: 'POST',
                headers: headers,
                body: JSON.stringify({
                    jsonrpc: '2.0',
                    method: method,
                    params: params,
                    id: Date.now()
                })
            });

            if (!response.ok) {
                throw new Error('RPC request failed: ' + response.status);
            }

            const data = await response.json();
            if (data.error) {
                throw new Error(data.error.message || 'Unknown RPC error');
            }

            return data.result;
        },

        copyToClipboard: function(text) {
            return navigator.clipboard.writeText(text).then(() => {
                return true;
            }).catch(() => {
                return false;
            });
        },

        async copyAddress(element) {
            const address = element.dataset.address;
            const success = await this.copyToClipboard(address);
            if (success) {
                element.classList.add('copied');
                setTimeout(() => element.classList.remove('copied'), 2000);
            }
        },

        async getBalance(provider, address) {
            try {
                const balance = await this.fetchRPC(provider.rpcUrl, 'eth_getBalance', [address, 'latest']);
                return this.weiToEth(balance);
            } catch (error) {
                console.error('Failed to fetch balance:', error);
                return null;
            }
        },

        async getBlockNumber(provider) {
            try {
                const blockNumber = await this.fetchRPC(provider.rpcUrl, 'eth_blockNumber');
                return this.hexToInt(blockNumber);
            } catch (error) {
                console.error('Failed to fetch block number:', error);
                return null;
            }
        },

        async getTransactionReceipt(provider, txHash) {
            try {
                const receipt = await this.fetchRPC(provider.rpcUrl, 'eth_getTransactionReceipt', [txHash]);
                return receipt;
            } catch (error) {
                console.error('Failed to fetch transaction receipt:', error);
                return null;
            }
        },

        async getGasPrice(provider) {
            try {
                const gasPrice = await this.fetchRPC(provider.rpcUrl, 'eth_gasPrice');
                return this.formatGwei(gasPrice);
            } catch (error) {
                console.error('Failed to fetch gas price:', error);
                return null;
            }
        },

        updateTransactionStatus(txHash, status) {
            const elements = document.querySelectorAll('[data-tx-hash="' + txHash + '"]');
            elements.forEach(el => {
                el.classList.remove('pending', 'success', 'failed');
                el.classList.add(status);
            });
        },

        initAutoRefresh(selector, interval = 30000) {
            const elements = document.querySelectorAll(selector);
            if (!elements.length) return;

            elements.forEach(async (el) => {
                const address = el.dataset.address;
                if (address) {
                    const balance = await this.getBalance(window.ethereumProviders?.main || {}, address);
                    if (balance !== null) {
                        el.textContent = this.formatBalance(balance) + ' ETH';
                    }
                }
            });

            setInterval(() => {
                this.initAutoRefresh(selector, interval);
            }, interval);
        },

        init() {
            document.querySelectorAll('[data-copy-address]').forEach(btn => {
                btn.addEventListener('click', () => this.copyAddress(btn));
            });

            document.querySelectorAll('[data-auto-refresh]').forEach(el => {
                const interval = parseInt(el.dataset.autoRefresh) || 30000;
                this.initAutoRefresh('[data-balance]', interval);
            });
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => window.EthereumPlugin.init());
    } else {
        window.EthereumPlugin.init();
    }
})();