<?php

declare(strict_types=1);

namespace Toolkit\Otp\RateLimit;

use Toolkit\Otp\Storage\FileOtpStorage;
use Toolkit\Otp\Config\OtpConfig;

/**
 * File-based rate limiter implementation.
 * 
 * Tracks request counts per identifier using file storage.
 * Uses a sliding window algorithm to limit requests within a time period.
 * 
 * @package Toolkit\Otp\RateLimit
 * @author Toolkit Team
 * @since 2.0.0
 */
class FileRateLimiter implements RateLimiterInterface
{
    /**
     * @var int Maximum number of requests allowed per window.
     */
    private int $maxRequests;

    /**
     * @var int Time window in seconds.
     */
    private int $windowSeconds;

    /**
     * @var string Directory for storing rate limit data.
     */
    private string $storageDir;

    /**
     * Constructor.
     *
     * @param int $maxRequests Maximum requests per window (default: 5).
     * @param int $windowSeconds Time window in seconds (default: 300 = 5 minutes).
     * @param string|null $storageDir Optional storage directory.
     */
    public function __construct(
        int $maxRequests = 5,
        int $windowSeconds = 300,
        ?string $storageDir = null
    ) {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
        
        if ($storageDir === null) {
            $storageDir = OtpConfig::getStorageDir() . '/rate_limits';
        }
        
        $this->storageDir = $storageDir;
        
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowed(string $identifier): bool
    {
        $data = $this->getData($identifier);
        
        if ($data === null) {
            return true;
        }

        // Clean old entries outside the window
        $now = time();
        $windowStart = $now - $this->windowSeconds;
        $data['requests'] = array_filter($data['requests'], fn($timestamp) => $timestamp > $windowStart);
        
        return count($data['requests']) < $this->maxRequests;
    }

    /**
     * {@inheritdoc}
     */
    public function recordRequest(string $identifier): int
    {
        $data = $this->getData($identifier) ?? ['requests' => [], 'window_start' => time()];
        
        $now = time();
        $windowStart = $now - $this->windowSeconds;
        
        // Remove old entries
        $data['requests'] = array_filter($data['requests'], fn($timestamp) => $timestamp > $windowStart);
        
        // Add new request
        $data['requests'][] = $now;
        $data['window_start'] = $now;
        
        $this->saveData($identifier, $data);
        
        return count($data['requests']);
    }

    /**
     * {@inheritdoc}
     */
    public function getRemainingRequests(string $identifier): int
    {
        $data = $this->getData($identifier);
        
        if ($data === null) {
            return $this->maxRequests;
        }

        $now = time();
        $windowStart = $now - $this->windowSeconds;
        $currentRequests = count(array_filter($data['requests'], fn($timestamp) => $timestamp > $windowStart));
        
        return max(0, $this->maxRequests - $currentRequests);
    }

    /**
     * {@inheritdoc}
     */
    public function getResetTime(string $identifier): int
    {
        $data = $this->getData($identifier);
        
        if ($data === null || empty($data['requests'])) {
            return time();
        }

        $now = time();
        $windowStart = $now - $this->windowSeconds;
        $oldestInWindow = min(array_filter($data['requests'], fn($timestamp) => $timestamp > $windowStart));
        
        return $oldestInWindow + $this->windowSeconds;
    }

    /**
     * {@inheritdoc}
     */
    public function reset(string $identifier): void
    {
        $filePath = $this->getFilePath($identifier);
        
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxRequests(): int
    {
        return $this->maxRequests;
    }

    /**
     * {@inheritdoc}
     */
    public function getWindowSeconds(): int
    {
        return $this->windowSeconds;
    }

    /**
     * Get the file path for an identifier.
     *
     * @param string $identifier
     * @return string
     */
    private function getFilePath(string $identifier): string
    {
        $sanitized = preg_replace('/[^a-zA-Z0-9._-]/', '_', $identifier);
        return $this->storageDir . '/' . $sanitized . '.json';
    }

    /**
     * Get rate limit data for an identifier.
     *
     * @param string $identifier
     * @return array|null
     */
    private function getData(string $identifier): ?array
    {
        $filePath = $this->getFilePath($identifier);
        
        if (!file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        
        if ($data === null) {
            return null;
        }

        return $data;
    }

    /**
     * Save rate limit data for an identifier.
     *
     * @param string $identifier
     * @param array $data
     * @return void
     */
    private function saveData(string $identifier, array $data): void
    {
        $filePath = $this->getFilePath($identifier);
        
        $handle = fopen($filePath, 'w');
        if ($handle !== false) {
            flock($handle, LOCK_EX);
            fwrite($handle, json_encode($data));
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * Clean up expired rate limit files.
     *
     * @return int Number of cleaned files.
     */
    public function cleanup(): int
    {
        $cleaned = 0;
        $files = glob($this->storageDir . '/*.json');
        
        if ($files === false) {
            return 0;
        }

        $now = time();
        $windowStart = $now - $this->windowSeconds;

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $data = json_decode($content, true);
            if ($data === null || empty($data['requests'])) {
                unlink($file);
                $cleaned++;
                continue;
            }

            // Check if all requests are older than the window
            $recentRequests = array_filter($data['requests'], fn($timestamp) => $timestamp > $windowStart);
            
            if (empty($recentRequests)) {
                unlink($file);
                $cleaned++;
            }
        }

        return $cleaned;
    }

    /**
     * Get all identifiers currently being rate limited.
     *
     * @return array Array of identifiers with their request counts.
     */
    public function getAllLimitedIdentifiers(): array
    {
        $limited = [];
        $files = glob($this->storageDir . '/*.json');
        
        if ($files === false) {
            return [];
        }

        $now = time();
        $windowStart = $now - $this->windowSeconds;

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $data = json_decode($content, true);
            if ($data === null || empty($data['requests'])) {
                continue;
            }

            $recentRequests = array_filter($data['requests'], fn($timestamp) => $timestamp > $windowStart);
            
            if (!empty($recentRequests)) {
                $identifier = basename($file, '.json');
                $limited[$identifier] = count($recentRequests);
            }
        }

        return $limited;
    }
}
