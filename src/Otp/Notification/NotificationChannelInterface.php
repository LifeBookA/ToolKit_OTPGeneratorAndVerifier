<?php

declare(strict_types=1);

namespace Toolkit\Otp\Notification;

/**
 * Interface for OTP notification channels.
 * 
 * Provides a contract for sending OTP codes through various channels
 * such as SMS, email, push notifications, etc.
 * 
 * @package Toolkit\Otp\Notification
 * @author Toolkit Team
 * @since 2.0.0
 */
interface NotificationChannelInterface
{
    /**
     * Send an OTP code to a recipient.
     *
     * @param string $recipient The recipient (email, phone number, etc.).
     * @param string $code The OTP code to send.
     * @param array $context Additional context (e.g., expiry time, identifier).
     * @return bool True if sent successfully, false otherwise.
     */
    public function send(string $recipient, string $code, array $context = []): bool;

    /**
     * Get the channel name/type.
     *
     * @return string
     */
    public function getChannelName(): string;

    /**
     * Check if the channel is available/configured.
     *
     * @return bool
     */
    public function isAvailable(): bool;
}
