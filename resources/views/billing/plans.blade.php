@extends('layouts.app')
@section('title', 'پلن‌ها و پرداخت | Gobiz')
@section('content')
@php($F = '\App\Support\Fa')
@php($st = ['paid' => 'موفق', 'pending' => 'در انتظار', 'failed' => 'ناموفق'])
<h2>پلن‌ها و پرداخت</h2>
<div class="box" style="margin-bottom:16px">
  @if($current)<b>پلن فعلی: {{ $current->plan->name }}</b> · تا {{ $F::jdate($current->ends_at) }}
  @else<b>پلن رایگان</b>@endif
  <div class="meta">آگهی فعال و در انتظار: {{ $F::digits($used) }} از {{ $F::digits($limit) }}</div>
</div>
<div class="grid">
@foreach($plans as $p)
  <div class="item"><div class="b" style="padding:18px;gap:8px">
    <h3 style="font-size:20px">{{ $p->name }}</h3>
    <span class="price" style="font-size:20px">{{ $F::price($p->price) }}</span>
    <span class="meta">{{ $F::digits($p->duration_days) }} روز · تا {{ $F::digits($p->max_listings) }} آگهی</span>
    <span class="meta">{{ $p->featured_slots ? $F::digits($p->featured_slots) . ' آگهی ویژه' : 'بدون آگهی ویژه' }}</span>
    @if($current && $current->plan_id == $p->id)<a class="btn fill" href="{{ route('billing.checkout', $p) }}">تمدید</a>
    @else<a class="btn fill" href="{{ route('billing.checkout', $p) }}">خرید</a>@endif
  </div></div>
@endforeach
</div>
@if($payments->isNotEmpty())
<h2>پرداخت‌های اخیر</h2>
<table class="tbl"><tr><th>تاریخ</th><th>پلن</th><th>مبلغ</th><th>وضعیت</th><th>کد پیگیری</th></tr>
@foreach($payments as $x)
<tr><td>{{ $F::jdate($x->created_at) }}</td><td>{{ $x->plan?->name }}</td><td>{{ $F::price($x->amount) }}</td>
  <td><a href="{{ route('billing.result', $x) }}"><span class="badge">{{ $st[$x->status] }}</span></a></td><td>{{ $x->ref_id }}</td></tr>
@endforeach</table>
@endif
@endsection
