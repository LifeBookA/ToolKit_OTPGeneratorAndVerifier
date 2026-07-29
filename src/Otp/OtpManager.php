<?php

/**
 * OtpManager - Main class for OTP management
 * 
 * Implements the OtpInterface and provides complete OTP functionality.
 * 
 * @package Toolkit\Otp
 * @version 1.0.0
 */

namespace Toolkit\Otp;

use Toolkit\Otp\Contracts\OtpInterface;
use Toolkit\Otp\Generator\OtpGeneratorInterface;
use Toolkit\Otp\Generator\NumericOtpGenerator;
use Toolkit\Otp\Storage\OtpStorageInterface;
use Toolkit\Otp\Storage\FileOtpStorage;
use Toolkit\Otp\Verifier\OtpVerifierInterface;
use Toolkit\Otp\Verifier\StandardOtpVerifier;
use Toolkit\Otp\Result\OtpVerificationResult;
use Toolkit\Otp\Config\OtpConfig;
use Toolkit\Otp\Helpers\OtpHelper;
use Toolkit\Otp\Exceptions\OtpException;

class OtpManager implements OtpInterface
{
    /**
     * @var OtpGeneratorInterface OTP code generator
     */
    protected OtpGeneratorInterface $generator;

    /**
     * @var OtpStorageInterface OTP storage handler
     */
    protected OtpStorageInterface $storage;

    /**
     * @var OtpVerifierInterface OTP verifier
     */
    protected OtpVerifierInterface $verifier;

    /**
     * @var array Configuration options
     */
    protected array $config;

    /**
     * Constructor
     * 
     * @param OtpStorageInterface|null $storage Optional storage instance
     * @param OtpGeneratorInterface|null $generator Optional generator instance
     * @param OtpVerifierInterface|null $verifier Optional verifier instance
     * @param array|null $config Optional configuration overrides
     */
    public function __construct(
        ?OtpStorageInterface $storage = null,
        ?OtpGeneratorInterface $generator = null,
        ?OtpVerifierInterface $verifier = null,
        ?array $config = null
    ) {
        $this->storage = $storage ?? new FileOtpStorage();
        $this->generator = $generator ?? $this->createDefaultGenerator();
        $this->verifier = $verifier ?? new StandardOtpVerifier();
        $this->config = $config ?? OtpConfig::getAll();
    }

    /**
     * Create default generator based on config
     * 
     * @return OtpGeneratorInterface Generator instance
     */
    private function createDefaultGenerator(): OtpGeneratorInterface
    {
        $type = OtpConfig::getGeneratorType();
        
        return match ($type) {
            'alphanumeric' => new \Toolkit\Otp\Generator\AlphaNumericOtpGenerator(),
            default => new NumericOtpGenerator(),
        };
    }

    /**
     * Generate a new OTP code for the given identifier
     * 
     * @param string $identifier Unique identifier (e.g., email, phone number)
     * @param int|null $length Length of the OTP code (default from config)
     * @param int|null $ttl Time to live in seconds (default from config)
     * @return string The generated OTP code
     * @throws OtpException If identifier is invalid
     */
    public function generate(string $identifier, int $length = null, int $ttl = null): string
    {
        // Validate identifier
        if (!OtpHelper::validateIdentifier($identifier)) {
            throw new OtpException('شناسه نامعتبر است');
        }

        // Use default values if not provided
        $length = $length ?? OtpConfig::getDefaultLength();
        $ttl = $ttl ?? OtpConfig::getDefaultTtl();

        // Generate the OTP code
        $code = $this->generator->generate($length);

        // Store the OTP
        $this->storage->save($identifier, $code, $ttl);

        return $code;
    }

    /**
     * Verify an OTP code for the given identifier
     * 
     * @param string $identifier Unique identifier
     * @param string $code The OTP code to verify
     * @return OtpVerificationResult Result of the verification
     */
    public function verify(string $identifier, string $code): OtpVerificationResult
    {
        return $this->verifier->verify($identifier, $code, $this->storage);
    }

    /**
     * Invalidate all OTPs for the given identifier
     * 
     * @param string $identifier Unique identifier
     * @return void
     */
    public function invalidate(string $identifier): void
    {
        $this->storage->delete($identifier);
    }

    /**
     * Get remaining attempts for the given identifier
     * 
     * @param string $identifier Unique identifier
     * @return int Number of remaining attempts
     */
    public function getRemainingAttempts(string $identifier): int
    {
        $data = $this->storage->get($identifier);
        
        if ($data === null) {
            return 0;
        }

        $maxAttempts = OtpConfig::getMaxAttempts();
        $currentAttempts = $data['attempts'] ?? 0;

        return max(0, $maxAttempts - $currentAttempts);
    }

    /**
     * Get the storage instance
     * 
     * @return OtpStorageInterface Storage instance
     */
    public function getStorage(): OtpStorageInterface
    {
        return $this->storage;
    }

    /**
     * Get the generator instance
     * 
     * @return OtpGeneratorInterface Generator instance
     */
    public function getGenerator(): OtpGeneratorInterface
    {
        return $this->generator;
    }

    /**
     * Get the verifier instance
     * 
     * @return OtpVerifierInterface Verifier instance
     */
    public function getVerifier(): OtpVerifierInterface
    {
        return $this->verifier;
    }

    /**
     * Get current configuration
     * 
     * @return array Configuration array
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
