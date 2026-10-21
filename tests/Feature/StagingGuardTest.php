<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** محافظ نسخه تست: رمز HTTP + noindex؛ بدون STAGING_PASSWORD هیچ تأثیری ندارد. */
class StagingGuardTest extends TestCase {
    use RefreshDatabase;

    private function basic(string $user, string $pass): array {
        return ['Authorization' => 'Basic '.base64_encode($user.':'.$pass)];
    }

    public function test_diagnostics(): void {
        $loaded = count(app()->getProviders(\App\Providers\GobizServiceProvider::class));
        $web = app(\Illuminate\Contracts\Http\Kernel::class)->getMiddlewareGroups()['web'] ?? [];
        $this->assertContains(\App\Http\Middleware\StagingGuard::class, app('router')->getMiddlewareGroups()['web'] ?? [], 'router web: '.count(app('router')->getMiddlewareGroups()['web'] ?? []));
        $this->assertSame(1, $loaded, 'provider not loaded');
        $this->assertContains(\App\Http\Middleware\StagingGuard::class, $web, 'web group: '.json_encode($web));
    }

    public function test_inactive_without_password(): void {
        config(['gobiz.staging.password' => '']);
        $r = $this->get('/up');
        $r->assertOk();
        $this->assertNull($r->headers->get('X-Robots-Tag'));
    }

    public function test_requires_password_and_blocks_indexing(): void {
        config(['gobiz.staging.user' => 'gobiz', 'gobiz.staging.password' => 'secret']);
        $r = $this->get('/login');
        $r->assertStatus(401);
        $this->assertStringContainsString('Basic', (string) $r->headers->get('WWW-Authenticate'));
        $this->assertStringContainsString('noindex', (string) $r->headers->get('X-Robots-Tag'));
    }

    public function test_wrong_password_is_rejected(): void {
        config(['gobiz.staging.user' => 'gobiz', 'gobiz.staging.password' => 'secret']);
        $this->withHeaders($this->basic('gobiz', 'wrong'))->get('/login')->assertStatus(401);
    }

    public function test_correct_password_passes_with_noindex(): void {
        config(['gobiz.staging.user' => 'gobiz', 'gobiz.staging.password' => 'secret']);
        $r = $this->withHeaders($this->basic('gobiz', 'secret'))->get('/login');
        $r->assertOk();
        $this->assertStringContainsString('noindex', (string) $r->headers->get('X-Robots-Tag'));
    }

    public function test_health_check_and_bank_callback_stay_open(): void {
        config(['gobiz.staging.user' => 'gobiz', 'gobiz.staging.password' => 'secret']);
        $this->get('/up')->assertOk();
        $this->assertNotSame(401, $this->get('/payment/callback/zarinpal')->getStatusCode());
    }
}
