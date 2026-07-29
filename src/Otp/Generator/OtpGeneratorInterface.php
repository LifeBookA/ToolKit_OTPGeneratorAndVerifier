<?php

/**
 * OtpGeneratorInterface - Interface for OTP generators
 * 
 * Defines the contract for OTP code generation.
 * 
 * @package Toolkit\Otp\Generator
 * @version 1.0.0
 */

namespace Toolkit\Otp\Generator;

interface OtpGeneratorInterface
{
    /**
     * Generate an OTP code of specified length
     * 
     * @param int $length Length of the OTP code (4-10)
     * @return string The generated OTP code
     * @throws \InvalidArgumentException If length is out of valid range
     */
    public function generate(int $length): string;
}
