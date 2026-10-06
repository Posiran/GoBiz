@extends('layouts.app')
@section('title', 'ثبت‌نام | Gobiz')
@section('content')
<form class="box form" method="post" action="{{ route('register.post') }}" style="max-width:420px">
  @csrf
  <h1>ساخت حساب Gobiz</h1>
  <p class="meta">احراز هویت با شماره موبایل و کد یک‌بارمصرف انجام می‌شود.</p>
  <label>نام و نام خانوادگی
    <input name="name" value="{{ old('name') }}" required autofocus>
  </label>
  <label>شماره موبایل
    <input name="mobile" value="{{ old('mobile') }}" inputmode="numeric" autocomplete="tel" placeholder="09123456789" required>
  </label>
  <button class="btn fill">ثبت‌نام و دریافت کد</button>
  <p class="meta">قبلاً ثبت‌نام کرده‌اید؟ <a href="{{ route('login') }}"><b>ورود با OTP</b></a></p>
</form>
@endsection
