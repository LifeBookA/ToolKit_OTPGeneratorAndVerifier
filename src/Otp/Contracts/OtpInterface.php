<?php

/**
 * OtpInterface - Main interface for OTP module
 * 
 * Defines the contract for OTP generation, verification, and management.
 * 
 * @package Toolkit\Otp\Contracts
 * @version 1.0.0
 */

namespace Toolkit\Otp\Contracts;

use Toolkit\Otp\Result\OtpVerificationResult;

interface OtpInterface
{
    /**
     * Generate a new OTP code for the given identifier
     * 
     * @param string $identifier Unique identifier (e.g., email, phone number)
     * @param int|null $length Length of the OTP code (default from config)
     * @param int|null $ttl Time to live in seconds (default from config)
     * @return string The generated OTP code
     * @throws \Toolkit\Otp\Exceptions\OtpException If identifier is invalid
     */
    public function generate(string $identifier, int $length = null, int $ttl = null): string;

    /**
     * Verify an OTP code for the given identifier
     * 
     * @param string $identifier Unique identifier
     * @param string $code The OTP code to verify
     * @return OtpVerificationResult Result of the verification
     */
    public function verify(string $identifier, string $code): OtpVerificationResult;

    /**
     * Invalidate all OTPs for the given identifier
     * 
     * @param string $identifier Unique identifier
     * @return void
     */
    public function invalidate(string $identifier): void;

    /**
     * Get remaining attempts for the given identifier
     * 
     * @param string $identifier Unique identifier
     * @return int Number of remaining attempts
     */
    public function getRemainingAttempts(string $identifier): int;
}
