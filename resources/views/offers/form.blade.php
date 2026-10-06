@extends('layouts.app')
@section('title', ($offer->exists ? 'ویرایش پیش‌فاکتور' : 'پیش‌فاکتور جدید') . ' | Gobiz')
@section('content')
<form class="box form" style="max-width:980px" method="post" action="{{ $offer->exists ? route('offers.update', $offer) : route('offers.store') }}">@csrf
  @if($offer->exists)@method('PUT')@endif
  @foreach($ctx['param'] as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
  <h1>{{ $offer->exists ? 'ویرایش پیش‌فاکتور' : 'پیش‌فاکتور جدید' }}</h1>
  <p class="meta">خریدار: <b>{{ $ctx['buyer']->name }}</b> · موضوع: {{ $ctx['title'] }}</p>

  <h2>اقلام</h2>
  <div style="overflow-x:auto"><table class="tbl" id="items"><thead><tr><th>شرح</th><th>واحد</th><th>مقدار</th><th>قیمت واحد (تومان)</th><th>جمع</th><th></th></tr></thead>
    <tbody>@foreach(array_values($items) as $i => $it)@include('offers.row', ['i' => $i, 'it' => $it])@endforeach</tbody></table></div>
  <datalist id="units">@foreach(['عدد', 'دستگاه', 'دست', 'تن', 'کیلوگرم', 'متر', 'متر مربع', 'لیتر', 'بسته', 'ساعت کار'] as $u)<option value="{{ $u }}">@endforeach</datalist>
  <template id="rowTpl">@include('offers.row', ['i' => '__i__', 'it' => []])</template>
  <div><button type="button" class="btn" id="addRow">+ افزودن ردیف</button></div>

  <div class="cols">
    <label>درصد مالیات<input name="tax_percent" value="{{ old('tax_percent', $offer->tax_percent ?? 0) }}" inputmode="numeric" required></label>
    <label>هزینه حمل (تومان)<input name="shipping_cost" value="{{ old('shipping_cost', $offer->shipping_cost ?: 0) }}" inputmode="numeric"></label>
  </div>
  <div class="box" style="padding:10px 14px">جمع کل: <b id="grand">—</b></div>

  <h2>شرایط معامله</h2>
  <div class="cols">
    <label>شرایط تحویل<select name="delivery_terms">
      @foreach(\App\Models\Offer::DELIVERY as $k => $v)<option value="{{ $k }}" @selected(old('delivery_terms', $offer->delivery_terms) === $k)>{{ $v }}</option>@endforeach</select></label>
    <label>محل تحویل<input name="delivery_place" value="{{ old('delivery_place', $offer->delivery_place) }}" maxlength="200"></label>
    <label>مدت تحویل (روز پس از پذیرش)<input name="delivery_days" value="{{ old('delivery_days', $offer->delivery_days) }}" inputmode="numeric"></label>
    <label>درصد پیش‌پرداخت (۰ تا ۱۰۰)<input name="advance_percent" value="{{ old('advance_percent', $offer->advance_percent) }}" inputmode="numeric" required></label>
    <label>گارانتی (ماه)<input name="warranty_months" value="{{ old('warranty_months', $offer->warranty_months) }}" inputmode="numeric"></label>
    <label>مهلت بازرسی خریدار پس از تحویل (روز)<input name="inspection_days" value="{{ old('inspection_days', $offer->inspection_days) }}" inputmode="numeric" required></label>
    <label>اعتبار پیش‌فاکتور (روز از امروز)<input name="valid_days" value="{{ old('valid_days', $validDays) }}" inputmode="numeric" required></label>
  </div>
  <label>شرایط تسویه مانده<textarea name="payment_terms" rows="2" maxlength="1000" placeholder="مثلاً: مانده هنگام تحویل با حواله بانکی">{{ old('payment_terms', $offer->payment_terms) }}</textarea></label>
  <label>توضیحات<textarea name="notes" rows="3" maxlength="2000">{{ old('notes', $offer->notes) }}</textarea></label>
  <div class="acts"><button class="btn" name="action" value="draft">ذخیره پیش‌نویس</button><button class="btn fill" name="action" value="send">ذخیره و ارسال برای خریدار</button></div>
</form>
<script>
(() => {
  const body = document.querySelector('#items tbody'), tpl = document.getElementById('rowTpl').innerHTML;
  let n = body.rows.length;
  const num = s => parseFloat(String(s ?? '').replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/[,٬\s]/g, '')) || 0;
  const fmt = v => Math.round(v).toLocaleString('fa-IR');
  const calc = () => {
    let sub = 0;
    [...body.rows].forEach(tr => { const t = num(tr.querySelector('.q').value) * num(tr.querySelector('.p').value); sub += t; tr.querySelector('.lt').textContent = fmt(t); });
    const tax = sub * num(document.querySelector('[name=tax_percent]').value) / 100, ship = num(document.querySelector('[name=shipping_cost]').value);
    document.getElementById('grand').textContent = fmt(sub + tax + ship) + ' تومان';
  };
  document.getElementById('addRow').addEventListener('click', () => { body.insertAdjacentHTML('beforeend', tpl.replaceAll('__i__', n++)); calc(); });
  body.addEventListener('click', e => { if (e.target.classList.contains('rm') && body.rows.length > 1) { e.target.closest('tr').remove(); calc(); } });
  document.querySelector('form').addEventListener('input', calc); calc();
})();
</script>
@endsection
