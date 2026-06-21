-- Ethereum Plugin Database Schema

CREATE TABLE IF NOT EXISTS wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    address VARCHAR(42) NOT NULL UNIQUE,
    private_key TEXT,
    label VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tx_hash VARCHAR(66) NOT NULL UNIQUE,
    block_number BIGINT NULL,
    block_hash VARCHAR(66) NULL,
    from_address VARCHAR(42) NOT NULL,
    to_address VARCHAR(42) NULL,
    value_wei DECIMAL(78, 0) DEFAULT 0,
    value_eth VARCHAR(64) DEFAULT '0',
    gas BIGINT DEFAULT 0,
    gas_price_gwei VARCHAR(64) NULL,
    max_fee_per_gas_gwei VARCHAR(64) NULL,
    max_priority_fee_per_gas_gwei VARCHAR(64) NULL,
    input TEXT NULL,
    tx_type INT DEFAULT 0,
    status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
    network VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tx_hash (tx_hash),
    INDEX idx_from_address (from_address),
    INDEX idx_to_address (to_address),
    INDEX idx_block_number (block_number),
    INDEX idx_network (network)
);

CREATE TABLE IF NOT EXISTS blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    number BIGINT NOT NULL UNIQUE,
    hash VARCHAR(66) NOT NULL,
    parent_hash VARCHAR(66) NULL,
    author VARCHAR(42) NULL,
    miner VARCHAR(42) NULL,
    timestamp INT NOT NULL,
    difficulty BIGINT DEFAULT 0,
    total_difficulty BIGINT DEFAULT 0,
    gas_limit BIGINT DEFAULT 0,
    gas_used BIGINT DEFAULT 0,
    size BIGINT DEFAULT 0,
    extra_data TEXT NULL,
    network VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_number (number),
    INDEX idx_hash (hash),
    INDEX idx_network (network)
);

CREATE TABLE IF NOT EXISTS contract_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_index INT NOT NULL,
    transaction_hash VARCHAR(66) NOT NULL,
    transaction_index INT NULL,
    block_number BIGINT NOT NULL,
    block_hash VARCHAR(66) NULL,
    address VARCHAR(42) NOT NULL,
    data TEXT NULL,
    topics JSON NULL,
    removed BOOLEAN DEFAULT FALSE,
    network VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_transaction_hash (transaction_hash),
    INDEX idx_address (address),
    INDEX idx_block_number (block_number),
    INDEX idx_network (network)
);

CREATE TABLE IF NOT EXISTS token_balances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wallet_address VARCHAR(42) NOT NULL,
    token_address VARCHAR(42) NOT NULL,
    balance DECIMAL(78, 18) DEFAULT 0,
    decimals INT DEFAULT 18,
    symbol VARCHAR(20) NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wallet_token (wallet_address, token_address),
    INDEX idx_wallet (wallet_address),
    INDEX idx_token (token_address)
);

CREATE TABLE IF NOT EXISTS network_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    network_key VARCHAR(50) NOT NULL UNIQUE,
    rpc_url TEXT NOT NULL,
    chain_id INT NOT NULL,
    currency VARCHAR(20) NOT NULL,
    min_confirmations INT DEFAULT 12,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);