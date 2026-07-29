<?php

namespace Toolkit\Tests;

/**
 * Unit Tests for OTP Storage Implementations
 * 
 * @package Toolkit\Tests
 * @version 3.0.2
 */

require_once __DIR__ . '/../src/Bootstrap.php';
require_once __DIR__ . '/TestCase.php';

use Toolkit\Otp\Storage\FileOtpStorage;
use Toolkit\Otp\Config\OtpConfig;

class StorageTests extends TestCase
{
    public static function runAll(): void
    {
        echo "\n\033[1m=== Storage Tests ===\033[0m\n\n";
        
        self::testFileStorageSaveAndGet();
        self::testFileStorageExpiration();
        self::testFileStorageIncrementAttempts();
        self::testFileStorageDelete();
    }

    private static function testFileStorageSaveAndGet(): void
    {
        $storage = new FileOtpStorage();
        $identifier = 'test_storage_' . time();
        $code = '123456';
        $ttl = 300;
        
        $storage->save($identifier, $code, $ttl);
        $data = $storage->get($identifier);
        
        self::assertNotNull($data, "FileOtpStorage::testSaveAndGet");
        self::assertEquals($code, $data['code'], "FileOtpStorage::testCodeMatches");
    }

    private static function testFileStorageExpiration(): void
    {
        $storage = new FileOtpStorage();
        $identifier = 'test_expiry_' . time();
        $code = '654321';
        $ttl = 1; // 1 second TTL
        
        $storage->save($identifier, $code, $ttl);
        sleep(2); // Wait for expiration
        $data = $storage->get($identifier);
        
        self::assertNull($data, "FileOtpStorage::testExpiration");
    }

    private static function testFileStorageIncrementAttempts(): void
    {
        $storage = new FileOtpStorage();
        $identifier = 'test_attempts_' . time();
        $code = '111111';
        $ttl = 300;
        
        $storage->save($identifier, $code, $ttl);
        $attempts1 = $storage->incrementAttempts($identifier);
        $attempts2 = $storage->incrementAttempts($identifier);
        
        self::assertEquals(1, $attempts1, "FileOtpStorage::testIncrementAttemptsFirst");
        self::assertEquals(2, $attempts2, "FileOtpStorage::testIncrementAttemptsSecond");
    }

    private static function testFileStorageDelete(): void
    {
        $storage = new FileOtpStorage();
        $identifier = 'test_delete_' . time();
        $code = '999999';
        $ttl = 300;
        
        $storage->save($identifier, $code, $ttl);
        $existsBefore = $storage->exists($identifier);
        $storage->delete($identifier);
        $existsAfter = $storage->exists($identifier);
        
        self::assertTrue($existsBefore, "FileOtpStorage::testExistsBeforeDelete");
        self::assertFalse($existsAfter, "FileOtpStorage::testNotExistsAfterDelete");
    }
}

StorageTests::runAll();
