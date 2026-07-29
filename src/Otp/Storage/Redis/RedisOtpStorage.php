<?php

declare(strict_types=1);

namespace Toolkit\Otp\Storage\Redis;

use Toolkit\Otp\Config\OtpConfig;
use Toolkit\Otp\Storage\RedisStorageInterface;

/**
 * Redis-based OTP storage implementation.
 * 
 * Stores OTP data in Redis with automatic expiration using TTL.
 * Uses Redis hashes to store code, expiry, and attempts.
 * Requires the php-redis extension.
 * 
 * @package Toolkit\Otp\Storage\Redis
 * @author Toolkit Team
 * @since 2.0.0
 */
class RedisOtpStorage implements RedisStorageInterface
{
    /**
     * @var \Redis|null The Redis connection instance.
     */
    private ?\Redis $connection = null;

    /**
     * @var string Key prefix for OTP entries.
     */
    private string $keyPrefix = 'otp:';

    /**
     * @var array Connection configuration.
     */
    private array $config = [];

    /**
     * Constructor.
     *
     * @param \Redis|null $redis Optional existing Redis instance.
     */
    public function __construct(?\Redis $redis = null)
    {
        if ($redis !== null) {
            $this->connection = $redis;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(
        string $host = '127.0.0.1',
        int $port = 6379,
        ?string $password = null,
        int $database = 0
    ): void {
        $this->config = [
            'host' => $host,
            'port' => $port,
            'password' => $password,
            'database' => $database,
        ];

        if (!extension_loaded('redis')) {
            throw new \RuntimeException('Redis extension is not installed.');
        }

        $this->connection = new \Redis();
        
        try {
            $connected = $this->connection->connect($host, $port, 2.5);
            
            if (!$connected) {
                throw new \RuntimeException('Failed to connect to Redis server.');
            }

            if ($password !== null) {
                $this->connection->auth($password);
            }

            if ($database > 0) {
                $this->connection->select($database);
            }
        } catch (\RedisException $e) {
            throw new \RuntimeException("Redis connection failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection(): ?\Redis
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function setKeyPrefix(string $prefix): void
    {
        $this->keyPrefix = $prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function getKeyPrefix(): string
    {
        return $this->keyPrefix;
    }

    /**
     * {@inheritdoc}
     */
    public function getTtl(string $identifier): int
    {
        $this->ensureConnection();
        
        $key = $this->buildKey($identifier);
        return $this->connection->ttl($key);
    }

    /**
     * Build the Redis key for an identifier.
     *
     * @param string $identifier
     * @return string
     */
    private function buildKey(string $identifier): string
    {
        // Sanitize identifier for use as Redis key
        $sanitized = preg_replace('/[^a-zA-Z0-9._-]/', '_', $identifier);
        return $this->keyPrefix . $sanitized;
    }

    /**
     * {@inheritdoc}
     */
    public function save(string $identifier, string $code, int $ttl): void
    {
        $this->ensureConnection();

        $key = $this->buildKey($identifier);
        $expiry = time() + $ttl;

        $data = [
            'code' => $code,
            'expiry' => (string)$expiry,
            'attempts' => '0',
        ];

        $this->connection->hMSet($key, $data);
        $this->connection->expire($key, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $identifier): ?array
    {
        $this->ensureConnection();

        $key = $this->buildKey($identifier);
        $data = $this->connection->hGetAll($key);

        if ($data === false || empty($data)) {
            return null;
        }

        // Check if expired (Redis should handle this, but double-check)
        $expiry = (int)($data['expiry'] ?? 0);
        if ($expiry < time()) {
            $this->delete($identifier);
            return null;
        }

        return [
            'code' => $data['code'] ?? '',
            'expiry' => $expiry,
            'attempts' => (int)($data['attempts'] ?? 0),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $identifier): void
    {
        $this->ensureConnection();

        $key = $this->buildKey($identifier);
        $this->connection->del($key);
    }

    /**
     * {@inheritdoc}
     */
    public function incrementAttempts(string $identifier): int
    {
        $this->ensureConnection();

        $key = $this->buildKey($identifier);
        $newAttempts = $this->connection->hIncrBy($key, 'attempts', 1);
        
        return (int)$newAttempts;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $identifier): bool
    {
        $this->ensureConnection();

        $key = $this->buildKey($identifier);
        return $this->connection->exists($key) === 1;
    }

    /**
     * Ensure Redis connection is established.
     *
     * @return void
     */
    private function ensureConnection(): void
    {
        if ($this->connection === null) {
            if (!empty($this->config)) {
                $this->setConnection(
                    $this->config['host'],
                    $this->config['port'],
                    $this->config['password'],
                    $this->config['database']
                );
            } else {
                throw new \RuntimeException('Redis connection not established. Call setConnection() or provide a Redis instance.');
            }
        }
    }

    /**
     * Ping the Redis server to check connection.
     *
     * @return bool True if connection is alive.
     */
    public function ping(): bool
    {
        $this->ensureConnection();
        
        try {
            $response = $this->connection->ping();
            return $response === true || $response === '+PONG';
        } catch (\RedisException $e) {
            return false;
        }
    }

    /**
     * Get statistics about stored OTPs.
     *
     * @return array Statistics including total keys and memory usage.
     */
    public function getStats(): array
    {
        $this->ensureConnection();

        $pattern = $this->keyPrefix . '*';
        $keys = $this->connection->keys($pattern);
        $totalKeys = count($keys);

        $info = $this->connection->info();
        
        return [
            'total_otp_keys' => $totalKeys,
            'memory_used' => $info['used_memory_human'] ?? 'unknown',
            'connected_clients' => $info['connected_clients'] ?? 0,
        ];
    }

    /**
     * Clean up all OTP entries (useful for testing).
     *
     * @return int Number of deleted keys.
     */
    public function cleanupAll(): int
    {
        $this->ensureConnection();

        $pattern = $this->keyPrefix . '*';
        $keys = $this->connection->keys($pattern);
        
        if (empty($keys)) {
            return 0;
        }

        return $this->connection->del(...$keys);
    }
}
