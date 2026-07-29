<?php

namespace Toolkit\Tests;

/**
 * Unit Tests for OTP Verifier
 * 
 * @package Toolkit\Tests
 * @version 3.0.2
 */

require_once __DIR__ . '/../src/Bootstrap.php';
require_once __DIR__ . '/TestCase.php';

use Toolkit\Otp\OtpManager;
use Toolkit\Otp\Storage\FileOtpStorage;
use Toolkit\Otp\Verifier\StandardOtpVerifier;

class VerifierTests extends TestCase
{
    public static function runAll(): void
    {
        echo "\n\033[1m=== Verifier Tests ===\033[0m\n\n";
        
        self::testSuccessfulVerification();
        self::testFailedVerification();
        self::testExpiredCode();
        self::testBlockedAfterMaxAttempts();
    }

    private static function testSuccessfulVerification(): void
    {
        $storage = new FileOtpStorage();
        $identifier = 'test_success_' . time();
        $code = '555666';
        $ttl = 300;
        
        // Save code directly
        $storage->save($identifier, $code, $ttl);
        
        // Verify with correct code
        $verifier = new StandardOtpVerifier();
        $result = $verifier->verify($identifier, $code, $storage);
        
        self::assertTrue($result->isValid, "StandardOtpVerifier::testSuccessfulVerification");
        self::assertEquals('success', $result->status, "StandardOtpVerifier::testSuccessStatus");
    }

    private static function testFailedVerification(): void
    {
        $storage = new FileOtpStorage();
        $identifier = 'test_fail_' . time();
        $storedCode = '777888';
        $wrongCode = '000000';
        $ttl = 300;
        
        $storage->save($identifier, $storedCode, $ttl);
        
        $verifier = new StandardOtpVerifier();
        $result = $verifier->verify($identifier, $wrongCode, $storage);
        
        self::assertFalse($result->isValid, "StandardOtpVerifier::testFailedVerification");
        self::assertEquals('invalid', $result->status, "StandardOtpVerifier::testInvalidStatus");
    }

    private static function testExpiredCode(): void
    {
        $storage = new FileOtpStorage();
        $identifier = 'test_expired_' . time();
        $code = '999000';
        $ttl = 1; // 1 second TTL
        
        $storage->save($identifier, $code, $ttl);
        sleep(2); // Wait for expiration
        
        $verifier = new StandardOtpVerifier();
        $result = $verifier->verify($identifier, $code, $storage);
        
        self::assertFalse($result->isValid, "StandardOtpVerifier::testExpiredCode");
        // After expiration, the file is deleted so status becomes 'not_found'
        self::assertTrue(in_array($result->status, ['expired', 'not_found']), "StandardOtpVerifier::testExpiredOrNotFoundStatus");
    }

    private static function testBlockedAfterMaxAttempts(): void
    {
        $storage = new FileOtpStorage();
        $identifier = 'test_blocked_' . time();
        $storedCode = '111222';
        $wrongCode = '000000';
        $ttl = 300;
        
        $storage->save($identifier, $storedCode, $ttl);
        
        // Make 5 wrong attempts (max attempts)
        $verifier = new StandardOtpVerifier();
        for ($i = 0; $i < 5; $i++) {
            $verifier->verify($identifier, $wrongCode, $storage);
        }
        
        // Try one more time - should be blocked
        $result = $verifier->verify($identifier, $wrongCode, $storage);
        
        self::assertFalse($result->isValid, "StandardOtpVerifier::testBlockedAfterMaxAttempts");
        self::assertEquals('blocked', $result->status, "StandardOtpVerifier::testBlockedStatus");
    }
}

VerifierTests::runAll();
