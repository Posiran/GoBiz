@php($img = $l->media->firstWhere('kind', 'image'))
<a class="item" href="{{ route('listings.show', $l->slug) }}">
  @if($img)<img src="{{ asset('storage/'.$img->path) }}" alt="{{ $l->title }}" style="height:140px;width:100%;object-fit:cover">
  @else<div class="ph c{{ $l->category_id % 8 + 1 }}">{{ $l->category?->name }}</div>@endif
  <div class="b">
    @if($l->business)
      <span class="tag">{{ $l->type === 'startup' ? 'استارتاپ' : 'کسب‌وکار فعال' }} · {{ \App\Models\BusinessDetail::SALE_TYPE[$l->business->sale_type] }}</span>
    @endif
    <h3>{{ $l->title }}</h3>
    <span class="price">{{ \App\Support\Fa::price($l->price) }}</span>
    @if($l->business)
      @php($bm = $l->business->metrics($l->price))
      <span class="meta">{{ $l->business->industry ?: 'حوزه فعالیت نامشخص' }} · @if($bm['valuation']) ارزش‌گذاری {{ \App\Support\Fa::price($bm['valuation']) }}@else مذاکره‌ای@endif</span>
    @else
      <span class="meta">{{ $l->city?->name }}@if($l->industrialTown) · {{ $l->industrialTown->name }}@endif</span>
    @endif
    @if($l->company?->isVerified())<span class="tag">شرکت تأییدشده</span>@endif
  </div>
</a>
