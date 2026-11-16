/* Native Builder labels own CTAs; preserve names on shared/plugin controls. */
document.addEventListener('DOMContentLoaded',()=>{
 document.querySelectorAll('a[href],button').forEach(control=>{
  if(control.closest('#wpadminbar')||control.hasAttribute('aria-label')||control.hasAttribute('aria-labelledby'))return;
  const text=(control.textContent||'').replace(/\s+/g,' ').trim();
  let label=text;
  if(control.closest('.dona-footer-signup')&&control.tagName==='BUTTON')label='Subscribe to the DONA newsletter';
  else if(control.closest('.dona-currency')&&control.tagName==='BUTTON')label='Store currency: '+text;
  if(label)control.setAttribute('aria-label',label);
 });
});
