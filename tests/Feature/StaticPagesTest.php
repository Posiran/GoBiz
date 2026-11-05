<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaticPagesTest extends TestCase {
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_static_pages_render_and_footer_links_to_them(): void {
        foreach (['about' => 'درباره ما', 'terms' => 'قوانین و شرایط استفاده', 'privacy' => 'حریم خصوصی', 'contact' => 'تماس با ما'] as $p => $t)
            $this->get(route('pages.show', $p))->assertOk()->assertSee($t);
        $this->get('/page/unknown')->assertNotFound();
        $this->get(route('pages.show', 'terms'))->assertSee(route('pages.show', 'privacy'), false);
    }

    public function test_contact_page_shows_configured_details_only(): void {
        $this->get(route('pages.show', 'contact'))->assertSee('هنوز تنظیم نشده');
        config(['gobiz.contact_email' => 'info@example.test']);
        $this->get(route('pages.show', 'contact'))->assertSee('info@example.test');
    }
}
