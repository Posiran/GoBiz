@extends('layouts.app')
@section('title', 'تأیید موبایل | Gobiz')
@section('content')
<div class="box form" style="max-width:420px">
  <h1>تأیید شماره موبایل</h1>
  <p class="meta">کد ۵ رقمی به شماره <b dir="ltr">{{ auth()->user()->mobile }}</b> پیامک شد.</p>
  <form method="post" action="{{ route('verify.check') }}" class="form" style="margin:0">@csrf
    <label>کد تأیید<input name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="10" required autofocus></label>
    <button class="btn fill">تأیید</button>
  </form>
  <form method="post" action="{{ route('verify.resend') }}" style="margin:0">@csrf<button class="btn" style="background:none;cursor:pointer;font:inherit;color:inherit">ارسال دوباره کد</button></form>
</div>
@endsection
