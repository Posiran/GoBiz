
document.addEventListener('DOMContentLoaded',()=>{
  const theme=document.querySelector('[data-theme-toggle]');
  if(theme) theme.addEventListener('click',()=>{document.documentElement.classList.toggle('dim')});
  document.querySelectorAll('[data-demo]').forEach(el=>el.addEventListener('click',e=>{e.preventDefault(); const msg=el.dataset.demo; const box=document.querySelector('#demo-toast'); if(box){box.textContent=msg;box.hidden=false;setTimeout(()=>box.hidden=true,2400)}}));
  const search=document.querySelector('[data-filter-input]');
  if(search){search.addEventListener('input',()=>{const q=search.value.trim().toLowerCase();document.querySelectorAll('[data-card]').forEach(c=>c.style.display=!q||c.innerText.toLowerCase().includes(q)?'':'none')})}
  document.querySelectorAll('[data-tab]').forEach(tab=>tab.addEventListener('click',()=>{document.querySelectorAll('[data-tab]').forEach(t=>t.classList.remove('active'));tab.classList.add('active');const target=tab.dataset.tab;document.querySelectorAll('[data-pane]').forEach(p=>p.hidden=p.dataset.pane!==target)}));
});
