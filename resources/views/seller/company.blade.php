@extends('layouts.app')
@section('title', 'اطلاعات شرکت | Gobiz')
@section('content')
<form class="box form" method="post" action="{{ route('seller.company.save') }}">@csrf
  <h1>{{ $company->exists ? 'ویرایش اطلاعات شرکت' : 'ثبت شرکت' }}</h1>
  <label>نام شرکت / کارخانه<input name="name" value="{{ old('name', $company->name) }}" required></label>
  <div class="cols">
    <label>نوع فعالیت<select name="business_type">
      @foreach(['manufacturer' => 'تولیدکننده', 'trader' => 'بازرگان', 'agent' => 'نماینده', 'service' => 'خدماتی'] as $k => $v)
        <option value="{{ $k }}" @selected(old('business_type', $company->business_type) === $k)>{{ $v }}</option>@endforeach</select></label>
    <label>تعداد کارکنان<select name="employees_range"><option value="">—</option>
      @foreach(['1-10', '11-50', '51-200', '201-500', '500+'] as $v)<option @selected(old('employees_range', $company->employees_range) === $v)>{{ $v }}</option>@endforeach</select></label>
  </div>
  <div class="cols">
    <label>شناسه ملی<input name="national_id" value="{{ old('national_id', $company->national_id) }}" inputmode="numeric"></label>
    <label>شماره ثبت<input name="registration_no" value="{{ old('registration_no', $company->registration_no) }}"></label>
  </div>
  <div class="cols">
    <label>سال تأسیس (مثلاً ۱۳۹۰)<input name="established_year" value="{{ old('established_year', $company->established_year) }}" inputmode="numeric"></label>
    <label>تلفن<input name="phone" value="{{ old('phone', $company->phone) }}" inputmode="tel"></label>
  </div>
  @include('partials.geo-fields', ['withTown' => false])
  <label>آدرس<input name="address" value="{{ old('address', $company->address) }}"></label>
  <label>وب‌سایت<input name="website" value="{{ old('website', $company->website) }}" placeholder="https://"></label>
  <label>معرفی شرکت<textarea name="description" rows="5">{{ old('description', $company->description) }}</textarea></label>
  <button class="btn fill">ذخیره</button>
</form>
@include('partials.geo-js')
@endsection
