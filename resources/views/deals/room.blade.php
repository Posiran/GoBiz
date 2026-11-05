@extends('layouts.app')
@section('title', 'اتاق داده | Gobiz')
@section('robots', 'noindex,nofollow')
@section('content')
@php $F = '\App\Support\Fa'; @endphp
<h2>اتاق داده: <a href="{{ route('listings.show', $listing->slug) }}">{{ $listing->title }}</a></h2>
<p class="meta">اسناد این بخش محرمانه است و فقط برای فروشنده و کاربران دارای دسترسی تأییدشده قابل مشاهده است. هر دانلود ثبت می‌شود.</p>
@if($docs->isEmpty())<div class="empty">هنوز سندی بارگذاری نشده است.</div>
@else
<table class="tbl"><tr><th>عنوان</th><th>حجم</th><th>تاریخ</th><th></th></tr>
@foreach($docs as $d)
<tr><td>{{ $d->title }}</td><td>{{ $F::digits(number_format(round($d->size / 1024))) }} کیلوبایت</td><td>{{ $F::jdate($d->created_at) }}</td>
<td><a href="{{ route('dataroom.download', $d) }}">دانلود</a>
@if($owner)<form method="post" action="{{ route('dataroom.destroy', $d) }}" style="display:inline">@csrf @method('DELETE')<button>حذف</button></form>@endif</td></tr>
@endforeach</table>
@endif
@if($owner)
<h3>بارگذاری سند</h3>
<form method="post" action="{{ route('dataroom.upload', $listing) }}" enctype="multipart/form-data">@csrf
<input name="title" required maxlength="150" placeholder="عنوان سند"><input type="file" name="file" required><button>بارگذاری</button></form>
@elseif(!$myDeal)
<h3>ارسال نامه قصد (LOI)</h3>
<form method="post" action="{{ route('deals.store', $listing) }}">@csrf
<input name="offer_amount" required placeholder="مبلغ پیشنهادی (تومان)">
<input name="stake_percent" placeholder="درصد سهم (اختیاری)">
<input type="date" name="valid_until">
<textarea name="terms" maxlength="3000" placeholder="شرایط پیشنهادی"></textarea><button>ارسال نامه قصد</button></form>
@else<p><a href="{{ route('deals.show', $myDeal) }}">مشاهده معامله شما</a></p>@endif
@endsection
