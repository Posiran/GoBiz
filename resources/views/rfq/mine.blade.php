@extends('layouts.app')
@section('title', 'درخواست‌های من | Gobiz')
@section('content')
@php($F = '\App\Support\Fa')
<h2>درخواست‌های من <a class="btn fill" href="{{ route('rfq.create') }}">درخواست جدید</a></h2>
@if($rfqs->isEmpty())<div class="empty">هنوز درخواستی ثبت نکرده‌اید.</div>
@else
<table class="tbl"><tr><th>عنوان</th><th>تاریخ</th><th>پیشنهادها</th><th>وضعیت</th><th></th></tr>
@foreach($rfqs as $r)
<tr><td>{{ $r->title }}</td><td>{{ $F::jdate($r->created_at) }}</td><td>{{ $F::digits($r->offers) }}</td>
  <td><span class="badge">{{ $r->status === 'open' ? 'باز' : 'بسته' }}</span></td>
  <td>@if($r->status === 'open')<form method="post" action="{{ route('rfq.close', $r) }}">@csrf<button>بستن</button></form>@endif</td></tr>
@endforeach</table>
{{ $rfqs->links('pagination::default') }}
<p class="meta">پیشنهادها در <a href="{{ route('inbox.index') }}"><b>پیام‌ها</b></a> نمایش داده می‌شوند.</p>
@endif
@endsection
