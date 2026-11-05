@extends('layouts.app')
@section('title', 'معامله‌های من | Gobiz')
@section('robots', 'noindex,nofollow')
@section('content')
@php $F = '\App\Support\Fa'; @endphp
<h2>معامله‌های من</h2>
@if($deals->isEmpty())<div class="empty">معامله‌ای وجود ندارد.</div>
@else
<table class="tbl"><tr><th>آگهی</th><th>نقش من</th><th>مبلغ پیشنهادی (تومان)</th><th>وضعیت</th><th></th></tr>
@foreach($deals as $d)
<tr><td>{{ $d->listing->title }}</td><td>{{ $d->seller_id === $me ? 'فروشنده' : 'خریدار' }}</td>
<td>{{ $F::digits(number_format($d->offer_amount)) }}</td><td><span class="badge">{{ \App\Models\Deal::LABELS[$d->status] }}</span></td>
<td><a href="{{ route('deals.show', $d) }}">مشاهده</a></td></tr>
@endforeach</table>
{{ $deals->links('pagination::default') }}
@endif
@endsection
