<?php

namespace Toolkit\Otp\Notification;

/**
 * SMS Notification Channel
 * 
 * Simulates sending OTP codes via SMS by saving to a .sms file.
 * In production, integrate with a real SMS service (Twilio, Kavehnegar, etc.).
 */
class SmsNotificationChannel implements NotificationChannelInterface
{
    /**
     * @var string Directory to store simulated SMS messages
     */
    private string $storageDir;
    
    /**
     * @var string|null Real SMS service callback (optional)
     */
    private $sendCallback;

    /**
     * Constructor
     * 
     * @param string|null $storageDir Directory for storing simulated SMS
     */
    public function __construct(?string $storageDir = null)
    {
        $this->storageDir = $storageDir ?? __DIR__ . '/../../../otp_notifications/sms';
        
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    /**
     * Set a callback for real SMS sending (optional)
     * 
     * @param callable $callback Function that receives ($phoneNumber, $message)
     */
    public function setSendCallback(callable $callback): void
    {
        $this->sendCallback = $callback;
    }

    /**
     * Send OTP code via SMS
     * 
     * @param string $recipient Phone number
     * @param string $identifier User identifier
     * @param string $code OTP code
     * @param array $context Additional context data
     * @return bool True if sent successfully
     */
    public function send(string $recipient, string $identifier, string $code, array $context = []): bool
    {
        $template = $context['template'] ?? 'default';
        
        // Generate SMS message
        $message = $this->generateSmsMessage($code, $template, $context);
        
        // If a real send callback is provided, use it
        if ($this->sendCallback !== null) {
            try {
                call_user_func($this->sendCallback, $recipient, $message);
                return true;
            } catch (\Exception $e) {
                // Fall through to simulation mode
            }
        }
        
        // Simulation mode: save to file
        return $this->simulateSend($recipient, $message);
    }

    /**
     * Generate SMS message based on template
     * 
     * @param string $code OTP code
     * @param string $template Template name
     * @param array $context Additional context
     * @return string SMS message
     */
    private function generateSmsMessage(string $code, string $template, array $context): string
    {
        switch ($template) {
            case 'minimal':
                return "Code: $code";
                
            case 'persian':
                return $this->generatePersianSms($code, $context);
                
            case 'default':
            default:
                return $this->generateDefaultSms($code, $context);
        }
    }

    /**
     * Generate default SMS message
     */
    private function generateDefaultSms(string $code, array $context): string
    {
        $serviceName = $context['service_name'] ?? 'Our Service';
        $expiryMinutes = $context['expiry_minutes'] ?? 5;
        
        return "Your $serviceName verification code: $code (Valid for $expiryMinutes min)";
    }

    /**
     * Generate Persian SMS message
     */
    private function generatePersianSms(string $code, array $context): string
    {
        $serviceName = $context['service_name'] ?? 'سرویس ما';
        $expiryMinutes = $context['expiry_minutes'] ?? ۵;
        
        return "کد تأیید $serviceName: $code\n(معتبر به مدت $expiryMinutes دقیقه)";
    }

    /**
     * Simulate sending SMS by saving to file
     * 
     * @param string $recipient Phone number
     * @param string $message SMS message
     * @return bool True if saved successfully
     */
    private function simulateSend(string $recipient, string $message): bool
    {
        $filename = $this->storageDir . DIRECTORY_SEPARATOR . 
                    'sms_' . date('Y-m-d_H-i-s') . '_' . md5($recipient) . '.sms';
        
        $content = "To: $recipient\n";
        $content .= "Date: " . date('Y-m-d H:i:s') . "\n";
        $content .= "---\n";
        $content .= $message;
        
        return file_put_contents($filename, $content) !== false;
    }

    /**
     * Get the channel name
     * 
     * @return string Channel name
     */
    public function getChannelName(): string
    {
        return 'sms';
    }

    /**
     * Check if this channel is available
     * 
     * @return bool Always true for SMS (simulation mode)
     */
    public function isAvailable(): bool
    {
        return is_writable($this->storageDir);
    }
}
