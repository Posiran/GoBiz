@extends('layouts.app')
@section('title', 'گزارش اقدام‌ها | مدیریت Gobiz')
@section('content')
<div class="tabs"><a href="{{ route('admin.index') }}">← مدیریت</a>
  @foreach(['' => 'همه', 'listing' => 'آگهی', 'company' => 'شرکت', 'order' => 'سفارش', 'payment' => 'پرداخت', 'auth' => 'ورود'] as $k => $v)<a class="{{ $action === $k ? 'on' : '' }}" href="{{ route('admin.audit', $k ? ['action' => $k] : []) }}">{{ $v }}</a>@endforeach</div>
@if($rows->isEmpty())<div class="empty">موردی ثبت نشده است.</div>
@else
<table class="tbl"><tr><th>زمان</th><th>کاربر</th><th>اقدام</th><th>موضوع</th><th>جزئیات</th><th>IP</th></tr>
@foreach($rows as $x)<tr><td class="lt">{{ \App\Support\Fa::jdate($x->created_at) }}</td><td>{{ $x->user?->name ?? 'سیستم/بانک' }}</td><td dir="ltr">{{ $x->action }}</td><td dir="ltr">{{ $x->subject_type }}@if($x->subject_id) #{{ $x->subject_id }}@endif</td><td dir="ltr">{{ $x->meta ? json_encode($x->meta, JSON_UNESCAPED_UNICODE) : '' }}</td><td dir="ltr">{{ $x->ip }}</td></tr>@endforeach</table>
{{ $rows->links('pagination::default') }}
@endif
@endsection
