<?php
namespace Tests\Feature;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;
class ZzDiagTest extends TestCase {
    public function test_zz(): void {
        $php = Blade::compileString(file_get_contents(resource_path('views/listings/show.blade.php')));
        $out = [];
        foreach (explode("\n", $php) as $i => $l) {
            if (preg_match('/\b(if|elseif|else|endif|foreach|endforeach|endif;)\b\s*[:(;]|<\?php (end|else)/', $l)) $out[] = ($i+1).':'.mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($l))), 0, 50);
        }
        $this->fail('DIAG '.implode(' | ', $out));
    }
}
