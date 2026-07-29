<?php

/**
 * OTP Demo - Demonstration of OTP Generator & Verifier
 * 
 * This script demonstrates the complete OTP workflow including:
 * - Generating OTP codes
 * - Successful verification
 * - Failed verification attempts
 * - Account blocking after max attempts
 * - OTP expiration
 * 
 * @package Toolkit\Examples
 * @version 1.0.0
 */

// Bootstrap the Toolkit
require_once __DIR__ . '/../src/Bootstrap.php';

use Toolkit\Otp\OtpManager;
use Toolkit\Otp\Config\OtpConfig;
use Toolkit\Otp\Helpers\OtpHelper;

// Set color codes for CLI output
$colors = [
    'reset' => "\033[0m",
    'green' => "\033[32m",
    'red' => "\033[31m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'cyan' => "\033[36m",
    'bold' => "\033[1m",
];

/**
 * Print a formatted message
 */
function printMessage(string $message, string $color = 'reset', bool $bold = false): void
{
    global $colors;
    $boldCode = $bold ? $colors['bold'] : '';
    echo "{$boldCode}{$colors[$color]}{$message}{$colors['reset']}\n";
}

/**
 * Print a section header
 */
function printSection(string $title): void
{
    global $colors;
    echo "\n";
    printMessage(str_repeat('=', 60), 'cyan', true);
    printMessage($title, 'cyan', true);
    printMessage(str_repeat('=', 60), 'cyan', true);
    echo "\n";
}

// Start demo
printSection('🔐 نمایش ماژول تولید و اعتبارسنجی کد یکبارمصرف (OTP)');

printMessage("نسخه PHP: " . PHP_VERSION, 'blue');
printMessage("مسیر ذخیره‌سازی: " . OtpConfig::getStorageDir(), 'blue');
printMessage("طول پیش‌فرض: " . OtpConfig::getDefaultLength(), 'blue');
printMessage("زمان انقضا پیش‌فرض: " . OtpConfig::getDefaultTtl() . " ثانیه", 'blue');
printMessage("حداکثر تلاش: " . OtpConfig::getMaxAttempts(), 'blue');

// Create OTP Manager instance
$otpManager = new OtpManager();

// ============================================
// Scenario 1: Successful Verification
// ============================================
printSection('سناریو ۱: تولید و تأیید موفقیت‌آمیز کد');

$identifier = 'user@example.com';
$length = 6;
$ttl = 300; // 5 minutes

printMessage("شناسه: {$identifier}", 'blue');
printMessage("طول کد: {$length}", 'blue');
printMessage("زمان انقضا: {$ttl} ثانیه", 'blue');

// Generate OTP
$generatedCode = $otpManager->generate($identifier, $length, $ttl);
printMessage("\n✅ کد تولید شده: {$generatedCode}", 'green', true);

// Simulate sending the code (in real scenario, this would be SMS/Email)
printMessage("(در سناریوی واقعی، این کد از طریق پیامک یا ایمیل ارسال می‌شود)", 'yellow');

// Verify with correct code
printMessage("\n🔄 تلاش برای تأیید با کد صحیح...", 'blue');
$result = $otpManager->verify($identifier, $generatedCode);

printMessage("وضعیت: {$result->status}", $result->isValid ? 'green' : 'red', true);
printMessage("پیام: {$result->message}", 'reset');
printMessage("معتبر: " . ($result->isValid ? 'بله' : 'خیر'), $result->isValid ? 'green' : 'red');

// Try to verify again (should fail - OTP is one-time use)
printMessage("\n🔄 تلاش مجدد برای تأیید با همان کد (باید ناموفق باشد)...", 'blue');
$result2 = $otpManager->verify($identifier, $generatedCode);
printMessage("وضعیت: {$result2->status}", $result2->isValid ? 'green' : 'red', true);
printMessage("پیام: {$result2->message}", 'reset');

// ============================================
// Scenario 2: Failed Attempts and Blocking
// ============================================
printSection('سناریو ۲: تلاش‌های ناموفق و مسدود شدن');

$identifier2 = 'test@example.com';
$code2 = $otpManager->generate($identifier2, 6, 300);

printMessage("شناسه: {$identifier2}", 'blue');
printMessage("کد تولید شده: {$code2}", 'green', true);

$maxAttempts = OtpConfig::getMaxAttempts();

// Make failed attempts
for ($i = 1; $i <= $maxAttempts + 1; $i++) {
    $wrongCode = '000000'; // Wrong code
    printMessage("\n🔄 تلاش شماره {$i} با کد اشتباه: {$wrongCode}", 'yellow');
    
    $result = $otpManager->verify($identifier2, $wrongCode);
    
    printMessage("وضعیت: {$result->status}", $result->isValid ? 'green' : 'red', true);
    printMessage("پیام: {$result->message}", 'reset');
    
    if ($result->remainingAttempts > 0) {
        printMessage("تلاش‌های باقی‌مانده: {$result->remainingAttempts}", 'yellow');
    } else {
        printMessage("⛔ حساب مسدود شد!", 'red', true);
    }
}

// Clean up
$otpManager->invalidate($identifier2);

// ============================================
// Scenario 3: OTP Expiration
// ============================================
printSection('سناریو ۳: انقضای کد OTP');

$identifier3 = 'expire@example.com';
$shortTtl = 5; // 5 seconds TTL

printMessage("شناسه: {$identifier3}", 'blue');
printMessage("زمان انقضا: {$shortTtl} ثانیه", 'yellow', true);

$code3 = $otpManager->generate($identifier3, 6, $shortTtl);
printMessage("کد تولید شده: {$code3}", 'green', true);

printMessage("\n⏳ در حال انتظار برای انقضا... (" . ($shortTtl + 1) . " ثانیه)", 'yellow');
sleep($shortTtl + 1);

printMessage("\n🔄 تلاش برای تأیید پس از انقضا...", 'blue');
$result = $otpManager->verify($identifier3, $code3);

printMessage("وضعیت: {$result->status}", $result->isValid ? 'green' : 'red', true);
printMessage("پیام: {$result->message}", 'reset');
printMessage("منقضی شده: " . ($result->isExpired() ? 'بله' : 'خیر'), $result->isExpired() ? 'red' : 'green');

// ============================================
// Scenario 4: Alphanumeric OTP
// ============================================
printSection('سناریو ۴: تولید کد حروفی-عددی (Alphanumeric)');

// Temporarily change generator type
OtpConfig::setGeneratorType('alphanumeric');
$otpManagerAlpha = new OtpManager();

$identifier4 = 'alpha@example.com';
$code4 = $otpManagerAlpha->generate($identifier4, 8, 300);

printMessage("شناسه: {$identifier4}", 'blue');
printMessage("نوع ژنراتور: حروفی-عددی", 'blue');
printMessage("کد تولید شده: {$code4}", 'green', true);

// Verify
$result = $otpManagerAlpha->verify($identifier4, $code4);
printMessage("\n🔄 تأیید کد:", 'blue');
printMessage("وضعیت: {$result->status}", $result->isValid ? 'green' : 'red', true);
printMessage("پیام: {$result->message}", 'reset');

// Reset to numeric
OtpConfig::setGeneratorType('numeric');

// ============================================
// Scenario 5: Helper Functions
// ============================================
printSection('سناریو ۵: توابع کمکی');

// Validate identifier
$testIdentifiers = [
    'valid@email.com',
    'user123',
    'invalid@email!',
    '',
    'test_user-name.test',
];

printMessage("بررسی اعتبار شناسه‌ها:", 'blue', true);
foreach ($testIdentifiers as $id) {
    $isValid = OtpHelper::validateIdentifier($id);
    $status = $isValid ? '✅ معتبر' : '❌ نامعتبر';
    $color = $isValid ? 'green' : 'red';
    printMessage("  '{$id}' => {$status}", $color);
}

// Format remaining attempts
printMessage("\nنمایش تعداد تلاش‌های باقی‌مانده:", 'blue', true);
printMessage("  5 تلاش: " . OtpHelper::formatRemainingAttempts(5, 5), 'cyan');
printMessage("  3 تلاش: " . OtpHelper::formatRemainingAttempts(3, 5), 'cyan');
printMessage("  1 تلاش: " . OtpHelper::formatRemainingAttempts(1, 5), 'yellow');
printMessage("  0 تلاش: " . OtpHelper::formatRemainingAttempts(0, 5), 'red');

// ============================================
// Final Summary
// ============================================
printSection('📊 خلاصه نمایش');

printMessage("✅ تمام سناریوها با موفقیت اجرا شدند!", 'green', true);
printMessage("\nماژول OTP شامل قابلیت‌های زیر است:", 'cyan', true);
printMessage("  • تولید کد عددی و حروفی-عددی", 'reset');
printMessage("  • ذخیره‌سازی مبتنی بر فایل با قفل‌گذاری", 'reset');
printMessage("  • محدودیت تعداد تلاش و مسدودسازی", 'reset');
printMessage("  • انقضای خودکار کدها", 'reset');
printMessage("  • حذف خودکار پس از استفاده موفق", 'reset');
printMessage("  • توابع کمکی برای اعتبارسنجی", 'reset');

echo "\n";
