<?php

/**
 * OtpVerifierInterface - Interface for OTP verifiers
 * 
 * Defines the contract for OTP verification.
 * 
 * @package Toolkit\Otp\Verifier
 * @version 1.0.0
 */

namespace Toolkit\Otp\Verifier;

use Toolkit\Otp\Storage\OtpStorageInterface;
use Toolkit\Otp\Result\OtpVerificationResult;

interface OtpVerifierInterface
{
    /**
     * Verify an OTP code for the given identifier
     * 
     * @param string $identifier Unique identifier
     * @param string $code The OTP code to verify
     * @param OtpStorageInterface $storage Storage instance
     * @return OtpVerificationResult Result of the verification
     */
    public function verify(string $identifier, string $code, OtpStorageInterface $storage): OtpVerificationResult;
}
