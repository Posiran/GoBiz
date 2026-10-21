@extends('layouts.app')
@section('title', $listing->title . ' | Gobiz')
@php
  $img = $listing->media->firstWhere('kind', 'image');
  $ld = array_filter(['@context' => 'https://schema.org', '@type' => 'Product', 'name' => $listing->title,
      'description' => \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', strip_tags($listing->description ?: $listing->title)), 300),
      'image' => $img ? asset('storage/' . $img->path) : null,
      'brand' => $listing->brand ? ['@type' => 'Brand', 'name' => $listing->brand] : null,
      'offers' => $listing->price ? ['@type' => 'Offer', 'price' => $listing->price * 10, 'priceCurrency' => 'IRR', 'availability' => 'https://schema.org/InStock', 'url' => url()->current()] : null]);
@endphp
@section('desc', \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', strip_tags($listing->description ?: $listing->title)), 155))
@if($img)@section('og_image', asset('storage/' . $img->path))@endif
@if($listing->status !== 'published')@section('robots', 'noindex,nofollow')@endif
@if(!$listing->business)@push('head')<script type="application/ld+json">@json($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG)</script>@endpush@endif
@section('content')
@php($F = '\App\Support\Fa')
<div class="crumbs"><a href="{{ route('home') }}">خانه</a> / <a href="{{ route('listings.index', ['category' => $listing->category->slug]) }}">{{ $listing->category->name }}</a></div>
<div class="detail">
  <article class="box">
    @if($listing->status !== 'published')<div class="alert">پیش‌نمایش — وضعیت آگهی: {{ ['pending' => 'در انتظار تأیید', 'rejected' => 'ردشده', 'draft' => 'پیش‌نویس', 'sold' => 'فروخته‌شده', 'expired' => 'منقضی'][$listing->status] ?? $listing->status }}</div>@endif
    @forelse($listing->media->where('kind', 'image') as $m)<img src="{{ asset('storage/'.$m->path) }}" alt="{{ $listing->title }}" style="width:100%;max-height:420px;object-fit:cover;border-radius:8px;margin-bottom:10px">
    @empty<div class="ph big c{{ $listing->category_id % 8 + 1 }}">{{ $listing->category->name }}</div>@endforelse
    <h1>{{ $listing->title }}</h1>
    <p class="meta">{{ $listing->city?->name }}@if($listing->industrialTown) · {{ $listing->industrialTown->name }}@endif</p>
    <table class="spec">
      @if($listing->brand)<tr><th>برند / مدل</th><td>{{ $listing->brand }} {{ $listing->model }}</td></tr>@endif
      @if($listing->manufacture_year)<tr><th>سال ساخت</th><td>{{ $F::digits($listing->manufacture_year) }}</td></tr>@endif
      @if($listing->origin_country)<tr><th>کشور سازنده</th><td>{{ $listing->origin_country }}</td></tr>@endif
      @if($listing->condition_state)<tr><th>وضعیت</th><td>{{ ['new' => 'نو', 'used' => 'دست‌دوم', 'refurbished' => 'بازسازی‌شده'][$listing->condition_state] }}</td></tr>@endif
      @if($listing->warranty_months)<tr><th>گارانتی</th><td>{{ $F::digits($listing->warranty_months) }} ماه</td></tr>@endif
      @foreach($listing->attributeValues as $a)<tr><th>{{ $a->name }}</th><td>{{ $a->type === 'bool' ? 'دارد' : $F::digits($a->pivot->value) . ' ' . $a->unit }}</td></tr>@endforeach
      @if($listing->factory)
        <tr><th>زمین / بنا</th><td>{{ $F::digits($listing->factory->land_area) }} / {{ $F::digits($listing->factory->building_area) }} متر</td></tr>
        <tr><th>برق</th><td>{{ $F::digits($listing->factory->power_kw) }} کیلووات</td></tr>
        <tr><th>پروانه بهره‌برداری</th><td>{{ $listing->factory->has_license ? 'دارد' : 'ندارد' }}</td></tr>
      @endif
    </table>
    @if($listing->business)
    @php
      $b = $listing->business;
      $canSee = in_array($access, ['open', 'owner', 'approved'], true);
      $m = $b->metrics($listing->price);
    @endphp
    <h2>اطلاعات کسب‌وکار</h2>
    <table class="spec">
      <tr><th>نوع معامله</th><td>{{ \App\Models\BusinessDetail::SALE_TYPE[$b->sale_type] }}</td></tr>
      @if($b->industry)<tr><th>حوزه فعالیت</th><td>{{ $b->industry }}</td></tr>@endif
      @if($b->business_model)<tr><th>مدل کسب‌وکار</th><td>{{ $b->business_model }}</td></tr>@endif
      @if($b->founded_year)<tr><th>سال تأسیس</th><td>{{ $F::digits($b->founded_year) }}</td></tr>@endif
      @if($b->team_size)<tr><th>اندازه تیم</th><td>{{ $F::digits($b->team_size) }} نفر</td></tr>@endif
      @if($b->stage)<tr><th>مرحله</th><td>{{ \App\Models\BusinessDetail::STAGE[$b->stage] }}</td></tr>@endif
      @if($b->stake_percent)<tr><th>سهم موضوع معامله</th><td>{{ $F::digits(rtrim(rtrim(number_format($b->stake_percent, 2, '.', ''), '0'), '.')) }}٪</td></tr>@endif
    </table>
    @if($canSee)
      <h2>اطلاعات مالی و جزئیات <span class="badge">محرمانه</span></h2>
      <table class="spec">
        @if($b->annual_revenue !== null)<tr><th>درآمد سالانه</th><td>{{ $F::price($b->annual_revenue) }}</td></tr>@endif
        @if($b->annual_profit !== null)<tr><th>سود سالانه</th><td>{{ $F::price($b->annual_profit) }}</td></tr>@endif
        @if($b->monthly_revenue !== null)<tr><th>درآمد ماهانه (MRR)</th><td>{{ $F::price($b->monthly_revenue) }}</td></tr>@endif
        @if($b->customers_count !== null)<tr><th>تعداد مشتریان</th><td>{{ $F::digits($b->customers_count) }}</td></tr>@endif
        @if($m['valuation'])<tr><th>ارزش کل کسب‌وکار{{ $b->valuation ? '' : ' (برآورد)' }}</th><td>{{ $F::price($m['valuation']) }}</td></tr>@endif
        @if($m['margin'] !== null)<tr><th>حاشیه سود</th><td>{{ $F::digits($m['margin']) }}٪</td></tr>@endif
        @if($m['profit_multiple'])<tr><th>مضرب سود (ارزش ÷ سود سالانه)</th><td>{{ $F::digits($m['profit_multiple']) }} برابر</td></tr>@endif
        @if($m['revenue_multiple'])<tr><th>مضرب درآمد (ارزش ÷ درآمد سالانه)</th><td>{{ $F::digits($m['revenue_multiple']) }} برابر</td></tr>@endif
        @if($b->reason_for_sale)<tr><th>دلیل فروش / جذب سرمایه</th><td style="white-space:pre-line">{{ $b->reason_for_sale }}</td></tr>@endif
        @if($b->assets_included)<tr><th>موارد شامل معامله</th><td style="white-space:pre-line">{{ $b->assets_included }}</td></tr>@endif
      </table>
      <p class="meta">اعداد را فروشنده اعلام کرده و Gobiz صحت آن‌ها را تأیید نکرده است؛ پیش از هر معامله بررسی مالی مستقل (due diligence) انجام دهید.</p>
    @else
      <div class="box" style="margin:12px 0"><b>اطلاعات مالی، تعداد مشتریان و دلیل فروش محرمانه است.</b>
        @guest
          <p><a class="btn fill" href="{{ route('login') }}">برای درخواست دسترسی وارد شوید</a></p>
        @else
          @if($access === 'pending')<p class="alert">درخواست شما در انتظار تأیید فروشنده است.</p>
          @elseif($access === 'rejected')<p class="alert err">فروشنده درخواست دسترسی شما را رد کرده است.</p>
          @else
          <form class="form" method="post" action="{{ route('business.access.request', $listing) }}" style="margin:8px 0 0;max-width:none">@csrf
            <label>معرفی کوتاه شما و هدف از خرید یا سرمایه‌گذاری (اختیاری)<textarea name="message" rows="3" maxlength="500"></textarea></label>
            <label class="chk"><input type="checkbox" name="accept_nda" value="1" required> تعهد محرمانگی را می‌پذیرم</label>
            <p class="meta">متقاضی متعهد می‌شود اطلاعات مالی و عملیاتی این آگهی را محرمانه نگه دارد، فقط برای ارزیابی همین معامله به کار ببرد و بدون اجازه فروشنده با دیگران به اشتراک نگذارد. این تعهد توافقی ساده بین طرفین است و جایگزین قرارداد حقوقی نیست.</p>
            <button class="btn fill">درخواست دسترسی</button></form>
          @endif
        @endguest
      </div>
    @endif
    @endif
    <h2>توضیحات</h2>
    <p style="white-space:pre-line">{{ $listing->description }}</p>
  </article>
  <aside class="box">
    <div class="price" style="font-size:22px">{{ $F::price($listing->price) }}</div>
    @if($listing->price_negotiable)<span class="meta">قابل مذاکره</span>@endif
    <hr style="border:0;border-top:1px solid var(--line);margin:14px 0">
    <b>@if($listing->company->status === 'active')<a href="{{ route('companies.show', $listing->company->slug) }}">{{ $listing->company->name }}</a>@else{{ $listing->company->name }}@endif</b>
    @if($listing->company->isVerified())<div><span class="tag">فروشنده تأییدشده</span></div>@endif
    <p class="meta">{{ ['manufacturer' => 'تولیدکننده', 'trader' => 'بازرگان', 'agent' => 'نماینده', 'service' => 'خدماتی'][$listing->company->business_type] }}</p>
    @auth
      @if(auth()->user()->company?->id === $listing->company_id)<p class="meta">این آگهی شماست.</p>
      @elseif($listing->status === 'published')
      <form class="form" method="post" action="{{ route('inquiries.store', $listing) }}" style="margin:0;gap:8px">@csrf
        <label>پیام شما<textarea name="message" rows="4" minlength="10" required placeholder="تعداد، زمان تحویل، شهر مقصد و ...">{{ old('message') }}</textarea></label>
        <label>قیمت پیشنهادی شما (تومان، اختیاری)<input name="offered_price" value="{{ old('offered_price') }}" inputmode="numeric"></label>
        <button class="btn fill">ارسال درخواست قیمت</button>
      </form>
      @endif
    @else
      <a class="btn fill" href="{{ route('login') }}" style="display:block;text-align:center">برای درخواست قیمت وارد شوید</a>
    @endauth
  </aside>
</div>
@endsection
