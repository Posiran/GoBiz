<?php
namespace Tests\Feature;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;
class ZzDiagTest extends TestCase {
    public function test_zz(): void {
        $php = Blade::compileString(file_get_contents(resource_path('views/listings/show.blade.php')));
        $html = preg_replace('/<\?php.*?\?>/s', '', $php);   // فقط بخش‌های HTML خام
        preg_match_all('/.{0,40}@(?:end)?(?:if|elseif|else|php|auth|guest|forelse|foreach|section|push)\b.{0,40}/su', $html, $m);
        $this->fail('DIAG '.json_encode(array_map(fn ($x) => preg_replace('/\s+/', ' ', $x), $m[0]), JSON_UNESCAPED_UNICODE));
    }
}
