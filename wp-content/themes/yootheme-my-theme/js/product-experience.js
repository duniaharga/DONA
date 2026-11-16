(() => {
    const modal = document.querySelector('#dona-360-modal');
    if (!modal) return;
    const photoStage = modal.querySelector('.dona-bag-photo-stage');
    const image = photoStage.querySelector('img');
    const pins = [...modal.querySelectorAll('.dona-bag-hotspot')];
    const panels = [...modal.querySelectorAll('.dona-bag-feature-panel')];
    const video = modal.querySelector('video');
    const status = modal.querySelector('.dona-360-status');
    let opener = null, activePin = null, positionSpinPins = null;
    document.addEventListener('click', event => {
        const trigger = event.target.closest('.dona-view-360');
        if (trigger) opener = trigger;
    });
    const positionPins = () => {
        if(positionSpinPins){positionSpinPins();return;}
        if(!image.hasAttribute('src') || !image.complete || !image.naturalWidth)return;
        const width=image.naturalWidth;
        const height=image.naturalHeight || Number(image.getAttribute('height'));
        if (!width || !height) return;
        const scale=Math.min(photoStage.clientWidth/width,photoStage.clientHeight/height);
        const w=width*scale,h=height*scale,x=(photoStage.clientWidth-w)/2,y=(photoStage.clientHeight-h)/2;
        pins.forEach(pin=>{pin.style.left=x+w*Number(pin.dataset.x)/100+'px';pin.style.top=y+h*Number(pin.dataset.y)/100+'px';});
        photoStage.dataset.ready='true';
    };
    image.addEventListener('load', positionPins);
    new ResizeObserver(positionPins).observe(photoStage);
    const closeFeatures = (restore=false) => {
        panels.forEach(panel=>{panel.hidden=true;});pins.forEach(pin=>pin.setAttribute('aria-expanded','false'));
        if(restore && activePin)activePin.focus({preventScroll:true});activePin=null;
    };
    pins.forEach(pin=>pin.addEventListener('click',()=>{
        const wasOpen=pin.getAttribute('aria-expanded')==='true';closeFeatures();if(wasOpen)return;
        activePin=pin;pin.setAttribute('aria-expanded','true');
        const panel=document.getElementById(pin.getAttribute('aria-controls'));panel.hidden=false;
        const detail=panel.querySelector('[data-dona-detail-src]');if(detail&&!detail.hasAttribute('src'))detail.src=detail.getAttribute('data-dona-detail-src');
        panel.focus();
    }));
    modal.querySelectorAll('.dona-bag-feature-close').forEach(button=>button.addEventListener('click',()=>closeFeatures(true)));
    modal.addEventListener('keydown',event=>{if(event.key==='Escape' && activePin){event.preventDefault();event.stopPropagation();closeFeatures(true);}});
    const form=document.querySelector('form.variations_form');
    const sizeSelect=form?.querySelector('select[name="attribute_size"],select[name="attribute_pa_size"]');
    const sizes=modal.querySelector('.dona-bag-sizes');
    const syncSizes=()=>{
        if(!sizeSelect)return;
        const group=sizes.querySelector('[role=group]');const focusedSize=group.querySelector('button:focus')?.dataset.sizeValue;group.replaceChildren();
        [...sizeSelect.options].filter(option=>option.value).forEach(option=>{
            const button=document.createElement('button');button.type='button';button.dataset.sizeValue=option.value;button.textContent=option.textContent.trim();
            button.setAttribute('aria-pressed',String(option.value===sizeSelect.value));button.disabled=option.disabled||sizeSelect.disabled;
            button.addEventListener('click',()=>{sizeSelect.value=option.value;if(window.jQuery)window.jQuery(sizeSelect).trigger('change');else sizeSelect.dispatchEvent(new Event('change',{bubbles:true}));syncSizes();});
            group.append(button);
        });
        sizes.hidden=!!modal.querySelector('.dona-spin-viewer')||!group.children.length;if(focusedSize)[...group.children].find(button=>button.dataset.sizeValue===focusedSize)?.focus({preventScroll:true});
        sizes.querySelector('.dona-bag-size-status').textContent=sizeSelect.value?'Selected size: '+sizeSelect.selectedOptions[0].textContent.trim():'';
    };
    if(sizeSelect){
        if(window.jQuery)window.jQuery(form).on('woocommerce_update_variation_values found_variation reset_data',syncSizes);
        if(window.jQuery)window.jQuery(sizeSelect).on('change',syncSizes);else sizeSelect.addEventListener('change',syncSizes);
        syncSizes();
    }
    const setMode=mode=>{
        closeFeatures();const rotation=mode==='rotation' && !!video;
        photoStage.hidden=rotation;
        if(video){modal.querySelector('.dona-360-stage').hidden=!rotation;modal.querySelector('.dona-360-controls').hidden=!rotation;if(!rotation)video.pause();}
        modal.querySelectorAll('[data-bag-mode]').forEach(button=>button.setAttribute('aria-pressed',String(button.dataset.bagMode===mode)));
        if(rotation && !video.hasAttribute('src')){status.textContent='Loading rotation…';video.src=video.getAttribute('data-dona-360-src');video.load();}
        if(!rotation){status.textContent='';positionPins();}
    };
    modal.querySelectorAll('[data-bag-mode]').forEach(button=>button.addEventListener('click',()=>setMode(button.dataset.bagMode)));
    modal.addEventListener('beforeshow',event=>{if(event.target!==modal)return;if(!image.hasAttribute('src'))image.src=image.getAttribute('data-dona-bag-src');setMode('photo');syncSizes();});
    modal.addEventListener('shown',event=>{if(event.target===modal)positionPins();});
    modal.addEventListener('hidden',event=>{if(event.target!==modal)return;if(video)video.pause();closeFeatures();if(opener?.isConnected)opener.focus({preventScroll:true});});
    image.addEventListener('error',()=>{status.textContent='Unable to load the bag image. Please try again later.';});
    const spin=modal.querySelector('.dona-spin-viewer');
    if(spin){
        const stage=spin.querySelector('.dona-spin-stage'),picture=spin.querySelector('.dona-spin-image'),range=spin.querySelector('input'),out=spin.querySelector('output');
        let frame=0,drag=null;
        const columns=(spin.dataset.columns||'').split(',').map(Number);
        const uneven=columns.length===5 && columns[0]===0 && columns.every((v,i)=>Number.isFinite(v)&&(!i||v>columns[i-1]));
        const fitFrame=()=>{
            if(!uneven)return;
            const col=frame%4,full=columns[4],width=columns[col+1]-columns[col],height=Number(spin.dataset.sheetHeight)/2||full/4;
            const scale=Math.min(stage.clientWidth,stage.clientHeight)/Math.max(...columns.slice(1).map((v,i)=>v-columns[i]),height);
            picture.style.width=width*scale+'px';picture.style.height=height*scale+'px';picture.style.aspectRatio='auto';
            picture.style.backgroundSize=(full/width*100)+'% 200%';
            picture.style.backgroundPosition=(columns[col]/(full-width)*100)+'% '+(frame<4?0:100)+'%';
        };
        pins.forEach(pin=>stage.append(pin));panels.forEach(panel=>stage.append(panel));
        positionSpinPins=()=>{
            const box=picture.getBoundingClientRect(),outer=stage.getBoundingClientRect();
            pins.forEach(pin=>{pin.style.left=box.left-outer.left+box.width*Number(pin.dataset.x)/100+'px';pin.style.top=box.top-outer.top+box.height*Number(pin.dataset.y)/100+'px';});
            stage.dataset.ready='true';
        };
        picture.addEventListener('transitionend',positionSpinPins);
        new ResizeObserver(()=>{fitFrame();positionSpinPins();}).observe(stage);
        const render=value=>{closeFeatures();frame=(Math.round(value)%8+8)%8;picture.style.clipPath=uneven?'none':([1,2,5,6].includes(frame)?'inset(0 4%)':'none');picture.style.backgroundPosition=((frame%4)/3*100)+'% '+(frame<4?0:100)+'%';range.value=String(frame);range.setAttribute('aria-valuetext',frame*45+' degrees');out.value=frame*45+'°';picture.setAttribute('aria-label','Bag view at '+frame*45+' degrees');fitFrame();positionSpinPins();};
        const load=()=>{picture.style.backgroundImage='url("'+spin.dataset.sheet+'")';photoStage.hidden=true;sizes.hidden=true;fitFrame();positionSpinPins();};
        modal.addEventListener('beforeshow',event=>{if(event.target===modal)load();});
        spin.querySelectorAll('[data-spin-step]').forEach(button=>button.addEventListener('click',()=>render(frame+Number(button.dataset.spinStep))));range.addEventListener('input',()=>render(Number(range.value)));
        stage.addEventListener('pointerdown',event=>{if(event.button!==0||event.target.closest('button,.dona-bag-feature-panel'))return;drag={id:event.pointerId,x:event.clientX,frame};stage.setPointerCapture(event.pointerId);});
        stage.addEventListener('pointermove',event=>{
            if(activePin || event.target.closest('button,.dona-bag-feature-panel'))return;
            if(drag?.id===event.pointerId){render(drag.frame+(event.clientX-drag.x)/35);return;}
            if(event.pointerType==='mouse' && !event.buttons){const box=stage.getBoundingClientRect();render(Math.min(7,Math.floor((event.clientX-box.left)/box.width*8)));}
        });
        ['pointerup','pointercancel','lostpointercapture'].forEach(name=>stage.addEventListener(name,()=>{drag=null;}));
        spin.querySelectorAll('[data-spin-scale]').forEach(button=>button.addEventListener('click',()=>{picture.style.transform='scale('+button.dataset.spinScale+')';requestAnimationFrame(positionSpinPins);spin.querySelectorAll('[data-spin-scale]').forEach(other=>other.setAttribute('aria-pressed',String(other===button)));}));
    }
    if(!video)return;
    const slider=modal.querySelector('input[type=range]'),output=modal.querySelector('output');
    const steps=[...modal.querySelectorAll('[data-dona-360-step]')];
    const ready=()=>Number.isFinite(video.duration)&&video.duration>0;
    const showAngle=angle=>{slider.value=String(angle);output.value=angle+'°';slider.setAttribute('aria-valuetext',angle+' degrees');};
    const rotate=angle=>{if(!ready())return;const value=((Math.round(angle)%360)+360)%360;video.pause();video.currentTime=Math.min(video.duration-.01,value/360*video.duration);showAngle(value);};
    steps.forEach(button=>{button.disabled=true;button.addEventListener('click',()=>rotate(Number(slider.value)+Number(button.getAttribute('data-dona-360-step'))));});
    video.addEventListener('loadedmetadata',()=>{slider.disabled=!ready();steps.forEach(button=>{button.disabled=!ready();});status.textContent=ready()?'':'Use the video controls to inspect this clip.';});
    video.addEventListener('error',()=>{status.textContent='Unable to load rotation video.';slider.disabled=true;steps.forEach(button=>{button.disabled=true;});});
    slider.addEventListener('input',()=>rotate(Number(slider.value)));
    video.addEventListener('timeupdate',()=>{if(ready())showAngle(Math.min(359,Math.round(video.currentTime/video.duration*360)));});
    let pointer=null;
    video.addEventListener('pointerdown',event=>{if(!ready()||event.button!==0||event.clientY>video.getBoundingClientRect().bottom-48)return;pointer={id:event.pointerId,x:event.clientX,y:event.clientY,angle:Number(slider.value),dragging:false};});
    video.addEventListener('pointermove',event=>{if(!pointer||pointer.id!==event.pointerId)return;const dx=event.clientX-pointer.x,dy=event.clientY-pointer.y;if(!pointer.dragging){if(Math.abs(dy)>Math.abs(dx)&&Math.abs(dy)>8){pointer=null;return;}if(Math.abs(dx)<8)return;pointer.dragging=true;video.setPointerCapture(event.pointerId);video.classList.add('is-dragging');}rotate(pointer.angle+dx/Math.max(1,video.clientWidth)*360);});
    const endDrag=()=>{pointer=null;video.classList.remove('is-dragging');};video.addEventListener('pointerup',endDrag);video.addEventListener('pointercancel',endDrag);
})();
