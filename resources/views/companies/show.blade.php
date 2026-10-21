@extends('layouts.app')
@section('title', $company->name . ' | Gobiz')
@section('desc', \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', strip_tags($company->description ?: $company->name . ' در Gobiz')), 155))
@php
  $types = ['manufacturer' => 'تولیدکننده', 'trader' => 'بازرگان', 'agent' => 'نماینده', 'service' => 'خدماتی'];
  $F = '\App\Support\Fa';
  $ld = array_filter(['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => $company->name, 'url' => url()->current(),
      'description' => $company->description, 'telephone' => $company->phone, 'foundingDate' => null,
      'address' => $company->city ? ['@type' => 'PostalAddress', 'addressLocality' => $company->city->name, 'addressRegion' => $company->city->province->name, 'addressCountry' => 'IR', 'streetAddress' => $company->address] : null]);
@endphp
@push('head')<script type="application/ld+json">@json($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG)</script>@endpush
@section('content')
<div class="crumbs"><a href="{{ route('home') }}">خانه</a> / <a href="{{ route('companies.index') }}">شرکت‌ها</a></div>
<div class="box" style="margin-top:12px">
  <h1 style="margin:0">{{ $company->name }}</h1>
  <p class="meta">{{ $types[$company->business_type] }}@if($company->city) · {{ $company->city->name }}، {{ $company->city->province->name }}@endif
    @if($company->established_year) · تأسیس {{ $F::digits($company->established_year) }}@endif
    @if($company->employees_range) · {{ $F::digits($company->employees_range) }} نفر@endif</p>
  @if($company->verified_level >= 1)<span class="tag">{{ $company->verified_level >= 2 ? 'بازدید میدانی شده' : 'مدارک تأییدشده' }}</span>@endif
  @if($company->description)<p style="white-space:pre-line">{{ $company->description }}</p>@endif
  @if($company->website)<p><a href="{{ $company->website }}" rel="nofollow noopener" target="_blank">{{ $company->website }}</a></p>@endif
</div>
<h2>آگهی‌ها و فرصت‌های این شرکت</h2>
@if($listings->isEmpty())<div class="empty">آگهی فعالی ندارد.</div>
@else<div class="grid">@foreach($listings as $l)@include('partials.listing-card', ['l' => $l])@endforeach</div>{{ $listings->links('pagination::default') }}@endif
@endsection
