<?php
// مبالغ در پایگاه‌داده «تومان» هستند؛ هر سه درگاه با «ریال» (×۱۰) فراخوانی می‌شوند.
return [
    'free_listings' => (int) env('FREE_LISTINGS', 3),   // سقف آگهی فعال برای شرکت بدون پلن
    // درگاه‌های فعال (جدا با کاما)؛ فقط آن‌هایی که اطلاعاتشان کامل است نمایش داده می‌شوند
    'enabled' => array_values(array_filter(array_map('trim', explode(',', env('PAYMENT_ENABLED', 'zarinpal'))))),

    'zarinpal' => [
        'merchant_id' => env('ZARINPAL_MERCHANT_ID'),
        'sandbox' => (bool) env('ZARINPAL_SANDBOX', false),
    ],
    'behpardakht' => [   // به‌پرداخت ملت (SOAP؛ نیازمند افزونه PHP soap)
        'terminal_id' => env('BEHPARDAKHT_TERMINAL_ID'),
        'username' => env('BEHPARDAKHT_USERNAME'),
        'password' => env('BEHPARDAKHT_PASSWORD'),
        'wsdl' => env('BEHPARDAKHT_WSDL', 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl'),
        'start_url' => env('BEHPARDAKHT_START_URL', 'https://bpm.shaparak.ir/pgwchannel/startpay.mellat'),
    ],
    'sadad' => [         // سداد، بانک ملی
        'merchant_id' => env('SADAD_MERCHANT_ID'),
        'terminal_id' => env('SADAD_TERMINAL_ID'),
        'key' => env('SADAD_KEY'),   // کلید تراکنش (Base64)
        'base_url' => env('SADAD_BASE_URL', 'https://sadad.shaparak.ir/vpg/api/v0'),
        'purchase_url' => env('SADAD_PURCHASE_URL', 'https://sadad.shaparak.ir/VPG/Purchase'),
    ],
];
