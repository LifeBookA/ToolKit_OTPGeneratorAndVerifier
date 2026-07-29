<?php

/**
 * OtpStorageInterface - Interface for OTP storage implementations
 * 
 * Defines the contract for storing and retrieving OTP data.
 * 
 * @package Toolkit\Otp\Storage
 * @version 1.0.0
 */

namespace Toolkit\Otp\Storage;

interface OtpStorageInterface
{
    /**
     * Save OTP data for an identifier
     * 
     * @param string $identifier Unique identifier
     * @param string $code The OTP code
     * @param int $ttl Time to live in seconds
     * @return void
     */
    public function save(string $identifier, string $code, int $ttl): void;

    /**
     * Get OTP data for an identifier
     * 
     * @param string $identifier Unique identifier
     * @return array|null Array with 'code', 'expiry', 'attempts' or null if not found
     */
    public function get(string $identifier): ?array;

    /**
     * Delete OTP data for an identifier
     * 
     * @param string $identifier Unique identifier
     * @return void
     */
    public function delete(string $identifier): void;

    /**
     * Increment attempt count for an identifier
     * 
     * @param string $identifier Unique identifier
     * @return int New attempt count after increment
     */
    public function incrementAttempts(string $identifier): int;

    /**
     * Check if OTP exists for an identifier
     * 
     * @param string $identifier Unique identifier
     * @return bool True if OTP exists, false otherwise
     */
    public function exists(string $identifier): bool;
}
