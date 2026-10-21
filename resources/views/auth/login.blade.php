@extends('layouts.app')
@section('title', 'ورود با کد یک‌بارمصرف | Gobiz')
@section('content')
<form class="box form" method="post" action="{{ route('login.post') }}" style="max-width:420px">
  @csrf
  <h1>ورود به Gobiz</h1>
  <p class="meta">ورود بدون رمز عبور و فقط با کد یک‌بارمصرف پیامکی</p>
  <label>شماره موبایل
    <input name="mobile" value="{{ old('mobile') }}" inputmode="numeric" autocomplete="tel" placeholder="09123456789" required autofocus>
  </label>
  <button class="btn fill">دریافت کد ورود</button>
  <p class="meta">حساب ندارید؟ <a href="{{ route('register') }}"><b>ثبت‌نام کنید</b></a></p>
</form>
@endsection
