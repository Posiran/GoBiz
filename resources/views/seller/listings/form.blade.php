@extends('layouts.app')
@section('title', ($listing->exists ? 'ویرایش آگهی' : 'آگهی جدید') . ' | Gobiz')
@section('content')
@php($v = fn ($k) => old($k, $listing->$k))
<form class="box form" method="post" enctype="multipart/form-data"
  action="{{ $listing->exists ? route('seller.listings.update', $listing) : route('seller.listings.store') }}">@csrf
  @if($listing->exists)@method('PUT')<div class="alert">با ذخیره تغییرات، آگهی دوباره برای تأیید مدیر ارسال می‌شود.</div>@endif
  <h1>{{ $listing->exists ? 'ویرایش آگهی' : 'ثبت آگهی جدید' }}</h1>
  <label>عنوان آگهی<input name="title" value="{{ $v('title') }}" maxlength="200" required></label>
  <div class="cols">
    <label>دسته<select name="category_id" required><option value="">انتخاب</option>
      @foreach($categories as $c)<optgroup label="{{ $c->name }}">
        @foreach($c->children as $k)<option value="{{ $k->id }}" @selected($v('category_id') == $k->id)>{{ $k->name }}</option>@endforeach</optgroup>@endforeach</select></label>
    <label>نوع آگهی<select name="type" id="type">
      @foreach(['machine' => 'ماشین‌آلات', 'factory' => 'کارخانه / سوله', 'part' => 'قطعه و ابزار', 'service' => 'خدمات', 'business' => 'فروش کسب‌وکار فعال', 'startup' => 'استارتاپ / جذب سرمایه'] as $k => $t)<option value="{{ $k }}" @selected($v('type') === $k)>{{ $t }}</option>@endforeach</select></label>
  </div>
  @include('partials.geo-fields', ['withTown' => true])
  <div id="specBox">
  <h2>مشخصات</h2>
  <div class="cols">
    <label>برند<input name="brand" value="{{ $v('brand') }}"></label>
    <label>مدل<input name="model" value="{{ $v('model') }}"></label>
    <label>سال ساخت<input name="manufacture_year" value="{{ $v('manufacture_year') }}" inputmode="numeric"></label>
    <label>کشور سازنده<input name="origin_country" value="{{ $v('origin_country') }}"></label>
    <label>وضعیت<select name="condition_state"><option value="">—</option>
      @foreach(['new' => 'نو', 'used' => 'دست‌دوم', 'refurbished' => 'بازسازی‌شده'] as $k => $t)<option value="{{ $k }}" @selected($v('condition_state') === $k)>{{ $t }}</option>@endforeach</select></label>
    <label>گارانتی (ماه)<input name="warranty_months" value="{{ $v('warranty_months') }}" inputmode="numeric"></label>
  </div>
  <div id="attrBox" data-values='@json(old("attrs", $attrValues))'></div>
  <label class="chk"><input type="checkbox" name="has_installation" value="1" @checked($v('has_installation'))> نصب و راه‌اندازی انجام می‌شود</label>
  </div>

  <div id="businessBox" hidden>
    @php($bd = $listing->business)
    <h2>اطلاعات کسب‌وکار / استارتاپ</h2>
    <p class="meta">متن «توضیحات» آگهی عمومی است؛ اطلاعات محرمانه را آنجا ننویسید. موارد «محرمانه» زیر فقط به متقاضیانی نمایش داده می‌شود که شما تأیید کنید.</p>
    <div class="cols">
      <label>نوع معامله<select name="sale_type">@foreach(\App\Models\BusinessDetail::SALE_TYPE as $k => $t)<option value="{{ $k }}" @selected(old('sale_type', $bd?->sale_type) === $k)>{{ $t }}</option>@endforeach</select></label>
      <label>حوزه فعالیت<input name="industry" value="{{ old('industry', $bd?->industry) }}" maxlength="100"></label>
      <label>مدل کسب‌وکار<input name="business_model" value="{{ old('business_model', $bd?->business_model) }}" maxlength="100" placeholder="مثلاً SaaS، تولیدی، خدماتی"></label>
      <label>سال تأسیس<input name="founded_year" value="{{ old('founded_year', $bd?->founded_year) }}" inputmode="numeric"></label>
      <label>تعداد اعضای تیم<input name="team_size" value="{{ old('team_size', $bd?->team_size) }}" inputmode="numeric"></label>
      <label>مرحله (استارتاپ)<select name="stage"><option value="">—</option>@foreach(\App\Models\BusinessDetail::STAGE as $k => $t)<option value="{{ $k }}" @selected(old('stage', $bd?->stage) === $k)>{{ $t }}</option>@endforeach</select></label>
      <label>درصد سهم قابل واگذاری / سرمایه‌پذیری<input name="stake_percent" value="{{ old('stake_percent', $bd?->stake_percent) }}" inputmode="decimal"></label>
      <label>ارزش‌گذاری کل (تومان، محرمانه)<input name="valuation" value="{{ old('valuation', $bd?->valuation) }}" inputmode="numeric"></label>
      <label>درآمد سالانه (تومان، محرمانه)<input name="annual_revenue" value="{{ old('annual_revenue', $bd?->annual_revenue) }}" inputmode="numeric"></label>
      <label>سود سالانه (تومان، محرمانه)<input name="annual_profit" value="{{ old('annual_profit', $bd?->annual_profit) }}" inputmode="numeric"></label>
      <label>درآمد ماهانه / MRR (تومان، محرمانه)<input name="monthly_revenue" value="{{ old('monthly_revenue', $bd?->monthly_revenue) }}" inputmode="numeric"></label>
      <label>تعداد مشتریان (محرمانه)<input name="customers_count" value="{{ old('customers_count', $bd?->customers_count) }}" inputmode="numeric"></label>
    </div>
    <label>دلیل فروش / جذب سرمایه (محرمانه)<textarea name="reason_for_sale" rows="3" maxlength="3000">{{ old('reason_for_sale', $bd?->reason_for_sale) }}</textarea></label>
    <label>دارایی‌ها و مواردی که شامل معامله است (محرمانه)<textarea name="assets_included" rows="3" maxlength="3000">{{ old('assets_included', $bd?->assets_included) }}</textarea></label>
    <label class="chk"><input type="checkbox" name="nda_required" value="1" @checked(old('nda_required', $bd?->nda_required ?? true))> اطلاعات محرمانه فقط پس از تأیید من و پذیرش تعهد محرمانگی نمایش داده شود</label>
    <p class="meta">قیمت آگهی در این نوع، «قیمت درخواستی» (یا مبلغ سرمایه موردنیاز) است.</p>
  </div>

  <div id="factoryBox" hidden>
    <h2>مشخصات کارخانه / سوله</h2>
    @php($f = $listing->factory)
    <div class="cols">
      <label>مساحت زمین (متر)<input name="land_area" value="{{ old('land_area', $f?->land_area) }}" inputmode="numeric"></label>
      <label>زیربنا (متر)<input name="building_area" value="{{ old('building_area', $f?->building_area) }}" inputmode="numeric"></label>
      <label>برق (کیلووات)<input name="power_kw" value="{{ old('power_kw', $f?->power_kw) }}" inputmode="numeric"></label>
      <label>موقعیت<select name="zone_type"><option value="">—</option>
        @foreach(['industrial_town' => 'داخل شهرک صنعتی', 'free_zone' => 'منطقه آزاد', 'outside_town' => 'خارج از شهرک'] as $k => $t)<option value="{{ $k }}" @selected(old('zone_type', $f?->zone_type) === $k)>{{ $t }}</option>@endforeach</select></label>
    </div>
    <label class="chk"><input type="checkbox" name="has_license" value="1" @checked(old('has_license', $f?->has_license))> پروانه بهره‌برداری دارد</label>
  </div>

  <h2>قیمت و معامله</h2>
  <div class="cols">
    <label>نوع معامله<select name="deal_type">
      @foreach(['sale' => 'فروش', 'rent' => 'اجاره', 'lease' => 'رهن / لیزینگ'] as $k => $t)<option value="{{ $k }}" @selected($v('deal_type') === $k)>{{ $t }}</option>@endforeach</select></label>
    <label>قیمت (تومان؛ خالی = توافقی)<input name="price" value="{{ $v('price') }}" inputmode="numeric"></label>
    <label>حداقل سفارش<input name="min_order_qty" value="{{ $v('min_order_qty') }}" inputmode="numeric"></label>
    <label>موجودی<input name="stock_qty" value="{{ $v('stock_qty') }}" inputmode="numeric"></label>
  </div>
  <label class="chk"><input type="checkbox" name="price_negotiable" value="1" @checked($v('price_negotiable'))> قیمت قابل مذاکره است</label>
  <label>توضیحات<textarea name="description" rows="6">{{ $v('description') }}</textarea></label>

  <h2>تصاویر (حداکثر ۸ عکس، هر کدام تا ۴ مگابایت)</h2>
  @if($listing->exists && $listing->media->isNotEmpty())
  <div class="thumbs">@foreach($listing->media->where('kind', 'image') as $m)
    <label><img src="{{ asset('storage/'.$m->path) }}" alt=""><input type="checkbox" name="remove_images[]" value="{{ $m->id }}"> حذف</label>@endforeach</div>
  @endif
  <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
  <button class="btn fill">{{ $listing->exists ? 'ذخیره تغییرات' : 'ثبت آگهی' }}</button>
</form>
@include('partials.geo-js')
@include('partials.attr-js')
<script>
const t = document.getElementById('type'), fb = document.getElementById('factoryBox'), bb = document.getElementById('businessBox'), sb = document.getElementById('specBox');
const sync = () => { const biz = ['business', 'startup'].includes(t.value); fb.hidden = t.value !== 'factory'; bb.hidden = !biz; sb.hidden = biz; };
t.addEventListener('change', sync); sync();
</script>
@endsection
