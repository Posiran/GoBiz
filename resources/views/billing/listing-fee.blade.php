@extends('layouts.app')
@section('title', 'هزینه ثبت آگهی | Gobiz')
@section('content')
@php($F = '\App\Support\Fa')
<form class="box form" method="post" action="{{ route('billing.listing_fee.pay', $listing) }}" style="max-width:480px">@csrf
  <h1>هزینه ثبت آگهی کسب‌وکار</h1>
  <p>«{{ $listing->title }}» — این هزینه یک بار پرداخت می‌شود و پس از آن آگهی برای تأیید مدیر آماده است.</p>
  <p class="price" style="font-size:20px">مبلغ قابل پرداخت: {{ $F::price($fee) }}</p>
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
