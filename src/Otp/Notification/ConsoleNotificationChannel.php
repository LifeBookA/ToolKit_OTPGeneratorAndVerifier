<?php

declare(strict_types=1);

namespace Toolkit\Otp\Notification;

/**
 * Console/CLI notification channel for testing and development.
 * 
 * Outputs OTP codes to the console instead of sending them via SMS or email.
 * Useful for local development and testing environments.
 * 
 * @package Toolkit\Otp\Notification
 * @author Toolkit Team
 * @since 2.0.0
 */
class ConsoleNotificationChannel implements NotificationChannelInterface
{
    /**
     * @var bool Whether to include context information in output.
     */
    private bool $verbose;

    /**
     * Constructor.
     *
     * @param bool $verbose Include additional context in output.
     */
    public function __construct(bool $verbose = true)
    {
        $this->verbose = $verbose;
    }

    /**
     * {@inheritdoc}
     */
    public function send(string $recipient, string $code, array $context = []): bool
    {
        $output = PHP_EOL;
        $output .= "╔══════════════════════════════════════════════════════════╗" . PHP_EOL;
        $output .= "║  🔐 OTP Code Generated (Console Channel)                 ║" . PHP_EOL;
        $output .= "╠══════════════════════════════════════════════════════════╣" . PHP_EOL;
        $output .= sprintf("║  Recipient: %-49s ║", substr($recipient, 0, 49)) . PHP_EOL;
        $output .= sprintf("║  Code: %-52s ║", $code) . PHP_EOL;
        
        if ($this->verbose && !empty($context)) {
            $output .= "╠──────────────────────────────────────────────────────────╣" . PHP_EOL;
            
            if (isset($context['expiry'])) {
                $expiryTime = date('Y-m-d H:i:s', $context['expiry']);
                $output .= sprintf("║  Expires: %-49s ║", $expiryTime) . PHP_EOL;
            }
            
            if (isset($context['ttl'])) {
                $output .= sprintf("║  TTL: %-53d ║", $context['ttl']) . PHP_EOL;
            }
            
            if (isset($context['identifier'])) {
                $output .= sprintf("║  Identifier: %-46s ║", substr($context['identifier'], 0, 46)) . PHP_EOL;
            }
        }
        
        $output .= "╚══════════════════════════════════════════════════════════╝" . PHP_EOL;
        $output .= PHP_EOL;

        echo $output;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getChannelName(): string
    {
        return 'console';
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        return PHP_SAPI === 'cli';
    }

    /**
     * Set verbose mode.
     *
     * @param bool $verbose
     * @return void
     */
    public function setVerbose(bool $verbose): void
    {
        $this->verbose = $verbose;
    }

    /**
     * Get verbose mode setting.
     *
     * @return bool
     */
    public function isVerbose(): bool
    {
        return $this->verbose;
    }
}
