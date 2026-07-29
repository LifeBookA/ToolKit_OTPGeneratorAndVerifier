<?php

/**
 * OtpExpiredException - Exception for expired OTP codes
 * 
 * Thrown when an OTP code has expired.
 * 
 * @package Toolkit\Otp\Exceptions
 * @version 1.0.0
 */

namespace Toolkit\Otp\Exceptions;

class OtpExpiredException extends OtpException
{
    /**
     * Constructor
     * 
     * @param string $message Error message
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = 'کد OTP منقضی شده است',
        int $code = 410,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
