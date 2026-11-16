jQuery(function ($) {
    const drawer=document.querySelector('#dona-account-drawer');
    const modal=document.querySelector('#dona-account-modal');
    if(!drawer||!modal||typeof UIkit==='undefined')return;
    const content=drawer.querySelector('.dona-account-content');
    const status=drawer.querySelector('.dona-account-status');
    const authStatus=modal.querySelector('.dona-account-auth-status');
    let tab='for-you',request,requestNumber=0;
    // Private, page-memory cache only: never store account HTML in localStorage.
    const cache=new Map([
        ['for-you',{html:content.innerHTML,time:Date.now()}],
        ['profile',{html:drawer.querySelector('#dona-account-dashboard-template').innerHTML,time:Date.now()}],
        ['addresses',{html:drawer.querySelector('#dona-account-addresses-template').innerHTML,time:Date.now()}],
        ['orders',{html:drawer.querySelector('#dona-account-orders-template').innerHTML,time:Date.now()}]
    ]);
    const productCache=new Map();
    function updateSwitch(){drawer.querySelectorAll('.dona-account-switcher button').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.accountPanel===tab)));}
    function render(html){content.innerHTML=html;content.removeAttribute('aria-busy');status.textContent='';drawer.querySelector('.dona-account-scroll').scrollTop=0;loadProducts();}
    function resetCache(panels){cache.clear();productCache.clear();for(const [key,html] of Object.entries(panels||{}))cache.set(key,{html,time:Date.now()});}

    function readIds(key){try{const ids=JSON.parse(localStorage.getItem(key)||'[]');return Array.isArray(ids)?[...new Set(ids.map(Number).filter(x=>Number.isInteger(x)&&x>0))].slice(0,100):[];}catch{return [];}}
    const currentProductId=Number(donaAccount.productId);
    if(Number.isInteger(currentProductId)&&currentProductId>0){try{localStorage.setItem('dona-recently-viewed-v1',JSON.stringify([currentProductId,...readIds('dona-recently-viewed-v1').filter(x=>x!==currentProductId)].slice(0,12)));}catch{}}
    function loadProducts(){
        content.querySelectorAll('[data-account-products]').forEach(list=>{
            const ids=readIds('dona-recently-viewed-v1');
            list.replaceChildren();
            if(!ids.length){const p=document.createElement('p');p.className='dona-account-empty';p.textContent='Your recently viewed bags will appear here.';list.append(p);return;}
            const productKey=ids.join(',');if(productCache.has(productKey)){list.innerHTML=productCache.get(productKey);return;}
            list.textContent='Loading…';
            window.donaProductCards(ids).done(response=>{
                if(!list.isConnected)return;
                list.replaceChildren();
                if(!response.success||!response.data.length){list.textContent='No available products to show.';return;}
                response.data.forEach(item=>{
                    const card=document.createElement('a');card.className='dona-account-product';card.href=item.url;
                    const image=document.createElement('img');image.src=item.image;image.alt='';image.width=78;image.height=88;image.loading='eager';
                    const text=document.createElement('span');const title=document.createElement('span');title.textContent=item.name;
                    const price=document.createElement('span');price.className='dona-account-product-price';price.innerHTML=item.price;
                    text.append(title,price);card.append(image,text);list.append(card);
                });
                productCache.set(productKey,list.innerHTML);
            }).fail(()=>{if(list.isConnected)list.textContent='Products could not load. Please reopen your account to try again.';});
        });
    }
    function panel(next=tab,orderId=0,refresh=false){
        tab=next;updateSwitch();const number=++requestNumber;
        if(request)request.abort();
        const key=orderId?'order:'+orderId:tab;const saved=cache.get(key);
        if(saved){render(saved.html);if(!refresh&&(tab!=='orders'||Date.now()-saved.time<60000))return;}
        else {
            const heading=document.createElement('h2');heading.id='dona-account-drawer-title';
            heading.textContent=tab==='orders'?'Your orders':tab==='addresses'?'Your addresses':'Account details';
            content.replaceChildren(heading);
        }
        status.textContent=saved?'Updating…':'Loading…';content.setAttribute('aria-busy','true');
        request=$.post(donaAccount.url,{action:'dona_account',nonce:donaAccount.nonce,operation:'panel',tab,order_id:orderId})
            .done(r=>{if(number!==requestNumber)return;if(!r.success){status.textContent=r.data?.message||'Unable to load your account.';return;}cache.set(key,{html:r.data.html,time:Date.now()});render(r.data.html);})
            .fail((xhr,error)=>{if(number===requestNumber&&error!=='abort')status.textContent=xhr.responseJSON?.data?.message||'Unable to load your account. Please try again.';})
            .always(()=>{if(number===requestNumber)content.removeAttribute('aria-busy');});
    }
    function nativeAccount(url,form=null){
        const destination=new URL(url,location.href),base=new URL(donaAccount.accountUrl);
        if(destination.origin!==base.origin||!destination.pathname.startsWith(base.pathname)){status.textContent='This account action is unavailable here.';return;}
        const number=++requestNumber;if(request)request.abort();
        tab=destination.pathname.includes('/edit-address')?'addresses':'profile';updateSwitch();
        const saved=cache.get('native:'+url);
        if(!form&&saved&&Date.now()-saved.time<300000){render(saved.html);$(content).find('.country_to_state').trigger('change');return;}
        status.textContent=form?'Saving…':'Loading account…';content.setAttribute('aria-busy','true');
        request=$.ajax({url,method:form?'POST':'GET',data:form?new FormData(form):undefined,processData:!form,contentType:form?false:undefined,dataType:'html'})
            .done(html=>{
                if(number!==requestNumber)return;
                const doc=new DOMParser().parseFromString(html,'text/html');
                const freshNonce=doc.querySelector('#dona-account-drawer')?.dataset.accountNonce;
                if(freshNonce)donaAccount.nonce=freshNonce;
                const body=doc.querySelector('.woocommerce-MyAccount-content');

                if(!body){
                    const guest=doc.querySelector('#dona-account-drawer .dona-account-content');
                    resetCache(guest?{'for-you':guest.innerHTML}:{});
                    if(guest)panel('for-you');
                    status.textContent='Please sign in again to continue.';return;
                }
                const wrap=document.createElement('div');wrap.className='woocommerce dona-native-account';wrap.dataset.accountUrl=url;

                const notices=doc.querySelector('#tm-main .woocommerce-notices-wrapper');if(notices)wrap.append(notices);
                wrap.append(body);
                const output='<h2 id="dona-account-drawer-title">'+(tab==='addresses'?'Your addresses':'Account details')+'</h2>'+wrap.outerHTML;
                if(form){cache.clear();productCache.clear();}else cache.set('native:'+url,{html:output,time:Date.now()});
                render(output);$(content).find('.country_to_state').trigger('change');
            })
            .fail(()=>{if(number===requestNumber)status.textContent='Unable to load your account. Please try again.';})
            .always(()=>{if(number===requestNumber)content.removeAttribute('aria-busy');});
    }
    $(document).on('click.donaAccountDrawer','a[href]',function(e){
            const target=new URL(this.href,location.href),base=new URL(donaAccount.accountUrl);
            const route=target.pathname.replace(/\/$/,'');const root=base.pathname.replace(/\/$/,'');
            if(this.closest('.dona-native-account')&&target.origin===base.origin&&(route.includes('customer-logout')||target.searchParams.get('action')==='logout')){e.preventDefault();logout();return;}
            if(this.closest('.dona-native-account')&&target.origin===base.origin&&route.startsWith(root)){
                e.preventDefault();
                if(route.includes('customer-logout')){logout();return;}
                if(route===root){panel('for-you');return;}
                nativeAccount(target.href);return;
            }
            if(!this.classList.contains('dona-account') && !(target.origin===base.origin && [root,root+'/orders',root+'/edit-account',root+'/edit-address'].includes(route)))return;
            e.preventDefault();tab=route===root+'/orders'?'orders':route===root+'/edit-account'?'profile':route===root+'/edit-address'?'addresses':'for-you';UIkit.offcanvas(drawer).show();
        })
        .on('click.donaAccountDrawer','#dona-account-drawer [data-account-panel]',function(){panel(this.dataset.accountPanel);})
        .on('click.donaAccountDrawer','[data-account-refresh]',function(){panel('orders',0,true);})
        .on('submit.donaAccountDrawer','.dona-native-account form',function(e){e.preventDefault();nativeAccount(this.getAttribute('action')||this.closest('.dona-native-account').dataset.accountUrl||donaAccount.accountUrl,this);})
        .on('click.donaAccountDrawer','[data-account-order]',function(){panel('orders',this.dataset.accountOrder);})
        .on('click.donaAccountDrawer','[data-account-signin]',function(){UIkit.offcanvas(drawer).hide();authStatus.textContent='';UIkit.modal(modal).show();})
        .on('click.donaAccountDrawer','[data-account-tab]',function(){
            const view=this.dataset.accountTab;modal.querySelector('.dona-account-forms').dataset.accountView=view;
            modal.querySelectorAll('[data-account-tab]').forEach(b=>b.setAttribute('aria-pressed',String(b===this)));
            modal.querySelector('#dona-account-title').textContent=view==='register'?'Create your DONA account':'Sign in to DONA';authStatus.textContent='';
        })
        .on('click.donaAccountDrawer','#dona-account-modal .lost_password a',function(e){
            e.preventDefault();modal.querySelector('.dona-account-reset').hidden=false;modal.querySelector('.dona-account-forms').hidden=true;modal.querySelector('.dona-account-tabs').hidden=true;modal.querySelector('#dona-account-title').textContent='Reset your password';authStatus.textContent='';
        })
        .on('click.donaAccountDrawer','[data-account-return-login]',function(){
            modal.querySelector('.dona-account-reset').hidden=true;modal.querySelector('.dona-account-forms').hidden=false;modal.querySelector('.dona-account-tabs').hidden=false;modal.querySelector('#dona-account-title').textContent='Sign in to DONA';authStatus.textContent='';
        })
        .on('submit.donaAccountDrawer','#dona-account-modal form',function(e){
            e.preventDefault();const form=this;const submit=form.querySelector('[type=submit]');
            const operation=form.classList.contains('dona-account-reset')?'reset':form.classList.contains('register')?'register':'login';
            const values=Object.fromEntries($(form).serializeArray().map(x=>[x.name,x.value]));
            // WC's page-level submit keys must not trigger its redirecting form handler.
            delete values.login;delete values.register;
            submit.disabled=true;authStatus.textContent=operation==='reset'?'Sending…':'Please wait…';
            $.post(donaAccount.url,{...values,action:'dona_account',operation,nonce:donaAccount.nonce})
                .done(r=>{
                    form.querySelectorAll('input[type=password]').forEach(input=>input.value='');
                    if(!r.success){authStatus.textContent=r.data?.message||'Please check your details and try again.';return;}
                    if(r.data.nonce)donaAccount.nonce=r.data.nonce;
                    if(operation==='reset'||r.data.requiresLogin){authStatus.textContent=r.data.message;return;}
                    drawer.querySelector('[data-account-logout]').hidden=!r.data.authenticated;resetCache(r.data.panels);UIkit.modal(modal).hide();UIkit.offcanvas(drawer).show();$(document.body).trigger('wc_fragment_refresh');
                }).fail(xhr=>authStatus.textContent=xhr.responseJSON?.data?.message||'Unable to connect. Please try again.')
                .always(()=>submit.disabled=false);
        })
        .on('click.donaAccountDrawer','[data-account-logout]',logout);
    function logout(){
        $.post(donaAccount.url,{action:'dona_account',operation:'logout',nonce:donaAccount.nonce})
            .done(r=>{if(!r.success){status.textContent=r.data?.message||'Unable to sign out.';return;}donaAccount.nonce=r.data.nonce;drawer.querySelector('[data-account-logout]').hidden=!r.data.authenticated;resetCache(r.data.panels);panel('for-you');$(document.body).trigger('wc_fragment_refresh');})
            .fail(()=>status.textContent='Unable to sign out. Please try again.');
    }
    UIkit.util.on(drawer,'beforeshow',()=>panel(tab));
});
