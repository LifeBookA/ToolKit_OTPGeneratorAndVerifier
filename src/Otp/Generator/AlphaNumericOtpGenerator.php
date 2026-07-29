<?php

/**
 * AlphaNumericOtpGenerator - Generates alphanumeric OTP codes
 * 
 * Uses cryptographically secure random_int() for code generation.
 * Generates codes with uppercase letters (A-Z) and digits (0-9).
 * 
 * @package Toolkit\Otp\Generator
 * @version 1.0.0
 */

namespace Toolkit\Otp\Generator;

class AlphaNumericOtpGenerator implements OtpGeneratorInterface
{
    /**
     * @var string Character set for OTP generation (A-Z, 0-9)
     */
    private const CHARSET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    /**
     * Generate an alphanumeric OTP code of specified length
     * 
     * @param int $length Length of the OTP code (4-10)
     * @return string The generated alphanumeric OTP code
     * @throws \InvalidArgumentException If length is out of valid range
     */
    public function generate(int $length): string
    {
        if ($length < 4 || $length > 10) {
            throw new \InvalidArgumentException('OTP length must be between 4 and 10');
        }

        $code = '';
        $charsetLength = strlen(self::CHARSET);

        for ($i = 0; $i < $length; $i++) {
            $index = random_int(0, $charsetLength - 1);
            $code .= self::CHARSET[$index];
        }

        return $code;
    }
}
