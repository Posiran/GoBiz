<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class EnsureMobileVerified {
    public function handle(Request $request, Closure $next) {
        $u = $request->user();
        if (!$u->mobile_verified_at && !$u->isAdmin())
            return redirect()->route('verify.show')->with('msg', 'ابتدا شماره موبایل خود را تأیید کنید.');
        return $next($request);
    }
}
