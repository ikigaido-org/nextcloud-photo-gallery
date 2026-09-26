/* SPDX-License-Identifier: AGPL-3.0-only */
import { registerFileAction, FileType, Permission } from '@nextcloud/files';
import { getCurrentUser, getRequestToken } from '@nextcloud/auth';
import { generateRemoteUrl, generateUrl } from '@nextcloud/router';
import { AlbumImport, validateName, safeUrl } from './album-import.mjs';
let opened=false;
function el(tag,text,attributes={}){const n=document.createElement(tag);if(text)n.textContent=text;for(const [k,v] of Object.entries(attributes))n.setAttribute(k,v);return n;}
function field(label,input){const wrapper=el('label',label);wrapper.append(input);return wrapper;}
async function checkAccess(){
    const response=await fetch(generateUrl('/apps/photo_gallery/tools/access'),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}});
    if(!response.ok || !(await response.json()).allowed)throw new Error('Die Albumwerkzeuge sind für dieses Konto nicht freigegeben. Bitte die Dateien-Seite neu laden.');
}
async function openDialog(node){
    if(opened)return null;opened=true;
    const user=getCurrentUser();if(!user){opened=false;return null;}
    try{await checkAccess();}catch(e){opened=false;window.alert(e.message);return null;}
    const api=new AlbumImport({davRoot:generateRemoteUrl('dav'),uid:user.uid,token:getRequestToken});
    const dialog=el('dialog','',{class:'pg-import-dialog','aria-labelledby':'pg-import-title'});
    const title=el('h2','Fotoalbum aus Ordner erstellen',{id:'pg-import-title'});
    const explanation=el('p','Erstellt ein privates Photos-Album für '+user.displayName+'. Die Originaldateien bleiben unverändert.');
    const folder=el('p',node.path||node.basename,{class:'pg-import-path'});
    const name=el('input','',{type:'text',maxlength:'200',value:node.basename,required:''});
    const location=el('input','',{type:'text',maxlength:'200',placeholder:'Optional'});
    const recursive=el('input','',{type:'checkbox'});
    const fields=el('div','',{class:'pg-import-fields'});fields.append(field('Albumname',name),field('Ort',location),field('Unterordner einbeziehen',recursive));
    const status=el('p','Zuerst die Dateien prüfen. Es wird noch kein Album angelegt.',{role:'status','aria-live':'polite'});
    const detail=el('details'),summary=el('summary','Dateien anzeigen'),fileList=el('ul');detail.append(summary,fileList);detail.hidden=true;
    const errors=el('pre','',{class:'pg-import-errors'});errors.hidden=true;
    const controls=el('div','',{class:'pg-import-controls'});
    const scan=el('button','Dateien prüfen',{type:'button'}),create=el('button','Album erstellen',{type:'button'}),stop=el('button','Anhalten',{type:'button'}),close=el('button','Schließen',{type:'button'});
    create.disabled=true;stop.hidden=true;controls.append(scan,create,stop,close);
    const photos=el('a','In Photos öffnen und teilen',{href:generateUrl('/apps/photos/albums'),class:'pg-import-open'});photos.hidden=true;
    dialog.append(title,explanation,folder,fields,status,detail,errors,controls,photos);document.body.append(dialog);
    let busy=false,scanning=false,abort=null,plan=null,album=null,done=new Set(),stopped=false;
    const beforeUnload=e=>{if(busy && !scanning){e.preventDefault();e.returnValue='';}};window.addEventListener('beforeunload',beforeUnload);
    function setBusy(value){busy=value;scan.disabled=value||!!album;create.disabled=value||!plan?.files.length||!!plan?.blocked.length;close.disabled=value;stop.hidden=!value;name.disabled=value||!!album;location.disabled=value||!!album;recursive.disabled=value||!!album;}
    function invalidate(){if(album)return;plan=null;create.disabled=true;detail.hidden=true;status.textContent='Auswahl geändert. Bitte Dateien erneut prüfen.';}
    recursive.addEventListener('change',invalidate);
    function dispose(){if(busy)return;dialog.close();dialog.remove();window.removeEventListener('beforeunload',beforeUnload);opened=false;}
    close.onclick=dispose;dialog.addEventListener('cancel',e=>{e.preventDefault();dispose();});
    stop.onclick=()=>{stopped=true;if(scanning)abort?.abort();status.textContent=scanning?'Prüfung wird abgebrochen …':'Import hält nach der laufenden Datei an …';};
    scan.onclick=async()=>{
        scanning=true;setBusy(true);errors.hidden=true;detail.hidden=true;abort=new AbortController();plan=null;
        try{
            await checkAccess();
            plan=await api.scan(node.source,recursive.checked,abort.signal,(count,folders)=>{status.textContent=`${count} Medien in ${folders} Ordnern gefunden …`;});
            status.textContent=`${plan.files.length} Fotos/Videos gefunden. ${plan.skipped} Einträge übersprungen.`;
            fileList.replaceChildren(...plan.files.slice(0,100).map(f=>el('li',f.name)));if(plan.files.length>100)fileList.append(el('li',`… und ${plan.files.length-100} weitere.`));detail.hidden=!plan.files.length;
            if(plan.blocked.length){status.textContent+=` ${plan.blocked.length} Dateien gehören einem anderen Konto. Photos erlaubt diese Zuordnung nicht; es wird kein Album erstellt.`;errors.textContent=plan.blocked.slice(0,10).map(f=>f.name).join('\n');errors.hidden=false;}
            else if(!plan.files.length){status.textContent+=' Kein Album wird erstellt.';}
        }catch(e){status.textContent=e.name==='AbortError'?'Prüfung abgebrochen.':'Prüfung fehlgeschlagen: '+e.message;}
        finally{scanning=false;setBusy(false);}
    };
    create.onclick=async()=>{
        if(!plan?.files.length||plan.blocked.length)return;
        try{validateName(name.value);}catch(e){status.textContent=e.message;return;}
        scanning=false;stopped=false;setBusy(true);errors.hidden=true;
        const failures=[];
        try{
            await checkAccess();
            if(!album){status.textContent='Privates Album wird angelegt …';album=await api.create(name.value,location.value);photos.hidden=false;}
            for(const file of plan.files){
                if(stopped)break;if(done.has(file.id))continue;
                status.textContent=`${done.size} / ${plan.files.length} hinzugefügt – ${file.name}`;
                try{await api.add(album,file);done.add(file.id);}
                catch(e){failures.push(file.name+': '+e.message);if([401,403].includes(e.status)||done.size===0){stopped=true;break;}}
            }
            const remaining=plan.files.length-done.size;
            status.textContent=`„${album.name}“: ${done.size} / ${plan.files.length} Medien hinzugefügt. `+(remaining?'Import unvollständig. Bereits hinzugefügte Dateien bleiben im privaten Album.':'Fertig. Öffentlich teilen kannst du das Album in Photos.');
            if(album.warning)failures.push(album.warning);
            if(failures.length){errors.textContent=failures.slice(0,10).join('\n')+(failures.length>10?`\n… ${failures.length-10} weitere Fehler.`:'');errors.hidden=false;}
            create.textContent=remaining?'Restliche Dateien erneut versuchen':'Fertig';
        }catch(e){status.textContent='Album konnte nicht erstellt werden: '+e.message;}
        finally{setBusy(false);if(album && done.size===plan.files.length)create.disabled=true;}
    };
    dialog.showModal();name.focus();name.select();return null;
}
registerFileAction({
    id:'photo-gallery-create-album',displayName:()=> 'Fotoalbum aus Ordner erstellen',
    iconSvgInline:()=>'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8" cy="9" r="1.5"/><path d="m4 17 5-5 4 4 3-3 4 4"/></svg>',
    enabled:({nodes})=>{
        const user=getCurrentUser();
        if(!user||nodes.length!==1||nodes[0].type!==FileType.Folder||(nodes[0].permissions&Permission.READ)===0)return false;
        try{safeUrl(nodes[0].source,generateRemoteUrl('dav')+'/files/'+encodeURIComponent(user.uid));return true;}catch{return false;}
    },
    exec:({nodes})=>openDialog(nodes[0]),order:35,
});
