<tr>
  <td><input name="items[{{ $i }}][title]" value="{{ $it['title'] ?? '' }}" required maxlength="200" placeholder="عنوان کالا یا خدمت" aria-label="عنوان">
      <input name="items[{{ $i }}][description]" value="{{ $it['description'] ?? '' }}" maxlength="500" placeholder="توضیح (اختیاری)" aria-label="توضیح" style="margin-top:4px"></td>
  <td><input name="items[{{ $i }}][unit]" value="{{ $it['unit'] ?? 'عدد' }}" list="units" required maxlength="20" size="7" aria-label="واحد"></td>
  <td><input name="items[{{ $i }}][quantity]" value="{{ $it['quantity'] ?? 1 }}" inputmode="decimal" required class="q" size="7" aria-label="مقدار"></td>
  <td><input name="items[{{ $i }}][unit_price]" value="{{ $it['unit_price'] ?? '' }}" inputmode="numeric" required class="p" size="12" aria-label="قیمت واحد"></td>
  <td class="lt">—</td>
  <td><button type="button" class="rm" aria-label="حذف ردیف">×</button></td>
</tr>
