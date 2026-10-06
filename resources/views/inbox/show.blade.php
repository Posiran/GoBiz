@extends('layouts.app')
@section('title', $subject . ' | Gobiz')
@section('content')
@php($F = '\App\Support\Fa')
<div class="crumbs"><a href="{{ route('inbox.index') }}">پیام‌ها</a></div>
<h2>@if($root->listing)<a href="{{ route('listings.show', $root->listing->slug) }}">{{ $subject }}</a>@else{{ $subject }}@endif</h2>
@if($root->listing_id && $root->to_user_id === auth()->id() && auth()->user()->company?->status === 'active')
<p><a class="btn fill" href="{{ route('offers.create', ['inquiry' => $root->id]) }}">صدور پیش‌فاکتور رسمی برای این خریدار</a></p>
@endif
@foreach($thread as $m)
<div class="box" style="margin-bottom:10px;max-width:760px;{{ $m->from_user_id === auth()->id() ? 'border-inline-start:4px solid var(--accent)' : '' }}">
  <div class="meta">{{ $m->from_user_id === auth()->id() ? 'شما' : ($m->from->company?->name ?? $m->from->name) }} · {{ $F::jdate($m->created_at) }}</div>
  <p style="white-space:pre-line;margin:6px 0">{{ $m->message }}</p>
  @if($m->offered_price)<div class="price">قیمت پیشنهادی: {{ $F::price($m->offered_price) }}</div>@endif
</div>
@endforeach
<form class="box form" method="post" action="{{ route('inbox.reply', $root) }}" style="margin:12px 0">@csrf
  <label>پاسخ شما<textarea name="message" rows="4" required minlength="2"></textarea></label>
  <label>قیمت (تومان، اختیاری)<input name="offered_price" inputmode="numeric"></label>
  <button class="btn fill">ارسال پاسخ</button>
</form>
@endsection
