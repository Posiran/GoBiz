<?php

/*
| تنظیمات استقرار خودکار Gobiz — این فایل را با نام deploy-config.php کنار deploy.php روی هاست بگذارید.
| این فایل رمز دارد؛ آن را داخل public_html قرار ندهید و در GitHub هم ثبت نکنید.
*/

return [
    // مخزن GitHub
    'repository' => 'Posiran/GoBiz',

    // توکن فقط‌خواندنی GitHub (Fine-grained token با دسترسی Contents: Read-only به همین مخزن).
    // مخزن GoBiz عمومی است؛ توکن لازم نیست، اما با توکن محدودیت تعداد درخواست GitHub هم برداشته می‌شود.
    'token' => '',

    // branch: شاخه‌ای که GitHub Actions می‌سازد
    // public_link: پوشه وب همین دامنه در cPanel (Domains ← Document Root)؛ اسکریپت آن را به نسخه فعال لینک می‌کند.
    //   فقط پوشه خالی یا دارای فایل‌های پیش‌فرض cPanel جایگزین می‌شود؛ پوشه‌ای که فایل دارد دست نمی‌خورد.
    //   public_html (پوشه دامنه اصلی هاست) فقط با 'allow_public_html' => true پذیرفته می‌شود.
    'environments' => [
        // test.gobiz.ir — از شاخه develop
        'staging' => ['branch' => 'release/staging', 'public_link' => '~/test.gobiz.ir'],
        // gobiz.ir — از شاخه main
        'production' => ['branch' => 'release/production', 'public_link' => '~/gobiz.ir'],
    ],
];
