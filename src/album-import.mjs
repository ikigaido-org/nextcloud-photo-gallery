/* SPDX-License-Identifier: AGPL-3.0-only */
const DAV='DAV:', OC='http://owncloud.org/ns';
export const xmlEscape=(s)=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&apos;'}[c]));
const value=(el,ns,name)=>el.getElementsByTagNameNS(ns,name)[0]?.textContent||'';
export function parseDav(xml,base){
    const doc=new DOMParser().parseFromString(xml,'application/xml');
    if(doc.getElementsByTagName('parsererror').length || doc.documentElement.localName!=='multistatus')throw Error('Ungültige WebDAV-Antwort.');
    return [...doc.getElementsByTagNameNS(DAV,'response')].map(r=>{
        const url=new URL(value(r,DAV,'href'),base);
        const p=[...r.getElementsByTagNameNS(DAV,'propstat')].filter(p=>/\s200\s/.test(value(p,DAV,'status')));
        const prop=(ns,name)=>p.map(p=>value(p,ns,name)).find(Boolean)||'';
        const folder=p.some(p=>p.getElementsByTagNameNS(DAV,'collection').length>0);
        return {url:url.href,name:decodeURIComponent(url.pathname.replace(/\/$/,'').split('/').pop()),folder,
            id:prop(OC,'fileid'),owner:prop(OC,'owner-id'),mime:prop(DAV,'getcontenttype').split(';')[0],ok:p.length>0};
    });
}
export function safeUrl(url,root){
    const u=new URL(url),r=new URL(root);const path=r.pathname.replace(/\/$/,'');
    if(u.origin!==r.origin || u.username || u.password || u.search || u.hash || (u.pathname!==path && u.pathname!==path+'/' && !u.pathname.startsWith(path+'/')))throw Error('Datei liegt außerhalb des ausgewählten Ordners.');
    return u.href;
}
export function validateName(name){
    name=name.trim();if(!name || name.length>200 || /[\\/\x00-\x1f\x7f]/.test(name) || name==='.' || name==='..')throw Error('Bitte einen Albumnamen ohne Schrägstriche angeben (maximal 200 Zeichen).');return name;
}
export class AlbumImport {
    constructor({davRoot,uid,token,fetcher=fetch}){this.davRoot=davRoot.replace(/\/$/,'');this.uid=uid;this.token=token;this.fetcher=fetcher;}
    async request(url,method,headers={},body,signal){
        const safe=safeUrl(url,this.davRoot);
        const controller=new AbortController();const cancel=()=>controller.abort(signal?.reason);
        if(signal?.aborted)cancel();else signal?.addEventListener('abort',cancel,{once:true});
        const timeout=setTimeout(()=>controller.abort(new DOMException('Zeitüberschreitung','TimeoutError')),45000);
        let r;try{r=await this.fetcher(safe,{method,headers:{requesttoken:this.token()||'',...headers},body,signal:controller.signal,credentials:'same-origin',redirect:'error',cache:'no-store'});}
        finally{clearTimeout(timeout);signal?.removeEventListener('abort',cancel);} 
        if(!r.ok){let detail='';try{const text=await r.text();const doc=new DOMParser().parseFromString(text,'application/xml');detail=doc.getElementsByTagNameNS('http://sabredav.org/ns','message')[0]?.textContent||'';}catch{}
            const e=new Error(`HTTP ${r.status}${detail?' – '+detail.slice(0,300):''}`);e.status=r.status;throw e;}
        return r;
    }
    async list(url,signal){
        const r=await this.request(url,'PROPFIND',{'Depth':'1','Content-Type':'application/xml'},'<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"><d:prop><d:resourcetype/><d:getcontenttype/><oc:fileid/><oc:owner-id/></d:prop></d:propfind>',signal);
        return parseDav(await r.text(),url);
    }
    async scan(root,recursive,signal,onProgress=()=>{}){
        safeUrl(root,this.davRoot+'/files/'+encodeURIComponent(this.uid));
        const queue=[root],seen=new Set(),ids=new Set(),files=[],blocked=[];let skipped=0;
        while(queue.length){
            if(signal?.aborted)throw new DOMException('Abgebrochen','AbortError');
            const url=queue.shift(),key=new URL(url).pathname.replace(/\/$/,'');if(seen.has(key))continue;seen.add(key);
            if(seen.size>1000)throw Error('Mehr als 1000 Ordner. Bitte einen kleineren Anlassordner auswählen.');
            const rows=await this.list(url,signal);
            const self=rows.find(n=>new URL(n.url).pathname.replace(/\/$/,'')===key);
            if(!self?.ok || !self.folder)throw Error('Der Ordner ist nicht lesbar.');
            if(rows.some(n=>n.name==='.nomedia'||n.name==='.noimage')){skipped++;continue;}
            for(const n of rows){
                safeUrl(n.url,root);const child=new URL(n.url).pathname.replace(/\/$/,'');if(child===key)continue;
                // Never follow unexpected paths returned by a storage provider.
                if(!child.startsWith(key+'/') || child.slice(key.length+1).includes('/'))throw Error('Unerwartete Ordnerstruktur in der WebDAV-Antwort.');
                if(!n.ok)throw Error('Nicht alle Dateien konnten geprüft werden.');
                if(n.name.startsWith('.')){skipped++;continue;}
                if(n.folder){if(recursive)queue.push(n.url);continue;}
                if(!/^(image|video)\//.test(n.mime) || !/^\d+$/.test(n.id)){skipped++;continue;}
                if(ids.has(n.id))continue;ids.add(n.id);
                if(n.owner && n.owner!==this.uid){blocked.push(n);continue;}
                files.push(n);if(files.length+blocked.length>5000)throw Error('Mehr als 5000 Medien. Bitte einen kleineren Ordner auswählen.');
            }
            onProgress(files.length,seen.size);
        }
        return {files,blocked,skipped,folders:seen.size};
    }
    async create(name,location){
        name=validateName(name);const url=this.davRoot+'/photos/'+encodeURIComponent(this.uid)+'/albums/'+encodeURIComponent(name);
        try{await this.request(url,'MKCOL');}catch(e){if([405,409].includes(e.status))e.message='Album konnte nicht neu angelegt werden. Prüfe, ob der Name bereits existiert. '+e.message;throw e;}
        let warning='';
        if(location.trim()){
            try{
                const r=await this.request(url,'PROPPATCH',{'Content-Type':'application/xml'},'<?xml version="1.0"?><d:propertyupdate xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns"><d:set><d:prop><nc:location>'+xmlEscape(location.trim())+'</nc:location></d:prop></d:set></d:propertyupdate>');
                const doc=new DOMParser().parseFromString(await r.text(),'application/xml');
                const statuses=[...doc.getElementsByTagNameNS(DAV,'status')];
                if(!statuses.length || statuses.some(s=>! /\s200\s/.test(s.textContent)))throw Error('Ort wurde nicht gespeichert.');
            }catch(e){warning='Album angelegt, Ort nicht gespeichert: '+e.message;}
        }
        return {url,name,warning};
    }
    async add(album,file){
        const destination=safeUrl(album.url,this.davRoot+'/photos/'+encodeURIComponent(this.uid)+'/albums')+'/'+encodeURIComponent(file.name);
        try{await this.request(file.url,'COPY',{'Destination':destination,'Overwrite':'F'});}
        catch(e){
            // A connection can fail after the server committed COPY. Confirm membership, not just HTTP 409.
            try{const members=await this.list(album.url);if(members.some(n=>n.id===file.id || n.name.startsWith(file.id+'-')))return;}catch{}
            throw e;
        }
    }
}
