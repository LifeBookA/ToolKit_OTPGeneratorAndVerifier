<?php

declare(strict_types=1);

namespace Toolkit\Otp\Storage;

/**
 * Interface for Redis-based OTP storage.
 * 
 * Provides methods for storing and retrieving OTP data in Redis.
 * This interface extends the base OtpStorageInterface with Redis-specific operations.
 * 
 * @package Toolkit\Otp\Storage
 * @author Toolkit Team
 * @since 2.0.0
 */
interface RedisStorageInterface extends OtpStorageInterface
{
    /**
     * Set the Redis connection parameters.
     *
     * @param string $host Redis host.
     * @param int $port Redis port.
     * @param string|null $password Redis password (optional).
     * @param int $database Redis database number.
     * @return void
     */
    public function setConnection(
        string $host = '127.0.0.1',
        int $port = 6379,
        ?string $password = null,
        int $database = 0
    ): void;

    /**
     * Get the Redis instance.
     *
     * @return \Redis|null
     */
    public function getConnection(): ?\Redis;

    /**
     * Set a custom key prefix for OTP entries.
     *
     * @param string $prefix
     * @return void
     */
    public function setKeyPrefix(string $prefix): void;

    /**
     * Get the key prefix.
     *
     * @return string
     */
    public function getKeyPrefix(): string;

    /**
     * Get TTL of an OTP entry directly from Redis.
     *
     * @param string $identifier
     * @return int TTL in seconds, -1 if no expiry, -2 if key doesn't exist.
     */
    public function getTtl(string $identifier): int;
}
