@extends('layouts.app')
@section('title', 'آگهی‌های من | Gobiz')
@section('content')
@php($st = ['draft' => 'پیش‌نویس', 'pending' => 'در انتظار تأیید', 'published' => 'منتشرشده', 'sold' => 'فروخته‌شده', 'expired' => 'منقضی', 'rejected' => 'ردشده'])
<h2>آگهی‌های من <a class="btn fill" href="{{ route('seller.listings.create') }}">آگهی جدید</a></h2>
@if($listings->isEmpty())<div class="empty">هنوز آگهی‌ای ثبت نکرده‌اید.</div>
@else
<table class="tbl"><tr><th>عنوان</th><th>وضعیت</th><th>بازدید</th><th></th></tr>
@foreach($listings as $l)
<tr><td><a href="{{ route('listings.show', $l->slug) }}">{{ $l->title }}</a></td>
  <td><span class="badge">{{ $st[$l->status] }}</span></td><td>{{ \App\Support\Fa::digits($l->views) }}</td>
  <td><a class="btn" href="{{ route('seller.listings.edit', $l) }}">ویرایش</a>
    @if($l->status === 'published')<form method="post" action="{{ route('seller.listings.feature', $l) }}">@csrf<button title="با سهمیه پلن، ۳۰ روز">ویژه</button></form>@endif
    <form method="post" action="{{ route('seller.listings.destroy', $l) }}" onsubmit="return confirm('این آگهی حذف شود؟')">@csrf @method('DELETE')<button>حذف</button></form></td></tr>
@endforeach</table>
{{ $listings->links('pagination::default') }}
@endif
@endsection
