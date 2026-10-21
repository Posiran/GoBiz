<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class EnsureHasCompany {
    public function handle(Request $request, Closure $next) {
        if (!$request->user()->company)
            return redirect()->route('seller.company.edit')->with('msg', 'ابتدا اطلاعات شرکت خود را ثبت کنید.');
        return $next($request);
    }
}
