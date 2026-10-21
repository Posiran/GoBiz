@extends('layouts.app')
@section('title', 'نتیجه پرداخت | Gobiz')
@section('content')
@php($F = '\App\Support\Fa')
<div class="box" style="max-width:520px;margin:24px auto">
  @if($payment->status === 'paid')
    <h1 style="color:var(--ok)">پرداخت موفق</h1>
    <p>پلن «{{ $payment->plan?->name }}» برای شرکت شما فعال شد.</p>
    <p class="meta">مبلغ: {{ $F::price($payment->amount) }} · کد پیگیری: <b dir="ltr">{{ $payment->ref_id }}</b> · {{ $F::jdate($payment->paid_at) }}</p>
  @elseif($payment->status === 'failed')
    <h1 style="color:#c0392b">پرداخت ناموفق</h1>
    <p>{{ $payment->error ?: 'پرداخت انجام نشد.' }}</p>
    <p class="meta">اگر مبلغی از حساب شما کسر شده، طبق قوانین شاپرک معمولاً ظرف ۷۲ ساعت برگشت داده می‌شود.</p>
  @else
    <h1>در حال بررسی</h1>
    <p>نتیجه پرداخت هنوز از بانک تأیید نشده است. چند دقیقه بعد همین صفحه را تازه کنید. اگر مبلغ کسر شده و پلن فعال نشد، با پشتیبانی تماس بگیرید.</p>
  @endif
  <a class="btn fill" href="{{ route('billing.plans') }}">بازگشت به پلن‌ها</a>
</div>
@endsection
