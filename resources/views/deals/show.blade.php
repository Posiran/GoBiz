@extends('layouts.app')
@section('title', 'معامله | Gobiz')
@section('robots', 'noindex,nofollow')
@section('content')
@php $F = '\App\Support\Fa'; @endphp
<h2>معامله: {{ $deal->listing->title }}</h2>
<p class="meta">خریدار: {{ $deal->buyer->name }} · فروشنده: {{ $deal->seller->name }} · وضعیت: <span class="badge">{{ \App\Models\Deal::LABELS[$deal->status] }}</span></p>
<div class="card">
<p>مبلغ پیشنهادی: <b>{{ $F::digits(number_format($deal->offer_amount)) }}</b> تومان @if($deal->stake_percent)· سهم: {{ $deal->stake_percent }}٪ @endif @if($deal->valid_until)· اعتبار تا {{ $F::jdate($deal->valid_until) }} @endif</p>
@if($deal->terms)<p style="white-space:pre-line">{{ $deal->terms }}</p>@endif
<p class="meta">نامه قصد (LOI) تعهد قانونی خرید نیست و پرداخت در گوبیز انجام نمی‌شود؛ قرارداد و تسویه خارج از سامانه و با مسئولیت طرفین انجام می‌شود.</p>
</div>
<h3>گفتگو</h3>
@foreach($deal->messages as $m)
<div class="card"><b>{{ $m->user->name }}</b> <small class="meta">{{ $F::jdate($m->created_at) }}</small><p style="white-space:pre-line">{{ $m->body }}</p></div>
@endforeach
@if($deal->isOpen())
<form method="post" action="{{ route('deals.message', $deal) }}">@csrf
<textarea name="body" required minlength="2" maxlength="2000" placeholder="پیام شما"></textarea><button>ارسال</button></form>
<form method="post" action="{{ route('deals.decide', $deal) }}">@csrf
@if($isSeller)<button name="action" value="accept">پذیرش نامه قصد</button><button name="action" value="reject">رد</button>
@else<button name="action" value="withdraw">انصراف</button>@endif</form>
@endif
@endsection
