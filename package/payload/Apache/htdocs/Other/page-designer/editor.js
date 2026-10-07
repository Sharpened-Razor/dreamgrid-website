(() => {
    'use strict';
    const config = JSON.parse(document.getElementById('designer-config').textContent);
    let page = config.page, revision = config.revision, pages = config.pages, dirty = false, busy = false, savedSlug = config.page.slug;
    let previewTimer, previewSequence = 0, previewAbort;
    const files = new Map(), objectUrls = new Map();
    const form = document.getElementById('designer-form');
    const notice = document.getElementById('notice');
    const frame = document.getElementById('preview');
    const clone = value => JSON.parse(JSON.stringify(value));
    const el = (tag, attrs = {}, text) => { const node = document.createElement(tag); for (const [key,value] of Object.entries(attrs)) { if (key === 'class') node.className = value; else node.setAttribute(key,value); } if (text !== undefined) node.textContent = text; return node; };
    function show(message, error = false) { notice.textContent = message; notice.classList.toggle('error', error); }
    function changed() { dirty = true; document.getElementById('save-state').textContent = 'Unsaved changes'; clearTimeout(previewTimer); previewTimer = setTimeout(preview, 250); }
    function assign(target, key, value) { target[key] = value; changed(); }
    function field(parent, target, key, label, type = 'text', options = {}) {
        const wrapper = el('div',{class:'field' + (options.full?' full':'')});
        const id = 'field-' + field.count++;
        const caption = el('label',{for:id}, label);
        let input;
        if (type === 'select') {
            input = el('select',{id}); for (const [value,text] of Object.entries(options.choices)) { const option = el('option',{value},text); input.append(option); }
        } else if (type === 'textarea') input = el('textarea',{id});
        else input = el('input',{id,type});
        if(type === 'checkbox') { input.checked = !!target[key]; const row=el('div',{class:'check'}); row.append(input,caption); wrapper.append(row); }
        else { input.value = target[key] ?? ''; wrapper.append(caption,input); }
        for(const attr of ['min','max','step','maxlength']) if(options[attr]!==undefined) input.setAttribute(attr,options[attr]);
        if(type === 'range') { const output=el('output',{},input.value+(options.unit||'')); caption.append(' · ',output); input.addEventListener('input',()=>output.textContent=input.value+(options.unit||'')); }
        input.addEventListener('input',()=> { const value=type==='checkbox'?input.checked:(['range','number'].includes(type)?Number(input.value):input.value); assign(target,key,value); if(options.onChange) options.onChange(value); });
        parent.append(wrapper); return input;
    }
    field.count = 0;
    function upload(parent, target, key, label, fileKey) {
        const w=el('div',{class:'field'}), id='field-'+field.count++, input=el('input',{id,type:'file',accept:'image/jpeg,image/png,image/gif,image/webp'});
        w.append(el('label',{for:id},label),input);
        const note=el('span',{class:'muted'},files.has(fileKey)?files.get(fileKey).name:(target[key]?'Saved image':'No image')); w.append(note);
        input.addEventListener('change',()=> {
            const file=input.files[0]; if(!file) return;
            if(file.size>config.fileLimit || !['image/jpeg','image/png','image/gif','image/webp'].includes(file.type)) { show('Choose a JPG, PNG, GIF or WebP image within the server size limit.',true); input.value=''; return; }
            if(objectUrls.has(fileKey)) URL.revokeObjectURL(objectUrls.get(fileKey));
            files.set(fileKey,file); objectUrls.set(fileKey,URL.createObjectURL(file)); note.textContent=file.name; changed();
        });
        const remove=el('button',{type:'button',class:'secondary'},'Remove image'); remove.addEventListener('click',()=> { target[key]=''; files.delete(fileKey); if(objectUrls.has(fileKey)) URL.revokeObjectURL(objectUrls.get(fileKey)); objectUrls.delete(fileKey); input.value=''; note.textContent='No image'; changed(); });
        w.append(remove); parent.append(w);
    }
    function details(parent,title,open=false,cls='settings') { const d=el('details',{class:cls}); d.open=open; d.append(el('summary',{},title)); parent.append(d); return d; }
    const fontChoices=Object.fromEntries(Object.entries(config.fonts).map(([k,v])=>[k,v.label]));
    function style(parent,target,key,label,min,max) {
        const group=el('div',{class:'style-group'}); group.append(el('h3',{},label)); const grid=el('div',{class:'fields'}); group.append(grid);
        field(grid,target,key+'Font','Font','select',{choices:{inherit:'Use global page font',...fontChoices}});
        field(grid,target,key+'Size','Size (px)','number',{min,max}); field(grid,target,key+'Color','Colour','color');
        field(grid,target,key+'Align','Alignment','select',{choices:{left:'Left',center:'Center',right:'Right'}});
        field(grid,target,key+'Bold','Bold','checkbox'); field(grid,target,key+'Italic','Italic','checkbox'); parent.append(group);
    }
    function settings() {
        const root=document.getElementById('page-settings'); root.replaceChildren();
        let d=details(root,'Page settings',true), grid=el('div',{class:'fields'}); d.append(grid);
        field(grid,page,'title','Page title','text',{maxlength:120});
        field(grid,page,'slug','URL slug (blank uses title)','text',{maxlength:80});
        field(grid,page,'access','Access','select',{choices:{public:'Public',members:'Members',admin:'Admin'}});
        field(grid,page,'columns','Card columns','select',{choices:{1:'1 column',2:'2 columns',3:'3 columns',4:'4 columns'},onChange:v=>page.columns=Number(v)});
        field(grid,page,'published','Publish this page','checkbox',{full:true});
        d.append(el('p',{class:'muted'},'Draft pages are visible only in admin previews.'));
        d=details(root,'Background',true); grid=el('div',{class:'fields'}); d.append(grid);
        field(grid,page,'backgroundColor','Background colour','color'); upload(grid,page,'backgroundImage','Background image','background_image');
        let custom;
        field(grid,page,'backgroundFit','Fit mode','select',{choices:{stretch:'Fit page exactly',cover:'Cover page',contain:'Fit whole image',custom:'Custom size'},full:true,onChange:v=>custom.hidden=v!=='custom'});
        custom=el('div',{class:'fields full'}); custom.hidden=page.backgroundFit!=='custom'; grid.append(custom);
        field(custom,page,'backgroundWidth','Width','range',{min:50,max:200,unit:'%'});
        const height=field(custom,page,'backgroundHeight','Height','range',{min:50,max:200,unit:'%'}); height.disabled=page.backgroundLock;
        field(custom,page,'backgroundLock','Lock aspect ratio','checkbox',{full:true,onChange:v=>height.disabled=v});
        field(grid,page,'backgroundPositionX','Horizontal position','range',{min:0,max:100,unit:'%'}); field(grid,page,'backgroundPositionY','Vertical position','range',{min:0,max:100,unit:'%'});
        d.append(el('p',{class:'muted'},'Exact fit stretches the image. Cover may crop. Whole image preserves all of it. With aspect ratio locked, custom height is automatic.'));
        d=details(root,'Control Center menu'); grid=el('div',{class:'fields'}); d.append(grid);
        field(grid,page,'showInMenu','Show in Control Center menu','checkbox',{full:true}); field(grid,page,'menuLabel','Menu label (blank uses title)','text',{maxlength:40}); field(grid,page,'menuIcon','Icon','select',{choices:config.icons}); field(grid,page,'menuLocation','Location','select',{choices:config.locations}); field(grid,page,'menuOrder','Order (lower first)','number',{min:0,max:999});
        d.append(el('p',{class:'muted'},'Only published pages appear in the menu. Changes update the Control Center after saving.'));
        d=details(root,'Page typography'); grid=el('div',{class:'fields'}); d.append(grid); field(grid,page.typography,'globalFont','Global page font','select',{choices:fontChoices,full:true});
        style(d,page.typography,'title','Page title',18,96); style(d,page.typography,'cardTitle','Default card title',10,64); style(d,page.typography,'cardText','Default card text',9,48); style(d,page.typography,'button','Default button',9,40);
    }
    function newCard() {
        const id=Array.from(crypto.getRandomValues(new Uint8Array(6)),v=>v.toString(16).padStart(2,'0')).join('');
        const typography={}; for(const [key,source] of Object.entries({title:'cardTitle',text:'cardText',button:'button'})) for(const suffix of ['Font','Size','Bold','Italic','Color','Align']) typography[key+suffix]=page.typography[source+suffix];
        return {id,title:'',text:'',image:'',linkLabel:'',linkUrl:'',newTab:false,span:1,backgroundColor:'#171c1b',picture:{fit:'cover',height:220,x:50,y:50},customTypography:false,typography};
    }
    function cards(openId) {
        const root=document.getElementById('cards'); const opened=new Set(Array.from(root.querySelectorAll('details.card[open]')).map(n=>n.dataset.id)); if(openId) opened.add(openId); root.replaceChildren();
        page.cards.forEach((card,index)=> {
            const d=details(root,'Card '+(index+1)+' · '+(card.title||'Untitled'),opened.has(card.id),'card'); d.dataset.id=card.id;
            const bar=el('div',{class:'card-bar'}); bar.append(el('span',{},'Card '+(index+1)));
            for(const [text,delta] of [['↑',-1],['↓',1]]) {
                const b=el('button',{type:'button',class:'secondary','aria-label':'Move card '+(index+1)+(delta<0?' up':' down')},text); b.disabled=index+delta<0 || index+delta>=page.cards.length;
                b.addEventListener('click',()=> { [page.cards[index],page.cards[index+delta]]=[page.cards[index+delta],page.cards[index]]; cards(card.id); changed(); }); bar.append(b);
            }
            const remove=el('button',{type:'button',class:'danger','aria-label':'Delete card '+(index+1)},'Delete'); remove.addEventListener('click',()=> { if(!confirm('Remove this card? Save the page to apply the change.')) return; page.cards.splice(index,1); const key='image_'+card.id; files.delete(key); if(objectUrls.has(key)) URL.revokeObjectURL(objectUrls.get(key)); objectUrls.delete(key); cards(); changed(); }); bar.append(remove); d.append(bar);
            const grid=el('div',{class:'fields'}); d.append(grid);
            field(grid,card,'title','Title','text',{maxlength:120,onChange:v=>d.querySelector('summary').textContent='Card '+(index+1)+' · '+(v||'Untitled')}); field(grid,card,'span','Card width','select',{choices:{1:'1 column',2:'2 columns',3:'3 columns',4:'4 columns'},onChange:v=>card.span=Number(v)}); field(grid,card,'text','Text','textarea',{full:true,maxlength:5000});
            upload(grid,card,'image','Picture','image_'+card.id); field(grid,card,'backgroundColor','Card colour','color');
            const picture=details(d,'Picture controls'); const pg=el('div',{class:'fields'}); picture.append(pg);
            field(pg,card.picture,'fit','Fit mode','select',{choices:{stretch:'Fit exactly',cover:'Cover',contain:'Fit whole image'}}); field(pg,card.picture,'height','Height','range',{min:120,max:700,unit:' px'}); field(pg,card.picture,'x','Horizontal position','range',{min:0,max:100,unit:'%'}); field(pg,card.picture,'y','Vertical position','range',{min:0,max:100,unit:'%'});
            field(grid,card,'linkLabel','Link button text','text',{maxlength:80}); field(grid,card,'linkUrl','Link URL','text',{maxlength:2048}); field(grid,card,'newTab','Open link in new tab','checkbox',{full:true});
            const types=details(d,'Card typography'); let custom;
            field(types,card,'customTypography','Custom typography for this card','checkbox',{onChange:v=>custom.hidden=!v});
            custom=el('div'); custom.hidden=!card.customTypography; types.append(custom); style(custom,card.typography,'title','Title',10,64); style(custom,card.typography,'text','Text',9,48); style(custom,card.typography,'button','Button',9,40);
        });
        document.getElementById('add-card').disabled=page.cards.length>=30;
    }
    function library() {
        const list=document.getElementById('page-list'); list.replaceChildren();
        for(const p of pages) { const a=el('a',{href:'/Other/admin-page-designer.php?id='+encodeURIComponent(p.id),class:'page-link'+(p.id===page.id?' active':'')}); a.append(el('strong',{},p.title),el('span',{},'/'+p.slug+' · '+(p.published?'Published':'Draft'))); list.append(a); }
        if(!pages.length) list.append(el('p',{class:'muted'},'No custom pages yet.'));
        document.getElementById('delete-page').hidden=!page.id; document.getElementById('open-page').disabled=!page.id;
    }
    function syncMenu(menuPages) { if(window.parent!==window) window.parent.postMessage({type:'cc-page-designer-menu-sync',pages:menuPages},location.origin); }
    async function request(action, extra={}, uploads=false, signal) {
        const body=new FormData(); body.set('csrf',config.csrf); body.set('action',action); body.set('revision',revision); body.set('page',JSON.stringify(page)); for(const [k,v] of Object.entries(extra)) body.set(k,v);
        if(uploads) for(const [key,file] of files) body.set(key,file);
        const response=await fetch('/Other/admin-page-designer.php',{method:'POST',body,credentials:'same-origin',signal});
        const text=await response.text(); let data; try { data=JSON.parse(text); } catch(e) { throw new Error('The server did not return a designer response. Your edits remain here. Check your login and upload size.'); }
        if(!response.ok || !data.ok) throw new Error(data.error || 'Request failed.'); return data;
    }
    async function preview() {
        const sequence=++previewSequence; if(previewAbort) previewAbort.abort(); previewAbort=new AbortController();
        document.getElementById('preview-state').textContent='Updating…';
        try {
            const data=await request('preview',{},false,previewAbort.signal); if(sequence!==previewSequence) return;
            frame.onload=()=> {
                const doc=frame.contentDocument; if(!doc) return;
                if(objectUrls.has('background_image')) doc.querySelector('.cp-bg').style.backgroundImage='linear-gradient(rgba(0,0,0,.28),rgba(0,0,0,.28)),url("'+objectUrls.get('background_image')+'")';
                for(const card of page.cards) { const key='image_'+card.id; if(!objectUrls.has(key)) continue; const img=doc.querySelector('[data-card-id="'+card.id+'"] img'); if(img) { img.src=objectUrls.get(key); img.hidden=false; img.style.display='block'; } }
                doc.addEventListener('click',event=> { if(event.target.closest('a')) event.preventDefault(); });
            };
            frame.srcdoc=data.html; document.getElementById('preview-state').textContent='Unsaved preview';
        } catch(e) { if(e.name!=='AbortError' && sequence===previewSequence) document.getElementById('preview-state').textContent=e.message; }
    }
    function clearFiles() { for(const url of objectUrls.values()) URL.revokeObjectURL(url); files.clear(); objectUrls.clear(); }
    async function save() {
        if(busy) return;
        if(!page.title.trim()) { show('Enter a page title.',true); return; }
        if(!form.reportValidity()) return;
        const bytes=Array.from(files.values()).reduce((sum,f)=>sum+f.size,0)+new TextEncoder().encode(JSON.stringify(page)).length+65536;
        if((config.postLimit>0 && bytes>config.postLimit) || files.size>config.maxUploads) { show('These uploads exceed the server request limit. Upload fewer or smaller images per save.',true); return; }
        busy=true; form.inert=true; document.getElementById('save-page').disabled=true; show('Saving…');
        try {
            const data=await request('save',{},true); page=data.page; revision=data.revision; savedSlug=page.slug; clearFiles(); dirty=false;
            pages=pages.filter(p=>p.id!==page.id).concat([page]).sort((a,b)=>a.title.localeCompare(b.title));
            history.replaceState(null,'','/Other/admin-page-designer.php?id='+page.id); settings(); cards(); library(); syncMenu(data.menuPages); document.getElementById('save-state').textContent='Saved'; show('Page saved.'); preview();
        } catch(e) { show(e.message,true); } finally { busy=false; form.inert=false; document.getElementById('save-page').disabled=false; }
    }
    document.getElementById('save-page').addEventListener('click',save);
    form.addEventListener('submit',event=> { event.preventDefault(); save(); });
    document.getElementById('add-card').addEventListener('click',()=> { if(page.cards.length>=30) return; const card=newCard(); page.cards.push(card); cards(card.id); changed(); });
    document.getElementById('delete-page').addEventListener('click',async()=> {
        if(busy || !page.id || !confirm('Delete this saved page? It will be removed from the website and Control Center menu.')) return;
        busy=true; try { const data=await request('delete',{id:page.id}); dirty=false; syncMenu(data.menuPages); location.href='/Other/admin-page-designer.php'; } catch(e) { show(e.message,true); busy=false; }
    });
    document.getElementById('open-page').addEventListener('click',()=> { if(savedSlug) window.open('/Other/custom-page.php?page='+encodeURIComponent(savedSlug)+'&preview=1','_blank','noopener'); });
    document.getElementById('preview-width').addEventListener('change',event=>frame.style.width=event.target.value);
    window.addEventListener('beforeunload',event=> { if(dirty) { event.preventDefault(); event.returnValue=''; } });
    document.addEventListener('keydown',event=> { if((event.ctrlKey || event.metaKey) && event.key==='s') { event.preventDefault(); save(); } });
    settings(); cards(page.cards[0]?.id); library(); syncMenu(config.menuPages); preview();
})();
