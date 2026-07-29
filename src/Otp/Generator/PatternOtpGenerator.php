<?php

declare(strict_types=1);

namespace Toolkit\Otp\Generator;

/**
 * Custom pattern-based OTP generator.
 * 
 * Generates OTP codes based on a custom pattern string.
 * Pattern tokens:
 *   - # : Random digit (0-9)
 *   - @ : Random uppercase letter (A-Z)
 *   - * : Random alphanumeric (A-Z, 0-9)
 *   - Any other character is treated as a literal.
 * 
 * Example patterns:
 *   - "######" => "123456"
 *   - "###-###" => "123-456"
 *   - "@@####" => "AB1234"
 *   - "OTP-####" => "OTP-5678"
 * 
 * @package Toolkit\Otp\Generator
 * @author Toolkit Team
 * @since 2.0.0
 */
class PatternOtpGenerator implements OtpGeneratorInterface
{
    /**
     * @var string The pattern to use for code generation.
     */
    private string $pattern;

    /**
     * Constructor.
     *
     * @param string $pattern The pattern string (default: "######").
     */
    public function __construct(string $pattern = '######')
    {
        $this->pattern = $pattern;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(int $length = null): string
    {
        // If length is provided and different from pattern, adjust pattern
        if ($length !== null && strlen($this->pattern) !== $length) {
            // Create a new pattern with the specified length using digits
            $this->pattern = str_repeat('#', $length);
        }

        return $this->generateFromPattern($this->pattern);
    }

    /**
     * Generate a code from the pattern.
     *
     * @param string $pattern
     * @return string
     */
    private function generateFromPattern(string $pattern): string
    {
        $result = '';
        $patternLength = strlen($pattern);

        for ($i = 0; $i < $patternLength; $i++) {
            $char = $pattern[$i];

            switch ($char) {
                case '#':
                    // Random digit (0-9)
                    $result .= (string)random_int(0, 9);
                    break;

                case '@':
                    // Random uppercase letter (A-Z)
                    $result .= chr(random_int(65, 90)); // ASCII A-Z
                    break;

                case '*':
                    // Random alphanumeric (A-Z, 0-9)
                    $alphanumeric = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                    $result .= $alphanumeric[random_int(0, strlen($alphanumeric) - 1)];
                    break;

                default:
                    // Literal character
                    $result .= $char;
                    break;
            }
        }

        return $result;
    }

    /**
     * Set a new pattern.
     *
     * @param string $pattern
     * @return void
     */
    public function setPattern(string $pattern): void
    {
        $this->pattern = $pattern;
    }

    /**
     * Get the current pattern.
     *
     * @return string
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * Get the effective length of the generated code (including literals).
     *
     * @return int
     */
    public function getEffectiveLength(): int
    {
        return strlen($this->pattern);
    }

    /**
     * Get the number of random characters in the pattern.
     *
     * @return int
     */
    public function getRandomCharCount(): int
    {
        $count = 0;
        $patternLength = strlen($this->pattern);

        for ($i = 0; $i < $patternLength; $i++) {
            $char = $this->pattern[$i];
            if ($char === '#' || $char === '@' || $char === '*') {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Validate a pattern string.
     *
     * @param string $pattern
     * @return bool True if pattern is valid.
     */
    public static function isValidPattern(string $pattern): bool
    {
        if (empty($pattern)) {
            return false;
        }

        // Check if pattern contains at least one random token
        $hasRandomToken = preg_match('/[#@\*]/', $pattern);
        
        return $hasRandomToken === 1;
    }

    /**
     * Create a pattern with a specific format.
     *
     * @param int $digitCount Number of random digits.
     * @param int $letterCount Number of random letters.
     * @param string $separator Optional separator between groups.
     * @return string Generated pattern.
     */
    public static function createPattern(
        int $digitCount = 6,
        int $letterCount = 0,
        string $separator = ''
    ): string {
        $pattern = '';

        if ($letterCount > 0) {
            $pattern .= str_repeat('@', $letterCount);
        }

        if ($digitCount > 0) {
            if (!empty($pattern)) {
                $pattern .= $separator;
            }
            $pattern .= str_repeat('#', $digitCount);
        }

        return $pattern;
    }

    /**
     * Create a grouped pattern (e.g., "######" => "###-###").
     *
     * @param int $totalLength Total number of random digits.
     * @param int $groupSize Size of each group.
     * @param string $separator Separator between groups.
     * @return string Generated pattern.
     */
    public static function createGroupedPattern(
        int $totalLength = 6,
        int $groupSize = 3,
        string $separator = '-'
    ): string {
        $groups = [];
        $remaining = $totalLength;

        while ($remaining > 0) {
            $currentGroupSize = min($groupSize, $remaining);
            $groups[] = str_repeat('#', $currentGroupSize);
            $remaining -= $currentGroupSize;
        }

        return implode($separator, $groups);
    }
}
