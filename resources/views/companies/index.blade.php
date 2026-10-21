@extends('layouts.app')
@section('title', 'دایرکتوری شرکت‌ها و تأمین‌کنندگان صنعتی | Gobiz')
@section('desc', 'فهرست تولیدکنندگان، بازرگانان و نمایندگان ماشین‌آلات صنعتی در سراسر ایران؛ فیلتر بر اساس استان، شهر و تأیید مدارک.')
@section('robots', request()->except('page') ? 'noindex,follow' : 'index,follow')
@section('content')
@php($F = '\App\Support\Fa')
@php($types = ['manufacturer' => 'تولیدکننده', 'trader' => 'بازرگان', 'agent' => 'نماینده', 'service' => 'خدماتی'])
<div class="page">
<form class="filters" method="get" action="{{ route('companies.index') }}">
  <div><label>نام شرکت</label><input name="q" value="{{ $filters['q'] ?? '' }}"></div>
  <div><label>نوع فعالیت</label><select name="business_type"><option value="">همه</option>
    @foreach($types as $k => $v)<option value="{{ $k }}" @selected(($filters['business_type'] ?? '') === $k)>{{ $v }}</option>@endforeach</select></div>
  <div><label>استان</label><select name="province_id" id="province"><option value="">همه</option>
    @foreach($provinces as $p)<option value="{{ $p->id }}" @selected(($filters['province_id'] ?? '') == $p->id)>{{ $p->name }}</option>@endforeach</select></div>
  <div><label>شهر / شهرستان</label><select name="city_id" id="city"><option value="">همه</option>
    @foreach($cities as $c)<option value="{{ $c->id }}" @selected(($filters['city_id'] ?? '') == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
  <label><input type="checkbox" name="verified" value="1" style="width:auto" @checked(!empty($filters['verified']))> فقط تأییدشده</label>
  <button>اعمال فیلتر</button>
</form>
<section>
  <h1 style="font-size:22px;margin:0 0 12px">شرکت‌ها و تأمین‌کنندگان <small class="meta">({{ $F::digits($companies->total()) }})</small></h1>
  @if($companies->isEmpty())<div class="empty">شرکتی با این فیلترها پیدا نشد.</div>
  @else<div class="grid">@foreach($companies as $c)
    <a class="item" href="{{ route('companies.show', $c->slug) }}"><div class="b" style="gap:6px;padding:16px">
      <h3>{{ $c->name }}</h3>
      <span class="meta">{{ $types[$c->business_type] }} · {{ $c->city?->name }}{{ $c->city ? '، ' . $c->city->province->name : '' }}</span>
      <span class="meta">{{ $F::digits($c->live_listings) }} آگهی فعال</span>
      @if($c->verified_level >= 1)<span class="tag">{{ $c->verified_level >= 2 ? 'بازدید میدانی شده' : 'مدارک تأییدشده' }}</span>@endif
    </div></a>@endforeach</div>
  {{ $companies->links('pagination::default') }}@endif
</section>
</div>
@include('partials.geo-js')
@endsection
