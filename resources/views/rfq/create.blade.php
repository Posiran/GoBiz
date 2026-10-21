@extends('layouts.app')
@section('title', 'ثبت درخواست قیمت | Gobiz')
@section('content')
<form class="box form" method="post" action="{{ route('rfq.store') }}">@csrf
  <h1>درخواست قیمت</h1>
  <p class="meta">آنچه می‌خواهید را بنویسید؛ فروشندگان تأییدشده پیشنهاد قیمت خود را در «پیام‌ها» می‌فرستند.</p>
  <label>چه چیزی نیاز دارید؟<input name="title" value="{{ old('title') }}" maxlength="200" placeholder="مثلاً: دستگاه فرز CNC سه‌محور" required></label>
  <label>دسته (اختیاری)<select name="category_id"><option value="">—</option>
    @foreach($categories as $c)<optgroup label="{{ $c->name }}">@foreach($c->children as $k)<option value="{{ $k->id }}" @selected(old('category_id') == $k->id)>{{ $k->name }}</option>@endforeach</optgroup>@endforeach</select></label>
  @include('partials.geo-fields', ['withTown' => false, 'cityOptional' => true])
  <div class="cols">
    <label>تعداد<input name="quantity" value="{{ old('quantity') }}" inputmode="numeric"></label>
    <label>بودجه (تومان، اختیاری)<input name="budget" value="{{ old('budget') }}" inputmode="numeric"></label>
  </div>
  <label>جزئیات و مشخصات مورد نیاز<textarea name="details" rows="6" minlength="10" required>{{ old('details') }}</textarea></label>
  <button class="btn fill">ثبت درخواست</button>
</form>
@include('partials.geo-js')
@endsection
