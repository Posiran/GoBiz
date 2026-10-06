@extends('layouts.app')
@section('desc', 'بازار B2B گوبیز برای خرید و فروش ماشین‌آلات، کارخانه و سوله، قطعات و خدمات صنعتی، کسب‌وکارهای فعال و استارتاپ‌ها؛ همراه با استعلام، مذاکره و خرید.')
@section('content')
<section class="hero">
  <ul class="cats">
    @foreach($categories as $c)<li><a href="{{ route('listings.index', ['category' => $c->slug]) }}">{{ $c->name }}</a></li>@endforeach
  </ul>
  <div class="banner">
    <h1>بازار B2B گوبیز؛ از ماشین‌آلات و کارخانه تا خرید و سرمایه‌گذاری روی کسب‌وکار</h1>
    <p>محصول، دارایی صنعتی، کسب‌وکار فعال و استارتاپ را در یک بازار یکپارچه پیدا کنید؛ استعلام بگیرید، مذاکره کنید و سفارش یا معامله را پیش ببرید.</p>
    <div class="acts"><a class="btn" href="{{ route('listings.index') }}">مشاهده همه آگهی‌ها</a><a class="btn fill" href="{{ route('businesses.index') }}">ورود به بازار کسب‌وکار</a></div>
  </div>
</section>

<h2>آگهی‌های ویژه <a href="{{ route('listings.index') }}">مشاهده همه</a></h2>
@if($featured->isEmpty())<div class="empty">هنوز آگهی‌ای منتشر نشده است.</div>
@else<div class="grid">@foreach($featured as $l)@include('partials.listing-card', ['l' => $l])@endforeach</div>@endif

<section class="rfq" style="margin-top:24px">
  <div><h2>بازار خرید و فروش کسب‌وکار و استارتاپ</h2><p>فرصت‌های واقعی فروش کامل، فروش سهام و جذب سرمایه را در همان بازار Gobiz ببینید.</p></div>
  <a class="btn" href="{{ route('businesses.index') }}">مشاهده بازار کسب‌وکار</a>
</section>
@if($businesses->isEmpty())<div class="empty">فرصت کسب‌وکار یا استارتاپی برای نمایش منتشر نشده است.</div>
@else<div class="grid">@foreach($businesses as $l)@include('partials.listing-card', ['l' => $l])@endforeach</div>@endif

<section class="rfq" id="rfq">
  <div><h2>دنبال چیز مشخصی هستید؟</h2><p>مشخصات و تعداد را بنویسید؛ پیشنهاد قیمت چند فروشنده تأییدشده را دریافت کنید.</p></div>
  <a class="btn" href="{{ route('rfq.create') }}">ثبت درخواست قیمت</a>
</section>
@endsection
