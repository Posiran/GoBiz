<script>
// مشخصات فنی بر اساس دسته انتخاب‌شده (ساخت DOM، بدون innerHTML)
(() => {
  const sel = document.querySelector('[name=category_id]'), box = document.getElementById('attrBox');
  if (!sel || !box) return;
  const vals = JSON.parse(box.dataset.values || '{}');
  const render = async () => {
    box.replaceChildren();
    if (!sel.value) return;
    const rows = await (await fetch(`/categories/${sel.value}/attributes`)).json();
    if (!rows.length) return;
    const h = document.createElement('h2'); h.textContent = 'مشخصات فنی'; box.append(h);
    const grid = document.createElement('div'); grid.className = 'cols'; box.append(grid);
    rows.forEach(a => {
      const lab = document.createElement('label'); lab.append(a.name + (a.unit ? ` (${a.unit})` : ''));
      const v = vals[a.id] ?? ''; let inp;
      if (a.type === 'select') { inp = document.createElement('select'); inp.append(new Option('—', '')); (a.options || []).forEach(o => inp.append(new Option(o, o, false, o === v))); }
      else if (a.type === 'bool') { inp = document.createElement('input'); inp.type = 'checkbox'; inp.value = '1'; inp.checked = !!v; lab.className = 'chk'; }
      else { inp = document.createElement('input'); inp.value = v; if (a.type === 'number') inp.inputMode = 'numeric'; }
      inp.name = `attrs[${a.id}]`;
      a.type === 'bool' ? lab.prepend(inp) : lab.append(inp);
      grid.append(lab);
    });
  };
  sel.addEventListener('change', () => { Object.keys(vals).forEach(k => delete vals[k]); render(); });
  render();
})();
</script>
