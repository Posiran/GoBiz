@extends('layouts.app')
@section('title', 'پنل مدیریت | Gobiz')
@section('content')
@php($F = '\App\Support\Fa')
@php($lst = ['pending' => 'در انتظار', 'published' => 'منتشرشده', 'rejected' => 'ردشده', 'sold' => 'فروخته‌شده', 'expired' => 'منقضی'])
@php($cst = ['pending' => 'در انتظار', 'active' => 'فعال', 'suspended' => 'تعلیق‌شده'])
<div class="tabs">
  <a href="{{ route('admin.orders.index') }}">سفارش‌ها و اختلاف‌ها</a>
  <a class="{{ $tab === 'listings' ? 'on' : '' }}" href="{{ route('admin.index', ['tab' => 'listings']) }}">آگهی‌ها ({{ $F::digits($counts['listings']) }} در انتظار)</a>
  <a class="{{ $tab === 'payments' ? 'on' : '' }}" href="{{ route('admin.index', ['tab' => 'payments']) }}">پرداخت‌ها</a>
  <a class="{{ $tab === 'companies' ? 'on' : '' }}" href="{{ route('admin.index', ['tab' => 'companies']) }}">شرکت‌ها ({{ $F::digits($counts['companies']) }} در انتظار)</a>
</div>
<div class="tabs">
  @foreach(['companies' => $cst, 'payments' => ['paid' => 'موفق', 'pending' => 'در انتظار', 'failed' => 'ناموفق'], 'listings' => $lst][$tab] as $k => $t)<a class="{{ $status === $k ? 'on' : '' }}" href="{{ route('admin.index', ['tab' => $tab, 'status' => $k]) }}">{{ $t }}</a>@endforeach
</div>

@if($rows->isEmpty())<div class="empty">موردی در این وضعیت نیست.</div>
@elseif($tab === 'payments')
<table class="tbl"><tr><th>تاریخ</th><th>کاربر</th><th>پلن</th><th>مبلغ</th><th>درگاه</th><th>کد پیگیری</th><th>خطا</th></tr>
@foreach($rows as $x)
<tr><td>{{ $F::jdate($x->created_at) }}</td><td>{{ $x->user->name }}<br><small class="meta">{{ $x->user->mobile }}</small></td><td>{{ $x->plan?->name }}</td>
  <td>{{ $F::price($x->amount) }}</td><td>{{ $x->gateway }}</td><td dir="ltr">{{ $x->ref_id }}</td><td>{{ $x->error }}</td></tr>
@endforeach</table>
@elseif($tab === 'companies')
<table class="tbl"><tr><th>شرکت</th><th>مالک</th><th>شناسه ملی</th><th>وضعیت و سطح تأیید</th></tr>
@foreach($rows as $c)
<tr><td>{{ $c->name }}</td><td>{{ $c->user->name }}<br><small class="meta">{{ $c->user->mobile }}</small></td><td>{{ $c->national_id }}</td>
  <td><form method="post" action="{{ route('admin.company', $c) }}">@csrf
    <select name="status">@foreach($cst as $k => $t)<option value="{{ $k }}" @selected($c->status === $k)>{{ $t }}</option>@endforeach</select>
    <select name="verified_level">@foreach([0 => 'بدون تأیید مدارک', 1 => 'مدارک تأییدشده', 2 => 'بازدید میدانی'] as $k => $t)<option value="{{ $k }}" @selected($c->verified_level == $k)>{{ $t }}</option>@endforeach</select>
    <button>ذخیره</button></form></td></tr>
@endforeach</table>
@else
<table class="tbl"><tr><th>آگهی</th><th>شرکت</th><th>عملیات</th></tr>
@foreach($rows as $l)
<tr><td><a href="{{ route('listings.show', $l->slug) }}" target="_blank">{{ $l->title }}</a>@if($l->is_featured) <span class="badge">ویژه</span>@endif</td>
  <td>{{ $l->company->name }} <span class="badge">{{ $cst[$l->company->status] }}</span></td>
  <td><form method="post" action="{{ route('admin.listing', $l) }}">@csrf
    @if($l->status !== 'published')<button name="action" value="approve">تأیید و انتشار</button>@endif
    @if($l->status !== 'rejected')<button name="action" value="reject">رد</button>@endif
    @if($l->status === 'published')<button name="action" value="{{ $l->is_featured ? 'unfeature' : 'feature' }}">{{ $l->is_featured ? 'حذف ویژه' : 'ویژه (۳۰ روز)' }}</button>@endif
  </form></td></tr>
@endforeach</table>
@endif
{{ $rows->links('pagination::default') }}
@endsection
