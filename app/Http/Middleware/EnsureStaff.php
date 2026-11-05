<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

/** مدیر یا ناظر: دسترسی به بررسی شرکت‌ها و آگهی‌ها. گزارش اقدام‌ها و حل اختلاف فقط برای مدیر است (EnsureAdmin). */
class EnsureStaff {
    public function handle(Request $request, Closure $next) {
        abort_unless($request->user()?->isStaff(), 403);
        return $next($request);
    }
}
