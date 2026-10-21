@extends('layouts.app')
@section('title', 'درخواست‌های خریداران | Gobiz')
@section('content')
@php($F = '\App\Support\Fa')
<h2>درخواست‌های خریداران</h2>
<div class="tabs"><a class="{{ request('category') ? '' : 'on' }}" href="{{ route('rfq.board') }}">همه</a>
  @foreach($categories as $c)<a class="{{ request('category') === $c->slug ? 'on' : '' }}" href="{{ route('rfq.board', ['category' => $c->slug]) }}">{{ $c->name }}</a>@endforeach</div>
@if($rfqs->isEmpty())<div class="empty">درخواست بازی در این دسته نیست.</div>@endif
@foreach($rfqs as $r)
<article class="box" style="margin-bottom:12px">
  <b>{{ $r->title }}</b>
  <div class="meta">{{ $r->category?->name }} {{ $r->city ? '· ' . $r->city->name : '' }} · {{ $F::jdate($r->created_at) }}
    @if($r->quantity) · تعداد: {{ $F::digits($r->quantity) }}@endif @if($r->budget) · بودجه: {{ $F::price($r->budget) }}@endif</div>
  <p style="white-space:pre-line">{{ $r->details }}</p>
  @if(auth()->user()->company?->status === 'active')<a class="btn" href="{{ route('offers.create', ['rfq' => $r->id]) }}">صدور پیش‌فاکتور رسمی</a>@endif
  @if(in_array($r->id, $offered))<span class="tag">پیشنهاد شما ارسال شده</span>
  @else
  <details><summary class="btn fill" style="display:inline-block;cursor:pointer">ارسال پیشنهاد</summary>
    <form class="form" method="post" action="{{ route('rfq.offer', $r) }}" style="margin:12px 0 0;max-width:none">@csrf
      <label>پیام و شرایط شما<textarea name="message" rows="3" minlength="10" required></textarea></label>
      <label>قیمت پیشنهادی (تومان)<input name="offered_price" inputmode="numeric"></label>
      <button class="btn fill">ارسال</button></form></details>
  @endif
</article>
@endforeach
{{ $rfqs->links('pagination::default') }}
@endsection
