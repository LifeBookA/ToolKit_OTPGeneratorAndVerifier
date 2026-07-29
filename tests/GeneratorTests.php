<?php

/**
 * Unit Tests for OTP Generators
 * 
 * @package Toolkit\Tests
 * @version 3.0.0
 */

namespace Toolkit\Tests;

require_once __DIR__ . '/../src/Bootstrap.php';

use Toolkit\Otp\Generator\NumericOtpGenerator;
use Toolkit\Otp\Generator\AlphaNumericOtpGenerator;
use Toolkit\Otp\Generator\PatternOtpGenerator;

class GeneratorTests extends TestCase
{
    public static function runAll(): void
    {
        echo "\n\033[1m=== Generator Tests ===\033[0m\n\n";
        
        self::testNumericGeneratorLength();
        self::testNumericGeneratorRange();
        self::testNumericGeneratorUniqueness();
        self::testAlphaNumericGeneratorLength();
        self::testAlphaNumericGeneratorCharset();
        self::testPatternGeneratorWithHash();
        self::testPatternGeneratorWithSeparator();
    }

    private static function testNumericGeneratorLength(): void
    {
        $generator = new NumericOtpGenerator();
        $code = $generator->generate(6);
        self::assertEquals(6, strlen($code), "NumericOtpGenerator::testGenerateValidCode");
    }

    private static function testNumericGeneratorRange(): void
    {
        $generator = new NumericOtpGenerator();
        $code = $generator->generate(6);
        $isNumeric = ctype_digit($code);
        self::assertTrue($isNumeric, "NumericOtpGenerator::testGenerateNumericOnly");
    }

    private static function testNumericGeneratorUniqueness(): void
    {
        $generator = new NumericOtpGenerator();
        $codes = [];
        for ($i = 0; $i < 100; $i++) {
            $codes[] = $generator->generate(6);
        }
        $uniqueCodes = array_unique($codes);
        self::assertTrue(count($uniqueCodes) > 90, "NumericOtpGenerator::testGenerateUniqueCodes");
    }

    private static function testAlphaNumericGeneratorLength(): void
    {
        $generator = new AlphaNumericOtpGenerator();
        $code = $generator->generate(8);
        self::assertEquals(8, strlen($code), "AlphaNumericOtpGenerator::testGenerateValidCode");
    }

    private static function testAlphaNumericGeneratorCharset(): void
    {
        $generator = new AlphaNumericOtpGenerator();
        $code = $generator->generate(10);
        $isValid = ctype_alnum($code) && strtoupper($code) === $code;
        self::assertTrue($isValid, "AlphaNumericOtpGenerator::testGenerateUppercaseAlphanumeric");
    }

    private static function testPatternGeneratorWithHash(): void
    {
        $generator = new PatternOtpGenerator('######');
        $code = $generator->generate();
        $isNumeric = ctype_digit($code) && strlen($code) === 6;
        self::assertTrue($isNumeric, "PatternOtpGenerator::testHashPatternGeneratesDigits");
    }

    private static function testPatternGeneratorWithSeparator(): void
    {
        $generator = new PatternOtpGenerator('###-###');
        $code = $generator->generate();
        // Should produce something like "123-456"
        $parts = explode('-', $code);
        $validPattern = (count($parts) === 2 && strlen($parts[0]) === 3 && ctype_digit($parts[0]) && strlen($parts[1]) === 3 && ctype_digit($parts[1]));
        self::assertTrue($validPattern, "PatternOtpGenerator::testPatternWithSeparator");
    }
}

GeneratorTests::runAll();
