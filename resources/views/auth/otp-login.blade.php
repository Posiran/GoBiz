@extends('layouts.app')
@section('title', 'کد ورود | Gobiz')
@section('content')
<div class="box form" style="max-width:420px">
  <h1>کد ورود را وارد کنید</h1>
  <p class="meta">کد ۵ رقمی به شماره <b dir="ltr">{{ $mobile }}</b> توسط IPPanel ارسال شده است.</p>
  <form method="post" action="{{ route('login.otp.verify') }}" class="form" style="margin:0">
    @csrf
    <label>کد یک‌بارمصرف
      <input name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="5" pattern="[0-9۰-۹]{5}" required autofocus>
    </label>
    <button class="btn fill">ورود</button>
  </form>
  <form method="post" action="{{ route('login.otp.resend') }}" style="margin:8px 0 0">
    @csrf
    <button class="btn" style="background:none;cursor:pointer;font:inherit;color:inherit">ارسال دوباره کد</button>
  </form>
  <p class="meta"><a href="{{ route('login') }}">تغییر شماره موبایل</a></p>
</div>
@endsection
