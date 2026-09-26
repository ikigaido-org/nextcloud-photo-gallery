/* SPDX-License-Identifier: AGPL-3.0-only */
// Optional browser poster extraction when Nextcloud has no video preview.
// One decoder at a time; no server binary, permanent copies or uploads.
(() => {
    'use strict';
    const cache=new Map();let queue=Promise.resolve();
    function extract(url){return new Promise(resolve=>{
        const video=document.createElement('video');video.muted=true;video.playsInline=true;video.preload='auto';
        let done=false,timer;
        const finish=value=>{if(done)return;done=true;clearTimeout(timer);video.pause();video.removeAttribute('src');video.load();resolve(value);};
        const capture=()=>{if(video.readyState<2||!video.videoWidth)return;try{const canvas=document.createElement('canvas');const ratio=Math.min(1,640/video.videoWidth,640/video.videoHeight);canvas.width=Math.max(1,Math.round(video.videoWidth*ratio));canvas.height=Math.max(1,Math.round(video.videoHeight*ratio));canvas.getContext('2d').drawImage(video,0,0,canvas.width,canvas.height);finish(canvas.toDataURL('image/jpeg',0.8));}catch{finish(null);}};
        video.addEventListener('loadeddata',capture);video.addEventListener('seeked',capture);video.addEventListener('error',()=>finish(null));
        video.addEventListener('loadedmetadata',()=>{if(Number.isFinite(video.duration)&&video.duration>0.2)video.currentTime=Math.min(0.15,video.duration/2);});
        timer=setTimeout(()=>finish(null),12000);video.src=url;video.load();
    });}
    window.photoGalleryVideoPoster=url=>{
        if(!cache.has(url)){const job=queue.then(()=>extract(url)).catch(()=>null);cache.set(url,job);queue=job.then(()=>undefined);}
        return cache.get(url);
    };
})();
