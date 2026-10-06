@extends('layouts.app')
@section('title', 'پیام‌ها | Gobiz')
@section('content')
@php($F = '\App\Support\Fa')
@php($me = auth()->id())
<h2>پیام‌ها <a class="btn" href="{{ route('offers.index') }}">پیش‌فاکتورها</a> <a class="btn" href="{{ route('rfq.mine') }}">درخواست‌های من</a></h2>
@if($threads->isEmpty())<div class="empty">هنوز پیامی ندارید. از صفحه هر آگهی می‌توانید درخواست قیمت بفرستید.</div>
@else
<table class="tbl"><tr><th>موضوع</th><th>طرف مقابل</th><th>آخرین فعالیت</th><th></th></tr>
@foreach($threads as $t)
@php($other = $t->from_user_id === $me ? $t->to : $t->from)
@php($isNew = ($t->to_user_id === $me && $t->status === 'new') || $t->unread > 0)
<tr><td><a href="{{ route('inbox.show', $t) }}">{{ $t->listing?->title ?? $t->rfq?->title ?? 'پیام' }}</a>
    <small class="meta">{{ $t->rfq_id ? ' · درخواست قیمت' : '' }}</small></td>
  <td>{{ $other?->company?->name ?? $other?->name }}</td><td>{{ $F::jdate($t->updated_at) }}</td>
  <td>@if($isNew)<span class="badge" style="background:var(--accent);color:var(--accent-ink);border-color:var(--accent)">جدید</span>@endif</td></tr>
@endforeach</table>
{{ $threads->links('pagination::default') }}
@endif
@endsection
