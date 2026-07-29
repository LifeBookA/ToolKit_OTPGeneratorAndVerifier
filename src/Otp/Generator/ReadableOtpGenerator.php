<?php

namespace Toolkit\Otp\Generator;

/**
 * Readable OTP Generator
 * 
 * Generates OTP codes without ambiguous characters (O, 0, I, 1, l)
 * to improve readability and reduce user errors.
 */
class ReadableOtpGenerator implements OtpGeneratorInterface
{
    /**
     * Characters that are easy to distinguish (no ambiguous chars)
     */
    private const READABLE_CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    
    /**
     * @var int Minimum length
     */
    private const MIN_LENGTH = 4;
    
    /**
     * @var int Maximum length
     */
    private const MAX_LENGTH = 16;

    /**
     * Generate a readable OTP code
     * 
     * @param int $length Length of the code to generate
     * @return string Generated OTP code
     * @throws \InvalidArgumentException If length is out of valid range
     */
    public function generate(int $length): string
    {
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                "Length must be between " . self::MIN_LENGTH . " and " . self::MAX_LENGTH
            );
        }

        $code = '';
        $charLength = strlen(self::READABLE_CHARS);

        for ($i = 0; $i < $length; $i++) {
            $randomIndex = random_int(0, $charLength - 1);
            $code .= self::READABLE_CHARS[$randomIndex];
        }

        return $code;
    }
}
