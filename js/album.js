/* SPDX-License-Identifier: AGPL-3.0-only */
(() => {
    'use strict';
    const root=document.getElementById('pg-album');
    if (!root || root.dataset.available!=='1') return;
    const grid=document.getElementById('pg-grid'), status=document.getElementById('pg-status'), more=document.getElementById('pg-more');
    let hasMore=true, loadFailed=false;
    const sentinel=document.getElementById("pg-sentinel");
    let observer=null;
    let items=[], cursor=null, busy=false, viewer=null, infoOpen=false, active=0, panel=null, infoButton=null, abort=null, openingButton=null, popping=false;
    const placeholder=root.dataset.placeholder;
    const reducedMotion=matchMedia('(prefers-reduced-motion: reduce)').matches;
    const metadataCache=new Map();let panelTimer=null, layoutFrame=null;
    function sizeInfoPanel(){
        if(!infoOpen||!viewer)return;
        const outer=viewer.outer.get(),toolbar=outer.querySelector('.lg-toolbar');
        outer.style.setProperty('--pg-info-top',Math.max(56,Math.ceil(toolbar.getBoundingClientRect().height))+'px');
    }
    function layout(){
        layoutFrame=null;
        const gap=parseFloat(getComputedStyle(grid).rowGap)||6;
        grid.querySelectorAll('.pg-tile').forEach(tile=>{const ratio=Number(tile.dataset.ratio)||1;const h=tile.getBoundingClientRect().width/ratio;tile.style.gridRowEnd='span '+Math.max(1,Math.ceil((h+gap)/(1+gap)));});
    }
    function scheduleLayout(){if(layoutFrame===null)layoutFrame=requestAnimationFrame(layout);}
    const revealObserver='IntersectionObserver' in window?new IntersectionObserver(entries=>{
        entries.forEach(({target,isIntersecting})=>{if(isIntersecting){target.dataset.inView='1';if(target.dataset.ready==='1'){target.classList.add('is-visible');revealObserver.unobserve(target);}}});
    },{rootMargin:'0px 0px -12px 0px'}):null;
    function ready(tile,image){
        if(image.naturalWidth>0){const ratio=image.naturalWidth/image.naturalHeight;tile.dataset.ratio=String(ratio);tile.style.aspectRatio=String(ratio);}
        scheduleLayout();tile.dataset.ready='1';
        if(!revealObserver || tile.dataset.inView==='1')requestAnimationFrame(()=>tile.classList.add('is-visible'));
    }
    if('ResizeObserver' in window){let oldWidth=0;new ResizeObserver(entries=>{const width=entries[0].contentRect.width;if(width!==oldWidth){oldWidth=width;scheduleLayout();}}).observe(grid);}
    else window.addEventListener('resize',scheduleLayout);

    const strings={closeGallery:'Ansicht schliessen',toggleMaximize:'Vollbild umschalten',previousSlide:'Vorheriges Medium',nextSlide:'Nächstes Medium',download:'Herunterladen',playVideo:'Video abspielen',mediaLoadingFailed:'Medium konnte nicht geladen werden.'};
    function slides(){return items.map((item,i)=>item.kind==='video'?{
        video:{source:[{src:item.stream,type:item.mime}],attributes:{controls:true,preload:'metadata',playsinline:true}},
        poster:item._poster||placeholder,thumb:item._poster||placeholder,alt:`Video ${i+1}`
    }:{src:item.large,thumb:item._poster||item.thumbnail,alt:`Foto ${i+1}`});}
    async function load(){
        if(busy||!hasMore)return;busy=true;loadFailed=false;more.hidden=true;more.disabled=true;grid.setAttribute('aria-busy','true');if(!items.length)status.textContent='Fotos und Videos werden geladen …';
        try {
            const url=new URL(root.dataset.api,location.href);if(cursor!==null)url.searchParams.set('after',cursor);
            const response=await fetch(url,{credentials:'same-origin',cache:'no-store'});if(!response.ok)throw new Error('Album nicht verfügbar.');
            const data=await response.json();
            data.images.forEach(item=>{
                const index=items.length;items.push(item);
                const button=document.createElement('button');button.type='button';button.className='pg-tile';button.setAttribute('aria-label',`${item.kind==='video'?'Video':'Foto'} ${index+1} öffnen`);
                const image=document.createElement('img');image.src=item.thumbnail;image.alt='';image.loading='lazy';image.decoding='async';image.width=640;image.height=640;
                let failed=false;
                image.addEventListener('load',async()=>{if(!failed)item._poster=item.thumbnail;try{await image.decode();}catch{}ready(button,image);});
                image.addEventListener('error',async()=>{
                    if(failed)return;failed=true;item._poster=placeholder;image.src=placeholder;
                    if(item.kind==='video'&&window.photoGalleryVideoPoster){
                        const poster=await window.photoGalleryVideoPoster(item.stream);
                        if(poster){image.src=poster;item._poster=poster;if(viewer&&!viewer.lgOpened)viewer.refresh(slides());}
                    }
                });button.append(image);
                if(item.kind==='video'){const badge=document.createElement('span');badge.className='pg-video-badge';badge.textContent='▶ Video';button.append(badge);}
                button.addEventListener('click',()=>{openingButton=button;viewer.refresh(slides());viewer.openGallery(index);});grid.append(button);if(revealObserver)revealObserver.observe(button);
            });
            scheduleLayout();
            if(data.hasMore && (data.nextCursor===null || data.nextCursor===cursor))throw new Error('Album konnte nicht weiter geladen werden.');
            cursor=data.nextCursor;hasMore=data.hasMore;
            status.textContent=items.length?'':'Dieses Album enthält noch keine Fotos oder Videos.';
            if(viewer)viewer.refresh(slides());else setupViewer();
        } catch(error){loadFailed=true;status.textContent=error.message||'Album konnte nicht geladen werden.';more.hidden=false;more.textContent='Erneut versuchen';}
        finally{busy=false;more.disabled=false;grid.setAttribute('aria-busy','false');if(hasMore&&!loadFailed){requestAnimationFrame(maybeLoad);}}
    }
    function setInfo(value){
        infoOpen=value;if(!panel)return;clearTimeout(panelTimer);
        const outer=viewer.outer.get();
        sizeInfoPanel();
        infoButton.setAttribute('aria-expanded',String(value));panel.setAttribute('aria-hidden',String(!value));panel.inert=!value;
        if(value){panel.hidden=false;panel.getBoundingClientRect();requestAnimationFrame(()=>{if(infoOpen){panel.classList.add('is-open');outer.classList.add('pg-info-open');}});panel.querySelector('button').focus({preventScroll:true});readMetadata();}
        else{panel.classList.remove('is-open');outer.classList.remove('pg-info-open');if(abort)abort.abort();infoButton.focus();panelTimer=setTimeout(()=>{if(!infoOpen)panel.hidden=true;},reducedMotion?0:210);}
    }
    function showMetadata(body,data){
        body.replaceChildren();body.removeAttribute('aria-busy');
        if(!data.fields.length){body.textContent='Keine freigegebenen Metadaten verfügbar.';return;}
        const list=document.createElement('dl');data.fields.forEach(field=>{const term=document.createElement('dt'),value=document.createElement('dd');term.textContent=field.label;value.textContent=field.value;list.append(term,value);});body.append(list);
    }
    async function readMetadata(){
        if(!infoOpen||!panel)return;if(abort)abort.abort();abort=new AbortController();const signal=abort.signal;
        const body=panel.querySelector('.pg-info-body'),url=items[active].metadata;
        if(metadataCache.has(url)){showMetadata(body,metadataCache.get(url));return;}
        body.textContent='Informationen werden geladen …';body.setAttribute('aria-busy','true');
        try{
            const response=await fetch(url,{signal,cache:'no-store',credentials:'same-origin'});
            if(!response.ok)throw new Error('Informationen nicht verfügbar.');const data=await response.json();
            if(signal.aborted || !infoOpen || items[active].metadata!==url)return;
            metadataCache.set(url,data);showMetadata(body,data);
        }catch(error){if(error.name!=='AbortError'){body.removeAttribute('aria-busy');body.textContent=error.message;}}
    }
    function setupViewer(){
        root.addEventListener('lgAfterOpen',()=>{
            const outer=viewer.outer.get();
            if(!panel){
                infoButton=document.createElement('button');infoButton.type='button';infoButton.className='pg-info-toggle';const icon=document.createElementNS('http://www.w3.org/2000/svg','svg');icon.setAttribute('viewBox','0 0 24 24');icon.setAttribute('width','24');icon.setAttribute('height','24');icon.setAttribute('aria-hidden','true');const ring=document.createElementNS(icon.namespaceURI,'circle');ring.setAttribute('cx','12');ring.setAttribute('cy','12');ring.setAttribute('r','9');ring.setAttribute('fill','none');ring.setAttribute('stroke','currentColor');ring.setAttribute('stroke-width','2');const mark=document.createElementNS(icon.namespaceURI,'path');mark.setAttribute('d','M12 10v7M12 6v2');mark.setAttribute('stroke','currentColor');mark.setAttribute('stroke-width','2');icon.append(ring,mark);infoButton.append(icon);infoButton.title='Bildinformationen';infoButton.setAttribute('aria-label','Bildinformationen');infoButton.setAttribute('aria-controls','pg-info-panel');infoButton.setAttribute('aria-expanded','false');infoButton.addEventListener('click',()=>setInfo(!infoOpen));outer.querySelector('.lg-toolbar').append(infoButton);
                panel=document.createElement('aside');panel.id='pg-info-panel';panel.className='pg-info-panel';panel.hidden=true;panel.inert=true;panel.setAttribute('aria-hidden','true');panel.setAttribute('aria-labelledby','pg-info-title');
                const h=document.createElement('h2');h.id='pg-info-title';h.textContent='Informationen';
                const close=document.createElement('button');close.type='button';close.className='pg-info-close';close.textContent='×';close.setAttribute('aria-label','Informationen schliessen');close.addEventListener('click',()=>setInfo(false));
                const body=document.createElement('div');body.className='pg-info-body';body.setAttribute('aria-live','polite');panel.append(h,close,body);outer.append(panel);
                if('ResizeObserver' in window)new ResizeObserver(sizeInfoPanel).observe(outer.querySelector('.lg-toolbar'));
                else window.addEventListener('resize',sizeInfoPanel);
                outer.addEventListener('keydown',event=>{if(event.key==='Escape'&&infoOpen){event.preventDefault();event.stopImmediatePropagation();setInfo(false);}},true);
                outer.addEventListener('error',event=>{
                    const target=event.target;
                    if(target instanceof HTMLImageElement && !target.dataset.pgFallback){target.dataset.pgFallback='1';target.src=placeholder;}
                    if(target instanceof HTMLVideoElement){
                        const parent=target.parentElement;if(parent.querySelector('.pg-video-error'))return;
                        const note=document.createElement('div');note.className='pg-video-error';note.setAttribute('role','status');
                        const text=document.createElement('p');text.textContent='Dieses Video konnte hier nicht abgespielt werden. Du kannst es direkt im Browser öffnen.';
                        const link=document.createElement('a');link.href=items[active].stream;link.target='_blank';link.rel='noopener noreferrer';link.textContent='Video direkt öffnen';note.append(text,link);parent.append(note);
                    }
                },true);
            }
            if(infoOpen)setInfo(true);
        });
        root.addEventListener('lgBeforeOpen',()=>{history.pushState({...history.state,pgViewer:true},'',location.href);});
        root.addEventListener('lgAfterSlide',event=>{active=event.detail.index;if(infoOpen)readMetadata();});
        root.addEventListener('lgBeforeClose',()=>{if(abort)abort.abort();if(!popping&&history.state?.pgViewer)history.back();popping=false;});
        root.addEventListener('lgAfterClose',()=>{openingButton?.focus();});
        viewer=lightGallery(root,{dynamic:true,dynamicEl:slides(),plugins:[lgThumbnail,lgZoom,lgVideo],
            licenseKey:'0000-0000-000-0000',thumbnail:true,animateThumb:true,showThumbByDefault:true,
            thumbWidth:76,thumbHeight:'58px',thumbMargin:8,toggleThumb:false,enableThumbDrag:true,enableThumbSwipe:true,
            download:false,counter:true,zoom:true,actualSize:true,showZoomInOutIcons:true,
            autoplayFirstVideo:false,autoplayVideoOnSlide:false,videojs:false,
            enableSwipe:true,enableDrag:true,swipeToClose:true,hideBarsDelay:0,closable:true,
            mobileSettings:{controls:true,showCloseIcon:true,download:false},strings,
            speed:matchMedia('(prefers-reduced-motion: reduce)').matches?0:300});
        // The poster click is a user gesture: start the native player synchronously,
        // rather than waiting for metadata (which loses activation on some browsers).
        root.addEventListener('lgPosterClick',()=>{const video=viewer.outer.get().querySelector('.lg-current video');if(video){video.play().catch(()=>{/* Native controls allow another user-initiated attempt. */});}});
        window.addEventListener('popstate',()=>{if(viewer.lgOpened&&!history.state?.pgViewer){popping=true;viewer.closeGallery();}});
    }
    function maybeLoad(){
        if(!hasMore||busy||loadFailed||viewer?.lgOpened)return;
        const box=sentinel.getBoundingClientRect();
        if(box.top<innerHeight+400 && box.bottom>=0)load();
    }
    if('IntersectionObserver' in window){observer=new IntersectionObserver(maybeLoad,{rootMargin:'400px'});observer.observe(sentinel);}
    else window.addEventListener('scroll',maybeLoad,{passive:true});
    window.addEventListener('resize',maybeLoad);
    root.addEventListener('lgAfterClose',()=>requestAnimationFrame(maybeLoad));
    more.addEventListener('click',load);load();
})();
