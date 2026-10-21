@extends('layouts.app')
@section('title', 'سفارش ' . $order->number . ' | Gobiz')
@section('robots', 'noindex,nofollow')
@section('content')
@php
  $F = '\App\Support\Fa';
  $can = fn ($to) => \App\Support\Procurement::canMove($order->status, $to, $role);
  $S = \App\Models\Order::STATUS;
  $inspectionEnds = $order->inspectionEndsAt();
@endphp
<div class="crumbs"><a href="{{ route('orders.index', $role === 'seller' ? ['tab' => 'selling'] : []) }}">سفارش‌ها</a></div>
<article class="box" style="margin-top:12px">
  <h1 style="margin:0">سفارش {{ $order->number }} <span class="badge">{{ $S[$order->status] }}</span></h1>
  <p class="meta">فروشنده: <b>{{ $order->company->name }}</b> · خریدار: <b>{{ $order->buyerCompany?->name ?? $order->buyer->name }}</b> · ثبت: {{ $F::jdate($order->created_at) }}
    @if($order->offer) · از روی <a href="{{ route('offers.show', $order->offer) }}">پیش‌فاکتور {{ $order->offer->number }}</a>@endif</p>
  <table class="spec">
    @if($order->delivery_terms)<tr><th>شرایط تحویل</th><td>{{ \App\Models\Offer::DELIVERY[$order->delivery_terms] }}@if($order->delivery_place) — {{ $order->delivery_place }}@endif</td></tr>@endif
    <tr><th>آدرس تحویل</th><td style="white-space:pre-line">{{ $order->delivery_address }}</td></tr>
    @if($order->delivery_due_date)<tr><th>موعد تحویل</th><td>{{ $F::jdate($order->delivery_due_date) }}</td></tr>@endif
    @if($order->carrier || $order->tracking_no)<tr><th>حمل</th><td>{{ $order->carrier }} {{ $order->tracking_no ? '· کد رهگیری: ' . $order->tracking_no : '' }}</td></tr>@endif
    @if($order->warranty_months)<tr><th>گارانتی</th><td>{{ $F::digits($order->warranty_months) }} ماه</td></tr>@endif
    <tr><th>مهلت بازرسی</th><td>{{ $F::digits($order->inspection_days) }} روز پس از تحویل@if($order->status === 'delivered' && $inspectionEnds) (تا {{ $F::jdate($inspectionEnds) }})@endif</td></tr>
    @if($order->payment_terms)<tr><th>تسویه مانده</th><td style="white-space:pre-line">{{ $order->payment_terms }}</td></tr>@endif
    @if($order->cancel_reason)<tr><th>دلیل لغو</th><td>{{ $order->cancel_reason }}</td></tr>@endif
  </table>
  <h2>اقلام</h2>
  <div style="overflow-x:auto"><table class="tbl"><tr><th>#</th><th>شرح</th><th>واحد</th><th>مقدار</th><th>قیمت واحد</th><th>جمع</th></tr>
  @foreach($order->items as $i => $it)
    <tr><td>{{ $F::digits($i + 1) }}</td><td>{{ $it->title }}</td><td>{{ $it->unit }}</td><td>{{ $F::digits(rtrim(rtrim(number_format($it->quantity, 3, '.', ''), '0'), '.')) }}</td>
      <td>{{ $F::digits(number_format($it->unit_price)) }}</td><td>{{ $F::digits(number_format($it->line_total)) }}</td></tr>
  @endforeach</table></div>
  <table class="spec" style="max-width:420px;margin:12px 0 0 auto">
    <tr><th>جمع کل</th><td><b>{{ $F::price($order->total) }}</b></td></tr>
    @if($order->advance_amount)<tr><th>پیش‌پرداخت لازم</th><td>{{ $F::price($order->advance_amount) }}</td></tr>@endif
    <tr><th>پرداخت‌شده (تأییدشده)</th><td>{{ $F::price($order->paid_amount) }}</td></tr>
    <tr><th>مانده</th><td>{{ $F::price($order->remaining()) }}</td></tr>
  </table>
</article>

{{-- اقدام‌های وضعیت --}}
<div class="box" style="margin-top:12px"><h2 style="margin-top:0">اقدام‌ها</h2><div class="acts" style="align-items:flex-start">
  @if($role === 'seller' && $can('preparing'))<form method="post" action="{{ route('orders.preparing', $order) }}">@csrf<button class="btn">شروع آماده‌سازی (بدون انتظار برای پیش‌پرداخت)</button></form>@endif
  @if($role === 'seller' && $can('shipped'))
    <form method="post" action="{{ route('orders.ship', $order) }}" class="form" style="margin:0;max-width:none;flex-direction:row;flex-wrap:wrap;align-items:end">@csrf
      <label>حمل‌کننده<input name="carrier" maxlength="100"></label><label>کد رهگیری<input name="tracking_no" maxlength="100"></label><button class="btn fill">ثبت ارسال</button></form>@endif
  @if($can('delivered') && in_array($role, ['buyer', 'seller']))<form method="post" action="{{ route('orders.deliver', $order) }}">@csrf<button class="btn fill">{{ $role === 'buyer' ? 'کالا را تحویل گرفتم' : 'ثبت تحویل به خریدار' }}</button></form>@endif
  @if($role === 'buyer' && $can('completed'))<form method="post" action="{{ route('orders.complete', $order) }}" onsubmit="return confirm('سفارش تأیید نهایی شود؟')">@csrf<button class="btn fill">تأیید نهایی و تکمیل سفارش</button></form>@endif
  @if($role === 'buyer' && $can('disputed'))
    <details><summary class="btn">اعلام اختلاف</summary><form method="post" action="{{ route('orders.dispute', $order) }}" class="form" style="margin:8px 0">@csrf<label>دلیل<textarea name="reason" rows="3" maxlength="300" required></textarea></label><button class="btn">ثبت اختلاف</button></form></details>@endif
  @if(in_array($role, ['buyer', 'seller']) && $can('cancelled') && !($role === 'buyer' && $order->paid_amount > 0))
    <details><summary class="btn">لغو سفارش</summary><form method="post" action="{{ route('orders.cancel', $order) }}" class="form" style="margin:8px 0">@csrf<label>دلیل{{ $role === 'seller' ? ' (الزامی)' : '' }}<input name="reason" maxlength="300" {{ $role === 'seller' ? 'required' : '' }}></label><button class="btn">لغو</button></form></details>@endif
  @if($role === 'admin' && $order->status === 'disputed')
    <form method="post" action="{{ route('admin.orders.resolve', $order) }}" class="form" style="margin:0;max-width:none;flex-direction:row;flex-wrap:wrap;align-items:end">@csrf
      <label>تصمیم<select name="to"><option value="completed">تکمیل سفارش (به نفع فروشنده)</option><option value="cancelled">لغو سفارش (به نفع خریدار)</option></select></label>
      <label>توضیح<input name="note" maxlength="300" required></label><button class="btn fill">بستن اختلاف</button></form>@endif
</div></div>

{{-- اسناد پرداخت --}}
<h2>اسناد پرداخت</h2>
<p class="meta">پول از طریق Gobiz جابه‌جا نمی‌شود. خریدار پرداخت را مستقیم به فروشنده انجام می‌دهد و سند را اینجا ثبت می‌کند؛ فروشنده دریافت را تأیید می‌کند.</p>
@if($order->payments->isEmpty())<div class="empty">سندی ثبت نشده است.</div>
@else
<div style="overflow-x:auto"><table class="tbl"><tr><th>تاریخ</th><th>نوع / روش</th><th>مبلغ</th><th>پیگیری / بانک</th><th>فیش</th><th>وضعیت</th><th></th></tr>
@foreach($order->payments as $p)
<tr><td>{{ $F::jdate($p->paid_at) }}</td><td>{{ \App\Models\OrderPayment::KIND[$p->kind] }} · {{ \App\Models\OrderPayment::METHOD[$p->method] }}</td><td>{{ $F::price($p->amount) }}</td>
  <td>{{ $p->reference_no }} {{ $p->bank_name }}</td>
  <td>@if($p->receipt_path)<a href="{{ route('orders.receipt', [$order, $p]) }}" target="_blank" rel="noopener">مشاهده</a>@endif</td>
  <td><span class="badge">{{ \App\Models\OrderPayment::STATUS[$p->status] }}</span>@if($p->reject_reason)<br><small class="meta">{{ $p->reject_reason }}</small>@endif</td>
  <td>@if($role === 'seller' && $p->status === 'pending')
    <form method="post" action="{{ route('orders.payments.review', [$order, $p]) }}">@csrf<input type="hidden" name="action" value="confirm"><button>تأیید دریافت</button></form>
    <details><summary>رد</summary><form method="post" action="{{ route('orders.payments.review', [$order, $p]) }}">@csrf<input type="hidden" name="action" value="reject"><input name="reason" maxlength="300" placeholder="دلیل رد" required><button>رد سند</button></form></details>
  @endif</td></tr>
@endforeach</table></div>
@endif
@if($role === 'buyer' && $order->status !== 'cancelled' && \App\Support\Procurement::payableLeft($order) > 0)
<details class="box" style="margin-top:12px"><summary class="btn fill" style="display:inline-block;cursor:pointer">ثبت سند پرداخت</summary>
  <form class="form" method="post" action="{{ route('orders.pay', $order) }}" enctype="multipart/form-data" style="margin:12px 0 0;max-width:none">@csrf
    <div class="cols">
      <label>نوع<select name="kind">@foreach(\App\Models\OrderPayment::KIND as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></label>
      <label>روش<select name="method">@foreach(\App\Models\OrderPayment::METHOD as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></label>
      <label>مبلغ (تومان؛ حداکثر {{ $F::digits(number_format(\App\Support\Procurement::payableLeft($order))) }})<input name="amount" inputmode="numeric" required></label>
      <label>تاریخ پرداخت<input type="date" name="paid_at" max="{{ today()->toDateString() }}" required></label>
      <label>شماره پیگیری / سریال چک<input name="reference_no" maxlength="60"></label>
      <label>بانک<input name="bank_name" maxlength="80"></label>
      <label>نام پرداخت‌کننده<input name="payer_name" maxlength="120"></label>
      <label>تصویر فیش (jpg, png, webp, pdf تا ۴ مگابایت)<input type="file" name="receipt" accept=".jpg,.jpeg,.png,.webp,.pdf"></label>
    </div>
    <label>توضیح<input name="note" maxlength="300"></label><button class="btn fill">ثبت سند</button></form></details>
@endif

<h2>تاریخچه</h2>
<ul class="timeline box">@foreach($order->events as $e)
  <li><b>{{ $e->type === 'status' ? ($S[$e->from_status] ?? '—') . ' ← ' . ($S[$e->to_status] ?? '') : ['created' => 'ثبت سفارش', 'payment_added' => 'ثبت سند پرداخت', 'payment_confirmed' => 'تأیید پرداخت', 'payment_rejected' => 'رد پرداخت'][$e->type] ?? $e->type }}</b>
    @if($e->note) — {{ $e->note }}@endif<br><small>{{ $e->user?->name ?? 'سیستم' }} · {{ $F::jdate($e->created_at) }}</small></li>@endforeach</ul>
@endsection
