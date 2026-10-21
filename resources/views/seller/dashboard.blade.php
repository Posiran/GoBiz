@extends('layouts.app')
@section('title', 'پنل فروشنده | Gobiz')
@section('content')
@php($st = ['pending' => 'در انتظار تأیید', 'active' => 'فعال', 'suspended' => 'تعلیق‌شده'])
<h2>{{ $company->name }}
  <span class="badge">{{ $st[$company->status] }}</span></h2>
@if($company->status === 'pending')<div class="alert">شرکت شما در انتظار تأیید مدیر است. تا آن موقع آگهی‌ها منتشر نمی‌شوند.</div>@endif
@if($company->status === 'suspended')<div class="alert err">حساب شرکت شما تعلیق شده است. با پشتیبانی تماس بگیرید.</div>@endif
<div class="stat">
  @foreach(['pending' => 'در انتظار تأیید', 'published' => 'منتشرشده', 'rejected' => 'ردشده'] as $k => $v)
    <div class="box"><b>{{ \App\Support\Fa::digits($counts[$k] ?? 0) }}</b>{{ $v }}</div>
  @endforeach
</div>
<div class="acts"><a class="btn fill" href="{{ route('seller.listings.create') }}">ثبت آگهی / کسب‌وکار جدید</a>
  <a class="btn" href="{{ route('seller.listings.index') }}">آگهی‌های من</a>
  <a class="btn" href="{{ route('billing.plans') }}">پلن و پرداخت</a>
  <a class="btn" href="{{ route('offers.index', ['tab' => 'issued']) }}">پیش‌فاکتورها</a>
  <a class="btn" href="{{ route('orders.index') }}">سفارش‌ها</a>
  <a class="btn" href="{{ route('business.access.index') }}">درخواست‌های محرمانه کسب‌وکار</a><a class="btn" href="{{ route('businesses.index') }}">مشاهده بازار کسب‌وکار</a>
  <a class="btn" href="{{ route('rfq.board') }}">درخواست‌های خریداران</a>
  <a class="btn" href="{{ route('inbox.index') }}">پیام‌ها</a>
  <a class="btn" href="{{ route('seller.company.edit') }}">ویرایش اطلاعات شرکت</a></div>
@endsection
