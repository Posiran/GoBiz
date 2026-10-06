<?php
// IPPanel Edge API — https://apidoc.ippanel.com/ (Send Pattern: /v1/api/send)
return [
    'base_url' => env('IPPANEL_BASE_URL', 'https://edge.ippanel.com/v1'),
    'token'    => env('IPPANEL_TOKEN'),          // توکن API (هدر Authorization)
    'from'     => env('IPPANEL_FROM', ''),       // شماره ارسال‌کننده به فرمت E.164، مثل +983000505
    'patterns' => [
        'otp'     => env('IPPANEL_PATTERN_OTP'),      // پارامتر: code
        'inquiry' => env('IPPANEL_PATTERN_INQUIRY'),  // اختیاری؛ پارامتر: title
        'offer'   => env('IPPANEL_PATTERN_OFFER'),      // اختیاری؛ پارامتر: number (پیش‌فاکتور تازه برای خریدار)
        'access'  => env('IPPANEL_PATTERN_ACCESS'),     // اختیاری؛ پارامتر: title (درخواست دسترسی به اطلاعات محرمانه)
        'order'   => env('IPPANEL_PATTERN_ORDER'),      // اختیاری؛ پارامتر: number (سفارش تازه برای فروشنده)
    ],
];
