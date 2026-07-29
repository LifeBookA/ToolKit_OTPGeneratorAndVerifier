<?php

namespace Toolkit\Otp\Helpers;

/**
 * QR Code Helper for TOTP setup
 * 
 * Generates otpauth:// URI strings compatible with Google Authenticator,
 * Authy, and other TOTP applications.
 */
class QrCodeHelper
{
    /**
     * Generate an otpauth:// URI for TOTP setup
     * 
     * @param string $secret The shared secret key (base32 encoded)
     * @param string $accountName User's account name or email
     * @param string $issuer Service/Company name
     * @param int $timeStep Time step in seconds (default: 30)
     * @param int $digits Number of digits in the OTP (default: 6)
     * @param string $algorithm Hashing algorithm (default: SHA1)
     * @return string otpauth:// URI string
     */
    public static function generateTotpUri(
        string $secret,
        string $accountName,
        string $issuer,
        int $timeStep = 30,
        int $digits = 6,
        string $algorithm = 'SHA1'
    ): string {
        // URL encode the issuer and account name
        $encodedIssuer = rawurlencode($issuer);
        $encodedAccountName = rawurlencode($accountName);
        
        // Build the URI
        $uri = sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=%s&digits=%d&period=%d',
            $encodedIssuer,
            $encodedAccountName,
            strtoupper($secret),
            $encodedIssuer,
            strtoupper($algorithm),
            $digits,
            $timeStep
        );
        
        return $uri;
    }

    /**
     * Generate a Google Chart API URL for QR code image
     * 
     * Note: Google Chart API is deprecated but still works.
     * For production, consider using a local QR code library.
     * 
     * @param string $totpUri The otpauth:// URI
     * @param int $size Size of the QR code in pixels
     * @return string Google Chart API URL
     */
    public static function generateGoogleChartQrUrl(string $totpUri, int $size = 200): string
    {
        $encodedUri = rawurlencode($totpUri);
        return sprintf(
            'https://chart.googleapis.com/chart?chs=%dx%d&cht=qr&chl=%s',
            $size,
            $size,
            $encodedUri
        );
    }

    /**
     * Generate a simple text-based representation of QR code data
     * (For debugging purposes - not scannable)
     * 
     * @param string $totpUri The otpauth:// URI
     * @return string Text representation
     */
    public static function generateTextRepresentation(string $totpUri): string
    {
        $lines = [
            "┌─────────────────────────────────────────┐",
            "│         TOTP Setup Information          │",
            "├─────────────────────────────────────────┤",
            "│ URI: " . substr($totpUri, 0, 45) . str_repeat(' ', max(0, 45 - strlen($totpUri))) . "│",
        ];
        
        if (strlen($totpUri) > 45) {
            $remaining = substr($totpUri, 45);
            while (strlen($remaining) > 0) {
                $chunk = substr($remaining, 0, 45);
                $lines[] = "│      " . $chunk . str_repeat(' ', max(0, 45 - strlen($chunk))) . "│";
                $remaining = substr($remaining, 45);
            }
        }
        
        $lines[] = "├─────────────────────────────────────────┤";
        $lines[] = "│ Scan this with Google Authenticator     │";
        $lines[] = "└─────────────────────────────────────────┘";
        
        return implode("\n", $lines);
    }
}
