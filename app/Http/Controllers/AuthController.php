<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Support\Fa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller {
    public function register(Request $r) {
        $r->merge(['mobile' => Fa::latin($r->input('mobile'))]);
        $d = $r->validate([
            'name' => 'required|string|max:120',
            'mobile' => ['required', 'regex:/^09\d{9}$/', 'unique:users,mobile'],
        ], [
            'mobile.regex' => 'شماره موبایل باید مثل 09123456789 باشد.',
            'mobile.unique' => 'این شماره قبلاً ثبت شده است.',
        ]);

        // Password is kept only for compatibility with Laravel's default users table;
        // browser authentication is OTP-only and never uses this value.
        $d['password'] = bin2hex(random_bytes(32));
        $u = User::create($d);
        Auth::login($u);
        $r->session()->regenerate();

        $e = \App\Support\Otp::send($u->mobile, 'verify');
        return redirect()->route('verify.show')->with($e ? 'err' : 'msg', $e ?? 'ثبت‌نام انجام شد. کد تأیید به موبایل شما پیامک شد.');
    }

    /** ورود به سامانه فقط با OTP انجام می‌شود؛ رمز عبور دیگر در UI استفاده نمی‌شود. */
    public function login(Request $r) {
        return app(\App\Http\Controllers\OtpLoginController::class)->request($r);
    }

    public function logout(Request $r) {
        Auth::logout(); $r->session()->invalidate(); $r->session()->regenerateToken();
        return redirect()->route('home');
    }
}
