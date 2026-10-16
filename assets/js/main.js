// Nena Studio — main.js — vanilla JS, no frameworks
const $ = (s, r=document) => r.querySelector(s);
const $$ = (s, r=document) => [...r.querySelectorAll(s)];

// Mobile nav
const hamb = $('#hamb');
const navLinks = $('#navLinks');
if (hamb && navLinks) {
  hamb.addEventListener('click', () => {
    const open = navLinks.classList.toggle('open');
    hamb.setAttribute('aria-expanded', String(open));
  });
  navLinks.addEventListener('click', (e) => {
    if (e.target.matches('a')) { navLinks.classList.remove('open'); hamb.setAttribute('aria-expanded','false'); }
  });
}

// Smooth scroll + active section
$$('a[href^="#"]').forEach(a=>{
  a.addEventListener('click', (e)=>{
    const id = a.getAttribute('href');
    if (id.length>1) {
      const el = document.querySelector(id);
      if (el) { e.preventDefault(); el.scrollIntoView({behavior:'smooth', block:'start'}); history.pushState(null,'',id); }
    }
  });
});

// Reveal on scroll
const reveals = $$('.reveal');
const io = new IntersectionObserver((entries)=>{
  entries.forEach(e=>{ if(e.isIntersecting) e.target.classList.add('in'); });
},{threshold:0.12});
reveals.forEach(el=>io.observe(el));

// Portfolio filter + modal
const grid = $('#portfolioGrid');
const pills = $$('.pill');
let stories = [];
try { stories = JSON.parse($('#portfolioData')?.textContent || '[]'); } catch(e) { stories = []; }

function renderPortfolio(filter='all'){
  if (!grid) return;
  grid.innerHTML='';
  const list = filter==='all' ? stories : stories.filter(s=> s.category===filter);
  if (!list.length) {
    grid.innerHTML = '<div class="glass" style="grid-column:1/-1; border-radius:18px; padding:22px; text-align:center; color:var(--muted)">No stories in this category yet — check back soon ✨</div>';
    return;
  }
  list.forEach(s=>{
    const card = document.createElement('article');
    card.className='p-card glass stitch reveal in';
    card.tabIndex=0;
    card.setAttribute('role','button');
    card.setAttribute('aria-label', s.title + ' — ' + s.category);
    card.innerHTML = `
      <div class="p-media"><img loading="lazy" decoding="async" width="640" height="520" src="${esc(s.cover_image)}" alt="${esc(s.alt_text||s.title)}"></div>
      <div class="p-body">
        <div class="p-cat">${esc(s.category)}</div>
        <h3 class="p-title">${esc(s.title)}</h3>
        <p class="p-summary">${esc(s.summary||'')}</p>
      </div>`;
    const open = ()=> openStory(s.slug);
    card.addEventListener('click', open);
    card.addEventListener('keydown', (e)=>{ if(e.key==='Enter' || e.key===' ') { e.preventDefault(); open(); }});
    grid.appendChild(card);
  });
}
function esc(s){ return String(s||'').replace(/[&<>"']/g, m=> ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

pills.forEach(p=>{
  p.addEventListener('click', ()=>{
    pills.forEach(x=>x.setAttribute('aria-pressed','false'));
    p.setAttribute('aria-pressed','true');
    renderPortfolio(p.dataset.filter || 'all');
  });
});
renderPortfolio('all');

// Modal
const modal = $('#storyModal');
const modalTitle = $('#modalTitle');
const modalMedia = $('#modalMedia');
const modalStory = $('#modalStory');
const modalCarousel = $('#modalCarousel');
const modalVideoWrap = $('#modalVideoWrap');
function openStory(slug){
  const s = stories.find(x=> x.slug===slug);
  if(!s || !modal) return;
  modalTitle.textContent = s.title;
  modalMedia.innerHTML = `<img loading="eager" decoding="async" width="900" height="560" src="${esc(s.cover_image)}" alt="${esc(s.alt_text||s.title)}">`;
  modalStory.innerHTML = s.story || '';
  // carousel
  let imgs = [];
  try { imgs = JSON.parse(s.images||'[]'); if(!Array.isArray(imgs)) imgs=[]; } catch(e){ imgs=[]; }
  if (imgs.length){
    modalCarousel.style.display='flex';
    modalCarousel.innerHTML = imgs.map(src=> `<img loading="lazy" decoding="async" width="440" height="300" src="${esc(src)}" alt="${esc(s.title)} — detail">`).join('');
  } else { modalCarousel.style.display='none'; modalCarousel.innerHTML=''; }
  if (s.video){
    modalVideoWrap.style.display='grid';
    modalVideoWrap.innerHTML = `<video controls preload="metadata" playsinline style="width:100%;height:100%"><source src="${esc(s.video)}" type="video/mp4">Your browser does not support video.</video>`;
  } else { modalVideoWrap.style.display='none'; modalVideoWrap.innerHTML=''; }
  modal.classList.add('open');
  modal.setAttribute('aria-hidden','false');
  document.body.style.overflow='hidden';
  history.replaceState(null,'','#story-'+slug);
}
function closeStory(){
  if(!modal) return;
  modal.classList.remove('open');
  modal.setAttribute('aria-hidden','true');
  document.body.style.overflow='';
  if (location.hash.startsWith('#story-')) history.replaceState(null,'','#behind-the-stitches');
}
$('#modalClose')?.addEventListener('click', closeStory);
$('#modalBackdrop')?.addEventListener('click', closeStory);
document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') closeStory(); });
// open from hash
if (location.hash.startsWith('#story-')) {
  const slug = location.hash.replace('#story-','');
  setTimeout(()=> openStory(slug), 200);
}

// Service -> preselect form
$$('[data-service]').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    const v = btn.getAttribute('data-service');
    const radio = document.querySelector(`input[name="project_type"][value="${CSS.escape(v)}"]`);
    if (radio) { radio.checked=true; radio.dispatchEvent(new Event('change',{bubbles:true})); }
    document.querySelector('#lets-create-together')?.scrollIntoView({behavior:'smooth'});
    // highlight
    document.querySelectorAll('.radio-card').forEach(c=> c.style.outline='');
    if (radio) radio.closest('.radio-card').style.outline='2px solid rgba(0,166,166,0.28)';
    setTimeout(()=> radio?.closest('.radio-card') && (radio.closest('.radio-card').style.outline=''), 1800);
  });
});

// Contact pills
const contactPills = $$('.contact-pills button');
const contactDetailWrap = $('#contactDetailWrap');
const contactDetailInput = $('#contact_detail');
contactPills.forEach(b=>{
  b.addEventListener('click', ()=>{
    const on = b.getAttribute('aria-pressed')==='true';
    contactPills.forEach(x=> x.setAttribute('aria-pressed','false'));
    if (!on) {
      b.setAttribute('aria-pressed','true');
      const v = b.dataset.value;
      $('#contact_method').value = v;
      if (v==='Email') { contactDetailWrap.style.display='none'; contactDetailInput.required=false; contactDetailInput.placeholder=''; }
      else {
        contactDetailWrap.style.display='block';
        contactDetailInput.required=false;
        contactDetailInput.placeholder = v==='WhatsApp / Phone' ? 'e.g. +31 6 12345678' : 'e.g. @nena.studio';
        contactDetailInput.focus();
      }
    } else {
      $('#contact_method').value='';
      contactDetailWrap.style.display='none';
    }
  });
});

// Date flexible
const flexible = $('#flexible');
const targetDate = $('#target_date');
if (flexible && targetDate){
  flexible.addEventListener('change', ()=>{
    targetDate.disabled = flexible.checked;
    targetDate.required = !flexible.checked ? false : false;
    targetDate.style.opacity = flexible.checked ? '0.55' : '1';
    if (flexible.checked) targetDate.value='';
  });
}

// Drag & drop uploader with previews
const drop = $('#dropZone');
const fileInput = $('#inspiration');
const thumbs = $('#thumbs');
const fileList = [];
const MAX_MB = 10;
const ALLOWED = ['image/jpeg','image/png','image/webp','application/pdf'];

function addFiles(files){
  for (const f of files){
    if (!ALLOWED.includes(f.type) && !f.type.startsWith('image/')) { toast('Unsupported file: '+f.name, 'err'); continue; }
    if (f.size > MAX_MB*1024*1024) { toast(f.name+' is over 10MB', 'err'); continue; }
    if (fileList.length >= 8) { toast('Maximum 8 files', 'err'); break; }
    fileList.push(f);
  }
  renderThumbs();
  syncInput();
}
function renderThumbs(){
  if(!thumbs) return;
  thumbs.innerHTML='';
  fileList.forEach((f, idx)=>{
    const wrap = document.createElement('div');
    wrap.style.cssText='position:relative';
    let inner='';
    if (f.type.startsWith('image/')){
      const url = URL.createObjectURL(f);
      inner = `<img src="${url}" alt="Preview ${idx+1}: ${esc(f.name)}" width="84" height="84">`;
    } else {
      inner = `<div style="width:84px;height:84px; display:grid; place-items:center; border-radius:10px; border:1px solid rgba(0,109,119,0.10); background:#fff; font-size:11px; font-weight:700; color:var(--teal-dark); padding:6px; text-align:center">PDF<br>${esc(f.name.slice(0,14))}</div>`;
    }
    wrap.innerHTML = inner + `<button type="button" aria-label="Remove ${esc(f.name)}" style="position:absolute; top:-6px; right:-6px; width:22px; height:22px; border-radius:50%; border:none; background:var(--teal-dark); color:#fff; cursor:pointer; font-size:12px; line-height:1">×</button>`;
    wrap.querySelector('button').addEventListener('click', ()=>{ fileList.splice(idx,1); renderThumbs(); syncInput(); });
    thumbs.appendChild(wrap);
  });
}
function syncInput(){
  if(!fileInput) return;
  const dt = new DataTransfer();
  fileList.forEach(f=> dt.items.add(f));
  fileInput.files = dt.files;
}
if (drop && fileInput){
  drop.addEventListener('click', ()=> fileInput.click());
  drop.addEventListener('dragover', (e)=>{ e.preventDefault(); drop.classList.add('drag'); });
  drop.addEventListener('dragleave', ()=> drop.classList.remove('drag'));
  drop.addEventListener('drop', (e)=>{ e.preventDefault(); drop.classList.remove('drag'); addFiles(e.dataTransfer.files); });
  fileInput.addEventListener('change', ()=> addFiles(fileInput.files));
}

// AJAX form submit
const form = $('#enquiryForm');
const msg = $('#formMsg');
function toast(t, kind='ok'){
  if(!msg) return;
  msg.textContent=t;
  msg.className='form-msg '+(kind==='ok'?'ok':'err');
  msg.style.display='block';
}
if (form){
  form.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    const orig = btn.textContent;
    btn.disabled=true; btn.textContent='Sending…';
    msg.style.display='none';
    try{
      const fd = new FormData(form);
      // honeypot
      if (fd.get('website')) { toast('Submission blocked.', 'err'); btn.disabled=false; btn.textContent=orig; return; }
      const res = await fetch(form.action, {method:'POST', body: fd, headers:{'Accept':'application/json'}});
      const data = await res.json().catch(()=> ({}));
      if (res.ok && data.ok){
        toast('Thank you — your story is on its way! We\'ll be in touch soon ✨','ok');
        form.reset();
        fileList.length=0; renderThumbs(); syncInput();
        contactPills.forEach(x=>x.setAttribute('aria-pressed','false'));
        contactDetailWrap.style.display='none';
        if(flexible) { flexible.checked=false; targetDate.disabled=false; targetDate.style.opacity='1'; }
        // reset radio visuals
        document.querySelectorAll('.radio-card').forEach(c=> c.style.outline='');
      } else {
        toast(data.error || 'Something went wrong — please try again or email us directly.', 'err');
      }
    } catch(err){
      toast('Network error — please check your connection and try again.', 'err');
    } finally {
      btn.disabled=false; btn.textContent=orig;
    }
  });
}
