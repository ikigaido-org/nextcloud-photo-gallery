/*! SPDX-FileCopyrightText: 2026 Stephan Rickauer */
/* SPDX-License-Identifier: AGPL-3.0-only */
(() => {
    'use strict';
    function init() {
        document.querySelectorAll('.mpi-image img').forEach((image) => {
            let handled=false;
            const reveal=async()=>{try{await image.decode();}catch{}if(image.naturalWidth>0){image.hidden=false;requestAnimationFrame(()=>requestAnimationFrame(()=>image.classList.add('is-ready')));}};
            image.addEventListener('load',reveal);
            if(image.complete&&image.naturalWidth>0)reveal();
            const failed=async()=>{
                if(handled)return;handled=true;image.classList.remove('is-ready');image.hidden=true;
                if(image.dataset.videoStream && window.photoGalleryVideoPoster){
                    const poster=await window.photoGalleryVideoPoster(image.dataset.videoStream);
                    if(poster){image.src=poster;}
                }
            };
            image.addEventListener('error',failed);
            if(image.complete&&image.naturalWidth===0)failed();
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
// Restore the selected overview's scroll position after returning from an album.
(() => {
    const key='photo-gallery-scroll:'+location.pathname+location.search;
    window.addEventListener('pagehide',()=>{try{sessionStorage.setItem(key,String(scrollY));}catch{}});
    window.addEventListener('pageshow',()=>{try{const y=Number(sessionStorage.getItem(key));if(y>0)requestAnimationFrame(()=>scrollTo(0,y));}catch{}});
})();
