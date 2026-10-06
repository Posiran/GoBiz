<?php
namespace App\Console\Commands;
use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command {
    protected $signature = 'app:make-admin {mobile : مثل 09123456789}';
    protected $description = 'ساخت یا ارتقای کاربر به مدیر';

    public function handle(): int {
        $u = User::firstOrNew(['mobile' => $this->argument('mobile')]);
        $u->name = $u->name ?: 'مدیر سایت';
        // رمز تصادفی فقط برای سازگاری با users.password؛ ورود وب به‌صورت OTP-only است.
        $u->password = $u->password ?: bin2hex(random_bytes(32));
        $u->forceFill(['role' => 'admin', 'mobile_verified_at' => now()])->save();
        $this->info("مدیر آماده شد: {$u->mobile}"); return 0;
    }
}
