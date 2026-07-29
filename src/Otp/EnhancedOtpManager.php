<?php

declare(strict_types=1);

namespace Toolkit\Otp;

use Toolkit\Otp\Contracts\OtpInterface;
use Toolkit\Otp\Generator\OtpGeneratorInterface;
use Toolkit\Otp\Generator\NumericOtpGenerator;
use Toolkit\Otp\Storage\OtpStorageInterface;
use Toolkit\Otp\Storage\FileOtpStorage;
use Toolkit\Otp\Verifier\OtpVerifierInterface;
use Toolkit\Otp\Verifier\StandardOtpVerifier;
use Toolkit\Otp\Config\OtpConfig;
use Toolkit\Otp\Result\OtpVerificationResult;
use Toolkit\Otp\Logger\OtpLoggerInterface;
use Toolkit\Otp\Logger\FileOtpLogger;
use Toolkit\Otp\RateLimit\RateLimiterInterface;
use Toolkit\Otp\RateLimit\FileRateLimiter;
use Toolkit\Otp\Notification\NotificationChannelInterface;

/**
 * Enhanced OTP Manager with logging, rate limiting, and notification support.
 * 
 * Extends the base OtpManager functionality with:
 * - Event logging
 * - Rate limiting for OTP generation requests
 * - Multi-channel notifications
 * 
 * @package Toolkit\Otp
 * @author Toolkit Team
 * @since 2.0.0
 */
class EnhancedOtpManager implements OtpInterface
{
    /**
     * @var OtpGeneratorInterface
     */
    protected OtpGeneratorInterface $generator;

    /**
     * @var OtpStorageInterface
     */
    protected OtpStorageInterface $storage;

    /**
     * @var OtpVerifierInterface
     */
    protected OtpVerifierInterface $verifier;

    /**
     * @var OtpLoggerInterface|null
     */
    protected ?OtpLoggerInterface $logger = null;

    /**
     * @var RateLimiterInterface|null
     */
    protected ?RateLimiterInterface $rateLimiter = null;

    /**
     * @var NotificationChannelInterface[]
     */
    protected array $notificationChannels = [];

    /**
     * @var array Configuration options.
     */
    protected array $config;

    /**
     * Constructor.
     *
     * @param OtpStorageInterface|null $storage
     * @param OtpGeneratorInterface|null $generator
     * @param OtpVerifierInterface|null $verifier
     * @param OtpLoggerInterface|null $logger
     * @param RateLimiterInterface|null $rateLimiter
     * @param array $config
     */
    public function __construct(
        ?OtpStorageInterface $storage = null,
        ?OtpGeneratorInterface $generator = null,
        ?OtpVerifierInterface $verifier = null,
        ?OtpLoggerInterface $logger = null,
        ?RateLimiterInterface $rateLimiter = null,
        array $config = []
    ) {
        $this->storage = $storage ?? new FileOtpStorage();
        $this->generator = $generator ?? $this->createDefaultGenerator();
        $this->verifier = $verifier ?? new StandardOtpVerifier();
        $this->logger = $logger;
        $this->rateLimiter = $rateLimiter;
        $this->config = array_merge([
            'enable_logging' => true,
            'enable_rate_limiting' => false,
            'rate_limit_max_requests' => 5,
            'rate_limit_window' => 300,
        ], $config);

        // Initialize logger if enabled
        if ($this->config['enable_logging'] && $this->logger === null) {
            $this->logger = new FileOtpLogger();
        }

        // Initialize rate limiter if enabled
        if ($this->config['enable_rate_limiting'] && $this->rateLimiter === null) {
            $this->rateLimiter = new FileRateLimiter(
                $this->config['rate_limit_max_requests'],
                $this->config['rate_limit_window']
            );
        }
    }

    /**
     * Create the default generator based on config.
     *
     * @return OtpGeneratorInterface
     */
    protected function createDefaultGenerator(): OtpGeneratorInterface
    {
        $type = OtpConfig::getGeneratorType();
        
        if ($type === 'alphanumeric') {
            return new \Toolkit\Otp\Generator\AlphaNumericOtpGenerator();
        }
        
        return new NumericOtpGenerator();
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $identifier, int $length = null, int $ttl = null): string
    {
        // Check rate limit
        if ($this->rateLimiter !== null) {
            if (!$this->rateLimiter->isAllowed($identifier)) {
                throw new \RuntimeException(
                    'Rate limit exceeded. Please try again later.',
                    429
                );
            }
            $this->rateLimiter->recordRequest($identifier);
        }

        // Use defaults if not provided
        $length = $length ?? OtpConfig::getDefaultLength();
        $ttl = $ttl ?? OtpConfig::getDefaultTtl();

        // Generate code
        $code = $this->generator->generate($length);

        // Store OTP
        $this->storage->save($identifier, $code, $ttl);

        // Log generation
        if ($this->logger !== null) {
            $this->logger->logGeneration($identifier, $length, $ttl);
        }

        // Send notifications
        $this->sendNotifications($identifier, $code, $ttl);

        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function verify(string $identifier, string $code): OtpVerificationResult
    {
        $result = $this->verifier->verify($identifier, $code, $this->storage);

        // Log result
        if ($this->logger !== null) {
            if ($result->isValid) {
                $this->logger->logSuccess($identifier);
            } else {
                $this->logger->logFailure(
                    $identifier,
                    $result->status,
                    $result->remainingAttempts
                );
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidate(string $identifier): void
    {
        $this->storage->delete($identifier);

        if ($this->logger !== null) {
            $this->logger->logInvalidation($identifier);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRemainingAttempts(string $identifier): int
    {
        $data = $this->storage->get($identifier);
        
        if ($data === null) {
            return 0;
        }

        $maxAttempts = OtpConfig::getMaxAttempts();
        return max(0, $maxAttempts - $data['attempts']);
    }

    /**
     * Add a notification channel.
     *
     * @param NotificationChannelInterface $channel
     * @param string|null $name Optional name for the channel.
     * @return void
     */
    public function addNotificationChannel(
        NotificationChannelInterface $channel,
        ?string $name = null
    ): void {
        $key = $name ?? $channel->getChannelName();
        $this->notificationChannels[$key] = $channel;
    }

    /**
     * Remove a notification channel.
     *
     * @param string $name
     * @return void
     */
    public function removeNotificationChannel(string $name): void
    {
        unset($this->notificationChannels[$name]);
    }

    /**
     * Get all notification channels.
     *
     * @return NotificationChannelInterface[]
     */
    public function getNotificationChannels(): array
    {
        return $this->notificationChannels;
    }

    /**
     * Send OTP code through all configured notification channels.
     *
     * @param string $identifier
     * @param string $code
     * @param int $ttl
     * @return void
     */
    protected function sendNotifications(string $identifier, string $code, int $ttl): void
    {
        $context = [
            'identifier' => $identifier,
            'ttl' => $ttl,
            'expiry' => time() + $ttl,
        ];

        foreach ($this->notificationChannels as $channel) {
            if ($channel->isAvailable()) {
                $channel->send($identifier, $code, $context);
            }
        }
    }

    /**
     * Set the logger instance.
     *
     * @param OtpLoggerInterface $logger
     * @return void
     */
    public function setLogger(OtpLoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Get the logger instance.
     *
     * @return OtpLoggerInterface|null
     */
    public function getLogger(): ?OtpLoggerInterface
    {
        return $this->logger;
    }

    /**
     * Set the rate limiter instance.
     *
     * @param RateLimiterInterface $rateLimiter
     * @return void
     */
    public function setRateLimiter(RateLimiterInterface $rateLimiter): void
    {
        $this->rateLimiter = $rateLimiter;
    }

    /**
     * Get the rate limiter instance.
     *
     * @return RateLimiterInterface|null
     */
    public function getRateLimiter(): ?RateLimiterInterface
    {
        return $this->rateLimiter;
    }

    /**
     * Enable rate limiting.
     *
     * @param int $maxRequests
     * @param int $windowSeconds
     * @return void
     */
    public function enableRateLimiting(int $maxRequests = 5, int $windowSeconds = 300): void
    {
        $this->rateLimiter = new FileRateLimiter($maxRequests, $windowSeconds);
        $this->config['enable_rate_limiting'] = true;
    }

    /**
     * Disable rate limiting.
     *
     * @return void
     */
    public function disableRateLimiting(): void
    {
        $this->rateLimiter = null;
        $this->config['enable_rate_limiting'] = false;
    }

    /**
     * Check if an identifier is rate limited.
     *
     * @param string $identifier
     * @return bool
     */
    public function isRateLimited(string $identifier): bool
    {
        if ($this->rateLimiter === null) {
            return false;
        }

        return !$this->rateLimiter->isAllowed($identifier);
    }

    /**
     * Get rate limit information for an identifier.
     *
     * @param string $identifier
     * @return array|null Returns null if rate limiting is not enabled.
     */
    public function getRateLimitInfo(string $identifier): ?array
    {
        if ($this->rateLimiter === null) {
            return null;
        }

        return [
            'is_limited' => !$this->rateLimiter->isAllowed($identifier),
            'remaining_requests' => $this->rateLimiter->getRemainingRequests($identifier),
            'reset_time' => $this->rateLimiter->getResetTime($identifier),
            'max_requests' => $this->rateLimiter->getMaxRequests(),
            'window_seconds' => $this->rateLimiter->getWindowSeconds(),
        ];
    }
}
