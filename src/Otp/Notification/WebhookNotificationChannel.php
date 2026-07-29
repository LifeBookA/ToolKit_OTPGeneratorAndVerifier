<?php

namespace Toolkit\Otp\Notification;

/**
 * Webhook Notification Channel
 * 
 * Sends OTP codes to a webhook URL via HTTP POST request.
 * Useful for integrating with external services or custom notification systems.
 */
class WebhookNotificationChannel implements NotificationChannelInterface
{
    /**
     * @var string Webhook URL
     */
    private string $webhookUrl;
    
    /**
     * @var array Default headers for HTTP requests
     */
    private array $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    
    /**
     * @var int Request timeout in seconds
     */
    private int $timeout = 30;
    
    /**
     * @var bool Verify SSL certificates
     */
    private bool $verifySsl = true;

    /**
     * Constructor
     * 
     * @param string $webhookUrl Webhook URL endpoint
     */
    public function __construct(string $webhookUrl)
    {
        $this->webhookUrl = $webhookUrl;
    }

    /**
     * Set additional HTTP headers
     * 
     * @param array $headers Array of header strings
     * @return self
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * Set authorization header (Bearer token)
     * 
     * @param string $token Bearer token
     * @return self
     */
    public function setBearerToken(string $token): self
    {
        $this->headers[] = 'Authorization: Bearer ' . $token;
        return $this;
    }

    /**
     * Set API key header
     * 
     * @param string $apiKey API key
     * @param string $headerName Header name (default: 'X-API-Key')
     * @return self
     */
    public function setApiKey(string $apiKey, string $headerName = 'X-API-Key'): self
    {
        $this->headers[] = "$headerName: $apiKey";
        return $this;
    }

    /**
     * Set request timeout
     * 
     * @param int $seconds Timeout in seconds
     * @return self
     */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Enable/disable SSL verification
     * 
     * @param bool $verify Verify SSL certificates
     * @return self
     */
    public function setSslVerification(bool $verify): self
    {
        $this->verifySsl = $verify;
        return $this;
    }

    /**
     * Send OTP code via webhook
     * 
     * @param string $recipient Recipient identifier (phone, email, etc.)
     * @param string $identifier User identifier
     * @param string $code OTP code
     * @param array $context Additional context data
     * @return bool True if sent successfully
     */
    public function send(string $recipient, string $identifier, string $code, array $context = []): bool
    {
        $payload = array_merge([
            'event' => 'otp_generated',
            'recipient' => $recipient,
            'identifier' => $identifier,
            'code' => $code,
            'timestamp' => time(),
            'timestamp_iso' => date('c'),
        ], $context);
        
        return $this->sendRequest($payload);
    }

    /**
     * Send HTTP POST request to webhook
     * 
     * @param array $payload Request payload
     * @return bool True if request was successful (2xx status code)
     */
    private function sendRequest(array $payload): bool
    {
        $ch = curl_init($this->webhookUrl);
        
        if ($ch === false) {
            return false;
        }
        
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => $this->verifySsl,
            CURLOPT_SSL_VERIFYHOST => $this->verifySsl ? 2 : 0,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        // Consider 2xx status codes as success
        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }
        
        // Log error for debugging (in production, use proper logging)
        error_log("Webhook notification failed: HTTP $httpCode - $error");
        
        return false;
    }

    /**
     * Send a custom payload to the webhook
     * 
     * @param array $payload Custom payload
     * @return bool True if sent successfully
     */
    public function sendCustom(array $payload): bool
    {
        return $this->sendRequest($payload);
    }

    /**
     * Get the channel name
     * 
     * @return string Channel name
     */
    public function getChannelName(): string
    {
        return 'webhook';
    }

    /**
     * Check if this channel is available
     * 
     * @return bool True if webhook URL is valid and reachable
     */
    public function isAvailable(): bool
    {
        // Basic URL validation
        if (!filter_var($this->webhookUrl, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // Try to reach the webhook with a HEAD request
        $ch = curl_init($this->webhookUrl);
        
        if ($ch === false) {
            return false;
        }
        
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => $this->verifySsl,
            CURLOPT_RETURNTRANSFER => true,
        ]);
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Consider any response (even 4xx) as "available"
        // Only return false on connection errors
        return $httpCode > 0;
    }

    /**
     * Get the webhook URL
     * 
     * @return string Webhook URL
     */
    public function getWebhookUrl(): string
    {
        return $this->webhookUrl;
    }
}
