<?php
namespace App\Http\Controllers;

/** صفحات ثابت: درباره ما، قوانین، حریم خصوصی، تماس. متن قوانین پیش‌نویس است و پیش از انتشار باید حقوقدان بازبینی کند. */
class PageController extends Controller {
    private const PAGES = ['about' => 'درباره ما', 'terms' => 'قوانین و شرایط استفاده', 'privacy' => 'حریم خصوصی', 'contact' => 'تماس با ما'];

    public function show(string $page) {
        abort_unless(isset(self::PAGES[$page]), 404);
        return view('pages.'.$page, ['title' => self::PAGES[$page]]);
    }
}
