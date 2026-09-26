/*! SPDX-FileCopyrightText: 2026 Stephan Rickauer */
/* SPDX-License-Identifier: AGPL-3.0-only */
import { loadState } from '@nextcloud/initial-state';
(() => {
    if(window.__pgAccountSwitcher)return;
    window.__pgAccountSwitcher=true;
    function init(){
        // Public gallery pages never host account controls, with or without a header.
        const header=document.querySelector('#header .header-end');
        if(!header || !document.head.dataset.user || document.querySelector('.mpi-gallery'))return;
        let state;
        try { state=loadState('photo_gallery','account-switcher',null); }
        catch { return; }
        if(!state)return;
        const changeKey='photo_gallery.account.changed';
        let busy=false;
        // The core renders this native menu entry lazily. Delegation also works
        // after closing and reopening the profile menu, without DOM observers.
        document.addEventListener('click',async event=>{
            const entry=event.target instanceof Element ? event.target.closest('#photo-gallery-account-switch') : null;
            if(!entry)return;
            event.preventDefault(); // Core AccountMenuEntry must not show a navigation spinner.
            if(busy)return;
            busy=true;
            const action=entry.matches('button,a')?entry:entry.querySelector('button,a')||entry;
            action.disabled=true;action.setAttribute('aria-disabled','true');entry.setAttribute('aria-busy','true');
            document.getElementById('pg-switch-error')?.remove();

            try{
                const response=await fetch(state.active?state.finishUrl:state.startUrl,{
                    method:'POST',credentials:'same-origin',cache:'no-store',
                    headers:{'Accept':'application/json','requesttoken':state.token,'Content-Type':'application/x-www-form-urlencoded'},
                    body:new URLSearchParams({expectedUser:state.actor,expectedTarget:state.target}),
                });
                const result=await response.json().catch(()=>({}));
                if(!response.ok)throw new Error(result.message||'Kontowechsel fehlgeschlagen. Bitte die Seite neu laden.');
                const destination=new URL(result.redirect,location.href);
                if(destination.origin!==location.origin)throw new Error('Ungültiges Weiterleitungsziel. Bitte die Seite neu laden.');
                try{localStorage.setItem(changeKey,Date.now()+':'+Math.random());}catch{}
                location.assign(destination.href);
             }catch(error){
                const message=document.createElement('li');message.id='pg-switch-error';message.className='pg-switch-message';
                message.setAttribute('role','status');message.textContent=error.message;
                (entry.closest('li')||entry).after(message);
                action.disabled=false;action.removeAttribute('aria-disabled');busy=false;entry.removeAttribute('aria-busy');
            }
        },true);
        // Other open tabs share this session. Reload only after a switch, not on a timer.
        window.addEventListener('storage',event=>{if(event.key===changeKey)location.reload();});
        // Also correct stale pages restored from the browser's back/forward cache.
        window.addEventListener('pageshow',event=>{if(event.persisted)location.reload();});
    }
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});else init();
})();
