@extends('pages._layout')
@section('body')
@if(config('gobiz.contact_email') || config('gobiz.contact_phone'))
<p>@if(config('gobiz.contact_email'))ایمیل: <span dir="ltr">{{ config('gobiz.contact_email') }}</span><br>@endif
@if(config('gobiz.contact_phone'))تلفن: <span dir="ltr">{{ config('gobiz.contact_phone') }}</span>@endif</p>
@else<p>اطلاعات تماس هنوز تنظیم نشده است.</p>@endif
@endsection
