@extends('layouts.app')
@section('title', 'درخواست‌های دسترسی به اطلاعات محرمانه | Gobiz')
@section('robots', 'noindex,nofollow')
@section('content')
@php($F = '\App\Support\Fa')
<h2>درخواست‌های دسترسی به اطلاعات محرمانه</h2>
<p class="meta">متقاضی با پذیرش تعهد محرمانگی درخواست داده است. تأیید شما اطلاعات مالی و جزئیات آگهی را فقط برای همان کاربر باز می‌کند. پیش از تأیید، هویت و جدیت متقاضی را بسنجید.</p>
@if($rows->isEmpty())<div class="empty">درخواستی وجود ندارد.</div>
@else
<table class="tbl"><tr><th>آگهی</th><th>متقاضی</th><th>پیام</th><th>تاریخ</th><th>وضعیت</th></tr>
@foreach($rows as $a)
<tr><td><a href="{{ route('listings.show', $a->listing->slug) }}">{{ $a->listing->title }}</a></td>
  <td>{{ $a->user->name }}@if($a->user->company)<br><small class="meta">{{ $a->user->company->name }}</small>@endif</td>
  <td style="white-space:pre-line">{{ $a->message }}</td><td>{{ $F::jdate($a->created_at) }}</td>
  <td>@if($a->status === 'pending')
    <form method="post" action="{{ route('business.access.review', $a) }}">@csrf<button name="action" value="approve">تأیید</button><button name="action" value="reject">رد</button></form>
    @else<span class="badge">{{ $a->status === 'approved' ? 'تأییدشده' : 'ردشده' }}</span>@endif</td></tr>
@endforeach</table>
{{ $rows->links('pagination::default') }}
@endif
@endsection
