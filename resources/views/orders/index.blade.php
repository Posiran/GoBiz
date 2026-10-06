@extends('layouts.app')
@section('title', 'سفارش‌ها | Gobiz')
@section('content')
@php($F = '\App\Support\Fa')
<div class="tabs"><a class="{{ $tab === 'buying' ? 'on' : '' }}" href="{{ route('orders.index') }}">خرید من</a>
  @if(auth()->user()->company)<a class="{{ $tab === 'selling' ? 'on' : '' }}" href="{{ route('orders.index', ['tab' => 'selling']) }}">فروش من</a>@endif
  <a href="{{ route('offers.index') }}">پیش‌فاکتورها</a></div>
<div class="tabs"><a class="{{ $status ? '' : 'on' }}" href="{{ route('orders.index', ['tab' => $tab]) }}">همه</a>
  @foreach(\App\Models\Order::STATUS as $k => $v)<a class="{{ $status === $k ? 'on' : '' }}" href="{{ route('orders.index', ['tab' => $tab, 'status' => $k]) }}">{{ $v }}</a>@endforeach</div>
@if($orders->isEmpty())<div class="empty">سفارشی وجود ندارد. سفارش با پذیرش یک پیش‌فاکتور ساخته می‌شود.</div>
@else
<table class="tbl"><tr><th>شماره</th><th>{{ $tab === 'selling' ? 'خریدار' : 'فروشنده' }}</th><th>مبلغ کل</th><th>پرداخت‌شده</th><th>وضعیت</th></tr>
@foreach($orders as $o)
<tr><td><a href="{{ route('orders.show', $o) }}">{{ $o->number }}</a></td><td>{{ $tab === 'selling' ? $o->buyer->name : $o->company->name }}</td>
  <td>{{ $F::price($o->total) }}</td><td>{{ $F::price($o->paid_amount) }}</td><td><span class="badge">{{ \App\Models\Order::STATUS[$o->status] }}</span></td></tr>
@endforeach</table>
{{ $orders->links('pagination::default') }}
@endif
@endsection
