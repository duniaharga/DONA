document.addEventListener('DOMContentLoaded',()=>{
 const section=document.querySelector('.dona-reviews');if(!section)return;
 const form=section.querySelector('#review_form_wrapper'),write=section.querySelector('a[href="#review_form_wrapper"]');
 if(form&&write){form.hidden=true;write.setAttribute('aria-expanded','false');write.setAttribute('aria-controls','review_form_wrapper');write.addEventListener('click',event=>{event.preventDefault();form.hidden=false;write.setAttribute('aria-expanded','true');form.scrollIntoView({block:'start'});form.querySelector('textarea')?.focus({preventScroll:true});});}
 const reviewForm=section.querySelector('form#commentform'),ratingSelect=section.querySelector('select#rating');
 if(reviewForm&&ratingSelect){
  const notice=document.createElement('div');notice.className='dona-review-validation';notice.id='dona-review-rating-error';notice.setAttribute('role','alert');notice.hidden=true;
  const message=document.createElement('span');message.textContent='Please choose a star rating before submitting your review.';
  const choose=document.createElement('button');choose.type='button';choose.textContent='Choose rating';notice.append(message,choose);ratingSelect.closest('.comment-form-rating').append(notice);
  const focusStars=()=>section.querySelector('p.stars a')?.focus();choose.addEventListener('click',focusStars);
  ratingSelect.addEventListener('change',()=>{if(ratingSelect.value){notice.hidden=true;ratingSelect.removeAttribute('aria-invalid');}});
  const validateRating=event=>{
   if(event.type==='click'&&event.target.closest('p.stars a')){notice.hidden=true;ratingSelect.removeAttribute('aria-invalid');}
   if(event.type==='click'&&!event.target.closest('[type=submit],#submit'))return;
   if(!ratingSelect.value&&typeof wc_single_product_params!=='undefined'&&wc_single_product_params.review_rating_required==='yes'){
    event.preventDefault();event.stopImmediatePropagation();notice.hidden=false;ratingSelect.setAttribute('aria-invalid','true');ratingSelect.setAttribute('aria-describedby',notice.id);focusStars();
   }
  };
  reviewForm.addEventListener('submit',validateRating,true);reviewForm.addEventListener('click',validateRating,true);
 }
 const list=section.querySelector('ol.commentlist'),filter=section.querySelector('[data-dona-review-filter]'),sort=section.querySelector('[data-dona-review-sort]');
 if(!list){filter.disabled=true;sort.disabled=true;return;}
 const items=Array.from(list.children);
 const rating=item=>Number(Array.from(item.classList).find(name=>name.startsWith('dona-rating-'))?.split('-').pop()||0);
 const date=item=>Date.parse(item.querySelector('time')?.dateTime||'')||0;
 function update(){
  let shown=0;items.sort((a,b)=>sort.value==='highest'?rating(b)-rating(a)||date(b)-date(a):sort.value==='lowest'?rating(a)-rating(b)||date(b)-date(a):date(b)-date(a));
  items.forEach(item=>{item.hidden=filter.value!=='all'&&rating(item)!==Number(filter.value);if(!item.hidden)shown++;list.append(item);});
  section.querySelector('.dona-review-filter-status').hidden=shown>0;
 }
 filter.addEventListener('change',update);sort.addEventListener('change',update);
});
