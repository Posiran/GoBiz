<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModeratorRoleTest extends TestCase {
    use RefreshDatabase;

    protected bool $seed = true;

    private function user(string $role, string $mobile): User {
        $u = User::create(['name' => $role, 'mobile' => $mobile, 'password' => 'x']);
        $u->forceFill(['role' => $role, 'mobile_verified_at' => now()])->save();
        return $u;
    }

    public function test_moderator_reviews_content_but_not_audit_or_disputes(): void {
        $mod = $this->user('moderator', '09160000001');
        $this->actingAs($mod)->get(route('admin.index'))->assertOk()->assertDontSee(route('admin.audit'), false);
        $this->actingAs($mod)->get(route('admin.audit'))->assertForbidden();
        $this->actingAs($mod)->get(route('admin.orders.index'))->assertForbidden();
    }

    public function test_admin_keeps_full_access_and_regular_users_are_blocked(): void {
        $admin = $this->user('admin', '09160000002');
        $this->actingAs($admin)->get(route('admin.index'))->assertOk()->assertSee(route('admin.audit'), false);
        $this->actingAs($admin)->get(route('admin.audit'))->assertOk();
        $this->actingAs($this->user('seller', '09160000003'))->get(route('admin.index'))->assertForbidden();
    }
}
