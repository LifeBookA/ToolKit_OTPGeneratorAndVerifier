# مستندات کامل ماژول OTP (One-Time Password)

## 📚 معرفی

ماژول OTP یک سیستم کامل برای مدیریت کدهای یکبارمصرف است که برای احراز هویت دو مرحله‌ای، تأیید شماره موبایل، ایمیل و سایر سناریوهای امنیتی طراحی شده است.

## 🎯 موارد استفاده

- **احراز هویت دو مرحله‌ای (2FA)**
- **تأیید شماره موبایل**
- **تأیید آدرس ایمیل**
- **بازیابی رمز عبور**
- **تأیید تراکنش‌های مالی**
- **ورود بدون رمز عبور (Passwordless Login)**

## 🏗️ معماری ماژول

### اجزای اصلی

```
┌─────────────────────────────────────────────────────────┐
│                    OtpManager                            │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │
│  │  Generator  │  │   Storage   │  │   Verifier  │     │
│  └─────────────┘  └─────────────┘  └─────────────┘     │
└─────────────────────────────────────────────────────────┘
```

### جریان کار

1. **تولید**: `OtpManager.generate()` → ژنراتور کد می‌سازد → ذخیره‌ساز ذخیره می‌کند
2. **تأیید**: `OtpManager.verify()` → وریفایر بررسی می‌کند → نتیجه برمی‌گرداند
3. **انقضا**: ذخیره‌ساز به‌طور خودکار کدهای منقضی را حذف می‌کند

## 📊 دیاگرام کلاس‌ها

```
┌──────────────────────┐       ┌──────────────────────┐
│   OtpInterface       │       │ OtpGeneratorInterface│
├──────────────────────┤       ├──────────────────────┤
│ + generate()         │       │ + generate()         │
│ + verify()           │       └──────────┬───────────┘
│ + invalidate()       │                  │
│ + getRemainingAttempts()│               │ implements
└──────────┬───────────┘       ┌──────────▼───────────┐
           │ implements         │ NumericOtpGenerator  │
           │                    │ AlphaNumericOtpGenerator
┌──────────▼───────────┐       └──────────────────────┘
│    OtpManager        │
├──────────────────────┤       ┌──────────────────────┐
│ - generator          │       │ OtpStorageInterface  │
│ - storage            │       ├──────────────────────┤
│ - verifier           │       │ + save()             │
└──────────────────────┘       │ + get()              │
                               │ + delete()           │
                               │ + incrementAttempts()│
                               │ + exists()           │
                               └──────────┬───────────┘
                                          │ implements
                               ┌──────────▼───────────┐
                               │   FileOtpStorage     │
                               └──────────────────────┘
```

## 🔧 راهنمای پیاده‌سازی

### مثال ۱: سیستم ورود دو مرحله‌ای

```php
<?php

require_once 'src/Bootstrap.php';

use Toolkit\Otp\OtpManager;
use Toolkit\Otp\Config\OtpConfig;

class TwoFactorAuth
{
    private OtpManager $otpManager;
    
    public function __construct()
    {
        // تنظیمات سفارشی
        OtpConfig::setDefaultLength(6);
        OtpConfig::setDefaultTtl(180); // 3 دقیقه
        OtpConfig::setMaxAttempts(3);
        
        $this->otpManager = new OtpManager();
    }
    
    /**
     * مرحله 1: ارسال کد تأیید
     */
    public function sendVerificationCode(string $userId): string
    {
        // تولید کد
        $code = $this->otpManager->generate($userId);
        
        // در اینجا کد را از طریق SMS یا Email ارسال کنید
        // $this->smsService->send($userId, $code);
        // $this->emailService->send($userId, $code);
        
        return $code; // فقط برای تست - در تولید حذف شود
    }
    
    /**
     * مرحله 2: تأیید کد وارد شده
     */
    public function verifyCode(string $userId, string $code): array
    {
        $result = $this->otpManager->verify($userId, $code);
        
        return [
            'success' => $result->isValid,
            'message' => $result->message,
            'remainingAttempts' => $result->remainingAttempts,
        ];
    }
}

// استفاده
$auth = new TwoFactorAuth();
$code = $auth->sendVerificationCode('user123');
echo "کد ارسال شد: {$code}\n";

$result = $auth->verifyCode('user123', $code);
if ($result['success']) {
    echo "ورود موفق!\n";
} else {
    echo "خطا: {$result['message']}\n";
}
```

### مثال ۲: بازیابی رمز عبور

```php
<?php

require_once 'src/Bootstrap.php';

use Toolkit\Otp\OtpManager;
use Toolkit\Otp\Result\OtpVerificationResult;

class PasswordRecovery
{
    private OtpManager $otpManager;
    
    public function __construct()
    {
        $this->otpManager = new OtpManager();
    }
    
    /**
     * درخواست بازیابی رمز عبور
     */
    public function requestReset(string $email): bool
    {
        // بررسی وجود کاربر
        if (!$this->userExists($email)) {
            return false;
        }
        
        // تولید کد با اعتبار ۱۰ دقیقه
        $code = $this->otpManager->generate("reset:{$email}", 6, 600);
        
        // ارسال کد به ایمیل
        $this->sendResetEmail($email, $code);
        
        return true;
    }
    
    /**
     * تأیید کد و ریست رمز عبور
     */
    public function confirmReset(string $email, string $code, string $newPassword): OtpVerificationResult
    {
        $result = $this->otpManager->verify("reset:{$email}", $code);
        
        if ($result->isValid) {
            // تغییر رمز عبور
            $this->updatePassword($email, $newPassword);
        }
        
        return $result;
    }
    
    private function userExists(string $email): bool
    {
        // بررسی در دیتابیس
        return true;
    }
    
    private function sendResetEmail(string $email, string $code): void
    {
        // ارسال ایمیل
    }
    
    private function updatePassword(string $email, string $password): void
    {
        // به‌روزرسانی در دیتابیس
    }
}
```

### مثال ۳: تأیید تراکنش مالی

```php
<?php

require_once 'src/Bootstrap.php';

use Toolkit\Otp\OtpManager;
use Toolkit\Otp\Config\OtpConfig;

class TransactionVerifier
{
    private OtpManager $otpManager;
    
    public function __construct()
    {
        // کد کوتاه‌تر با اعتبار کمتر برای تراکنش
        OtpConfig::setDefaultLength(4);
        OtpConfig::setDefaultTtl(120);
        OtpConfig::setMaxAttempts(3);
        
        $this->otpManager = new OtpManager();
    }
    
    /**
     * شروع تراکنش با تأیید OTP
     */
    public function initiateTransaction(string $userId, float $amount): string
    {
        $transactionId = "txn:" . uniqid();
        
        // ذخیره اطلاعات تراکنش
        $_SESSION["pending_{$transactionId}"] = [
            'user_id' => $userId,
            'amount' => $amount,
            'created_at' => time(),
        ];
        
        // تولید و ارسال کد
        $code = $this->otpManager->generate($transactionId, 4, 120);
        
        // ارسال پیامک
        $this->sendSms($userId, "کد تأیید تراکنش: {$code}");
        
        return $transactionId;
    }
    
    /**
     * تأیید نهایی تراکنش
     */
    public function confirmTransaction(string $transactionId, string $code): bool
    {
        $result = $this->otpManager->verify($transactionId, $code);
        
        if ($result->isValid) {
            // انجام تراکنش
            $this->executeTransaction($transactionId);
            return true;
        }
        
        return false;
    }
    
    private function sendSms(string $phone, string $message): void
    {
        // ارسال پیامک
    }
    
    private function executeTransaction(string $transactionId): void
    {
        // اجرای تراکنش
    }
}
```

## 🎛️ پیکربندی پیشرفته

### استفاده از Dependency Injection

```php
<?php

use Toolkit\Otp\OtpManager;
use Toolkit\Otp\Storage\FileOtpStorage;
use Toolkit\Otp\Generator\NumericOtpGenerator;
use Toolkit\Otp\Verifier\StandardOtpVerifier;

// ایجاد وابستگی‌ها
$storage = new FileOtpStorage('/custom/path/to/storage');
$generator = new NumericOtpGenerator();
$verifier = new StandardOtpVerifier();

// تزریق به OtpManager
$otpManager = new OtpManager(
    storage: $storage,
    generator: $generator,
    verifier: $verifier
);
```

### تنظیمات محیطی

```php
<?php

use Toolkit\Otp\Config\OtpConfig;

// خواندن از متغیرهای محیطی
OtpConfig::setStorageDir(getenv('OTP_STORAGE_DIR') ?: '/tmp/otp');
OtpConfig::setDefaultLength((int)(getenv('OTP_LENGTH') ?: 6));
OtpConfig::setDefaultTtl((int)(getenv('OTP_TTL') ?: 300));
OtpConfig::setMaxAttempts((int)(getenv('OTP_MAX_ATTEMPTS') ?: 5));
OtpConfig::setGeneratorType(getenv('OTP_TYPE') ?: 'numeric');
```

## 🔍 عیب‌یابی

### مشکل: فایل‌های OTP حذف نمی‌شوند

**علت**: ممکن است فرآیند PHP دسترسی نوشتن نداشته باشد.

**راه‌حل**:
```bash
chmod 755 otp_storage
chown www-data:www-data otp_storage
```

### مشکل: کدهای تکراری تولید می‌شوند

**علت**: این بسیار نادر است اما ممکن است رخ دهد.

**راه‌حل**: طول کد را افزایش دهید:
```php
OtpConfig::setDefaultLength(8);
```

### مشکل: خطای قفل فایل

**علت**: درخواست‌های همزمان زیاد.

**راه‌حل**: از ذخیره‌سازی مبتنی بر دیتابیس استفاده کنید یا زمان TTL را کاهش دهید.

## 📈 بهترین روش‌ها

1. **طول کد**: حداقل ۶ رقم برای امنیت مناسب
2. **زمان انقضا**: بین ۲ تا ۵ دقیقه بسته به حساسیت
3. **تلاش‌های مجاز**: ۳ تا ۵ تلاش قبل از مسدودسازی
4. **شناسه‌ها**: از شناسه‌های یکتا مانند email یا user_id استفاده کنید
5. **لاگ‌گیری**: تمام تلاش‌های ناموفق را لاگ کنید
6. **Rate Limiting**: در سطح برنامه، محدودیت نرخ درخواست اعمال کنید

## 🔐 ملاحظات امنیتی

### جلوگیری از حملات Brute Force

```php
// پیاده‌سازی Rate Limiting ساده
class RateLimitedOtp extends OtpManager
{
    private array $requestCounts = [];
    
    public function generate(string $identifier, int $length = null, int $ttl = null): string
    {
        // بررسی محدودیت نرخ
        if (!$this->checkRateLimit($identifier)) {
            throw new \RuntimeException('Too many requests');
        }
        
        return parent::generate($identifier, $length, $ttl);
    }
    
    private function checkRateLimit(string $identifier): bool
    {
        $key = $identifier . ':' . floor(time() / 60); // کلید بر اساس دقیقه
        
        if (!isset($this->requestCounts[$key])) {
            $this->requestCounts[$key] = 0;
        }
        
        $this->requestCounts[$key]++;
        
        return $this->requestCounts[$key] <= 5; // حداکثر ۵ درخواست در دقیقه
    }
}
```

### پاک‌سازی دوره‌ای فایل‌های قدیمی

```php
// اسکریپت cron برای پاک‌سازی
$storageDir = OtpConfig::getStorageDir();
$files = glob($storageDir . '/*.json');

foreach ($files as $file) {
    if (filemtime($file) < time() - 3600) { // فایل‌های قدیمی‌تر از ۱ ساعت
        unlink($file);
    }
}
```

## 📞 پشتیبانی

برای گزارش مشکلات یا پیشنهاد ویژگی‌های جدید، لطفاً به مخزن GitHub مراجعه کنید.

---

**نسخه مستندات**: 1.0.0  
**آخرین به‌روزرسانی**: 2024
