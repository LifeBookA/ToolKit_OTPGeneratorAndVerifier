<?php

namespace Toolkit\Otp\Generator;

/**
 * Time-based One-Time Password (TOTP) Generator
 * 
 * Implements RFC 6238 TOTP algorithm for time-based OTP generation.
 * Compatible with Google Authenticator, Authy, and other TOTP apps.
 */
class TotpGenerator implements OtpGeneratorInterface
{
    /**
     * @var int Default time step in seconds
     */
    private const DEFAULT_TIME_STEP = 30;
    
    /**
     * @var int Default number of digits
     */
    private const DEFAULT_DIGITS = 6;
    
    /**
     * @var string Default hashing algorithm
     */
    private const DEFAULT_ALGORITHM = 'sha1';
    
    /**
     * @var int Time step in seconds
     */
    private int $timeStep;
    
    /**
     * @var int Number of digits in the OTP
     */
    private int $digits;
    
    /**
     * @var string Hashing algorithm
     */
    private string $algorithm;

    /**
     * Constructor
     * 
     * @param int $timeStep Time step in seconds (default: 30)
     * @param int $digits Number of digits (default: 6)
     * @param string $algorithm Hashing algorithm (default: 'sha1')
     */
    public function __construct(
        int $timeStep = self::DEFAULT_TIME_STEP,
        int $digits = self::DEFAULT_DIGITS,
        string $algorithm = self::DEFAULT_ALGORITHM
    ) {
        $this->timeStep = $timeStep;
        $this->digits = $digits;
        $this->algorithm = strtolower($algorithm);
    }

    /**
     * Generate a TOTP code based on current time
     * 
     * Note: This method requires a secret key, which is not part of the standard
     * OtpGeneratorInterface. Use generateForSecret() instead.
     * 
     * @param int $length Ignored for TOTP (uses $this->digits)
     * @return string Throws exception - use generateForSecret() instead
     * @throws \BadMethodCallException
     */
    public function generate(int $length): string
    {
        throw new \BadMethodCallException(
            "TOTP requires a secret key. Use generateForSecret(\$secret) instead."
        );
    }

    /**
     * Generate a TOTP code for a given secret
     * 
     * @param string $secret Base32-encoded secret key
     * @param int|null $timestamp Unix timestamp (null for current time)
     * @return string Generated TOTP code
     */
    public function generateForSecret(string $secret, ?int $timestamp = null): string
    {
        // Decode base32 secret
        $secretBytes = $this->base32Decode($secret);
        
        // Calculate time counter
        $timestamp = $timestamp ?? time();
        $counter = intdiv($timestamp, $this->timeStep);
        
        // Generate HMAC
        $counterBytes = pack('N', 0) . pack('N', $counter);
        $hash = hash_hmac($this->algorithm, $counterBytes, $secretBytes, true);
        
        // Dynamic truncation
        $offset = ord(substr($hash, -1)) & 0x0F;
        $binary = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;
        
        // Generate OTP
        $otp = str_pad(
            (string)($binary % pow(10, $this->digits)),
            $this->digits,
            '0',
            STR_PAD_LEFT
        );
        
        return $otp;
    }

    /**
     * Verify a TOTP code with optional time window tolerance
     * 
     * @param string $secret Base32-encoded secret key
     * @param string $code Code to verify
     * @param int $window Number of time steps to check before/after current (default: 1)
     * @return bool True if code is valid
     */
    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $currentTime = time();
        
        // Check current time step and surrounding windows
        for ($i = -$window; $i <= $window; $i++) {
            $expectedCode = $this->generateForSecret($secret, $currentTime + ($i * $this->timeStep));
            if (hash_equals($expectedCode, $code)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get the remaining seconds until the next code
     * 
     * @return int Seconds until next code
     */
    public function getRemainingSeconds(): int
    {
        return $this->timeStep - (time() % $this->timeStep);
    }

    /**
     * Get the current time counter
     * 
     * @param int|null $timestamp Unix timestamp (null for current time)
     * @return int Time counter
     */
    public function getCurrentCounter(?int $timestamp = null): int
    {
        return intdiv($timestamp ?? time(), $this->timeStep);
    }

    /**
     * Decode a base32 encoded string
     * 
     * @param string $secret Base32 encoded string
     * @return string Decoded binary data
     */
    private function base32Decode(string $secret): string
    {
        // Remove spaces and convert to uppercase
        $secret = strtoupper(str_replace(' ', '', $secret));
        
        // Character mapping
        $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $buffer = 0;
        $bitsLeft = 0;
        $result = '';
        
        for ($i = 0; $i < strlen($secret); $i++) {
            $char = $secret[$i];
            
            // Skip padding
            if ($char === '=') {
                continue;
            }
            
            $pos = strpos($charset, $char);
            if ($pos === false) {
                throw new \InvalidArgumentException("Invalid base32 character: $char");
            }
            
            $buffer = ($buffer << 5) | $pos;
            $bitsLeft += 5;
            
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $result .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }
        
        return $result;
    }

    /**
     * Generate a random base32 secret
     * 
     * @param int $length Length of the secret in bytes (default: 20)
     * @return string Base32-encoded secret
     */
    public static function generateSecret(int $length = 20): string
    {
        $bytes = random_bytes($length);
        return self::base32Encode($bytes);
    }

    /**
     * Encode binary data to base32
     * 
     * @param string $data Binary data
     * @return string Base32-encoded string
     */
    private static function base32Encode(string $data): string
    {
        $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $buffer = 0;
        $bitsLeft = 0;
        $result = '';
        
        for ($i = 0; $i < strlen($data); $i++) {
            $byte = ord($data[$i]);
            $buffer = ($buffer << 8) | $byte;
            $bitsLeft += 8;
            
            while ($bitsLeft >= 5) {
                $bitsLeft -= 5;
                $result .= $charset[($buffer >> $bitsLeft) & 0x1F];
            }
        }
        
        // Handle remaining bits
        if ($bitsLeft > 0) {
            $buffer <<= (5 - $bitsLeft);
            $result .= $charset[$buffer & 0x1F];
        }
        
        // Add padding
        $padding = (8 - (strlen($result) % 8)) % 8;
        if ($padding > 0) {
            $result .= str_repeat('=', $padding);
        }
        
        return $result;
    }
}
