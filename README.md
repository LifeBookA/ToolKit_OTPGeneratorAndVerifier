# 🔐 ماژول تولید و اعتبارسنجی کد یکبارمصرف (OTP)

ماژول OTP Generator & Verifier یک سیستم کامل برای تولید، ذخیره‌سازی و تأیید کدهای یکبارمصرف است که با PHP خالص (نسخه ۸.۲+) نوشته شده است.

## 📋 فهرست مطالب

- [ویژگی‌ها](#ویژگیها)
- [ساختار پروژه](#ساختار-پروژه)
- [نصب و راه‌اندازی](#نصب-و-راهاندازی)
- [نحوه استفاده](#نحوه-استفاده)
- [تنظیمات](#تنظیمات)
- [کلاس‌ها و اینترفیس‌ها](#کلاسها-و-اینترفیسها)
- [مثال‌ها](#مثالها)
- [استثناها](#استثناها)

## ✨ ویژگی‌ها

- **تولید کد امن**: استفاده از `random_int()` برای تولید کدهای رمزنگاری‌شده
- **دو نوع ژنراتور**: عددی (Numeric) و حروفی-عددی (Alphanumeric)
- **ذخیره‌سازی فایل**: ذخیره در فایل‌های JSON با قفل‌گذاری برای جلوگیری از هم‌نویسی
- **انقضای خودکار**: حذف خودکار کدهای منقضی‌شده
- **محدودیت تلاش**: امکان تعیین حداکثر تعداد تلاش برای تأیید
- **مسدودسازی**: مسدود شدن شناسه پس از رسیدن به حد مجاز تلاش
- **یکبارمصرف**: حذف کد پس از تأیید موفق
- **بدون وابستگی**: فقط از توابع داخلی PHP استفاده می‌کند

## 📁 ساختار پروژه

```
Toolkit/
├── src/
│   ├── Autoloader.php              # بارگذار خودکار کلاس‌ها
│   ├── Bootstrap.php               # راه‌انداز اولیه
│   └── Otp/
│       ├── Contracts/
│       │   └── OtpInterface.php    # اینترفیس اصلی
│       ├── Generator/
│       │   ├── OtpGeneratorInterface.php
│       │   ├── NumericOtpGenerator.php
│       │   └── AlphaNumericOtpGenerator.php
│       ├── Storage/
│       │   ├── OtpStorageInterface.php
│       │   └── FileOtpStorage.php
│       ├── Verifier/
│       │   ├── OtpVerifierInterface.php
│       │   └── StandardOtpVerifier.php
│       ├── Exceptions/
│       │   ├── OtpException.php
│       │   ├── OtpExpiredException.php
│       │   ├── OtpBlockedException.php
│       │   └── OtpInvalidException.php
│       ├── Config/
│       │   └── OtpConfig.php       # تنظیمات پیکربندی
│       ├── Helpers/
│       │   └── OtpHelper.php       # توابع کمکی
│       ├── Result/
│       │   └── OtpVerificationResult.php
│       └── OtpManager.php          # کلاس اصلی
├── examples/
│   └── otp_demo.php                # مثال کامل
├── docs/
│   └── otp.md                      # مستندات
└── README.md
```

## 🚀 نصب و راه‌اندازی

### پیش‌نیازها

- PHP 8.2 یا بالاتر
- دسترسی نوشتن به پوشه `otp_storage`

### راه‌اندازی سریع

```php
<?php

// بارگذاری Bootstrap
require_once 'src/Bootstrap.php';

use Toolkit\Otp\OtpManager;

// ایجاد نمونه OtpManager
$otpManager = new OtpManager();

// تولید کد برای یک شناسه
$code = $otpManager->generate('user@example.com', 6, 300);
echo "کد تولید شده: {$code}";

// تأیید کد
$result = $otpManager->verify('user@example.com', $code);

if ($result->isValid) {
    echo "تأیید موفق!";
} else {
    echo "خطا: {$result->message}";
}
```

## 📖 نحوه استفاده

### تولید کد OTP

```php
use Toolkit\Otp\OtpManager;

$otpManager = new OtpManager();

// تولید با تنظیمات پیش‌فرض
$code = $otpManager->generate('user@example.com');

// تولید با طول سفارشی (۴ تا ۱۰ رقم)
$code = $otpManager->generate('user@example.com', 8);

// تولید با زمان انقضای سفارشی (بر حسب ثانیه)
$code = $otpManager->generate('user@example.com', 6, 600); // 10 دقیقه
```

### تأیید کد OTP

```php
use Toolkit\Otp\OtpManager;

$otpManager = new OtpManager();

$result = $otpManager->verify('user@example.com', '123456');

// بررسی نتیجه
if ($result->isValid) {
    echo "✅ کد صحیح است";
} else {
    echo "❌ خطا: {$result->message}";
    echo "تلاش‌های باقی‌مانده: {$result->remainingAttempts}";
}

// روش‌های دیگر بررسی
if ($result->isSuccess()) { /* ... */ }
if ($result->isExpired()) { /* ... */ }
if ($result->isBlocked()) { /* ... */ }
if ($result->isNotFound()) { /* ... */ }
if ($result->isInvalid()) { /* ... */ }
```

### باطل کردن کد

```php
// حذف تمام کدهای یک شناسه
$otpManager->invalidate('user@example.com');
```

### دریافت تعداد تلاش‌های باقی‌مانده

```php
$remaining = $otpManager->getRemainingAttempts('user@example.com');
echo "تلاش‌های باقی‌مانده: {$remaining}";
```

## ⚙️ تنظیمات

تمام تنظیمات در کلاس `OtpConfig` قابل تغییر هستند:

```php
use Toolkit\Otp\Config\OtpConfig;

// مسیر ذخیره‌سازی فایل‌ها
OtpConfig::setStorageDir('/path/to/storage');

// طول پیش‌فرض کد (۴ تا ۱۰)
OtpConfig::setDefaultLength(6);

// زمان انقضای پیش‌فرض (ثانیه)
OtpConfig::setDefaultTtl(300); // 5 دقیقه

// حداکثر تعداد تلاش
OtpConfig::setMaxAttempts(5);

// نوع ژنراتور ('numeric' یا 'alphanumeric')
OtpConfig::setGeneratorType('alphanumeric');

// دریافت همه تنظیمات
$config = OtpConfig::getAll();
```

## 🏗️ کلاس‌ها و اینترفیس‌ها

### OtpManager (کلاس اصلی)

پیاده‌سازی کامل `OtpInterface`:

| متد | توضیح |
|-----|-------|
| `generate(string $identifier, int $length = null, int $ttl = null): string` | تولید کد جدید |
| `verify(string $identifier, string $code): OtpVerificationResult` | تأیید کد |
| `invalidate(string $identifier): void` | باطل کردن کد |
| `getRemainingAttempts(string $identifier): int` | دریافت تلاش‌های باقی‌مانده |

### ژنراتورها

#### NumericOtpGenerator
تولید کد کاملاً عددی (۰-۹)

#### AlphaNumericOtpGenerator
تولید کد حروف بزرگ (A-Z) و اعداد (۰-۹)

### ذخیره‌سازی

#### FileOtpStorage
ذخیره در فایل‌های JSON با ساختار:
```json
{
    "code": "123456",
    "expiry": 1765123456,
    "attempts": 0
}
```

### نتیجه تأیید

#### OtpVerificationResult

| خاصیت | نوع | توضیح |
|-------|-----|-------|
| `isValid` | bool | آیا تأیید موفق بود |
| `status` | string | وضعیت ('success', 'expired', 'blocked', 'invalid', 'not_found') |
| `message` | string | پیام فارسی |
| `remainingAttempts` | int | تلاش‌های باقی‌مانده |

متدهای کمکی:
- `toArray(): array` - تبدیل به آرایه
- `toJson(): string` - تبدیل به JSON
- `isSuccess(), isExpired(), isBlocked(), isNotFound(), isInvalid()` - بررسی وضعیت

### توابع کمکی (OtpHelper)

```php
use Toolkit\Otp\Helpers\OtpHelper;

// تولید کد امن
$code = OtpHelper::generateSecureCode(6, 'numeric');

// اعتبارسنجی شناسه
$isValid = OtpHelper::validateIdentifier('user@example.com');

// قالب‌بندی تعداد تلاش
$message = OtpHelper::formatRemainingAttempts(3, 5);
// خروجی: "تعداد 3 تلاش دیگر باقی مانده است"
```

## 📝 مثال‌ها

### مثال کامل

```php
<?php

require_once 'src/Bootstrap.php';

use Toolkit\Otp\OtpManager;
use Toolkit\Otp\Config\OtpConfig;

// تنظیمات سفارشی
OtpConfig::setMaxAttempts(3);
OtpConfig::setDefaultTtl(120);

$otpManager = new OtpManager();

// مرحله 1: تولید و ارسال کد
$identifier = 'user@example.com';
$code = $otpManager->generate($identifier, 6, 120);

// در سناریوی واقعی، کد را از طریق SMS یا Email ارسال کنید
echo "کد تأیید: {$code}\n";

// مرحله 2: دریافت کد از کاربر و تأیید
$userInput = readline('کد تأیید را وارد کنید: ');
$result = $otpManager->verify($identifier, $userInput);

if ($result->isValid) {
    echo "✅ ورود موفقیت‌آمیز بود!\n";
} else {
    echo "❌ خطا: {$result->message}\n";
    
    if ($result->remainingAttempts > 0) {
        echo "تعداد {$result->remainingAttempts} تلاش دیگر دارید.\n";
    } else {
        echo "حساب شما مسدود شد. لطفاً بعداً تلاش کنید.\n";
    }
}
```

### اجرای دمو

برای مشاهده نمایش کامل قابلیت‌ها:

```bash
php examples/otp_demo.php
```

## ⚠️ استثناها

| استثنا | کد HTTP | توضیح |
|--------|---------|-------|
| `OtpException` | - | استثنای پایه |
| `OtpExpiredException` | 410 | کد منقضی شده |
| `OtpBlockedException` | 429 | پایان تلاش‌های مجاز |
| `OtpInvalidException` | 400 | کد نامعتبر |

## 🔒 نکات امنیتی

1. **شناسه‌ها**: فقط از کاراکترهای `[a-zA-Z0-9@._-]` استفاده کنید
2. **طول کد**: حداقل ۶ رقم توصیه می‌شود
3. **زمان انقضا**: برای حساسیت بالا، TTL کوتاه‌تر انتخاب کنید
4. **تلاش‌های مجاز**: بسته به نیاز، بین ۳ تا ۵ تلاش مناسب است
5. **ذخیره‌سازی**: پوشه `otp_storage` باید خارج از دسترس وب باشد

## 📄 مجوز

این پروژه تحت مجوز MIT منتشر شده است.

---

**نسخه**: 1.0.0  
**نویسنده**: Toolkit Team  
**آخرین به‌روزرسانی**: 2024
