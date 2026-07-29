<?php

/**
 * OtpBlockedException - Exception for blocked OTP attempts
 * 
 * Thrown when maximum verification attempts have been exceeded.
 * 
 * @package Toolkit\Otp\Exceptions
 * @version 1.0.0
 */

namespace Toolkit\Otp\Exceptions;

class OtpBlockedException extends OtpException
{
    /**
     * Constructor
     * 
     * @param string $message Error message
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = 'تعداد تلاش مجاز به پایان رسیده است',
        int $code = 429,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
