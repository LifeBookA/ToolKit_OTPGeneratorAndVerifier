<?php

namespace Toolkit\Otp\Generator;

/**
 * Custom Charset OTP Generator
 * 
 * Generates OTP codes using a custom character set provided by the user.
 */
class CustomCharsetGenerator implements OtpGeneratorInterface
{
    /**
     * @var string Custom character set
     */
    private string $charset;
    
    /**
     * @var int Minimum length
     */
    private const MIN_LENGTH = 4;
    
    /**
     * @var int Maximum length
     */
    private const MAX_LENGTH = 32;

    /**
     * Constructor
     * 
     * @param string|null $charset Custom character set (default: alphanumeric uppercase)
     */
    public function __construct(?string $charset = null)
    {
        $this->charset = $charset ?? 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        
        if (strlen($this->charset) < 2) {
            throw new \InvalidArgumentException("Charset must contain at least 2 characters");
        }
    }

    /**
     * Generate an OTP code using the custom charset
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
        $charLength = strlen($this->charset);

        for ($i = 0; $i < $length; $i++) {
            $randomIndex = random_int(0, $charLength - 1);
            $code .= $this->charset[$randomIndex];
        }

        return $code;
    }

    /**
     * Get the current charset
     * 
     * @return string Current character set
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * Set a new charset
     * 
     * @param string $charset New character set
     * @return self
     */
    public function setCharset(string $charset): self
    {
        if (strlen($charset) < 2) {
            throw new \InvalidArgumentException("Charset must contain at least 2 characters");
        }
        
        $this->charset = $charset;
        return $this;
    }
}
