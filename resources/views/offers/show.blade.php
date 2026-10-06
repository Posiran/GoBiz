@extends('layouts.app')
@section('title', ($offer->number ?: 'پیش‌نویس پیش‌فاکتور') . ' | Gobiz')
@section('robots', 'noindex,nofollow')
@section('content')
@php($F = '\App\Support\Fa')
<div class="crumbs"><a href="{{ route('offers.index', $isSeller ? ['tab' => 'issued'] : []) }}">پیش‌فاکتورها</a></div>
<article class="box" style="margin-top:12px">
  <h1 style="margin:0">پیش‌فاکتور {{ $offer->number ?: '(پیش‌نویس)' }} <span class="badge">{{ \App\Models\Offer::STATUS[$offer->status] }}</span></h1>
  <p class="meta">فروشنده: <b>{{ $offer->company->name }}</b> · خریدار: <b>{{ $offer->buyer->name }}</b>
    @if($offer->sent_at) · تاریخ صدور: {{ $F::jdate($offer->sent_at) }}@endif @if($offer->valid_until) · اعتبار تا {{ $F::jdate($offer->valid_until) }}@endif</p>
  <div style="overflow-x:auto"><table class="tbl"><tr><th>#</th><th>شرح</th><th>واحد</th><th>مقدار</th><th>قیمت واحد</th><th>جمع</th></tr>
  @foreach($offer->items as $i => $it)
    <tr><td>{{ $F::digits($i + 1) }}</td><td>{{ $it->title }}@if($it->description)<br><small class="meta">{{ $it->description }}</small>@endif</td><td>{{ $it->unit }}</td>
      <td>{{ $F::digits(rtrim(rtrim(number_format($it->quantity, 3, '.', ''), '0'), '.')) }}</td><td>{{ $F::digits(number_format($it->unit_price)) }}</td><td>{{ $F::digits(number_format($it->line_total)) }}</td></tr>
  @endforeach</table></div>
  <table class="spec" style="max-width:420px;margin:12px 0 0 auto">
    <tr><th>جمع اقلام</th><td>{{ $F::price($offer->subtotal) }}</td></tr>
    @if($offer->tax_amount)<tr><th>مالیات ({{ $F::digits($offer->tax_percent) }}٪)</th><td>{{ $F::price($offer->tax_amount) }}</td></tr>@endif
    @if($offer->shipping_cost)<tr><th>هزینه حمل</th><td>{{ $F::price($offer->shipping_cost) }}</td></tr>@endif
    <tr><th><b>جمع کل</b></th><td><b>{{ $F::price($offer->total) }}</b></td></tr>
    @if($offer->advance_percent)<tr><th>پیش‌پرداخت ({{ $F::digits($offer->advance_percent) }}٪)</th><td>{{ $F::price((int) round($offer->total * $offer->advance_percent / 100)) }}</td></tr>@endif
  </table>
  <h2>شرایط</h2>
  <table class="spec">
    @if($offer->delivery_terms)<tr><th>شرایط تحویل</th><td>{{ \App\Models\Offer::DELIVERY[$offer->delivery_terms] }}@if($offer->delivery_place) — {{ $offer->delivery_place }}@endif</td></tr>@endif
    @if($offer->delivery_days)<tr><th>مدت تحویل</th><td>{{ $F::digits($offer->delivery_days) }} روز پس از پذیرش</td></tr>@endif
    @if($offer->warranty_months)<tr><th>گارانتی</th><td>{{ $F::digits($offer->warranty_months) }} ماه</td></tr>@endif
    <tr><th>مهلت بازرسی پس از تحویل</th><td>{{ $F::digits($offer->inspection_days) }} روز</td></tr>
    @if($offer->payment_terms)<tr><th>تسویه مانده</th><td style="white-space:pre-line">{{ $offer->payment_terms }}</td></tr>@endif
    @if($offer->notes)<tr><th>توضیحات</th><td style="white-space:pre-line">{{ $offer->notes }}</td></tr>@endif
    @if($offer->reject_reason)<tr><th>دلیل رد</th><td>{{ $offer->reject_reason }}</td></tr>@endif
  </table>
  @if($offer->order)<p><a class="btn fill" href="{{ route('orders.show', $offer->order) }}">مشاهده سفارش {{ $offer->order->number }}</a></p>@endif
</article>

@if($isSeller)
<div class="acts" style="margin-top:12px">
  @if($offer->isDraft())
    <a class="btn" href="{{ route('offers.edit', $offer) }}">ویرایش</a>
    <form method="post" action="{{ route('offers.send', $offer) }}">@csrf<button class="btn fill">ارسال برای خریدار</button></form>
  @elseif($offer->status === 'sent')
    <form method="post" action="{{ route('offers.withdraw', $offer) }}" onsubmit="return confirm('پیش‌فاکتور پس گرفته شود؟')">@csrf<button class="btn">پس‌گرفتن پیش‌فاکتور</button></form>
  @endif
</div>
@endif
@if($isBuyer && $offer->isOpen())
<div class="cols" style="display:grid;grid-template-columns:2fr 1fr;gap:12px;margin-top:12px">
  <form class="box form" method="post" action="{{ route('offers.accept', $offer) }}" style="margin:0;max-width:none">@csrf
    <h2 style="margin:0">پذیرش و ثبت سفارش</h2>
    <label>آدرس کامل تحویل<textarea name="delivery_address" rows="3" minlength="10" maxlength="1000" required>{{ old('delivery_address') }}</textarea></label>
    <p class="meta">با پذیرش، شرایط و مبالغ بالا به‌عنوان سفارش قطعی ثبت می‌شود و تغییر نمی‌کند.</p>
    <button class="btn fill">پذیرش پیش‌فاکتور</button></form>
  <form class="box form" method="post" action="{{ route('offers.reject', $offer) }}" style="margin:0;max-width:none">@csrf
    <h2 style="margin:0">رد پیش‌فاکتور</h2>
    <label>دلیل (اختیاری)<input name="reason" maxlength="300"></label><button class="btn">رد</button></form>
</div>
@endif
@endsection
