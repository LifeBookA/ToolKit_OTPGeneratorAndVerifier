<?php

/**
 * Base Test Case for Toolkit OTP Unit Tests
 * 
 * Provides common assertion methods and test utilities.
 * 
 * @package Toolkit\Tests
 * @version 3.0.0
 */

namespace Toolkit\Tests;

abstract class TestCase
{
    protected static int $passedTests = 0;
    protected static int $failedTests = 0;
    protected static array $testResults = [];

    /**
     * Assert that two values are equal
     */
    public static function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected === $actual) {
            self::$passedTests++;
            self::printResult(true, $message);
        } else {
            self::$failedTests++;
            self::printResult(false, $message . " (Expected: " . var_export($expected, true) . ", Got: " . var_export($actual, true) . ")");
        }
    }

    /**
     * Assert that a condition is true
     */
    public static function assertTrue(bool $condition, string $message = ''): void
    {
        if ($condition) {
            self::$passedTests++;
            self::printResult(true, $message);
        } else {
            self::$failedTests++;
            self::printResult(false, $message);
        }
    }

    /**
     * Assert that a condition is false
     */
    public static function assertFalse(bool $condition, string $message = ''): void
    {
        if (!$condition) {
            self::$passedTests++;
            self::printResult(true, $message);
        } else {
            self::$failedTests++;
            self::printResult(false, $message);
        }
    }

    /**
     * Assert that a value is null
     */
    public static function assertNull(mixed $value, string $message = ''): void
    {
        if ($value === null) {
            self::$passedTests++;
            self::printResult(true, $message);
        } else {
            self::$failedTests++;
            self::printResult(false, $message);
        }
    }

    /**
     * Assert that a value is not null
     */
    public static function assertNotNull(mixed $value, string $message = ''): void
    {
        if ($value !== null) {
            self::$passedTests++;
            self::printResult(true, $message);
        } else {
            self::$failedTests++;
            self::printResult(false, $message);
        }
    }

    /**
     * Assert that an exception is thrown
     */
    public static function assertThrows(callable $callback, string $exceptionClass, string $message = ''): void
    {
        try {
            $callback();
            self::$failedTests++;
            self::printResult(false, $message . " (Expected exception {$exceptionClass} but none was thrown)");
        } catch (\Throwable $e) {
            if ($e instanceof $exceptionClass) {
                self::$passedTests++;
                self::printResult(true, $message);
            } else {
                self::$failedTests++;
                self::printResult(false, $message . " (Expected {$exceptionClass}, got " . get_class($e) . ")");
            }
        }
    }

    /**
     * Print test result
     */
    private static function printResult(bool $passed, string $message): void
    {
        $status = $passed ? '✅' : '❌';
        $color = $passed ? "\033[32m" : "\033[31m";
        $reset = "\033[0m";
        echo "{$color}{$status} {$message}{$reset}\n";
        self::$testResults[] = ['passed' => $passed, 'message' => $message];
    }

    /**
     * Get test statistics
     */
    public static function getStats(): array
    {
        return [
            'passed' => self::$passedTests,
            'failed' => self::$failedTests,
            'total' => self::$passedTests + self::$failedTests
        ];
    }

    /**
     * Reset test statistics
     */
    public static function reset(): void
    {
        self::$passedTests = 0;
        self::$failedTests = 0;
        self::$testResults = [];
    }
}
