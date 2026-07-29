<?php

namespace Toolkit\Otp;

use Toolkit\Otp\Storage\FileOtpStorage;
use Toolkit\Otp\Logger\FileOtpLogger;

/**
 * OTP Analytics Module
 * 
 * Collects and analyzes OTP usage data, success rates, and detects suspicious activity.
 */
class OtpAnalytics
{
    /**
     * @var string Path to the analytics data file
     */
    private string $dataFile;
    
    /**
     * @var array In-memory cache of analytics data
     */
    private array $data = [];

    /**
     * Constructor
     * 
     * @param string|null $storageDir Directory for storing analytics data
     */
    public function __construct(?string $storageDir = null)
    {
        $storageDir = $storageDir ?? __DIR__ . '/../../otp_analytics';
        
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        
        $this->dataFile = $storageDir . DIRECTORY_SEPARATOR . 'analytics.json';
        $this->loadData();
    }

    /**
     * Load analytics data from file
     */
    private function loadData(): void
    {
        if (file_exists($this->dataFile)) {
            $content = file_get_contents($this->dataFile);
            $this->data = json_decode($content, true) ?? [];
        } else {
            $this->data = $this->getEmptyDataStructure();
        }
    }

    /**
     * Save analytics data to file
     */
    private function saveData(): void
    {
        file_put_contents($this->dataFile, json_encode($this->data, JSON_PRETTY_PRINT));
    }

    /**
     * Get empty data structure
     * 
     * @return array Empty analytics data
     */
    private function getEmptyDataStructure(): array
    {
        return [
            'total_generated' => 0,
            'total_verified' => 0,
            'successful_verifications' => 0,
            'failed_verifications' => 0,
            'expired_codes' => 0,
            'blocked_attempts' => 0,
            'by_identifier' => [],
            'hourly_stats' => [],
            'suspicious_activity' => [],
            'last_updated' => null,
        ];
    }

    /**
     * Record an OTP generation event
     * 
     * @param string $identifier User identifier
     */
    public function recordGeneration(string $identifier): void
    {
        $this->data['total_generated']++;
        $this->recordByIdentifier($identifier, 'generated');
        $this->recordHourlyStat('generated');
        $this->saveData();
    }

    /**
     * Record an OTP verification attempt
     * 
     * @param string $identifier User identifier
     * @param bool $success Whether verification was successful
     * @param string|null $failureReason Reason for failure (expired, blocked, invalid, not_found)
     */
    public function recordVerification(string $identifier, bool $success, ?string $failureReason = null): void
    {
        $this->data['total_verified']++;
        
        if ($success) {
            $this->data['successful_verifications']++;
            $this->recordByIdentifier($identifier, 'verified');
        } else {
            $this->data['failed_verifications']++;
            $this->recordByIdentifier($identifier, 'failed');
            
            // Track specific failure types
            if ($failureReason === 'expired') {
                $this->data['expired_codes']++;
            } elseif ($failureReason === 'blocked') {
                $this->data['blocked_attempts']++;
            }
            
            // Check for suspicious activity
            $this->checkSuspiciousActivity($identifier, $failureReason);
        }
        
        $this->recordHourlyStat($success ? 'verified_success' : 'verified_failed');
        $this->data['last_updated'] = time();
        $this->saveData();
    }

    /**
     * Record statistics by identifier
     * 
     * @param string $identifier User identifier
     * @param string $type Event type
     */
    private function recordByIdentifier(string $identifier, string $type): void
    {
        if (!isset($this->data['by_identifier'][$identifier])) {
            $this->data['by_identifier'][$identifier] = [
                'generated' => 0,
                'verified' => 0,
                'failed' => 0,
                'first_seen' => time(),
                'last_seen' => time(),
            ];
        }
        
        $this->data['by_identifier'][$identifier][$type]++;
        $this->data['by_identifier'][$identifier]['last_seen'] = time();
    }

    /**
     * Record hourly statistics
     * 
     * @param string $type Event type
     */
    private function recordHourlyStat(string $type): void
    {
        $hourKey = date('Y-m-d H:00');
        
        if (!isset($this->data['hourly_stats'][$hourKey])) {
            $this->data['hourly_stats'][$hourKey] = [
                'generated' => 0,
                'verified_success' => 0,
                'verified_failed' => 0,
            ];
        }
        
        if (isset($this->data['hourly_stats'][$hourKey][$type])) {
            $this->data['hourly_stats'][$hourKey][$type]++;
        }
    }

    /**
     * Check for suspicious activity
     * 
     * @param string $identifier User identifier
     * @param string|null $failureReason Failure reason
     */
    private function checkSuspiciousActivity(string $identifier, ?string $failureReason): void
    {
        if (!isset($this->data['by_identifier'][$identifier])) {
            return;
        }
        
        $stats = $this->data['by_identifier'][$identifier];
        $failedCount = $stats['failed'];
        
        // Flag as suspicious if more than 10 failed attempts
        if ($failedCount >= 10 && !isset($this->data['suspicious_activity'][$identifier])) {
            $this->data['suspicious_activity'][$identifier] = [
                'flagged_at' => time(),
                'failed_attempts' => $failedCount,
                'reason' => 'Excessive failed verification attempts',
            ];
        } elseif (isset($this->data['suspicious_activity'][$identifier])) {
            $this->data['suspicious_activity'][$identifier]['failed_attempts'] = $failedCount;
        }
    }

    /**
     * Get overall statistics
     * 
     * @return array Overall statistics
     */
    public function getOverallStats(): array
    {
        $totalVerified = $this->data['total_verified'] ?: 1; // Avoid division by zero
        
        return [
            'total_generated' => $this->data['total_generated'],
            'total_verified' => $this->data['total_verified'],
            'successful_verifications' => $this->data['successful_verifications'],
            'failed_verifications' => $this->data['failed_verifications'],
            'success_rate' => round(($this->data['successful_verifications'] / $totalVerified) * 100, 2),
            'expired_codes' => $this->data['expired_codes'],
            'blocked_attempts' => $this->data['blocked_attempts'],
            'unique_identifiers' => count($this->data['by_identifier']),
            'suspicious_accounts' => count($this->data['suspicious_activity']),
            'last_updated' => $this->data['last_updated'] ? date('Y-m-d H:i:s', $this->data['last_updated']) : null,
        ];
    }

    /**
     * Get statistics for a specific identifier
     * 
     * @param string $identifier User identifier
     * @return array|null Identifier statistics or null if not found
     */
    public function getIdentifierStats(string $identifier): ?array
    {
        if (!isset($this->data['by_identifier'][$identifier])) {
            return null;
        }
        
        $stats = $this->data['by_identifier'][$identifier];
        $totalAttempts = $stats['verified'] + $stats['failed'];
        $successRate = $totalAttempts > 0 ? round(($stats['verified'] / $totalAttempts) * 100, 2) : 0;
        
        return [
            'identifier' => $identifier,
            'generated' => $stats['generated'],
            'verified' => $stats['verified'],
            'failed' => $stats['failed'],
            'success_rate' => $successRate,
            'first_seen' => date('Y-m-d H:i:s', $stats['first_seen']),
            'last_seen' => date('Y-m-d H:i:s', $stats['last_seen']),
            'is_suspicious' => isset($this->data['suspicious_activity'][$identifier]),
        ];
    }

    /**
     * Get list of suspicious accounts
     * 
     * @return array List of suspicious accounts
     */
    public function getSuspiciousAccounts(): array
    {
        $suspicious = [];
        
        foreach ($this->data['suspicious_activity'] as $identifier => $info) {
            $suspicious[] = [
                'identifier' => $identifier,
                'flagged_at' => date('Y-m-d H:i:s', $info['flagged_at']),
                'failed_attempts' => $info['failed_attempts'],
                'reason' => $info['reason'],
            ];
        }
        
        return $suspicious;
    }

    /**
     * Get hourly statistics for the last N hours
     * 
     * @param int $hours Number of hours to retrieve
     * @return array Hourly statistics
     */
    public function getHourlyStats(int $hours = 24): array
    {
        $result = [];
        $now = time();
        
        for ($i = $hours - 1; $i >= 0; $i--) {
            $hourKey = date('Y-m-d H:00', $now - ($i * 3600));
            $result[$hourKey] = $this->data['hourly_stats'][$hourKey] ?? [
                'generated' => 0,
                'verified_success' => 0,
                'verified_failed' => 0,
            ];
        }
        
        return $result;
    }

    /**
     * Reset all analytics data
     */
    public function reset(): void
    {
        $this->data = $this->getEmptyDataStructure();
        $this->saveData();
    }

    /**
     * Export analytics data
     * 
     * @return array Complete analytics data
     */
    public function exportData(): array
    {
        return $this->data;
    }
}
