<?php

/**
 * OtpConfig - Configuration class for OTP module
 * 
 * Provides static configuration options for the OTP system.
 * 
 * @package Toolkit\Otp\Config
 * @version 1.0.0
 */

namespace Toolkit\Otp\Config;

class OtpConfig
{
    /**
     * @var string Storage directory for OTP files
     */
    private static string $storageDir = __DIR__ . '/../../../otp_storage';

    /**
     * @var int Default OTP code length
     */
    private static int $defaultLength = 6;

    /**
     * @var int Default time to live in seconds (5 minutes)
     */
    private static int $defaultTtl = 300;

    /**
     * @var int Maximum verification attempts allowed
     */
    private static int $maxAttempts = 5;

    /**
     * @var string Generator type ('numeric' or 'alphanumeric')
     */
    private static string $generatorType = 'numeric';

    /**
     * Get the storage directory path
     * 
     * @return string Storage directory path
     */
    public static function getStorageDir(): string
    {
        return self::$storageDir;
    }

    /**
     * Set the storage directory path
     * 
     * @param string $dir Storage directory path
     * @return void
     */
    public static function setStorageDir(string $dir): void
    {
        self::$storageDir = rtrim($dir, DIRECTORY_SEPARATOR);
    }

    /**
     * Get the default OTP code length
     * 
     * @return int Default length
     */
    public static function getDefaultLength(): int
    {
        return self::$defaultLength;
    }

    /**
     * Set the default OTP code length
     * 
     * @param int $length Default length (4-10)
     * @return void
     * @throws \InvalidArgumentException If length is out of valid range
     */
    public static function setDefaultLength(int $length): void
    {
        if ($length < 4 || $length > 10) {
            throw new \InvalidArgumentException('Default length must be between 4 and 10');
        }
        self::$defaultLength = $length;
    }

    /**
     * Get the default time to live
     * 
     * @return int Default TTL in seconds
     */
    public static function getDefaultTtl(): int
    {
        return self::$defaultTtl;
    }

    /**
     * Set the default time to live
     * 
     * @param int $ttl Default TTL in seconds
     * @return void
     * @throws \InvalidArgumentException If TTL is negative
     */
    public static function setDefaultTtl(int $ttl): void
    {
        if ($ttl < 0) {
            throw new \InvalidArgumentException('TTL cannot be negative');
        }
        self::$defaultTtl = $ttl;
    }

    /**
     * Get the maximum verification attempts
     * 
     * @return int Maximum attempts
     */
    public static function getMaxAttempts(): int
    {
        return self::$maxAttempts;
    }

    /**
     * Set the maximum verification attempts
     * 
     * @param int $attempts Maximum attempts
     * @return void
     * @throws \InvalidArgumentException If attempts is less than 1
     */
    public static function setMaxAttempts(int $attempts): void
    {
        if ($attempts < 1) {
            throw new \InvalidArgumentException('Maximum attempts must be at least 1');
        }
        self::$maxAttempts = $attempts;
    }

    /**
     * Get the generator type
     * 
     * @return string Generator type ('numeric' or 'alphanumeric')
     */
    public static function getGeneratorType(): string
    {
        return self::$generatorType;
    }

    /**
     * Set the generator type
     * 
     * @param string $type Generator type ('numeric' or 'alphanumeric')
     * @return void
     * @throws \InvalidArgumentException If type is invalid
     */
    public static function setGeneratorType(string $type): void
    {
        if (!in_array($type, ['numeric', 'alphanumeric'], true)) {
            throw new \InvalidArgumentException('Generator type must be "numeric" or "alphanumeric"');
        }
        self::$generatorType = $type;
    }

    /**
     * Get all configuration as an array
     * 
     * @return array Configuration array
     */
    public static function getAll(): array
    {
        return [
            'storageDir' => self::$storageDir,
            'defaultLength' => self::$defaultLength,
            'defaultTtl' => self::$defaultTtl,
            'maxAttempts' => self::$maxAttempts,
            'generatorType' => self::$generatorType,
        ];
    }
}
