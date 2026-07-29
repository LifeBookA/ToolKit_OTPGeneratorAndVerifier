<?php

declare(strict_types=1);

namespace Toolkit\Otp\RateLimit;

/**
 * Interface for rate limiting OTP requests.
 * 
 * Provides methods for tracking and limiting the number of OTP requests
 * per identifier within a specified time window.
 * 
 * @package Toolkit\Otp\RateLimit
 * @author Toolkit Team
 * @since 2.0.0
 */
interface RateLimiterInterface
{
    /**
     * Check if an identifier is allowed to make a request.
     *
     * @param string $identifier The identifier (e.g., email, phone, IP).
     * @return bool True if allowed, false if rate limited.
     */
    public function isAllowed(string $identifier): bool;

    /**
     * Record a request for an identifier.
     *
     * @param string $identifier The identifier.
     * @return int The number of requests made in the current window.
     */
    public function recordRequest(string $identifier): int;

    /**
     * Get the number of remaining requests for an identifier.
     *
     * @param string $identifier The identifier.
     * @return int Number of remaining requests.
     */
    public function getRemainingRequests(string $identifier): int;

    /**
     * Get the reset time (Unix timestamp) for an identifier's rate limit window.
     *
     * @param string $identifier The identifier.
     * @return int Unix timestamp when the window resets.
     */
    public function getResetTime(string $identifier): int;

    /**
     * Reset the rate limit counter for an identifier.
     *
     * @param string $identifier The identifier.
     * @return void
     */
    public function reset(string $identifier): void;

    /**
     * Get the maximum number of requests allowed per window.
     *
     * @return int
     */
    public function getMaxRequests(): int;

    /**
     * Get the time window in seconds.
     *
     * @return int
     */
    public function getWindowSeconds(): int;
}
