# نسخه ۲.۰.۰ - ویژگی‌های جدید

## 🎉 معرفی نسخه ۲.۰.۰

نسخه ۲.۰.۰ ماژول OTP Generator & Verifier با قابلیت‌های جدید و پیشرفته منتشر شد. این نسخه شامل سیستم‌های لاگ‌گیری، ذخیره‌سازی دیتابیس و Redis، محدودیت نرخ درخواست (Rate Limiting)، ژنراتور الگو-based، و کانال‌های اطلاع‌رسانی است.

---

## ✨ ویژگی‌های جدید اضافه شده

### ۱. سیستم لاگ‌گیری (Logging System)

#### فایل‌ها:
- `src/Otp/Logger/OtpLoggerInterface.php`
- `src/Otp/Logger/FileOtpLogger.php`

#### امکانات:
- ثبت تمام رویدادهای OTP (تولید، تأیید موفق/ناموفق، انقضا، مسدودسازی، باطل‌کردن)
- ذخیره در فایل JSON با فرمت ساختاریافته
- چرخش خودکار فایل لاگ هنگام رسیدن به حجم مشخص
- امکان خواندن لاگ‌های اخیر

#### مثال استفاده:
```php
use Toolkit\Otp\Logger\FileOtpLogger;

$logger = new FileOtpLogger('/path/to/logs', 1048576); // 1MB max file size
$logger->logGeneration('user@example.com', 6, 300);
$logger->logSuccess('user@example.com');

// خواندن لاگ‌های اخیر
$recentLogs = $logger->readRecentLogs(50);
```

---

### ۲. ذخیره‌سازی مبتنی بر دیتابیس (Database Storage)

#### فایل‌ها:
- `src/Otp/Storage/Database/DatabaseStorageInterface.php`
- `src/Otp/Storage/Database/PdoOtpStorage.php`

#### امکانات:
- پشتیبانی از MySQL، PostgreSQL، و SQLite
- ایجاد خودکار جدول در صورت عدم وجود
- پاک‌سازی خودکار رکوردهای منقضی‌شده
- استفاده از PDO برای اتصال امن

#### مثال استفاده:
```php
use Toolkit\Otp\Storage\Database\PdoOtpStorage;

$storage = new PdoOtpStorage();
$storage->setConnection(
    host: 'localhost',
    port: 3306,
    database: 'otp_db',
    username: 'root',
    password: 'secret',
    driver: 'mysql' // یا pgsql، sqlite
);
$storage->createTable();

// استفاده با OtpManager
$otpManager = new \Toolkit\Otp\OtpManager($storage);
```

#### ساختار جدول:
```sql
CREATE TABLE otp_entries (
    identifier VARCHAR(255) PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    expiry BIGINT NOT NULL,
    attempts INT DEFAULT 0,
    created_at BIGINT NOT NULL
);
```

---

### ۳. ذخیره‌سازی مبتنی بر Redis (Redis Storage)

#### فایل‌ها:
- `src/Otp/Storage/Redis/RedisStorageInterface.php`
- `src/Otp/Storage/Redis/RedisOtpStorage.php`

#### امکانات:
- استفاده از Redis Hashes برای ذخیره داده‌ها
- انقضای خودکار با TTL داخلی Redis
- آمار و اطلاعات لحظه‌ای
- پیشوند سفارشی برای کلیدها

#### مثال استفاده:
```php
use Toolkit\Otp\Storage\Redis\RedisOtpStorage;

$storage = new RedisOtpStorage();
$storage->setConnection(
    host: '127.0.0.1',
    port: 6379,
    password: null, // اختیاری
    database: 0
);
$storage->setKeyPrefix('myapp:otp:');

// بررسی وضعیت
if ($storage->ping()) {
    echo "Redis connected!";
}

// آمار
$stats = $storage->getStats();
echo "Total OTP keys: " . $stats['total_otp_keys'];
```

---

### ۴. محدودیت نرخ درخواست (Rate Limiting)

#### فایل‌ها:
- `src/Otp/RateLimit/RateLimiterInterface.php`
- `src/Otp/RateLimit/FileRateLimiter.php`

#### امکانات:
- الگوریتم sliding window برای محاسبه دقیق
- جلوگیری از سوءاستفاده و حملات Brute-force
- تنظیم تعداد مجاز درخواست در بازه زمانی مشخص
- امکان دریافت زمان بازنشانی محدودیت

#### مثال استفاده:
```php
use Toolkit\Otp\RateLimit\FileRateLimiter;

$rateLimiter = new FileRateLimiter(
    maxRequests: 5,      // حداکثر ۵ درخواست
    windowSeconds: 300   // در هر ۵ دقیقه
);

$identifier = 'user@example.com';

if (!$rateLimiter->isAllowed($identifier)) {
    $resetTime = $rateLimiter->getResetTime($identifier);
    echo "لطفاً پس از " . date('H:i:s', $resetTime) . " تلاش کنید.";
} else {
    $rateLimiter->recordRequest($identifier);
    // ادامه عملیات
}

// دریافت اطلاعات
$remaining = $rateLimiter->getRemainingRequests($identifier);
echo "تعداد درخواست باقی‌مانده: $remaining";
```

---

### ۵. ژنراتور کد با الگوی سفارشی (Pattern Generator)

#### فایل:
- `src/Otp/Generator/PatternOtpGenerator.php`

#### امکانات:
- تعریف الگوی دلخواه برای تولید کد
- توکن‌های مختلف:
  - `#` : رقم تصادفی (0-9)
  - `@` : حرف بزرگ تصادفی (A-Z)
  - `*` : کاراکتر الفبایی-عددی تصادفی
  - سایر کاراکترها:_literals_

#### مثال استفاده:
```php
use Toolkit\Otp\Generator\PatternOtpGenerator;

// الگوهای مختلف
$gen1 = new PatternOtpGenerator('######');        // "123456"
$gen2 = new PatternOtpGenerator('###-###');       // "123-456"
$gen3 = new PatternOtpGenerator('@@####');        // "AB1234"
$gen4 = new PatternOtpGenerator('OTP-####');      // "OTP-5678"

$code = $gen2->generate();
echo $code; // خروجی: 789-012

// ایجاد الگو با متدهای کمکی
$pattern = PatternOtpGenerator::createGroupedPattern(
    totalLength: 8,
    groupSize: 4,
    separator: '-'
);
// الگو: "####-####"

$gen5 = new PatternOtpGenerator($pattern);
```

---

### ۶. کانال‌های اطلاع‌رسانی (Notification Channels)

#### فایل‌ها:
- `src/Otp/Notification/NotificationChannelInterface.php`
- `src/Otp/Notification/ConsoleNotificationChannel.php`

#### امکانات:
- اینترفیس یکپارچه برای کانال‌های مختلف
- کانال کنسول برای توسعه و تست
- قابلیت افزودن کانال‌های سفارشی (SMS، Email، Push)

#### مثال استفاده:
```php
use Toolkit\Otp\Notification\ConsoleNotificationChannel;
use Toolkit\Otp\EnhancedOtpManager;

$consoleChannel = new ConsoleNotificationChannel(verbose: true);

$otpManager = new EnhancedOtpManager();
$otpManager->addNotificationChannel($consoleChannel, 'console');

// هنگام تولید کد، به‌صورت خودکار در کنسول نمایش داده می‌شود
$code = $otpManager->generate('user@example.com', 6, 300);
```

---

### ۷. مدیر OTP پیشرفته (Enhanced OTP Manager)

#### فایل:
- `src/Otp/EnhancedOtpManager.php`

#### امکانات:
- تمام قابلیت‌های OtpManager اصلی
- لاگ‌گیری خودکار رویدادها
- محدودیت نرخ درخواست
- پشتیبانی از چندین کانال اطلاع‌رسانی
- تنظیمات پیکربندی انعطاف‌پذیر

#### مثال استفاده کامل:
```php
use Toolkit\Otp\EnhancedOtpManager;
use Toolkit\Otp\Logger\FileOtpLogger;
use Toolkit\Otp\RateLimit\FileRateLimiter;
use Toolkit\Otp\Notification\ConsoleNotificationChannel;

// ایجاد نمونه با تمام قابلیت‌ها
$otpManager = new EnhancedOtpManager(
    storage: null,              // استفاده از FileOtpStorage پیش‌فرض
    generator: null,            // استفاده از NumericOtpGenerator پیش‌فرض
    verifier: null,             // استفاده از StandardOtpVerifier پیش‌فرض
    logger: new FileOtpLogger(),
    rateLimiter: new FileRateLimiter(5, 300),
    config: [
        'enable_logging' => true,
        'enable_rate_limiting' => true,
    ]
);

// افزودن کانال اطلاع‌رسانی
$otpManager->addNotificationChannel(new ConsoleNotificationChannel());

// تولید کد با لاگ و Rate Limiting
try {
    $code = $otpManager->generate('user@example.com', 6, 300);
    echo "کد تولید شد: $code";
} catch (\RuntimeException $e) {
    if ($e->getCode() === 429) {
        echo "محدودیت نرخ درخواست!";
    }
}

// تأیید کد با لاگ خودکار
$result = $otpManager->verify('user@example.com', $code);

// دریافت اطلاعات Rate Limit
$info = $otpManager->getRateLimitInfo('user@example.com');
if ($info !== null) {
    echo "درخواست‌های باقی‌مانده: " . $info['remaining_requests'];
    echo "زمان بازنشانی: " . date('H:i:s', $info['reset_time']);
}
```

---

## 📊 مقایسه نسخه‌ها

| ویژگی | v1.0.0 | v2.0.0 |
|-------|--------|--------|
| تولید کد عددی/حروفی | ✅ | ✅ |
| ذخیره‌سازی فایل JSON | ✅ | ✅ |
| انقضای خودکار | ✅ | ✅ |
| محدودیت تلاش تأیید | ✅ | ✅ |
| **لاگ‌گیری رویدادها** | ❌ | ✅ |
| **ذخیره‌سازی دیتابیس** | ❌ | ✅ |
| **ذخیره‌سازی Redis** | ❌ | ✅ |
| **Rate Limiting** | ❌ | ✅ |
| **ژنراتور الگو-based** | ❌ | ✅ |
| **کانال‌های اطلاع‌رسانی** | ❌ | ✅ |
| **مدیر پیشرفته OTP** | ❌ | ✅ |

---

## 🚀 ارتقاء از v1.0.0 به v2.0.0

### تغییرات سازگار با عقب (Backward Compatible)
تمامی کدهای نوشته‌شده برای v1.0.0 بدون تغییر در v2.0.0 کار می‌کنند.

### استفاده از قابلیت‌های جدید
برای بهره‌مندی از ویژگی‌های جدید، کافیست از کلاس‌های جدید استفاده کنید:

```php
// قبلاً (هنوز هم کار می‌کند)
$otpManager = new \Toolkit\Otp\OtpManager();

// جدید - با قابلیت‌های اضافی
$otpManager = new \Toolkit\Otp\EnhancedOtpManager(
    logger: new \Toolkit\Otp\Logger\FileOtpLogger(),
    rateLimiter: new \Toolkit\Otp\RateLimit\FileRateLimiter()
);
```

---

## 📦 نصب و راه‌اندازی

### نیازمندی‌های جدید

#### برای Database Storage:
- PHP PDO Extension
- یکی از دیتابیس‌های MySQL، PostgreSQL، یا SQLite

#### برای Redis Storage:
- PHP Redis Extension (`pecl install redis`)

### تنظیمات پیشنهادی

```php
// config/otp_config.php
return [
    'storage' => 'redis', // یا 'file'، 'database'
    'logging' => [
        'enabled' => true,
        'dir' => '/var/log/otp',
        'max_file_size' => 1048576, // 1MB
    ],
    'rate_limiting' => [
        'enabled' => true,
        'max_requests' => 5,
        'window_seconds' => 300,
    ],
    'generator' => [
        'type' => 'pattern',
        'pattern' => '######',
    ],
];
```

---

## 🔧 مثال‌های بیشتر

### مثال ۱: استفاده از SQLite برای ذخیره‌سازی
```php
use Toolkit\Otp\Storage\Database\PdoOtpStorage;
use Toolkit\Otp\OtpManager;

$storage = new PdoOtpStorage();
$storage->setConnection(
    database: '/path/to/otp.sqlite',
    driver: 'sqlite'
);
$storage->createTable();

$otpManager = new OtpManager(storage: $storage);
$code = $otpManager->generate('user@example.com');
```

### مثال ۲: ترکیب Rate Limiting با Logging
```php
use Toolkit\Otp\EnhancedOtpManager;
use Toolkit\Otp\Logger\FileOtpLogger;

$otpManager = new EnhancedOtpManager(
    logger: new FileOtpLogger(),
    config: ['enable_rate_limiting' => true]
);

$otpManager->enableRateLimiting(
    maxRequests: 3,      // فقط ۳ درخواست
    windowSeconds: 60    // در هر دقیقه
);

for ($i = 0; $i < 5; $i++) {
    try {
        $code = $otpManager->generate('test@example.com');
        echo "تلاش $i: موفق\n";
    } catch (\RuntimeException $e) {
        echo "تلاش $i: خطا - " . $e->getMessage() . "\n";
    }
}
```

### مثال ۳: ژنراتور با الگوی پیچیده
```php
use Toolkit\Otp\Generator\PatternOtpGenerator;

// الگو: دو حرف + چهار رقم + دو حرف
$generator = new PatternOtpGenerator('@@####@@');

for ($i = 0; $i < 5; $i++) {
    echo $generator->generate() . PHP_EOL;
}
// خروجی نمونه:
// AB1234CD
// XY5678ZW
// MN9012PQ
```

---

## 🐛 رفع مشکلات شناخته شده

### مشکل ۱: هم‌نویسی در فایل‌های JSON
**راه‌حل:** از `flock()` برای قفل‌گذاری استفاده شده است.

### مشکل ۲: تجمع فایل‌های منقضی‌شده
**راه‌حل:** حذف خودکار هنگام خواندن + متد `cleanupExpired()` در Database Storage.

### مشکل ۳: عدم شفافیت در خطاهای Rate Limiting
**راه‌حل:** پرتاب استثنا با کد ۴۲۲ و پیام واضح.

---

## 📝 changelog کامل

### v2.0.0 (۲۰۲۴)
- ➕ افزودن سیستم لاگ‌گیری با `FileOtpLogger`
- ➕ افزودن ذخیره‌سازی دیتابیس با `PdoOtpStorage`
- ➕ افزودن ذخیره‌سازی Redis با `RedisOtpStorage`
- ➕ افزودن Rate Limiting با `FileRateLimiter`
- ➕ افزودن ژنراتور الگو-based با `PatternOtpGenerator`
- ➕ افزودن کانال‌های اطلاع‌رسانی با `ConsoleNotificationChannel`
- ➕ افزودن `EnhancedOtpManager` با تمام قابلیت‌های جدید
- 📝 بهبود مستندات و مثال‌ها
- 🔧 بهینه‌سازی عملکرد و امنیت

### v1.0.0 (نسخه اولیه پایدار)
- ➕ تولید کد عددی و حروفی-عددی
- ➕ ذخیره‌سازی فایل JSON
- ➕ انقضا و محدودیت تلاش
- ➕ اینترفیس‌ها و استثناها
- ➕ مستندات کامل

---

## 🤝 مشارکت در پروژه

برای گزارش مشکلات یا پیشنهاد ویژگی‌های جدید، لطفاً به مخزن GitHub مراجعه کنید:
https://github.com/LifeBookA/ToolKit_OTP_Generator_and_Verifier

---

## 📄 مجوز

این پروژه تحت مجوز MIT منتشر شده است.
