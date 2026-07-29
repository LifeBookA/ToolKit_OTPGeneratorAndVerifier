<?php

/**
 * StandardOtpVerifier - Standard OTP verification implementation
 * 
 * Implements the verification logic for OTP codes.
 * 
 * @package Toolkit\Otp\Verifier
 * @version 1.0.0
 */

namespace Toolkit\Otp\Verifier;

use Toolkit\Otp\Storage\OtpStorageInterface;
use Toolkit\Otp\Result\OtpVerificationResult;
use Toolkit\Otp\Config\OtpConfig;

class StandardOtpVerifier implements OtpVerifierInterface
{
    /**
     * Verify an OTP code for the given identifier
     * 
     * @param string $identifier Unique identifier
     * @param string $code The OTP code to verify
     * @param OtpStorageInterface $storage Storage instance
     * @return OtpVerificationResult Result of the verification
     */
    public function verify(string $identifier, string $code, OtpStorageInterface $storage): OtpVerificationResult
    {
        // Get OTP data from storage
        $data = $storage->get($identifier);

        // If no data found, return not_found status
        if ($data === null) {
            return new OtpVerificationResult(
                isValid: false,
                status: 'not_found',
                message: 'کد نامعتبر یا منقضی شده است',
                remainingAttempts: 0
            );
        }

        $maxAttempts = OtpConfig::getMaxAttempts();
        $currentAttempts = $data['attempts'] ?? 0;

        // Check if attempts exceeded
        if ($currentAttempts >= $maxAttempts) {
            return new OtpVerificationResult(
                isValid: false,
                status: 'blocked',
                message: 'تعداد تلاش مجاز به پایان رسیده است',
                remainingAttempts: 0
            );
        }

        // Check if expired (double-check since get() should handle this)
        if (isset($data['expiry']) && time() > $data['expiry']) {
            $storage->delete($identifier);
            return new OtpVerificationResult(
                isValid: false,
                status: 'expired',
                message: 'کد منقضی شده است',
                remainingAttempts: 0
            );
        }

        // Check if code matches
        if ($code === $data['code']) {
            // Success - delete the OTP (one-time use)
            $storage->delete($identifier);
            return new OtpVerificationResult(
                isValid: true,
                status: 'success',
                message: 'کد تأیید شد',
                remainingAttempts: 0
            );
        }

        // Code doesn't match - increment attempts
        $newAttempts = $storage->incrementAttempts($identifier);
        $remainingAttempts = max(0, $maxAttempts - $newAttempts);

        // Check if now blocked after increment
        if ($newAttempts >= $maxAttempts) {
            return new OtpVerificationResult(
                isValid: false,
                status: 'blocked',
                message: 'تعداد تلاش مجاز به پایان رسیده است',
                remainingAttempts: 0
            );
        }

        return new OtpVerificationResult(
            isValid: false,
            status: 'invalid',
            message: 'کد اشتباه است',
            remainingAttempts: $remainingAttempts
        );
    }
}
