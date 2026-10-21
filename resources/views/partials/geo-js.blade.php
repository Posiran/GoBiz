<script>
// انتخاب زنجیره‌ای: استان ← شهر ← شهرک صنعتی
(() => {
  const $ = id => document.getElementById(id);
  const fill = (el, rows) => { if (el) el.innerHTML = '<option value="">' + (el.required ? 'انتخاب' : 'همه') + '</option>' + rows.map(r => `<option value="${r.id}">${r.name}</option>`).join(''); };
  const get = async url => (await fetch(url)).json();
  $('province')?.addEventListener('change', async e => { fill($('town'), []); fill($('city'), e.target.value ? await get(`/geo/provinces/${e.target.value}/cities`) : []); });
  $('city')?.addEventListener('change', async e => fill($('town'), e.target.value ? await get(`/geo/cities/${e.target.value}/towns`) : []));
})();
</script>
