@extends('layouts.app')
@section('title', 'پرداخت | Gobiz')
@section('content')
@php($F = '\App\Support\Fa')
<form class="box form" method="post" action="{{ route('billing.pay', $plan) }}" style="max-width:480px">@csrf
  <h1>خرید پلن {{ $plan->name }}</h1>
  <p>{{ $F::digits($plan->duration_days) }} روز · تا {{ $F::digits($plan->max_listings) }} آگهی · {{ $F::digits($plan->featured_slots) }} آگهی ویژه</p>
  <p class="price" style="font-size:20px">مبلغ قابل پرداخت: {{ $F::price($plan->price) }}</p>
  @if(empty($gateways))<div class="alert err">در حال حاضر هیچ درگاه پرداختی فعال نیست. با پشتیبانی تماس بگیرید.</div>
  @else
  <h2>انتخاب درگاه</h2>
  @foreach($gateways as $k => $g)
    <label class="chk"><input type="radio" name="gateway" value="{{ $k }}" @checked($loop->first) required> {{ $g->label() }}</label>
  @endforeach
  <button class="btn fill">پرداخت امن</button>
  @endif
</form>
@endsection
