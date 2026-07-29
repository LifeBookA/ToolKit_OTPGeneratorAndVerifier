<?php

/**
 * OtpHelper - Helper utilities for OTP module
 * 
 * Provides static utility methods for OTP operations.
 * 
 * @package Toolkit\Otp\Helpers
 * @version 1.0.0
 */

namespace Toolkit\Otp\Helpers;

use Toolkit\Otp\Generator\NumericOtpGenerator;
use Toolkit\Otp\Generator\AlphaNumericOtpGenerator;
use Toolkit\Otp\Generator\OtpGeneratorInterface;
use Toolkit\Otp\Config\OtpConfig;

class OtpHelper
{
    /**
     * @var string Valid identifier pattern (alphanumeric, @, ., _, -)
     */
    private const IDENTIFIER_PATTERN = '/^[a-zA-Z0-9@._-]+$/';

    /**
     * Generate a secure OTP code based on configured generator type
     * 
     * @param int $length Length of the code
     * @param string|null $type Generator type ('numeric' or 'alphanumeric')
     * @return string Generated OTP code
     * @throws \InvalidArgumentException If type is invalid
     */
    public static function generateSecureCode(int $length, ?string $type = null): string
    {
        $generatorType = $type ?? OtpConfig::getGeneratorType();

        $generator = match ($generatorType) {
            'numeric' => new NumericOtpGenerator(),
            'alphanumeric' => new AlphaNumericOtpGenerator(),
            default => throw new \InvalidArgumentException("Invalid generator type: {$generatorType}"),
        };

        return $generator->generate($length);
    }

    /**
     * Validate an identifier format
     * 
     * Identifiers must contain only alphanumeric characters and @._-
     * 
     * @param string $identifier The identifier to validate
     * @return bool True if valid, false otherwise
     */
    public static function validateIdentifier(string $identifier): bool
    {
        if (empty($identifier)) {
            return false;
        }

        return (bool) preg_match(self::IDENTIFIER_PATTERN, $identifier);
    }

    /**
     * Format remaining attempts as a user-friendly message
     * 
     * @param int $attempts Remaining attempts
     * @param int $max Maximum attempts allowed
     * @return string Formatted message in Persian
     */
    public static function formatRemainingAttempts(int $attempts, int $max): string
    {
        if ($attempts <= 0) {
            return 'تعداد تلاش شما به پایان رسیده است';
        }

        $remaining = max(0, $attempts);
        
        if ($remaining === 1) {
            return 'فقط ۱ تلاش دیگر باقی مانده است';
        }

        return "تعداد {$remaining} تلاش دیگر باقی مانده است";
    }

    /**
     * Get a generator instance based on type
     * 
     * @param string $type Generator type ('numeric' or 'alphanumeric')
     * @return OtpGeneratorInterface Generator instance
     * @throws \InvalidArgumentException If type is invalid
     */
    public static function getGenerator(string $type = 'numeric'): OtpGeneratorInterface
    {
        return match ($type) {
            'numeric' => new NumericOtpGenerator(),
            'alphanumeric' => new AlphaNumericOtpGenerator(),
            default => throw new \InvalidArgumentException("Invalid generator type: {$type}"),
        };
    }

    /**
     * Sanitize an identifier for use as filename
     * 
     * @param string $identifier Original identifier
     * @return string Sanitized identifier
     */
    public static function sanitizeIdentifier(string $identifier): string
    {
        return preg_replace('/[^a-zA-Z0-9@._-]/', '_', $identifier);
    }

    /**
     * Check if a code matches the expected format for a given type
     * 
     * @param string $code The code to check
     * @param string $type Expected type ('numeric' or 'alphanumeric')
     * @return bool True if code matches format
     */
    public static function validateCodeFormat(string $code, string $type = 'numeric'): bool
    {
        return match ($type) {
            'numeric' => ctype_digit($code),
            'alphanumeric' => ctype_alnum($code) && strtoupper($code) === $code,
            default => false,
        };
    }
}
