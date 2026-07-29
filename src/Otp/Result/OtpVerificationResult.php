<?php

/**
 * OtpVerificationResult - Value object for OTP verification results
 * 
 * Represents the result of an OTP verification attempt.
 * 
 * @package Toolkit\Otp\Result
 * @version 1.0.0
 */

namespace Toolkit\Otp\Result;

class OtpVerificationResult
{
    /**
     * @param bool $isValid Whether the verification was successful
     * @param string $status Status code ('success', 'expired', 'blocked', 'invalid', 'not_found')
     * @param string $message Human-readable message in Persian
     * @param int $remainingAttempts Number of remaining attempts (for failed verifications)
     */
    public function __construct(
        public readonly bool $isValid,
        public readonly string $status,
        public readonly string $message,
        public readonly int $remainingAttempts = 0
    ) {
    }

    /**
     * Convert the result to an associative array
     * 
     * @return array Associative array representation
     */
    public function toArray(): array
    {
        return [
            'isValid' => $this->isValid,
            'status' => $this->status,
            'message' => $this->message,
            'remainingAttempts' => $this->remainingAttempts,
        ];
    }

    /**
     * Check if the verification was successful
     * 
     * @return bool True if successful
     */
    public function isSuccess(): bool
    {
        return $this->isValid;
    }

    /**
     * Check if the OTP has expired
     * 
     * @return bool True if expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    /**
     * Check if the identifier is blocked
     * 
     * @return bool True if blocked
     */
    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    /**
     * Check if the OTP was not found
     * 
     * @return bool True if not found
     */
    public function isNotFound(): bool
    {
        return $this->status === 'not_found';
    }

    /**
     * Check if the code was invalid
     * 
     * @return bool True if invalid
     */
    public function isInvalid(): bool
    {
        return $this->status === 'invalid';
    }

    /**
     * Get a JSON representation of the result
     * 
     * @param int $flags JSON encoding flags
     * @return string JSON string
     */
    public function toJson(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->toArray(), $flags);
    }
}
