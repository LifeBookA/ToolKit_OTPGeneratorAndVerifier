<?php

/**
 * Test Runner for Toolkit OTP Unit Tests
 * 
 * Runs all test suites and provides a summary report.
 * 
 * @package Toolkit\Tests
 * @version 3.0.0
 */

namespace Toolkit\Tests;

require_once __DIR__ . '/TestCase.php';

echo "\n";
echo "============================================================\n";
echo "🧪 Running Toolkit OTP Unit Tests...\n";
echo "============================================================\n";
echo "\n";

$startTime = microtime(true);

// Run all test suites
require_once __DIR__ . '/GeneratorTests.php';
require_once __DIR__ . '/StorageTests.php';
require_once __DIR__ . '/VerifierTests.php';

$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

// Get final statistics
$stats = TestCase::getStats();

echo "\n";
echo "============================================================\n";
echo "📊 Test Summary\n";
echo "============================================================\n";
echo "\n";

$total = $stats['total'];
$passed = $stats['passed'];
$failed = $stats['failed'];

if ($failed === 0) {
    echo "\033[32m✅ All tests passed! ({$passed}/{$total})\033[0m\n";
} else {
    echo "\033[31m❌ Some tests failed! ({$passed}/{$total} passed, {$failed} failed)\033[0m\n";
}

echo "Time: {$duration}s\n";
echo "\n";
echo "============================================================\n";

exit($failed > 0 ? 1 : 0);
