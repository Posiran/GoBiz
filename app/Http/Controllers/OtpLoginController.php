<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Fa;
use App\Support\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OtpLoginController extends Controller
{
    public function request(Request $r)
    {
        $r->merge(['mobile' => Fa::latin($r->input('mobile'))]);
        $d = $r->validate([
            'mobile' => ['required', 'regex:/^09\d{9}$/'],
        ], ['mobile.regex' => 'شماره موبایل باید مثل 09123456789 باشد.']);

        $u = User::where('mobile', $d['mobile'])->first();
        if (!$u) {
            return back()->withInput()->withErrors([
                'mobile' => 'برای این شماره حسابی در Gobiz ثبت نشده است. ابتدا ثبت‌نام کنید.'
            ]);
        }

        $e = Otp::send($u->mobile, 'login');
        if ($e) return back()->withInput()->withErrors(['mobile' => $e]);

        $r->session()->put('otp_login_mobile', $u->mobile);
        return redirect()->route('login.otp')->with('msg', 'کد ورود به موبایل شما پیامک شد.');
    }

    public function show(Request $r)
    {
        $mobile = $r->session()->get('otp_login_mobile');
        if (!$mobile) return redirect()->route('login');
        return view('auth.otp-login', ['mobile' => $mobile]);
    }

    public function resend(Request $r)
    {
        $mobile = $r->session()->get('otp_login_mobile');
        if (!$mobile) return redirect()->route('login');
        $e = Otp::send($mobile, 'login');
        return $e
            ? back()->withErrors(['code' => $e])
            : back()->with('msg', 'کد ورود دوباره ارسال شد.');
    }

    public function verify(Request $r)
    {
        $mobile = $r->session()->get('otp_login_mobile');
        if (!$mobile) return redirect()->route('login');

        $r->validate(['code' => 'required|string|max:10']);
        if (!Otp::check($mobile, 'login', $r->input('code'))) {
            return back()->withErrors(['code' => 'کد واردشده درست نیست یا منقضی شده است.']);
        }

        $u = User::where('mobile', $mobile)->first();
        if (!$u) return redirect()->route('login')->withErrors(['mobile' => 'حساب کاربری پیدا نشد.']);

        Auth::login($u, true);
        $r->session()->forget('otp_login_mobile');
        $r->session()->regenerate();

        if (!$u->mobile_verified_at) {
            $u->forceFill(['mobile_verified_at' => now()])->save();
        }

        return redirect()->intended($u->isAdmin() ? route('admin.index') : route('seller.dashboard'));
    }
}
