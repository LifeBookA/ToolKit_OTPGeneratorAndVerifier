<?php

namespace Toolkit\Otp\Helpers;

/**
 * Backup Helper for OTP Storage
 * 
 * Provides backup and restore functionality for file-based OTP storage.
 */
class BackupHelper
{
    /**
     * @var string Source directory for OTP storage
     */
    private string $sourceDir;
    
    /**
     * @var string Backup directory
     */
    private string $backupDir;

    /**
     * Constructor
     * 
     * @param string $sourceDir Source directory containing OTP files
     * @param string|null $backupDir Backup directory (default: sourceDir/_backups)
     */
    public function __construct(string $sourceDir, ?string $backupDir = null)
    {
        $this->sourceDir = rtrim($sourceDir, DIRECTORY_SEPARATOR);
        $this->backupDir = $backupDir ?? $this->sourceDir . DIRECTORY_SEPARATOR . '_backups';
        
        // Create backup directory if it doesn't exist
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * Create a backup of all OTP files
     * 
     * @param string|null $backupName Optional backup name (default: timestamp)
     * @return string Path to the backup file
     * @throws \RuntimeException If backup fails
     */
    public function createBackup(?string $backupName = null): string
    {
        $backupName = $backupName ?? 'backup_' . date('Y-m-d_H-i-s');
        $backupFile = $this->backupDir . DIRECTORY_SEPARATOR . $backupName . '.zip';
        
        // Collect all JSON files
        $files = glob($this->sourceDir . DIRECTORY_SEPARATOR . '*.json');
        
        if (empty($files)) {
            // Create empty backup marker
            file_put_contents($backupFile . '.txt', "Empty backup - no OTP files found\nCreated: " . date('Y-m-d H:i:s'));
            return $backupFile . '.txt';
        }
        
        // Create zip archive
        $zip = new \ZipArchive();
        if ($zip->open($backupFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Failed to create backup archive");
        }
        
        foreach ($files as $file) {
            $filename = basename($file);
            $zip->addFile($file, $filename);
        }
        
        $zip->close();
        
        return $backupFile;
    }

    /**
     * Restore OTP files from a backup
     * 
     * @param string $backupFile Path to the backup file
     * @param bool $merge Merge with existing files (true) or replace all (false)
     * @return int Number of files restored
     * @throws \RuntimeException If restore fails
     */
    public function restoreBackup(string $backupFile, bool $merge = true): int
    {
        if (!file_exists($backupFile)) {
            throw new \RuntimeException("Backup file not found: $backupFile");
        }
        
        // Check if it's a zip file
        if (pathinfo($backupFile, PATHINFO_EXTENSION) === 'zip') {
            return $this->restoreFromZip($backupFile, $merge);
        }
        
        // Text backup (empty backup marker)
        return 0;
    }

    /**
     * Restore from a zip archive
     * 
     * @param string $zipFile Path to zip file
     * @param bool $merge Merge with existing files
     * @return int Number of files restored
     */
    private function restoreFromZip(string $zipFile, bool $merge): int
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipFile) !== true) {
            throw new \RuntimeException("Failed to open backup archive");
        }
        
        $count = 0;
        
        // If not merging, delete existing files first
        if (!$merge) {
            $existingFiles = glob($this->sourceDir . DIRECTORY_SEPARATOR . '*.json');
            foreach ($existingFiles as $file) {
                unlink($file);
            }
        }
        
        // Extract all files
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $fileInfo = $zip->statIndex($i);
            $filename = $fileInfo['name'];
            
            // Only extract JSON files
            if (pathinfo($filename, PATHINFO_EXTENSION) === 'json') {
                $content = $zip->getFromIndex($i);
                $targetPath = $this->sourceDir . DIRECTORY_SEPARATOR . basename($filename);
                file_put_contents($targetPath, $content);
                $count++;
            }
        }
        
        $zip->close();
        
        return $count;
    }

    /**
     * List all available backups
     * 
     * @return array List of backup files with metadata
     */
    public function listBackups(): array
    {
        $backups = [];
        $files = glob($this->backupDir . DIRECTORY_SEPARATOR . '*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $backups[] = [
                    'filename' => basename($file),
                    'path' => $file,
                    'size' => filesize($file),
                    'created' => filectime($file),
                    'modified' => filemtime($file),
                ];
            }
        }
        
        // Sort by creation time (newest first)
        usort($backups, fn($a, $b) => $b['created'] - $a['created']);
        
        return $backups;
    }

    /**
     * Delete an old backup
     * 
     * @param string $backupFilename Name of the backup file to delete
     * @return bool True if deleted successfully
     */
    public function deleteBackup(string $backupFilename): bool
    {
        $backupPath = $this->backupDir . DIRECTORY_SEPARATOR . $backupFilename;
        
        if (file_exists($backupPath) && is_file($backupPath)) {
            return unlink($backupPath);
        }
        
        return false;
    }

    /**
     * Clean up old backups (older than specified days)
     * 
     * @param int $daysKeep Number of days to keep backups
     * @return int Number of backups deleted
     */
    public function cleanupOldBackups(int $daysKeep = 30): int
    {
        $deleted = 0;
        $cutoffTime = time() - ($daysKeep * 24 * 60 * 60);
        $backups = $this->listBackups();
        
        foreach ($backups as $backup) {
            if ($backup['created'] < $cutoffTime) {
                if ($this->deleteBackup($backup['filename'])) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }

    /**
     * Get backup statistics
     * 
     * @return array Statistics about backups
     */
    public function getStats(): array
    {
        $backups = $this->listBackups();
        $totalSize = array_sum(array_column($backups, 'size'));
        
        return [
            'total_backups' => count($backups),
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'oldest_backup' => !empty($backups) ? date('Y-m-d H:i:s', end($backups)['created']) : null,
            'newest_backup' => !empty($backups) ? date('Y-m-d H:i:s', reset($backups)['created']) : null,
        ];
    }
}
