@extends('layouts.app')
@section('title', 'سرنخ‌های فروش | Gobiz')
@section('content')
@php($F = '\App\Support\Fa')
@php($st = ['new' => 'جدید', 'contacted' => 'تماس گرفته شد', 'quoted' => 'پیش‌فاکتور داده شد', 'negotiating' => 'در مذاکره', 'won' => 'موفق', 'lost' => 'از دست رفت'])
<h2>سرنخ‌های فروش <a href="{{ route('rfq.board') }}">همه‌ی درخواست‌های خریداران</a></h2>
<p class="meta">درخواست‌های قیمتی که با دسته و شهر شما بیشتر می‌خواند. امتیاز بر پایه کامل‌بودن درخواست (بودجه، تعداد، شهر، توضیحات) است.</p>
<div class="tabs"><a class="{{ !$status ? 'on' : '' }}" href="{{ route('seller.leads.index') }}">همه</a>
  @foreach($st as $k => $v)<a class="{{ $status === $k ? 'on' : '' }}" href="{{ route('seller.leads.index', ['status' => $k]) }}">{{ $v }}</a>@endforeach</div>
@if($leads->isEmpty())<div class="empty">سرنخی در این وضعیت نیست.</div>
@else
<table class="tbl"><tr><th>امتیاز</th><th>درخواست</th><th>تعداد / بودجه</th><th>وضعیت</th><th></th></tr>
@foreach($leads as $l)<tr><td><b>{{ $F::digits($l->score) }}</b></td>
  <td>{{ $l->rfq?->title }}<br><small class="meta">{{ $l->notes }}</small></td>
  <td>{{ $l->rfq?->quantity ? $F::digits($l->rfq->quantity) : '—' }} / {{ $l->rfq?->budget ? $F::price($l->rfq->budget) : '—' }}</td>
  <td><form method="post" action="{{ route('seller.leads.update', $l) }}">@csrf
    <select name="status" aria-label="وضعیت">@foreach($st as $k => $v)<option value="{{ $k }}" @selected($l->status === $k)>{{ $v }}</option>@endforeach</select>
    <input type="hidden" name="notes" value="{{ $l->notes }}"><button>ذخیره</button></form></td>
  <td>@if($l->rfq && $l->rfq->status === 'open')<a class="btn" href="{{ route('offers.create', ['rfq' => $l->rfq_id]) }}">صدور پیش‌فاکتور</a>@endif</td></tr>@endforeach</table>
{{ $leads->links('pagination::default') }}
@endif
@endsection
