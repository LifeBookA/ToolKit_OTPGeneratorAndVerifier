<?php

/**
 * OtpException - Base exception for OTP module
 * 
 * Base exception class for all OTP-related exceptions.
 * 
 * @package Toolkit\Otp\Exceptions
 * @version 1.0.0
 */

namespace Toolkit\Otp\Exceptions;

class OtpException extends \Exception
{
    /**
     * Constructor
     * 
     * @param string $message Error message
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = 'OTP error occurred',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
