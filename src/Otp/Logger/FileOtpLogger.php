<?php

declare(strict_types=1);

namespace Toolkit\Otp\Logger;

use Toolkit\Otp\Config\OtpConfig;

/**
 * File-based logger for OTP events.
 * 
 * Logs all OTP-related events to a JSON file with timestamps.
 * Each log entry includes timestamp, event type, identifier, and additional context.
 * 
 * @package Toolkit\Otp\Logger
 * @author Toolkit Team
 * @since 2.0.0
 */
class FileOtpLogger implements OtpLoggerInterface
{
    /**
     * @var string Path to the log file.
     */
    private string $logFile;

    /**
     * @var int Maximum log file size in bytes before rotation (default: 1MB).
     */
    private int $maxFileSize;

    /**
     * Constructor.
     *
     * @param string|null $logDir Optional directory for log files. Defaults to OtpConfig storage dir + '/logs'.
     * @param int $maxFileSize Maximum file size before rotation (bytes).
     */
    public function __construct(?string $logDir = null, int $maxFileSize = 1048576)
    {
        if ($logDir === null) {
            $logDir = OtpConfig::getStorageDir() . '/logs';
        }
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $this->logFile = $logDir . '/otp_events.log';
        $this->maxFileSize = $maxFileSize;
    }

    /**
     * {@inheritdoc}
     */
    public function logGeneration(string $identifier, int $length, int $ttl): void
    {
        $this->writeLog('generation', [
            'identifier' => $identifier,
            'length' => $length,
            'ttl' => $ttl,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function logSuccess(string $identifier): void
    {
        $this->writeLog('success', [
            'identifier' => $identifier,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function logFailure(string $identifier, string $reason, int $remainingAttempts): void
    {
        $this->writeLog('failure', [
            'identifier' => $identifier,
            'reason' => $reason,
            'remaining_attempts' => $remainingAttempts,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function logExpiration(string $identifier): void
    {
        $this->writeLog('expiration', [
            'identifier' => $identifier,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function logBlock(string $identifier): void
    {
        $this->writeLog('block', [
            'identifier' => $identifier,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function logInvalidation(string $identifier): void
    {
        $this->writeLog('invalidation', [
            'identifier' => $identifier,
        ]);
    }

    /**
     * Write a log entry to the file.
     *
     * @param string $eventType The type of event.
     * @param array $context Additional context data.
     * @return void
     */
    private function writeLog(string $eventType, array $context): void
    {
        // Check file size and rotate if necessary
        if (file_exists($this->logFile) && filesize($this->logFile) >= $this->maxFileSize) {
            $this->rotateLog();
        }

        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'timestamp_unix' => time(),
            'event_type' => $eventType,
            'context' => $context,
        ];

        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . PHP_EOL;

        $handle = fopen($this->logFile, 'a');
        if ($handle !== false) {
            flock($handle, LOCK_EX);
            fwrite($handle, $logLine);
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * Rotate the log file by renaming it with a timestamp.
     *
     * @return void
     */
    private function rotateLog(): void
    {
        $backupName = $this->logFile . '.' . date('Ymd_His');
        rename($this->logFile, $backupName);
    }

    /**
     * Get the path to the log file.
     *
     * @return string
     */
    public function getLogFile(): string
    {
        return $this->logFile;
    }

    /**
     * Read recent log entries.
     *
     * @param int $limit Maximum number of entries to return.
     * @return array Array of log entries.
     */
    public function readRecentLogs(int $limit = 100): array
    {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $recentLines = array_slice($lines, -$limit);
        
        $entries = [];
        foreach ($recentLines as $line) {
            $decoded = json_decode($line, true);
            if ($decoded !== null) {
                $entries[] = $decoded;
            }
        }

        return $entries;
    }
}
