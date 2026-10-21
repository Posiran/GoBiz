<?php
namespace App\Http\Controllers;
use App\Support\Otp;
use Illuminate\Http\Request;

class VerifyMobileController extends Controller {
    public function show(Request $r) {
        if ($r->user()->mobile_verified_at) return redirect()->route('seller.dashboard');
        return view('auth.verify');
    }
    public function resend(Request $r) {
        $e = Otp::send($r->user()->mobile, 'verify');
        return $e ? back()->withErrors($e) : back()->with('msg', 'کد تأیید پیامک شد.');
    }
    public function check(Request $r) {
        $r->validate(['code' => 'required|string|max:10']);
        $u = $r->user();
        if (!Otp::check($u->mobile, 'verify', $r->input('code'))) return back()->withErrors('کد واردشده درست نیست یا منقضی شده است.');
        $u->forceFill(['mobile_verified_at' => now()])->save();
        return redirect()->route('seller.dashboard')->with('msg', 'شماره موبایل شما تأیید شد.');
    }
}
