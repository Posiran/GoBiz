<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
@php
  $noindex = request()->is('panel*', 'admin*', 'login', 'register', 'verify-mobile*',  'rfq/create');
  $defaultDesc = 'بازار B2B ماشین‌آلات صنعتی، خطوط تولید و کارخانجات ایران؛ آگهی نو و دست‌دوم از تأمین‌کنندگان تأییدشده و درخواست قیمت آنلاین.';
@endphp
<title>@yield('title', 'Gobiz | خرید و فروش ماشین‌آلات و کارخانجات صنعتی')</title>
<meta name="description" content="@yield('desc', $defaultDesc)">
<meta name="robots" content="{{ $noindex ? 'noindex,nofollow' : trim($__env->yieldContent('robots', 'index,follow')) }}">
@unless($noindex)<link rel="canonical" href="{{ url()->current() }}">@endunless
<meta property="og:site_name" content="Gobiz"><meta property="og:locale" content="fa_IR"><meta property="og:type" content="website">
<meta property="og:title" content="@yield('title', 'Gobiz')"><meta property="og:description" content="@yield('desc', $defaultDesc)"><meta property="og:url" content="{{ url()->current() }}">
@hasSection('og_image')<meta property="og:image" content="@yield('og_image')">@endif
@stack('head')
<link rel="icon" href="{{ asset('img/favicon.svg') }}" type="image/svg+xml">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v28.0.0/dist/font-face.css">
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="top"><div class="wrap"><span>بازار B2B ایران · <a href="{{ route('listings.index') }}">همه آگهی‌ها</a> · <a href="{{ route('businesses.index') }}">کسب‌وکار و استارتاپ</a> · <a href="{{ route('companies.index') }}">شرکت‌ها</a></span><span>پشتیبانی: ۰۲۱-۰۰۰۰۰۰۰۰</span></div></div>
<header class="main"><div class="wrap">
<a class="logo" href="{{ route('home') }}" aria-label="Gobiz"><img src="{{ asset('img/logo-mark.svg') }}" alt="" width="34" height="34"><span dir="ltr">Gobiz</span></a>
<form class="search" role="search" action="{{ route('listings.index') }}">
<input type="search" name="q" value="{{ request('q') }}" placeholder="مثلاً: دستگاه CNC، کارخانه، استارتاپ SaaS یا کسب‌وکار فعال" aria-label="جستجو"><button>جستجو</button></form>
<div class="acts">
<a class="btn" href="{{ route('rfq.create') }}">درخواست قیمت</a>
@auth
@php($unread = \App\Models\Inquiry::where('to_user_id', auth()->id())->where('status', 'new')->count())
<a class="btn" href="{{ route('inbox.index') }}">پیام‌ها@if($unread) <span class="badge" style="background:var(--accent);color:var(--accent-ink);border-color:var(--accent)">{{ \App\Support\Fa::digits($unread) }}</span>@endif</a>
<a class="btn" href="{{ auth()->user()->isAdmin() ? route('admin.index') : route('seller.dashboard') }}">{{ auth()->user()->isAdmin() ? 'پنل مدیریت' : 'پنل من' }}</a>
<form method="post" action="{{ route('logout') }}" style="display:inline">@csrf<button class="btn" style="background:none;color:inherit;cursor:pointer;font:inherit">خروج</button></form>
@else
<a class="btn" href="{{ route('businesses.index') }}">خرید کسب‌وکار</a><a class="btn" href="{{ route('login') }}">ورود</a><a class="btn fill" href="{{ route('register') }}">ثبت آگهی رایگان</a>
@endauth</div>
</div></header>
<main class="wrap">
@if(session('err'))<div class="alert err">{{ session('err') }}</div>@endif
@if(session('msg'))<div class="alert ok">{{ session('msg') }}</div>@endif
@if($errors->any())<div class="alert err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
@yield('content')</main>
<footer><div class="wrap"><span>© Gobiz — همه حقوق محفوظ است</span><span>درباره ما · قوانین · تماس با ما</span></div></footer>
</body>
</html>
