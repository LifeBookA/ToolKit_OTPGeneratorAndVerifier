<?php

namespace Toolkit\Otp\Notification;

/**
 * Email Notification Channel
 * 
 * Simulates sending OTP codes via email by saving to a .email file.
 * In production, integrate with a real email service (PHPMailer, SwiftMailer, etc.).
 */
class EmailNotificationChannel implements NotificationChannelInterface
{
    /**
     * @var string Directory to store simulated emails
     */
    private string $storageDir;
    
    /**
     * @var string|null Real email service callback (optional)
     */
    private $sendCallback;

    /**
     * Constructor
     * 
     * @param string|null $storageDir Directory for storing simulated emails
     */
    public function __construct(?string $storageDir = null)
    {
        $this->storageDir = $storageDir ?? __DIR__ . '/../../../otp_notifications/email';
        
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    /**
     * Set a callback for real email sending (optional)
     * 
     * @param callable $callback Function that receives ($to, $subject, $body)
     */
    public function setSendCallback(callable $callback): void
    {
        $this->sendCallback = $callback;
    }

    /**
     * Send OTP code via email
     * 
     * @param string $recipient Email address
     * @param string $identifier User identifier
     * @param string $code OTP code
     * @param array $context Additional context data
     * @return bool True if sent successfully
     */
    public function send(string $recipient, string $identifier, string $code, array $context = []): bool
    {
        $subject = $context['subject'] ?? 'Your Verification Code';
        $template = $context['template'] ?? 'default';
        
        // Generate email body
        $body = $this->generateEmailBody($code, $identifier, $template, $context);
        
        // If a real send callback is provided, use it
        if ($this->sendCallback !== null) {
            try {
                call_user_func($this->sendCallback, $recipient, $subject, $body);
                return true;
            } catch (\Exception $e) {
                // Fall through to simulation mode
            }
        }
        
        // Simulation mode: save to file
        return $this->simulateSend($recipient, $subject, $body);
    }

    /**
     * Generate email body based on template
     * 
     * @param string $code OTP code
     * @param string $identifier User identifier
     * @param string $template Template name
     * @param array $context Additional context
     * @return string Email body
     */
    private function generateEmailBody(string $code, string $identifier, string $template, array $context): string
    {
        switch ($template) {
            case 'minimal':
                return "Your verification code is: $code";
                
            case 'html':
                return $this->generateHtmlEmail($code, $context);
                
            case 'default':
            default:
                return $this->generateDefaultEmail($code, $identifier, $context);
        }
    }

    /**
     * Generate default email body
     */
    private function generateDefaultEmail(string $code, string $identifier, array $context): string
    {
        $serviceName = $context['service_name'] ?? 'Our Service';
        $expiryMinutes = $context['expiry_minutes'] ?? 5;
        
        return <<<EMAIL
Hello,

You requested a verification code for $serviceName.

Your verification code is: $code

This code will expire in $expiryMinutes minutes.

If you did not request this code, please ignore this email.

Best regards,
$serviceName Team
EMAIL;
    }

    /**
     * Generate HTML email body
     */
    private function generateHtmlEmail(string $code, array $context): string
    {
        $serviceName = $context['service_name'] ?? 'Our Service';
        $primaryColor = $context['primary_color'] ?? '#4F46E5';
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: $primaryColor; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
        .code { font-size: 32px; font-weight: bold; letter-spacing: 5px; color: $primaryColor; text-align: center; padding: 20px; background: white; border: 2px dashed $primaryColor; margin: 20px 0; border-radius: 4px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Verification Code</h1>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>Your verification code for <strong>$serviceName</strong> is:</p>
            <div class="code">$code</div>
            <p>This code will expire in 5 minutes.</p>
            <p>If you did not request this code, please ignore this email.</p>
        </div>
        <div class="footer">
            <p>&copy; $serviceName. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Simulate sending email by saving to file
     * 
     * @param string $recipient Email address
     * @param string $subject Email subject
     * @param string $body Email body
     * @return bool True if saved successfully
     */
    private function simulateSend(string $recipient, string $subject, string $body): bool
    {
        $filename = $this->storageDir . DIRECTORY_SEPARATOR . 
                    'email_' . date('Y-m-d_H-i-s') . '_' . md5($recipient) . '.email';
        
        $content = "To: $recipient\n";
        $content .= "Subject: $subject\n";
        $content .= "Date: " . date('Y-m-d H:i:s') . "\n";
        $content .= "---\n";
        $content .= $body;
        
        return file_put_contents($filename, $content) !== false;
    }

    /**
     * Get the channel name
     * 
     * @return string Channel name
     */
    public function getChannelName(): string
    {
        return 'email';
    }

    /**
     * Check if this channel is available
     * 
     * @return bool Always true for email (simulation mode)
     */
    public function isAvailable(): bool
    {
        return is_writable($this->storageDir);
    }
}
