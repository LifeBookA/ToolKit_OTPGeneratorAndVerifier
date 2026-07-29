<?php

/**
 * NumericOtpGenerator - Generates numeric-only OTP codes
 * 
 * Uses cryptographically secure random_int() for code generation.
 * 
 * @package Toolkit\Otp\Generator
 * @version 1.0.0
 */

namespace Toolkit\Otp\Generator;

class NumericOtpGenerator implements OtpGeneratorInterface
{
    /**
     * Generate a numeric OTP code of specified length
     * 
     * @param int $length Length of the OTP code (4-10)
     * @return string The generated numeric OTP code
     * @throws \InvalidArgumentException If length is out of valid range
     */
    public function generate(int $length): string
    {
        if ($length < 4 || $length > 10) {
            throw new \InvalidArgumentException('OTP length must be between 4 and 10');
        }

        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= random_int(0, 9);
        }

        return $code;
    }
}
