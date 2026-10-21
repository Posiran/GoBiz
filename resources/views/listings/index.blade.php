@extends('layouts.app')
@section('title', request('category') === 'business-startup' || in_array(request('type'), ['business','startup']) ? 'خرید و فروش کسب‌وکار و استارتاپ | Gobiz' : 'بازار B2B Gobiz | ماشین‌آلات، کارخانه، قطعات و کسب‌وکار')
@section('robots', request()->except('page') ? 'noindex,follow' : 'index,follow')
@section('content')
<div class="page">
@if(request('category') === 'business-startup' || in_array(request('type'), ['business','startup']))<div class="alert ok"><b>بازار کسب‌وکار و استارتاپ Gobiz</b> · فروش کامل، فروش سهام و جذب سرمایه در همان بازار B2B</div>@endif
<form class="filters" method="get" action="{{ route('listings.index') }}">
  <div><label>جستجو</label><input name="q" value="{{ $filters['q'] ?? '' }}"></div>
  <div><label>بازار / دسته</label><select name="category"><option value="">همه بازارها</option>
    @foreach($categories as $c)
      <option value="{{ $c->slug }}" @selected(($filters['category'] ?? '') === $c->slug)>{{ $c->name }}</option>
      @foreach($c->children as $k)<option value="{{ $k->slug }}" @selected(($filters['category'] ?? '') === $k->slug)>— {{ $k->name }}</option>@endforeach
    @endforeach</select></div>
  @foreach($filterAttrs as $a)
  <div><label>{{ $a->name }}@if($a->unit) ({{ $a->unit }})@endif</label>
    @if($a->type === 'number')<div class="row"><input type="number" name="attr[{{ $a->id }}][min]" placeholder="از" value="{{ $filters['attr'][$a->id]['min'] ?? '' }}"><input type="number" name="attr[{{ $a->id }}][max]" placeholder="تا" value="{{ $filters['attr'][$a->id]['max'] ?? '' }}"></div>
    @elseif($a->type === 'select')<select name="attr[{{ $a->id }}]"><option value="">همه</option>@foreach($a->options ?? [] as $o)<option @selected(($filters['attr'][$a->id] ?? '') === $o)>{{ $o }}</option>@endforeach</select>
    @else<label style="color:var(--ink)"><input type="checkbox" name="attr[{{ $a->id }}]" value="1" style="width:auto" @checked(!empty($filters['attr'][$a->id]))> دارد</label>@endif</div>
  @endforeach
  <div><label>استان</label><select name="province_id" id="province"><option value="">همه</option>
    @foreach($provinces as $p)<option value="{{ $p->id }}" @selected(($filters['province_id'] ?? '') == $p->id)>{{ $p->name }}</option>@endforeach</select></div>
  <div><label>شهر / شهرستان</label><select name="city_id" id="city"><option value="">همه</option>
    @foreach($cities as $c)<option value="{{ $c->id }}" @selected(($filters['city_id'] ?? '') == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
  <div><label>شهرک صنعتی</label><select name="industrial_town_id" id="town"><option value="">همه</option>
    @foreach($towns as $t)<option value="{{ $t->id }}" @selected(($filters['industrial_town_id'] ?? '') == $t->id)>{{ $t->name }}</option>@endforeach</select></div>
  <div><label>نوع آگهی</label><select name="type"><option value="">همه</option>
    @foreach(['machine' => 'ماشین‌آلات', 'factory' => 'کارخانه / سوله', 'part' => 'قطعه و ابزار', 'service' => 'خدمات', 'business' => 'کسب‌وکار فعال', 'startup' => 'استارتاپ / سرمایه'] as $k => $v)<option value="{{ $k }}" @selected(($filters['type'] ?? '') === $k)>{{ $v }}</option>@endforeach</select></div>
  <div><label>نوع معامله کسب‌وکار</label><select name="sale_type"><option value="">همه</option>
    @foreach(\App\Models\BusinessDetail::SALE_TYPE as $k => $v)<option value="{{ $k }}" @selected(($filters['sale_type'] ?? '') === $k)>{{ $v }}</option>@endforeach</select></div>
  <div><label>وضعیت</label><select name="condition"><option value="">همه</option>
    @foreach(['new' => 'نو', 'used' => 'دست‌دوم', 'refurbished' => 'اکبند/بازسازی‌شده'] as $k => $v)<option value="{{ $k }}" @selected(($filters['condition'] ?? '') === $k)>{{ $v }}</option>@endforeach</select></div>
  <div><label>نوع معامله</label><select name="deal_type"><option value="">همه</option>
    @foreach(['sale' => 'فروش', 'rent' => 'اجاره', 'lease' => 'رهن/لیزینگ'] as $k => $v)<option value="{{ $k }}" @selected(($filters['deal_type'] ?? '') === $k)>{{ $v }}</option>@endforeach</select></div>
  <div class="row"><div><label>قیمت از</label><input type="number" name="price_min" value="{{ $filters['price_min'] ?? '' }}"></div>
  <div><label>تا</label><input type="number" name="price_max" value="{{ $filters['price_max'] ?? '' }}"></div></div>
  <label><input type="checkbox" name="verified" value="1" style="width:auto" @checked(!empty($filters['verified']))> فقط فروشنده تأییدشده</label>
  <button>اعمال فیلتر</button>
</form>

<section>
  <div class="bar"><b>{{ \App\Support\Fa::digits($listings->total()) }} آگهی</b>
    <select aria-label="مرتب‌سازی" onchange="const u = new URL(location); u.searchParams.set('sort', this.value); u.searchParams.delete('page'); location = u">
      @foreach(['newest' => 'جدیدترین', 'price_asc' => 'ارزان‌ترین', 'price_desc' => 'گران‌ترین'] as $k => $v)<option value="{{ $k }}" @selected(request('sort', 'newest') === $k)>{{ $v }}</option>@endforeach
    </select></div>
  @if($listings->isEmpty())<div class="empty">آگهی‌ای با این فیلترها پیدا نشد. فیلترها را کم کنید.</div>
  @else<div class="grid">@foreach($listings as $l)@include('partials.listing-card', ['l' => $l])@endforeach</div>
  {{ $listings->links('pagination::default') }}@endif
</section>
</div>
@include('partials.geo-js')
@endsection
