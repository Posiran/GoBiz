@extends('layouts.app')
@section('title', 'سفارش‌ها و اختلاف‌ها | مدیریت Gobiz')
@section('content')
@php($F = '\App\Support\Fa')
<div class="tabs"><a href="{{ route('admin.index') }}">← مدیریت</a>
  @foreach(['disputed' => 'اختلاف‌ها', 'shipped' => 'ارسال‌شده', 'delivered' => 'تحویل‌شده', 'completed' => 'تکمیل‌شده', 'cancelled' => 'لغوشده'] as $k => $v)<a class="{{ $status === $k ? 'on' : '' }}" href="{{ route('admin.orders.index', ['status' => $k]) }}">{{ $v }}</a>@endforeach</div>
@if($orders->isEmpty())<div class="empty">موردی نیست.</div>
@else
<table class="tbl"><tr><th>شماره</th><th>فروشنده</th><th>خریدار</th><th>مبلغ</th><th>پرداخت‌شده</th><th>کمیسیون</th></tr>
@foreach($orders as $o)<tr><td><a href="{{ route('orders.show', $o) }}">{{ $o->number }}</a></td><td>{{ $o->company->name }}</td><td>{{ $o->buyer->name }}</td><td>{{ $F::price($o->total) }}</td><td>{{ $F::price($o->paid_amount) }}</td><td>{{ $o->commission_amount > 0 ? $F::price($o->commission_amount) : '—' }}</td></tr>@endforeach</table>
{{ $orders->links('pagination::default') }}
@endif
@endsection
