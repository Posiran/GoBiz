<?php
// تنظیمات مخصوص استقرار Gobiz (مقدارها از .env هر محیط می‌آیند)
return [
    // نسخه تست (test.gobiz.ir): اگر STAGING_PASSWORD پر باشد، ورود با رمز HTTP لازم است و ایندکس موتورهای جستجو بسته می‌شود
    'staging' => [
        'user' => env('STAGING_USER', 'gobiz'),
        'password' => (string) env('STAGING_PASSWORD', ''),
    ],

    // کارهای پس از استقرار (php artisan gobiz:post-deploy)
    'deploy' => [
        'admin_mobile' => (string) env('ADMIN_MOBILE', ''),   // اولین مدیر سایت، فقط اگر هنوز مدیری وجود ندارد
        'demo_data' => (bool) env('DEMO_DATA', false),        // داده نمایشی؛ فقط خارج از production و فقط وقتی هیچ آگهی‌ای نیست
    ],

    // اطلاعات تماس صفحه «تماس با ما»؛ اگر خالی باشد چیزی نشان داده نمی‌شود
    'contact_email' => (string) env('CONTACT_EMAIL', ''),
    'contact_phone' => (string) env('CONTACT_PHONE', ''),

    // اگر پر باشد (جدا با کاما)، فقط این شماره‌ها پیامک واقعی می‌گیرند؛ برای بقیه فقط لاگ می‌شود (نسخه تست)
    'sms_only_to' => array_values(array_filter(array_map('trim', explode(',', (string) env('SMS_ONLY_TO', ''))))),
];
