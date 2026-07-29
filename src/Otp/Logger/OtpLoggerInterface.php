<?php

declare(strict_types=1);

namespace Toolkit\Otp\Logger;

/**
 * Interface for OTP logging.
 * 
 * Provides methods for logging OTP-related events such as generation,
 * verification attempts, expiration, and blocking.
 * 
 * @package Toolkit\Otp\Logger
 * @author Toolkit Team
 * @since 2.0.0
 */
interface OtpLoggerInterface
{
    /**
     * Log an OTP generation event.
     *
     * @param string $identifier The identifier (e.g., email, phone).
     * @param int $length The length of the generated OTP.
     * @param int $ttl The time-to-live in seconds.
     * @return void
     */
    public function logGeneration(string $identifier, int $length, int $ttl): void;

    /**
     * Log a successful verification event.
     *
     * @param string $identifier The identifier.
     * @return void
     */
    public function logSuccess(string $identifier): void;

    /**
     * Log a failed verification attempt.
     *
     * @param string $identifier The identifier.
     * @param string $reason The reason for failure (invalid, expired, blocked).
     * @param int $remainingAttempts The number of remaining attempts.
     * @return void
     */
    public function logFailure(string $identifier, string $reason, int $remainingAttempts): void;

    /**
     * Log an OTP expiration event.
     *
     * @param string $identifier The identifier.
     * @return void
     */
    public function logExpiration(string $identifier): void;

    /**
     * Log an OTP blocking event (max attempts reached).
     *
     * @param string $identifier The identifier.
     * @return void
     */
    public function logBlock(string $identifier): void;

    /**
     * Log an OTP invalidation event.
     *
     * @param string $identifier The identifier.
     * @return void
     */
    public function logInvalidation(string $identifier): void;
}
