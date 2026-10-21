<div class="cols">
  <label>استان<select name="province_id" id="province"><option value="">انتخاب</option>
    @foreach($provinces as $p)<option value="{{ $p->id }}" @selected($sel['province'] == $p->id)>{{ $p->name }}</option>@endforeach</select></label>
  <label>شهر / شهرستان<select name="city_id" id="city" @if(!($cityOptional ?? false)) required @endif><option value="">انتخاب</option>
    @foreach($cities as $c)<option value="{{ $c->id }}" @selected($sel['city'] == $c->id)>{{ $c->name }}</option>@endforeach</select></label>
</div>
@if($withTown ?? false)
<label>شهرک صنعتی (اختیاری)<select name="industrial_town_id" id="town"><option value="">همه</option>
  @foreach($towns as $t)<option value="{{ $t->id }}" @selected($sel['town'] == $t->id)>{{ $t->name }}</option>@endforeach</select></label>
@endif
