<?php

/**
 * FileOtpStorage - File-based OTP storage implementation
 * 
 * Stores OTP data in JSON files with file locking for concurrency safety.
 * 
 * @package Toolkit\Otp\Storage
 * @version 1.0.0
 */

namespace Toolkit\Otp\Storage;

use Toolkit\Otp\Config\OtpConfig;
use Toolkit\Otp\Exceptions\OtpException;

class FileOtpStorage implements OtpStorageInterface
{
    /**
     * @var string Directory path for storing OTP files
     */
    private string $storageDir;

    /**
     * Constructor
     * 
     * @param string|null $storageDir Optional custom storage directory
     */
    public function __construct(?string $storageDir = null)
    {
        $this->storageDir = $storageDir ?? OtpConfig::getStorageDir();
        $this->ensureDirectoryExists();
    }

    /**
     * Ensure the storage directory exists
     * 
     * @return void
     * @throws OtpException If directory cannot be created
     */
    private function ensureDirectoryExists(): void
    {
        if (!is_dir($this->storageDir)) {
            if (!mkdir($this->storageDir, 0755, true)) {
                throw new OtpException("Failed to create storage directory: {$this->storageDir}");
            }
        }
    }

    /**
     * Get the file path for an identifier
     * 
     * @param string $identifier Unique identifier
     * @return string Full file path
     */
    private function getFilePath(string $identifier): string
    {
        // Sanitize identifier for filename
        $sanitized = preg_replace('/[^a-zA-Z0-9@._-]/', '_', $identifier);
        return rtrim($this->storageDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $sanitized . '.json';
    }

    /**
     * Save OTP data for an identifier
     * 
     * @param string $identifier Unique identifier
     * @param string $code The OTP code
     * @param int $ttl Time to live in seconds
     * @return void
     * @throws OtpException If write operation fails
     */
    public function save(string $identifier, string $code, int $ttl): void
    {
        $filePath = $this->getFilePath($identifier);
        $data = [
            'code' => $code,
            'expiry' => time() + $ttl,
            'attempts' => 0,
        ];

        $jsonData = json_encode($data, JSON_PRETTY_PRINT);

        $handle = fopen($filePath, 'c+');
        if ($handle === false) {
            throw new OtpException("Failed to open file for writing: {$filePath}");
        }

        try {
            if (flock($handle, LOCK_EX)) {
                ftruncate($handle, 0);
                fwrite($handle, $jsonData);
                fflush($handle);
                flock($handle, LOCK_UN);
            } else {
                throw new OtpException("Failed to acquire lock for file: {$filePath}");
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Get OTP data for an identifier
     * 
     * @param string $identifier Unique identifier
     * @return array|null Array with 'code', 'expiry', 'attempts' or null if not found/expired
     */
    public function get(string $identifier): ?array
    {
        $filePath = $this->getFilePath($identifier);

        if (!file_exists($filePath)) {
            return null;
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return null;
        }

        try {
            if (flock($handle, LOCK_SH)) {
                $content = stream_get_contents($handle);
                flock($handle, LOCK_UN);
                
                if ($content === false || $content === '') {
                    fclose($handle);
                    return null;
                }

                $data = json_decode($content, true);
                if ($data === null) {
                    fclose($handle);
                    return null;
                }

                // Check if expired
                if (isset($data['expiry']) && time() > $data['expiry']) {
                    fclose($handle);
                    $this->delete($identifier);
                    return null;
                }

                fclose($handle);
                return $data;
            }
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        return null;
    }

    /**
     * Delete OTP data for an identifier
     * 
     * @param string $identifier Unique identifier
     * @return void
     */
    public function delete(string $identifier): void
    {
        $filePath = $this->getFilePath($identifier);
        
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * Increment attempt count for an identifier
     * 
     * @param string $identifier Unique identifier
     * @return int New attempt count after increment
     * @throws OtpException If read/write operation fails
     */
    public function incrementAttempts(string $identifier): int
    {
        $filePath = $this->getFilePath($identifier);

        if (!file_exists($filePath)) {
            return 0;
        }

        $handle = fopen($filePath, 'r+');
        if ($handle === false) {
            throw new OtpException("Failed to open file for incrementing attempts: {$filePath}");
        }

        try {
            if (flock($handle, LOCK_EX)) {
                $content = stream_get_contents($handle);
                if ($content === false || $content === '') {
                    return 0;
                }

                $data = json_decode($content, true);
                if ($data === null) {
                    return 0;
                }

                $data['attempts'] = ($data['attempts'] ?? 0) + 1;
                
                ftruncate($handle, 0);
                rewind($handle);
                fwrite($handle, json_encode($data));
                fflush($handle);
                flock($handle, LOCK_UN);

                return $data['attempts'];
            }
        } finally {
            fclose($handle);
        }

        return 0;
    }

    /**
     * Check if OTP exists for an identifier
     * 
     * @param string $identifier Unique identifier
     * @return bool True if OTP exists and not expired, false otherwise
     */
    public function exists(string $identifier): bool
    {
        return $this->get($identifier) !== null;
    }

    /**
     * Get the storage directory path
     * 
     * @return string Storage directory path
     */
    public function getStorageDir(): string
    {
        return $this->storageDir;
    }
}
