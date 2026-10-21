@extends('layouts.app')
@section('title', 'پیش‌فاکتورها | Gobiz')
@section('content')
@php($F = '\App\Support\Fa')
<div class="tabs"><a class="{{ $tab === 'received' ? 'on' : '' }}" href="{{ route('offers.index') }}">دریافتی</a>
  @if(auth()->user()->company)<a class="{{ $tab === 'issued' ? 'on' : '' }}" href="{{ route('offers.index', ['tab' => 'issued']) }}">صادرشده</a>@endif
  <a href="{{ route('orders.index') }}">سفارش‌ها</a></div>
@if($tab === 'issued')<p class="meta">پیش‌فاکتور را از «درخواست‌های خریداران» یا از گفتگوی استعلام یک آگهی خودتان صادر کنید.</p>@endif
@if($offers->isEmpty())<div class="empty">پیش‌فاکتوری وجود ندارد.</div>
@else
<table class="tbl"><tr><th>شماره</th><th>{{ $tab === 'issued' ? 'خریدار' : 'فروشنده' }}</th><th>مبلغ کل</th><th>اعتبار تا</th><th>وضعیت</th></tr>
@foreach($offers as $o)
<tr><td><a href="{{ route('offers.show', $o) }}">{{ $o->number ?: 'پیش‌نویس #' . $F::digits($o->id) }}</a></td>
  <td>{{ $tab === 'issued' ? $o->buyer->name : $o->company->name }}</td><td>{{ $F::price($o->total) }}</td>
  <td>{{ $o->valid_until ? $F::jdate($o->valid_until) : '—' }}</td><td><span class="badge">{{ \App\Models\Offer::STATUS[$o->status] }}</span></td></tr>
@endforeach</table>
{{ $offers->links('pagination::default') }}
@endif
@endsection
