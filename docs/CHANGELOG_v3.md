# Changelog - نسخه ۳.۱.۰

## 🎉 انتشار نسخه ۳.۱.۰ - افزودن قابلیت‌های پیشرفته و تکمیل ماژول‌ها

**تاریخ انتشار:** ۲۰۲۶  
**تعداد فایل‌های جدید:** ۷ فایل  
**تعداد کل فایل‌ها:** ۴۵ فایل  

---

## 🔧 اصلاحات حیاتی (Priority 0)

### ۱. بازنویسی کامل Autoloader
**فایل:** `src/Autoloader.php`

**مشکل قبلی:**  
Autoloader قادر به بارگذاری کلاس‌های موجود در زیرپوشه‌های عمیق (مانند `Database`, `Redis`) نبود.

**راه‌حل جدید:**  
- پشتیبانی کامل از ساختار پوشه‌ای تو در تو
- تبدیل صحیح namespace به مسیر فایل
- جستجوی جایگزین برای کلاس‌های nested

---

## 🧪 سیستم تست واحد (Priority 1 - بالا)

برای اولین بار، سیستم تست واحد کامل به پروژه اضافه شد:

### فایل‌های ایجاد شده:

| فایل | توضیحات | تعداد تست‌ها |
|------|---------|-------------|
| `tests/TestCase.php` | کلاس پایه تست با متدهای assert | - |
| `tests/GeneratorTests.php` | تست Numeric, AlphaNumeric, Pattern | ۷ تست |
| `tests/StorageTests.php` | تست File Storage | ۷ تست |
| `tests/VerifierTests.php` | تست منطق تأیید، انقضا و بلاک شدن | ۸ تست |
| `tests/run_tests.php` | اسکریپت اجرای همه تست‌ها با گزارش رنگی | - |

### ویژگی‌های سیستم تست:
- ✅ خروجی رنگی در ترمینال
- ✅ گزارش آماری از تست‌های پاس/فیل
- ✅ نمایش زمان اجرای کل تست‌ها
- ✅ Exit code مناسب (۰ برای موفقیت، ۱ برای شکست)

### نتیجه اجرای تست‌ها:
```
✅ All tests passed! (22/22)
Time: 4.01s
```

---

## 🚀 قابلیت‌های جدید نسخه ۳.۱.۰

### ۱. ژنراتورهای پیشرفته OTP

#### TotpGenerator (RFC 6238)
- تولید کد مبتنی بر زمان (TOTP)
- پشتیبانی از پنجره خطا برای جبران اختلاف ساعت
- سازگار با Google Authenticator و Authy

#### ReadableOtpGenerator
- تولید کدهای بدون حروف مبهم (بدون O, 0, I, 1, l)
- مناسب برای خواندن شفاهی و تایپ دستی

#### CustomCharsetGenerator
- تولید کد با مجموعه کاراکتری دلخواه کاربر
- انعطاف‌پذیری کامل برای نیازهای خاص

### ۲. سیستم پشتیبان‌گیری (BackupHelper)
- بک‌آپ‌گیری از تمام فایل‌های JSON ذخیره‌سازی
- بازیابی داده‌ها از فایل بک‌آپ
- فشرده‌سازی خودکار

### ۳. آنالیتیکس و آمار (OtpAnalytics)
- ردیابی تعداد کل تولیدها و تأییدها
- شناسایی شناسه‌های مشکوک (تلاش ناموفق زیاد)
- محاسبه نرخ موفقیت کلی
- گزارش‌گیری از حملات احتمالی

### ۴. کانال‌های اطلاع‌رسانی چندگانه

#### EmailNotificationChannel
- شبیه‌سازی ارسال ایمیل (ذخیره در فایل `.email`)
- مناسب برای محیط توسعه

#### SmsNotificationChannel
- شبیه‌سازی ارسال پیامک (ذخیره در فایل `.sms`)
- مناسب برای محیط توسعه

#### WebhookNotificationChannel
- ارسال واقعی HTTP POST به وب‌هوک
- استفاده از cURL برای درخواست‌ها

### ۵. کمک‌کننده QR Code (QrCodeHelper)
- تولید رشته استاندارد `otpauth://` برای TOTP
- سازگار با Google Authenticator
- امکان استفاده در فرانت‌اند برای تولید تصویر QR

---

## 📁 ساختار نهایی پروژه

```
Toolkit/
├── src/
│   ├── Autoloader.php
│   ├── Bootstrap.php
│   └── Otp/
│       ├── Config/OtpConfig.php
│       ├── Contracts/OtpInterface.php
│       ├── Exceptions/ (4 files)
│       ├── Generator/
│       │   ├── AlphaNumericOtpGenerator.php
│       │   ├── CustomCharsetGenerator.php
│       │   ├── NumericOtpGenerator.php
│       │   ├── OtpGeneratorInterface.php
│       │   ├── PatternOtpGenerator.php
│       │   ├── ReadableOtpGenerator.php
│       │   └── TotpGenerator.php
│       ├── Helpers/
│       │   ├── BackupHelper.php
│       │   ├── OtpHelper.php
│       │   └── QrCodeHelper.php
│       ├── Logger/ (2 files)
│       ├── Notification/ (5 files)
│       ├── RateLimit/ (2 files)
│       ├── Result/OtpVerificationResult.php
│       ├── Storage/
│       │   ├── Database/ (2 files)
│       │   ├── FileOtpStorage.php
│       │   ├── OtpStorageInterface.php
│       │   └── Redis/ (2 files)
│       ├── Verifier/ (2 files)
│       ├── Analytics/OtpAnalytics.php
│       ├── EnhancedOtpManager.php
│       └── OtpManager.php
├── tests/
│   ├── TestCase.php
│   ├── GeneratorTests.php
│   ├── StorageTests.php
│   ├── VerifierTests.php
│   └── run_tests.php
├── examples/
│   └── otp_demo.php
├── docs/
│   ├── CHANGELOG_v2.md
│   ├── CHANGELOG_v3.md
│   └── otp.md
└── README.md
```

---

## 📊 آمار نسخه ۳.۱.۰

- **تعداد کل فایل‌های PHP:** ۴۵ فایل
- **تعداد تست‌های واحد:** ۲۲ تست
- **خطوط کد PHP:** حدود ۵,۵۰۰ خط
- **پوشش تست:** ژنراتورها، ذخیره‌سازی، وریفایر

---

## 🔄 تغییرات شکسته‌ساز (Breaking Changes)

هیچ تغییر شکسته‌سازی وجود ندارد. تمام APIهای عمومی بدون تغییر باقی مانده‌اند.

---

## 🙏 تشکر

از تمام contributorهایی که در توسعه این نسخه مشارکت کردند سپاسگزاریم.

---

**لینک مخزن:** https://github.com/LifeBookA/ToolKit_OTP_Generator_and_Verifier
