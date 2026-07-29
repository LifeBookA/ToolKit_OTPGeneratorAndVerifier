<?php

/**
 * OtpInvalidException - Exception for invalid OTP codes
 * 
 * Thrown when an OTP code is invalid.
 * 
 * @package Toolkit\Otp\Exceptions
 * @version 1.0.0
 */

namespace Toolkit\Otp\Exceptions;

class OtpInvalidException extends OtpException
{
    /**
     * Constructor
     * 
     * @param string $message Error message
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = 'کد OTP نامعتبر است',
        int $code = 400,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
