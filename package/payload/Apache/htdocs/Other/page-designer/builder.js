(() => {
    'use strict';
    const config=JSON.parse(document.getElementById('designer-config').textContent);
    const presets=window.DreamGridPresets(config.gridName);
    const copy=v=>JSON.parse(JSON.stringify(v));
    let page=config.page, revision=config.revision, pages=config.pages, publishing=config.publishing, selectedRevision=null, historyRequest=0;
    let selected=null,selectedIds=new Set(),multiMode=false, inspectorTab='content', inspectorMode='element', panel='add', dirty=false, busy=false, previewMode=false;
    let device=1280, zoom='fit', previewTimer, sequence=0, controller, lastEdit='', lastEditTime=0;
    const undoStack=[],redoStack=[],files=new Map(),urls=new Map();
    const $=id=>document.getElementById(id), frame=$('canvas'), inspector=$('inspector');
    const create=(tag,attrs={},text)=>{const n=document.createElement(tag); for(const [k,v] of Object.entries(attrs)) { if(k==='class') n.className=v; else n.setAttribute(k,v); } if(text!==undefined) n.textContent=text; return n;};
    const themeDefault=()=>({background:'#f7f8fa',surface:'#ffffff',text:'#172b31',muted:'#65777c',accent:'#087f75',font:'segoe-ui',width:1200,radius:16});
    if(!page.builder) page.builder={version:1,theme:themeDefault(),blocks:page.id||page.cards.length?[presets.node('legacy',{}, {},[],'Existing page')]:[]};
    function traverse(nodes,callback,parent=null){for(const node of nodes){callback(node,parent);traverse(node.children||[],callback,node);}}
    function find(id){let found=null; traverse(page.builder.blocks,(node,parent)=>{if(node.id===id) found={node,parent,list:parent?parent.children:page.builder.blocks};}); return found;}
    function inheritDefaults(nodes){if(!page.builder.site?.styles)return;traverse(nodes,n=>{for(const [key,value]of Object.entries({paddingY:64,gap:24,radius:16}))if(n.style[key]===value)delete n.style[key];if(n.type==='heading'&&n.style.fontSize===36)delete n.style.fontSize;});}
    function selectedNode(){return selected && !selected.startsWith('card:') ? find(selected)?.node : null;}
    function legacyCard(){return selected?.startsWith('card:') ? page.cards.find(c=>c.id===selected.slice(5)) : null;}
    function total(){let n=0;traverse(page.builder.blocks,()=>n++);return n;}
    function snapshot(){return {page:copy(page),files:[...files],dirty,selected,selectedIds:[...selectedIds]};}
    function resetUrls(){for(const u of urls.values()) URL.revokeObjectURL(u);urls.clear();for(const [k,f] of files) urls.set(k,URL.createObjectURL(f));}
    function restore(s){page=copy(s.page);files.clear();for(const [k,f] of s.files) files.set(k,f);resetUrls();selected=s.selected||null;selectedIds=new Set(s.selectedIds||[]);if(selected && !find(selected) && !legacyCard()) selected=null;dirty=!!s.dirty;refresh();window.DreamGridEditing?.changed();}
    function mutate(fn,key='',refreshInspector=false){
        if(busy) return;
        const now=Date.now(); if(!key || key!==lastEdit || now-lastEditTime>900){undoStack.push(snapshot());if(undoStack.length>80)undoStack.shift();}
        lastEdit=key;lastEditTime=now;redoStack.length=0;fn();dirty=true;updateTop();if(panel==='layers') renderLibrary();if(refreshInspector) renderInspector();schedulePreview();window.DreamGridEditing?.changed();
    }
    function undo(){if(busy||!undoStack.length)return;redoStack.push(snapshot());restore(undoStack.pop());lastEdit='';show('Undone.');}
    function redo(){if(busy||!redoStack.length)return;undoStack.push(snapshot());restore(redoStack.pop());lastEdit='';show('Redone.');}
    function show(message,error=false){$('notice').textContent=message;$('notice').classList.toggle('error',error);}
    function updateTop(){if(selected&&!selectedIds.has(selected))selectedIds=new Set([selected]);if(!selected)selectedIds.clear();
        $('page-name').textContent=page.title||'Untitled page';$('canvas-page-label').textContent=(page.title||'UNTITLED PAGE').toUpperCase();
        $('save-state').textContent=busy?'Working…':(dirty?'Unsaved changes':(publishing.hasDraft?(publishing.published?'Draft saved · live page unchanged':'Draft saved · not published'):'All changes saved'));$('save-state').classList.toggle('dirty',dirty);
        $('undo').disabled=!undoStack.length||busy;$('redo').disabled=!redoStack.length||busy;
        $('save-page').disabled=busy;$('publish-page').disabled=busy||(publishing.published&&!publishing.hasDraft&&!dirty);$('publish-page').textContent=publishing.published?(publishing.hasDraft||dirty?'Publish changes':'Published ✓'):'Publish';
        $('revision-history').disabled=busy||!page.id;$('transfer-pages').disabled=busy;
        $('open-page').disabled=!page.id;$('element-count').textContent=total()+' elements';
        const node=selectedNode(),card=legacyCard();$('selection-path').textContent=card?'Existing page / '+(card.title||'Card'):(node?'Page / '+node.name:'Page canvas');window.DreamGridEditing?.selectionChanged();
    }
    function refresh(){renderLibrary();renderInspector();updateTop();schedulePreview();}
    function setPanel(value){panel=value;$('library-title').textContent={add:'Add to your page',layers:'Page structure',pages:'Your pages'}[panel];document.querySelectorAll('[data-panel]').forEach(b=>b.classList.toggle('active',b.dataset.panel===panel));renderLibrary();$('library-count').textContent=panel==='layers'?total():'';$('library').parentElement.classList.toggle('mobile-open',true);}
    function select(id,scroll=false,extend=false){if(id&& !id.startsWith('card:')&&(extend||multiMode)){if(selectedIds.has(id))selectedIds.delete(id);else selectedIds.add(id);selected=selectedIds.has(id)?id:[...selectedIds].at(-1)||null;}else{selected=id;selectedIds=new Set(id?[id]:[]);}inspectorMode='element';renderInspector();updateTop();highlight();if(panel==='layers')renderLibrary();$('inspector').parentElement.classList.add('mobile-open');if(scroll){const target=canvasTarget();target?.scrollIntoView({block:'center',behavior:'smooth'});}}
    const icons={section:'▭',columns:'▥',heading:'H',text:'T',image:'▧',button:'▰',navigation:'☰',hero:'◈',gallery:'▦',quote:'“',faq:'?',stats:'▤',video:'▷',divider:'―',spacer:'↕',footer:'⊥','site-branding':'◈','grid-login':'🔑',form:'✉',tabs:'▤',accordion:'≡',legacy:'▣'};
    const components=[['Structure',[['section','Section'],['columns','Columns']]],['Content',[['heading','Heading'],['text','Text'],['image','Image'],['button','Button'],['form','Form'],['grid-login','Member Login'],['site-branding','Site Branding']]],['Page sections',[['navigation','Navigation'],['hero','Hero'],['gallery','Gallery'],['quote','Quote'],['faq','FAQ'],['tabs','Tabs'],['accordion','Accordion'],['stats','Highlights'],['video','Video'],['footer','Footer']]],['Finishing touches',[['divider','Divider'],['spacer','Spacer']]]];
    function renderLibrary(){
        const root=$('library');root.replaceChildren();
        $('library-count').textContent=panel==='layers'?total():'';
        if(panel==='add'){
            for(const [label,entries] of components){root.append(create('h3',{class:'library-section-label'},label));const grid=create('div',{class:'component-grid'});root.append(grid);for(const [type,name]of entries){const button=create('button',{type:'button',class:'component',draggable:'true','data-component':type});button.append(create('span',{class:'component-icon','aria-hidden':'true'},icons[type]),create('span',{},name));button.addEventListener('click',()=>insert(type));button.addEventListener('dragstart',e=>{e.dataTransfer.setData('application/dreamgrid-component',type);e.dataTransfer.effectAllowed='copy';});grid.append(button);}}
            root.append(create('h3',{class:'library-section-label'},'Ready-made sections'));
            for(const [type,name,sub]of [['features','Feature grid','Three columns, ready to edit'],['cta','Call to action','A clear next step for visitors']]){const b=create('button',{class:'section-preset',type:'button',draggable:'true'});b.append(create('span',{},'▦'));const text=create('span');text.append(create('strong',{},name),create('small',{},sub));b.append(text);b.addEventListener('click',()=>insert(type));b.addEventListener('dragstart',e=>e.dataTransfer.setData('application/dreamgrid-component',type));root.append(b);}
            root.append(create('p',{class:'library-tip'},'Select a section to add inside it. Select an element to insert beside it. Drag elements directly onto the canvas.'));
        } else if(panel==='layers'){
            if(!page.builder.blocks.length)root.append(create('p',{class:'layer-empty'},'Your page starts here. Add a section or choose a complete layout.'));
            function row(id,name,type,depth){const b=create('button',{class:'layer-row'+(selectedIds.has(id)?' active':''),type:'button',draggable:'true','data-layer-id':id,'data-locked':String(!!window.DreamGridEditingModel.lockOwner(page.builder.blocks,id))});b.style.paddingLeft=(8+depth*13)+'px';b.style.width='100%';b.append(create('span',{class:'layer-icon'},icons[type]||'▢'),create('span',{class:'layer-label'},name),create('span',{class:'layer-kind'},type==='section'?'▾':''));b.addEventListener('click',e=>select(id,true,e.ctrlKey||e.metaKey||e.shiftKey));b.addEventListener('dragstart',e=>{if(window.DreamGridEditingModel.protectedNode(page.builder.blocks,id)){e.preventDefault();show('Unlock this element before moving it.',true);return;}e.dataTransfer.setData('application/dreamgrid-layer',id);e.dataTransfer.effectAllowed='move';});b.addEventListener('dragover',e=>{e.preventDefault();b.classList.add('drop-target');});b.addEventListener('dragleave',()=>b.classList.remove('drop-target'));b.addEventListener('drop',e=>{e.preventDefault();e.stopPropagation();const source=e.dataTransfer.getData('application/dreamgrid-layer');const type=e.dataTransfer.getData('application/dreamgrid-component');if(source)move(source,id,false);else if(type)insert(type,id,false);});root.append(b);}
            const walk=(nodes,depth)=>{for(const node of nodes){row(node.id,node.name,node.type,depth);if(node.type==='legacy')page.cards.forEach((c,i)=>row('card:'+c.id,c.title||'Card '+(i+1),'legacy',depth+1));walk(node.children,depth+1);}};walk(page.builder.blocks,0);
        } else {
            const add=create('a',{class:'primary new-page',href:'/Other/admin-page-designer.php'},'+ New page');root.append(add);
            for(const p of pages){const a=create('a',{class:'page-link'+(p.id===page.id?' active':''),href:'/Other/admin-page-designer.php?id='+encodeURIComponent(p.id)});a.append(create('strong',{},p.title),create('span',{},'/'+p.slug+' · '+(p.published?(p.hasDraft?'LIVE + DRAFT':'PUBLISHED'):'DRAFT')));root.append(a);}
            if(!pages.length)root.append(create('p',{class:'layer-empty'},'Save your first page to add it to this library.'));
        }
    }
    function insert(type,targetId=selected,inside=true){
        if(!presets.defaults[type])return;if(!canInsert(targetId))return;
        const node=presets.defaults[type]();inheritDefaults([node]);
        let added=0;traverse([node],()=>added++);if(total()+added+1>240){show('This addition would exceed the 240-element page limit.',true);return;}
        const destination=find(targetId);if(inside&&destination&&['section','columns','gallery','tabs','accordion'].includes(destination.node.type)){try{window.DreamGridEditingModel.append(copy(page.builder.blocks),[node],targetId);}catch(e){show(e.message,true);return;}}
        mutate(()=>{
            let location=find(targetId);const whole=['section','hero','navigation','footer','features','cta','tabs','accordion'].includes(type);
            if(location && inside && ['section','columns','gallery','tabs','accordion'].includes(location.node.type)) location.node.children.push(node);
            else if(location){
                if(whole){while(location.parent)location=find(location.parent.id);}
                const insertion=!whole&&!location.parent?presets.node('section',{}, {padding:32,paddingY:48,gap:24},[node],'Content section'):node;
                location.list.splice(location.list.indexOf(location.node)+1,0,insertion);
            }else if(whole)page.builder.blocks.push(node);
            else page.builder.blocks.push(presets.node('section',{}, {padding:32,paddingY:48,gap:24},[node],'Content section'));
            selected=node.id;inspectorMode='element';
        },'',true);renderLibrary();show('Added '+node.name+'. Click it on the canvas to edit.');
    }
    function move(sourceId,targetId,inside){if(!sourceId.startsWith('card:')){window.DreamGridEditing?.move(sourceId,targetId,inside);return;}if(!canInsert(targetId))return;
        if(sourceId===targetId)return;
        if(sourceId.startsWith('card:')&&targetId.startsWith('card:')){mutate(()=>{const from=page.cards.findIndex(c=>c.id===sourceId.slice(5)),to=page.cards.findIndex(c=>c.id===targetId.slice(5));if(from<0||to<0)return;const c=page.cards.splice(from,1)[0];page.cards.splice(to,0,c);},'',true);return;}
        const source=find(sourceId),target=find(targetId);if(!source||!target)return;
        let invalid=false;traverse([source.node],n=>{if(n.id===targetId)invalid=true;});if(invalid)return;
        mutate(()=>{source.list.splice(source.list.indexOf(source.node),1);const destination=find(targetId);if(inside&&['section','columns','gallery','tabs','accordion'].includes(destination.node.type))destination.node.children.push(source.node);else destination.list.splice(destination.list.indexOf(destination.node),0,source.node);selected=sourceId;},'',true);renderLibrary();
    }
    function duplicate(){if(!legacyCard()){window.DreamGridEditing?.duplicate();return;}if(window.DreamGridEditing?.legacyLocked())return;
        const card=legacyCard(); if(card){if(page.cards.length>=30)return;mutate(()=>{const c=copy(card);c.id=presets.node('text').id;page.cards.splice(page.cards.indexOf(card)+1,0,c);if(files.has('image_'+card.id))files.set('image_'+c.id,files.get('image_'+card.id));selected='card:'+c.id;resetUrls();},'',true);return;}
        const found=find(selected);if(!found)return;let added=0;traverse([found.node],()=>added++);if(total()+added>240){show('This duplicate would exceed the 240-element page limit.',true);return;}
        mutate(()=>{const n=copy(found.node);traverse([n],item=>{const old=item.id;item.id=presets.node('text').id;if(files.has('block_image_'+old))files.set('block_image_'+item.id,files.get('block_image_'+old));});n.name+=' copy';found.list.splice(found.list.indexOf(found.node)+1,0,n);selected=n.id;resetUrls();},'',true);renderLibrary();
    }
    function remove(){if(!legacyCard()){window.DreamGridEditing?.remove();return;}if(window.DreamGridEditing?.legacyLocked())return;const card=legacyCard(),found=find(selected);if(!card&&!found)return;mutate(()=>{if(card)page.cards.splice(page.cards.indexOf(card),1);else found.list.splice(found.list.indexOf(found.node),1);selected=found?.parent?.id||null;},'',true);renderLibrary();show('Removed. Undo is available.');}
    function shift(delta){if(!legacyCard()){window.DreamGridEditing?.shift(delta);return;}if(window.DreamGridEditing?.legacyLocked())return;const card=legacyCard();if(card){const i=page.cards.indexOf(card),j=i+delta;if(j<0||j>=page.cards.length)return;mutate(()=>[page.cards[i],page.cards[j]]=[page.cards[j],page.cards[i]]);return;}const f=find(selected);if(!f)return;const i=f.list.indexOf(f.node),j=i+delta;if(j<0||j>=f.list.length)return;mutate(()=>[f.list[i],f.list[j]]=[f.list[j],f.list[i]]);}
    let fieldNumber=0;
    function field(parent,target,key,label,type='text',options={}){
        const w=create('div',{class:'field'+(options.full?' full':'')+(type==='checkbox'?' check':'')}),id='property-'+fieldNumber++;
        const caption=create('label',{for:id},label);let input;
        const scopeNode=selectedNode(),scopeKeys=['fontSize','lineHeight','align','padding','paddingY','gap','height','minHeight','marginTop','marginBottom','imageX','imageY','fit','gridSpan','vertical'];
        const tokens=page.builder.site?.styles?config.siteLive.styles:page.builder.tokens;
        if(scopeNode&&target===scopeNode.style&&tokens){const keyMap={fontSize:scopeNode.type==='heading'?'headingSize':scopeNode.type==='text'?'textSize':null,paddingY:scopeNode.type==='section'?'sectionSpacing':null,gap:'gap',radius:scopeNode.type==='button'?'buttonRadius':null};if(keyMap[key])options={...options,default:tokens[keyMap[key]]};}
        const scoped=scopeNode&&target===scopeNode.style&&device!==1280&&scopeKeys.includes(key);
        if(scoped){options={...options,default:target[key]??options.default};scopeNode.responsive??={};target=scopeNode.responsive[device===768?'tablet':'mobile']??={};}
        if(type==='select'){input=create('select',{id});for(const[value,text]of Object.entries(options.choices)){const o=create('option',{value},text);input.append(o);}}
        else input=create(type==='textarea'?'textarea':'input',type==='textarea'?{id}:{id,type});
        for(const attr of ['min','max','step','maxlength','placeholder'])if(options[attr]!==undefined)input.setAttribute(attr,options[attr]);
        if(type==='checkbox')input.checked=!!target[key];else {
            let value=target[key]??options.default??(type==='select'?Object.keys(options.choices)[0]:'');
            if(type==='color' && !String(value).startsWith('#')) value=value==='white'?'#ffffff':page.builder.theme[value]||options.default||'#ffffff';
            input.value=value;
        }
        w.append(caption,input);if(type==='range'){const out=create('output',{},input.value+(options.unit||''));w.append(out);input.addEventListener('input',()=>out.textContent=input.value+(options.unit||''));}
        const event=type==='select'||type==='checkbox'?'change':'input';
        input.addEventListener(event,()=>{const value=type==='checkbox'?input.checked:(type==='number'||type==='range'?Number(input.value):input.value);mutate(()=>{target[key]=options.number?Number(value):value;if(options.onChange)options.onChange(value);},id,false);});if(!scoped&&scopeNode&&target===scopeNode.style&&target[key]!==undefined){const reset=create('button',{type:'button',class:'small-button'},'Use inherited value');reset.addEventListener('click',()=>mutate(()=>delete target[key],'',true));w.append(reset);}if(scoped){const reset=create('button',{type:'button',class:'small-button'},'Use desktop value');reset.addEventListener('click',()=>mutate(()=>delete target[key],'',true));w.append(reset);if(target[key]===undefined)w.classList.add('inherited');}parent.append(w);return input;
    }
    function group(parent,title){const g=create('section',{class:'inspector-group'});g.append(create('h3',{},title));const fields=create('div',{class:'fields'});g.append(fields);parent.append(g);return fields;}
    const fontChoices=Object.fromEntries(Object.entries(config.fonts).map(([k,f])=>[k,f.label]));
    function typography(parent,target,prefix,label,min=8,max=96){const g=group(parent,label);field(g,target,prefix+'Font','Font','select',{choices:{inherit:'Use page font',...fontChoices},full:true});field(g,target,prefix+'Size','Size','number',{min,max});field(g,target,prefix+'Color','Colour','color');field(g,target,prefix+'Align','Alignment','select',{choices:{left:'Left',center:'Center',right:'Right'},full:true});field(g,target,prefix+'Bold','Bold','checkbox');field(g,target,prefix+'Italic','Italic','checkbox');}
    function media(parent,target,key,fileKey,label='Image'){
        const w=create('div',{class:'field full'}),id='property-'+fieldNumber++,input=create('input',{id,type:'file',accept:'image/jpeg,image/png,image/gif,image/webp'});
        const url=urls.get(fileKey)||(target[key]&&page.id?'/Other/custom-page-asset.php?page='+encodeURIComponent(page.id)+'&file='+encodeURIComponent(target[key]):'');
        if(url)w.append(create('img',{src:url,alt:'Selected image',class:'media-thumbnail'}));
        w.append(create('label',{for:id},label),input);
        const note=create('span',{class:'inspector-note'},files.has(fileKey)?files.get(fileKey).name:(target[key]?'Saved image':'Upload a JPG, PNG, GIF or WebP image.'));w.append(note);
        input.addEventListener('change',()=>{const file=input.files[0];if(!file)return;if(file.size>config.fileLimit||!['image/jpeg','image/png','image/gif','image/webp'].includes(file.type)){show('Choose a supported image within the server size limit.',true);input.value='';return;}mutate(()=>{files.set(fileKey,file);resetUrls();},'',true);});
        const choose=create('button',{type:'button',class:'small-button'},'Choose from image library');choose.addEventListener('click',()=>window.DreamGridTools?.images(target,key,fileKey));w.append(choose);
        const removeImage=create('button',{type:'button',class:'small-button'},'Remove image');removeImage.addEventListener('click',()=>mutate(()=>{target[key]='';files.delete(fileKey);resetUrls();},'',true));w.append(removeImage);parent.append(w);
    }
    function itemsEditor(parent,props,kind){
        const box=create('div',{class:'item-list'});if(!props.items)props.items=[];
        props.items.forEach((item,index)=>{const row=create('div',{class:'item-editor'}),g=create('div',{class:'fields'});row.append(g);
            const keys=kind==='faq'?[['question','Question'],['answer','Answer']]:kind==='stats'?[['value','Value'],['label','Label']]:[['label','Label'],['url','Link URL']];
            for(const[key,label]of keys)field(g,item,key,label,key==='answer'?'textarea':'text',{full:true});
            const del=create('button',{type:'button'},'Remove entry');del.addEventListener('click',()=>mutate(()=>props.items.splice(index,1),'',true));row.append(del);box.append(row);});
        const add=create('button',{type:'button',class:'small-button'},'+ Add entry');add.disabled=props.items.length>=24;add.addEventListener('click',()=>mutate(()=>props.items.push(kind==='faq'?{question:'Your question',answer:'Your answer'}:kind==='stats'?{value:'New',label:'Your label'}:{label:'Link',url:''}),'',true));box.append(add);parent.append(box);
    }
    function convertLegacy(){
        const f=find(selected);if(!f||f.node.type!=='legacy')return;
        const result=window.DreamGridConvertLegacy(page,presets,page.cards.filter(c=>files.has('image_'+c.id)).map(c=>c.id));
        let count=0;traverse([result.block],()=>count++);if(total()-1+count>240){show('Conversion would exceed 240 elements. Remove some other elements first.',true);return;}
        const only=page.builder.blocks.length===1&&f.list===page.builder.blocks;
        mutate(()=>{
            f.list.splice(f.list.indexOf(f.node),1,result.block);
            if(only){Object.assign(page.builder.theme,{font:page.typography.globalFont,width:1500,background:page.backgroundColor,backgroundImage:page.backgroundImage,backgroundFit:page.backgroundFit,backgroundWidth:page.backgroundWidth,backgroundHeight:page.backgroundHeight,backgroundLock:page.backgroundLock,backgroundPositionX:page.backgroundPositionX,backgroundPositionY:page.backgroundPositionY});result.block.props.image='';result.block.style.background='transparent';}
            for(const pair of result.imagePairs)if(files.has('image_'+pair.cardId)){files.set('block_image_'+pair.nodeId,files.get('image_'+pair.cardId));files.delete('image_'+pair.cardId);}
            if(files.has('background_image')){files.set(only?'builder_background_image':'block_image_'+result.block.id,files.get('background_image'));files.delete('background_image');}
            resetUrls();selected=result.block.id;inspectorTab='content';
        },'',true);setPanel('layers');show('Converted into individual editable elements. Save to keep this conversion, or Undo to restore the existing layout.');
    }
    function backgroundFields(g,target,positionPrefix='backgroundPosition'){
        field(g,target,'backgroundFit','Background fit','select',{choices:{stretch:'Fit exactly',cover:'Cover',contain:'Contain',custom:'Custom size'},full:true,default:'cover'});
        field(g,target,'backgroundWidth','Background width','range',{min:50,max:200,default:100,unit:'%',full:true});field(g,target,'backgroundHeight','Background height','range',{min:50,max:200,default:100,unit:'%',full:true});field(g,target,'backgroundLock','Lock aspect ratio','checkbox');
        field(g,target,positionPrefix+'X','Horizontal position','range',{min:0,max:100,default:50,unit:'%',full:true});field(g,target,positionPrefix+'Y','Vertical position','range',{min:0,max:100,default:50,unit:'%',full:true});
    }
    function legacyInspector(card){
        if(card){$('inspector-title').textContent=card.title||'Existing card';$('inspector-kicker').textContent='EXISTING PAGE · CARD';
            if(inspectorTab==='content'){const g=group(inspector,'Card content');field(g,card,'title','Title','text',{full:true,maxlength:120});field(g,card,'text','Text','textarea',{full:true,maxlength:5000});media(g,card,'image','image_'+card.id);field(g,card,'linkLabel','Button label','text',{full:true});field(g,card,'linkUrl','Link URL','text',{full:true});field(g,card,'newTab','Open link in a new tab','checkbox');}
            else if(inspectorTab==='style'){const g=group(inspector,'Card appearance');field(g,card,'backgroundColor','Background','color',{full:true});field(g,card,'customTypography','Custom typography','checkbox');typography(inspector,card.typography,'title','Title typography',10,64);typography(inspector,card.typography,'text','Text typography',9,48);typography(inspector,card.typography,'button','Button typography',9,40);}
            else{const g=group(inspector,'Card layout');field(g,card,'span','Column span','number',{min:1,max:4,full:true});field(g,card.picture,'fit','Image fit','select',{choices:{stretch:'Fit exactly',cover:'Cover',contain:'Fit whole image'},full:true});field(g,card.picture,'height','Image height','range',{min:120,max:700,unit:' px',full:true});field(g,card.picture,'x','Horizontal position','range',{min:0,max:100,unit:'%',full:true});field(g,card.picture,'y','Vertical position','range',{min:0,max:100,unit:'%',full:true});}
        } else {
            $('inspector-title').textContent='Existing page';$('inspector-kicker').textContent='COMPATIBLE CONTENT';
            inspector.append(create('p',{class:'inspector-note'},'Your existing layout is preserved. Click a card on the canvas to edit it, or add new sections around this page.'));
            const convert=create('button',{type:'button',class:'outline'},'Convert to native elements');convert.addEventListener('click',convertLegacy);inspector.append(convert,create('p',{class:'inspector-note'},'Split this page into editable headings, images, text, buttons and containers. Conversion is unsaved until you save. Undo restores the original layout.'));
            const g=group(inspector,'Existing layout');field(g,page,'title','Page title','text',{full:true});field(g,page,'columns','Card columns','number',{min:1,max:4,full:true});field(g,page,'backgroundColor','Background colour','color',{full:true});media(g,page,'backgroundImage','background_image','Background image');
            field(g,page,'backgroundFit','Background fit','select',{choices:{stretch:'Fit page exactly',cover:'Cover',contain:'Fit whole image',custom:'Custom size'},full:true});field(g,page,'backgroundWidth','Background width','range',{min:50,max:200,unit:'%',full:true});field(g,page,'backgroundHeight','Background height','range',{min:50,max:200,unit:'%',full:true});field(g,page,'backgroundLock','Lock aspect ratio','checkbox');field(g,page,'backgroundPositionX','Horizontal position','range',{min:0,max:100,unit:'%',full:true});field(g,page,'backgroundPositionY','Vertical position','range',{min:0,max:100,unit:'%',full:true});
            const add=create('button',{type:'button',class:'outline'},'+ Add existing-style card');add.disabled=page.cards.length>=30;add.addEventListener('click',()=>mutate(()=>{const ct={};for(const[k,p]of Object.entries({title:'cardTitle',text:'cardText',button:'button'}))for(const s of ['Font','Size','Bold','Italic','Color','Align'])ct[k+s]=page.typography[p+s];const c={id:presets.node('text').id,title:'New card',text:'',image:'',span:1,backgroundColor:'#171c1b',linkLabel:'',linkUrl:'',newTab:false,picture:{fit:'cover',height:220,x:50,y:50},customTypography:false,typography:ct};page.cards.push(c);selected='card:'+c.id;},'',true));inspector.append(add);
            if(inspectorTab==='style'){const t=group(inspector,'Global typography');field(t,page.typography,'globalFont','Global page font','select',{choices:fontChoices,full:true});typography(inspector,page.typography,'title','Page title',18,96);typography(inspector,page.typography,'cardTitle','Default card title',10,64);typography(inspector,page.typography,'cardText','Default card text',9,48);typography(inspector,page.typography,'button','Default button',9,40);}
        }
    }
    function pageInspector(){
        $('inspector-kicker').textContent='PAGE SETTINGS';$('inspector-title').textContent='Page & publishing';
        const g=group(inspector,'Page details');field(g,page,'title','Page title','text',{full:true,maxlength:120});field(g,page,'slug','URL slug (blank uses title)','text',{full:true,maxlength:80});field(g,page,'access','Who can view this page?','select',{choices:{public:'Anyone',members:'Members',admin:'Admins'},full:true});g.append(create('p',{class:'inspector-note full'},publishing.published?'Visitors see the last published version. Save draft keeps edits private.':'This page is offline. Publish makes it available under its selected access rule.'));
        if(publishing.published){const b=create('button',{type:'button',class:'small-button full'},'Take page offline');b.addEventListener('click',takeOffline);g.append(b);}
        const m=group(inspector,'Control Center menu');field(m,page,'showInMenu','Show in Control Center','checkbox');field(m,page,'menuLabel','Menu label','text',{full:true,maxlength:40});field(m,page,'menuIcon','Icon','select',{choices:config.icons,full:true});field(m,page,'menuLocation','Location','select',{choices:config.locations,full:true});field(m,page,'menuOrder','Order','number',{min:0,max:999,full:true});
        inspector.append(create('p',{class:'inspector-note'},'The page URL uses this installation’s current host. Menu and access changes apply when you publish.'));
        window.DreamGridInteractionEditor?.page(inspector);
        if(page.id){const b=create('button',{type:'button',class:'small-button danger'},'Delete saved page');b.addEventListener('click',deletePage);inspector.append(b);}
    }
    function themeInspector(){
        $('inspector-kicker').textContent='DESIGN SYSTEM';$('inspector-title').textContent='Page theme';const theme=page.builder.theme;
        const palettes=group(inspector,'Colour palettes'),row=create('div',{class:'palette-row full'});palettes.append(row);
        for(const [name,values]of [['Coastal',['#f7f8fa','#ffffff','#172b31','#65777c','#087f75']],['Midnight',['#101c28','#182b3a','#edf6fa','#a3b8c4','#37bda9']],['Warm',['#faf6ef','#ffffff','#352c25','#807368','#ab7544']],['Violet',['#f6f4fb','#ffffff','#28213f','#7b718e','#7860b2']]]){const b=create('button',{type:'button',class:'palette',title:name,'aria-label':name+' palette'});for(const color of values.slice(0,3))b.append(create('i',{style:'background:'+color}));b.addEventListener('click',()=>mutate(()=>['background','surface','text','muted','accent'].forEach((key,i)=>theme[key]=values[i]),'',true));row.append(b);}
        const g=group(inspector,'Global colours');for(const key of ['background','surface','text','muted','accent'])field(g,theme,key,key[0].toUpperCase()+key.slice(1),'color');
        const bg=group(inspector,'Page background image');media(bg,theme,'backgroundImage','builder_background_image');backgroundFields(bg,theme);
        const t=group(inspector,'Typography & shape');field(t,theme,'font','Page font','select',{choices:fontChoices,full:true});field(t,theme,'width','Content width','range',{min:640,max:1800,step:20,unit:' px',full:true});field(t,theme,'radius','Corner radius','range',{min:0,max:64,unit:' px',full:true});
        inspector.append(create('p',{class:'inspector-note'},'These defaults apply across the new page sections. Existing-page sections keep their original appearance.'));
    }
    function renderInspector(){
        inspector.replaceChildren();inspector.inert=busy;const node=selectedNode(),card=legacyCard();if(inspectorMode==='element'&&window.DreamGridEditing?.multiInspector(inspector))return;
        document.querySelector('.inspector-tabs').hidden=inspectorMode!=='element'||(!node&&!card);
        document.querySelectorAll('[data-inspector-tab]').forEach(b=>b.classList.toggle('active',b.dataset.inspectorTab===inspectorTab));
        if(inspectorMode==='page'){pageInspector();return;}if(inspectorMode==='theme'){themeInspector();return;}
        if(card || node?.type==='legacy'){legacyInspector(card);}
        else if(node){
            const p=node.props,s=node.style;if(inspectorTab!=='content')inspector.append(create('p',{class:'device-style-note'},(device===1280?'Desktop defaults':device===768?'Tablet overrides':'Phone overrides')+' · Device-specific sizing and spacing follow this preview. Other appearance settings are shared.'));$('inspector-kicker').textContent=node.type.toUpperCase();$('inspector-title').textContent=node.name;
            if(inspectorTab==='content'){
                const g=group(inspector,'Element');field(g,node,'name','Layer name','text',{full:true});
                const keys={section:[['anchor','Section anchor']],columns:[],gallery:[],heading:[['text','Heading']],text:[['text','Text']],image:[['alt','Alternative text']],button:[['label','Button text'],['url','Link URL']],navigation:[['brand','Brand / title'],['buttonLabel','Action label'],['buttonUrl','Action URL']],hero:[['kicker','Eyebrow'],['title','Headline'],['subtitle','Description'],['buttonLabel','Button label'],['buttonUrl','Button URL'],['alt','Image alternative text'],['anchor','Section anchor']],quote:[['text','Quote'],['attribution','Attribution']],footer:[['brand','Brand / title'],['text','Footer text']],video:[['videoUrl','Video file URL']],faq:[],stats:[],spacer:[],divider:[]};
                let brandField;
                for(const[key,label]of keys[node.type]||[]){const input=field(g,p,key,label,['text','subtitle','title'].includes(key)?'textarea':'text',{full:true});if(key==='brand')brandField=input;}
                if(['navigation','footer','hero'].includes(node.type)) {
                    field(g,p,'useGridName',node.type==='hero'?'Include the current grid name':'Use the current grid name','checkbox',{onChange:v=>{if(brandField){brandField.disabled=v;brandField.value=v?config.gridName:p.brand;}}});
                    if(brandField&&p.useGridName){brandField.disabled=true;brandField.value=config.gridName;}
                }
                if(['heading','text'].includes(node.type))field(g,p,'designBinding','Content source','select',{choices:{'':'Custom text',gridName:'Current grid name',siteTitle:'Site title',loginAddress:'Login address',welcomeHeading:'Welcome heading',welcomeText:'Welcome text',footer:'Footer text'},full:true,onChange:v=>{if(!v)delete p.designBinding;}});
                if(node.type==='heading')field(g,p,'tag','Heading level','select',{choices:{h1:'H1 · Page heading',h2:'H2 · Section heading',h3:'H3 · Subheading',h4:'H4',h5:'H5',h6:'H6'},full:true});
                if(node.type==='button'){field(g,p,'newTab','Open in a new tab','checkbox');field(g,p,'disabled','Disable link','checkbox');}
                if(node.type==='heading')field(g,p,'fluidTitle','Scale heading on small screens','checkbox');
                if(['image','hero','video','section'].includes(node.type))media(g,p,'image','block_image_'+node.id,node.type==='video'?'Poster image':'Image');
                if(['navigation','footer'].includes(node.type)){field(g,p,'useSiteMenu','Use shared site menu','checkbox',{onChange:()=>renderInspector()});if(!p.useSiteMenu)window.DreamGridTools?.menu(inspector,p.items??=[],(fn,refresh)=>mutate(fn,'',!!refresh));else inspector.append(create('p',{class:'inspector-note'},'Edit the shared menu in Site settings → Navigation.'));}else if(['stats','faq'].includes(node.type))itemsEditor(inspector,p,node.type);
                if(['section','columns','gallery','tabs','accordion'].includes(node.type)){const b=create('button',{type:'button',class:'outline'},'+ Add inside this '+node.type);b.addEventListener('click',()=>setPanel('add'));inspector.append(b);}
                if(node.type==='video')inspector.append(create('p',{class:'inspector-note'},'Use a direct MP4 or WebM file URL. Local / paths remain portable.'));
            } else if(inspectorTab==='style'){
                const g=group(inspector,'Appearance');field(g,s,'background','Background','color',{default:page.builder.theme.surface});field(g,s,'color','Text colour','color',{default:page.builder.theme.text});field(g,s,'radius','Corner radius','range',{min:0,max:100,unit:' px',full:true,default:0});field(g,s,'shadow','Shadow','select',{choices:{none:'None',soft:'Soft',strong:'Strong'},full:true});field(g,s,'borderWidth','Border width','number',{min:0,max:12,default:0});field(g,s,'borderColor','Border colour','color',{default:'#dde5e8'});
                const t=group(inspector,'Text style');field(t,s,'font','Font','select',{choices:{inherit:'Use page font',...fontChoices},full:true,default:'inherit'});field(t,s,'fontSize','Size (px)','number',{min:8,max:160,default:{heading:36,hero:76,navigation:21,quote:27,stats:44,button:15}[node.type]||17});field(t,s,'lineHeight','Line height','number',{min:0.5,max:3,step:0.05,default:1.5});field(t,s,'align','Alignment','select',{choices:{left:'Left',center:'Center',right:'Right'},default:'left'});field(t,s,'bold','Bold','checkbox');field(t,s,'italic','Italic','checkbox');
            } else {
                const g=group(inspector,'Spacing & size');field(g,s,'padding','Padding','range',{min:0,max:200,unit:' px',default:0,full:true});field(g,s,'paddingY','Vertical padding','range',{min:0,max:240,unit:' px',default:0,full:true});field(g,s,'gap','Element gap','range',{min:0,max:120,unit:' px',default:24,full:true});field(g,s,'marginTop','Margin above','number',{min:0,max:160,default:0});field(g,s,'marginBottom','Margin below','number',{min:0,max:160,default:0});field(g,s,'width','Content width','select',{choices:{full:'Full width',wide:'Site width',medium:'Medium',narrow:'Narrow'},full:true});field(g,s,'minHeight','Minimum height','number',{min:0,max:1200,default:0,full:true});
                if(['image','spacer','video'].includes(node.type))field(g,s,'height','Height','range',{min:0,max:1200,default:node.type==='spacer'?64:300,unit:' px',full:true});
                if(node.type==='section'){field(g,s,'gridSpan','Desktop column span','number',{min:1,max:6,default:1,full:true});backgroundFields(g,s,'image');}
                if(node.type==='image'){field(g,s,'imageX','Horizontal image position','range',{min:0,max:100,default:50,full:true,unit:'%'});field(g,s,'imageY','Vertical image position','range',{min:0,max:100,default:50,full:true,unit:'%'});}
                if(node.type==='image')field(g,s,'fit','Image fitting','select',{choices:{cover:'Cover',contain:'Contain',fill:'Stretch'},full:true});
                if(['columns','gallery','stats'].includes(node.type)){const c=group(inspector,'Responsive columns');field(c,s,'columns','Desktop','number',{min:1,max:6,default:3});field(c,s,'tabletColumns','Tablet','number',{min:1,max:6,default:2});field(c,s,'mobileColumns','Phone','number',{min:1,max:3,default:1});}
                const v=group(inspector,'Visibility');field(v,s,'hideDesktop','Hide on desktop','checkbox');field(v,s,'hideTablet','Hide on tablet','checkbox');field(v,s,'hideMobile','Hide on phone','checkbox');
            }
        } else {
            $('inspector-kicker').textContent='VISUAL CANVAS';$('inspector-title').textContent='Make it your own';const d=create('div',{class:'empty-inspector'});d.append(create('div',{class:'empty-symbol'},'◈'),create('h3',{},'A page, built your way.'),create('p',{class:'inspector-note'},'Click any element on the canvas to edit its content, appearance and layout. Double-click a heading or paragraph to write directly on the page.'));
            const layout=create('button',{type:'button',class:'outline'},'Explore page layouts');layout.addEventListener('click',openTemplates);d.append(layout);inspector.append(d);
            const g=group(inspector,'Page essentials');field(g,page,'title','Page title','text',{full:true,maxlength:120});inspector.append(create('p',{class:'inspector-note'},'Drag elements to place them. Use Layers to organize nested sections and columns. Every change has undo.'));
        }
        if(node&&inspectorTab==='content')window.DreamGridForms?.inspector(inspector,node);
        if(node)window.DreamGridInteractionEditor?.element(inspector,node,inspectorTab);
        window.DreamGridTools?.inspector(inspector,node,inspectorMode);
        if(node||card){const row=create('div',{class:'action-row'});for(const[label,action]of [['↑',()=>shift(-1)],['↓',()=>shift(1)],['Duplicate',duplicate],['Delete',remove]]){const b=create('button',{type:'button',class:label==='Delete'?'danger':'','aria-label':label==='↑'?'Move element up':label==='↓'?'Move element down':label+' element'},label);b.addEventListener('click',action);row.append(b);}inspector.append(row);}window.DreamGridEditing?.decorateInspector(inspector,node);
    }
    function canvasTarget(){
        const doc=frame.contentDocument;if(!doc||!selected)return null;
        return selected.startsWith('card:')?doc.querySelector('[data-card-id="'+selected.slice(5)+'"]'):doc.querySelector('[data-block-id="'+selected+'"]');
    }
    function fitCanvas(){
        const doc=frame.contentDocument;if(!doc)return;
        const available=Math.max(220,$('canvas-scroll').clientWidth-60),scale=zoom==='fit'?Math.min(1,available/device):Number(zoom);
        const main=doc.querySelector('.wb-page')||doc.querySelector('.cp-wrap');
        const height=Math.max(760,main?Math.ceil(main.getBoundingClientRect().height+main.offsetTop+36):760);
        frame.style.width=device+'px';frame.style.height=height+'px';frame.style.transform='scale('+scale+')';
        $('canvas-shell').style.width=(device*scale)+'px';$('canvas-shell').style.height=(height*scale)+'px';
        $('canvas-size').textContent=device+' px · '+Math.round(scale*100)+'%';
    }
    function highlight(){
        const doc=frame.contentDocument;if(!doc)return;
        doc.querySelectorAll('.dg-selected').forEach(n=>n.classList.remove('dg-selected'));doc.querySelector('.dg-element-tools')?.remove();
        if(!previewMode)for(const id of selectedIds)doc.querySelector('[data-block-id="'+id+'"]')?.classList.add('dg-selected');const target=canvasTarget();if(!target||previewMode)return;target.classList.add('dg-selected');
        const rect=target.getBoundingClientRect(),tools=doc.createElement('div');tools.className='dg-element-tools';
        tools.style.top=Math.max(0,rect.top+doc.defaultView.scrollY-28)+'px';tools.style.left=Math.max(0,Math.min(rect.left,device-310))+'px';
        const label=doc.createElement('span');label.textContent=legacyCard()?'Existing card':selectedNode()?.name||'Element';tools.append(label);
        for(const[text,title,fn]of [['↑','Move up',()=>shift(-1)],['↓','Move down',()=>shift(1)],['⧉','Duplicate',duplicate],['×','Delete',remove]]){const b=doc.createElement('button');b.type='button';b.textContent=text;b.title=title;b.setAttribute('aria-label',title);b.addEventListener('click',e=>{e.stopPropagation();fn();});tools.append(b);}doc.body.append(tools);
    }
    let resizeObserver,interactionCleanup;
    function mountCanvas(){
        interactionCleanup?.();const doc=frame.contentDocument;if(!doc)return;
        interactionCleanup=window.DreamGridInteractions?.boot(doc,frame.contentWindow,{editing:!previewMode,width:()=>device,select:id=>select(id),resize:fitCanvas});
        const style=doc.createElement('style');style.textContent=`
          html{scroll-behavior:auto!important}body{overflow-x:hidden}a{cursor:pointer}
          .dg-editing [data-block-id],.dg-editing [data-card-id]{cursor:pointer;outline-offset:-1px}
          .dg-editing .dg-hover:not(.dg-selected){outline:1px solid #0b9e8580}
          .dg-selected{outline:2px solid #0a9f88!important;outline-offset:-2px!important}
          .dg-drop{outline:3px dashed #0a9f88!important;background-color:#6dd5bf20!important}
          [contenteditable=true]{outline:2px dashed #0a9f88!important;cursor:text!important;min-height:1em;white-space:pre-wrap}
          .dg-element-tools{position:absolute;z-index:99990;display:flex;align-items:center;background:#087f75;color:#fff;font:11px 'Segoe UI',sans-serif;height:27px;border-radius:4px 4px 0 0;box-shadow:0 3px 10px #0002;max-width:310px}
          .dg-element-tools>span{padding:0 10px;max-width:190px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}.dg-element-tools button{background:transparent;border:0;color:#fff;padding:3px 8px;cursor:pointer;font-size:14px}.dg-element-tools button:hover{background:#ffffff20}
          .dg-editing [data-container-id]:empty{min-height:120px;border:1px dashed #9abbb7;border-radius:8px;background:#eaf5f280;display:flex;align-items:center;justify-content:center}
          .dg-editing [data-container-id]:empty:after{content:'Drop an element here';color:#638a82;font:13px 'Segoe UI',sans-serif}
          .dg-empty{margin:60px auto;padding:70px 40px;max-width:720px;text-align:center;border:2px dashed #cedbdc;border-radius:16px;background:#f7faf9;font-family:'Segoe UI',sans-serif;color:#243e42}.dg-empty strong{font-size:34px;letter-spacing:-.03em;display:block}.dg-empty p{font-size:16px;color:#758b90;margin:18px 0 26px}.dg-empty button{border:0;border-radius:8px;background:#087f75;color:white;padding:13px 22px;cursor:pointer;font:600 14px 'Segoe UI',sans-serif}
        `;doc.head.append(style);doc.body.classList.toggle('dg-editing',!previewMode);
        if(!page.builder.blocks.length&&!previewMode){const empty=doc.createElement('div');empty.className='dg-empty';const title=doc.createElement('strong');title.textContent='Your next great page starts here.';const p=doc.createElement('p');p.textContent='Choose a complete layout or start adding your own sections.';const b=doc.createElement('button');b.textContent='Explore layouts';b.addEventListener('click',openTemplates);empty.append(title,p,b);doc.querySelector('.wb-page')?.append(empty);}
        for(const [key,url]of urls){
            if(key==='builder_background_image'){const bg=doc.querySelector('.wb-background');if(bg)bg.style.backgroundImage='linear-gradient(#0005,#0005),url("'+url+'")';}
            else if(key==='background_image'){const bg=doc.querySelector('.cp-bg')||doc.querySelector('[data-block-type="legacy"]');if(bg)bg.style.backgroundImage='linear-gradient(rgba(0,0,0,.28),rgba(0,0,0,.28)),url("'+url+'")';}
            else if(key.startsWith('image_')){const img=doc.querySelector('[data-card-id="'+key.slice(6)+'"] img');if(img){img.src=url;img.hidden=false;img.style.display='block';}}
            else if(key.startsWith('block_image_')){const section=doc.querySelector('[data-block-type="section"][data-block-id="'+key.slice(12)+'"]');if(section){section.style.backgroundImage='linear-gradient(#0005,#0005),url("'+url+'")';continue;}const old=doc.querySelector('[data-image-for="'+key.slice(12)+'"]');if(old){const img=doc.createElement('img');img.src=url;img.setAttribute('data-image-for',key.slice(12));img.alt=find(key.slice(12))?.node.props.alt||'';old.replaceWith(img);}}
        }
        doc.querySelectorAll('img').forEach(i=>{i.draggable=false;i.addEventListener('load',fitCanvas);});
        doc.querySelectorAll('[data-block-id]').forEach(n=>{const locked=!!window.DreamGridEditingModel.lockOwner(page.builder.blocks,n.dataset.blockId);n.draggable=!previewMode&&!locked;if(locked)n.title='Locked element';});
        const identify=e=>{const card=e.target.closest('[data-card-id]');return card?'card:'+card.dataset.cardId:e.target.closest('[data-block-id]')?.dataset.blockId;};
        doc.addEventListener('click',e=>{
            if(e.target.closest('.dg-element-tools')||e.target.closest('[contenteditable=true]'))return;
            const link=e.target.closest('a');if(link){const handled=e.defaultPrevented;e.preventDefault();if(previewMode&&!handled){const anchor=link.getAttribute('href');if(anchor?.startsWith('#')&&anchor!=='#')doc.getElementById(anchor.slice(1))?.scrollIntoView({behavior:'smooth'});else show('Open the saved page to follow external links.');}}
            if(previewMode)return;if(e.target.closest('[data-global-section]')){window.DreamGridTools?.site();return;}const id=identify(e);select(id||null,false,e.ctrlKey||e.metaKey||e.shiftKey);$('library').parentElement.classList.remove('mobile-open');
        });
        doc.addEventListener('mousemove',e=>{if(previewMode)return;doc.querySelector('.dg-hover')?.classList.remove('dg-hover');const target=e.target.closest('[data-card-id],[data-block-id]');target?.classList.add('dg-hover');});
        doc.addEventListener('dblclick',e=>{
            if(previewMode||busy||e.target.closest('.dg-element-tools'))return;if(e.target.closest('[data-global-section]'))return;const lockedId=identify(e);if(window.DreamGridEditing?.isLocked(lockedId)){show('Unlock this element before editing it.',true);return;}
            let editable=e.target.closest('[data-edit-prop]'),target,key;
            if(editable){const node=find(editable.closest('[data-block-id]').dataset.blockId)?.node;if(node){target=node.props;key=editable.dataset.editProp;}}
            else{const cardNode=e.target.closest('[data-card-id]');if(cardNode){const card=page.cards.find(c=>c.id===cardNode.dataset.cardId);editable=e.target.closest('h2,p');if(editable&&card){target=card;key=editable.tagName==='H2'?'title':'text';}}else if(e.target.closest('.cp-title')){editable=e.target.closest('.cp-title');target=page;key='title';}}
            if(!editable||!target)return;e.preventDefault();e.stopPropagation();editable.contentEditable='plaintext-only';editable.draggable=false;editable.focus();const previous=target[key]||'';
            const range=doc.createRange();range.selectNodeContents(editable);const selection=doc.defaultView.getSelection();selection.removeAllRanges();selection.addRange(range);
            editable.addEventListener('blur',()=>{const value=editable.innerText.replace(/\r/g,'').trim();editable.removeAttribute('contenteditable');if(value!==previous)mutate(()=>{target[key]=value;if(key==='brand'||key==='kicker')target.useGridName=false;},'',true);},{once:true});
        });
        doc.addEventListener('dragstart',e=>{if(previewMode||busy||e.target.closest('[contenteditable=true]')){e.preventDefault();return;}const id=identify(e);if(!id||window.DreamGridEditing?.isProtected(id)){e.preventDefault();return;}e.dataTransfer.setData('application/dreamgrid-layer',id);e.dataTransfer.effectAllowed='move';});
        doc.addEventListener('dragover',e=>{if(previewMode||busy)return;e.preventDefault();doc.querySelector('.dg-drop')?.classList.remove('dg-drop');e.target.closest('[data-block-id]')?.classList.add('dg-drop');});
        doc.addEventListener('dragleave',e=>{if(!e.relatedTarget)doc.querySelector('.dg-drop')?.classList.remove('dg-drop');});
        doc.addEventListener('drop',e=>{e.preventDefault();if(previewMode||busy)return;doc.querySelector('.dg-drop')?.classList.remove('dg-drop');const target=e.target.closest('[data-block-id]')?.dataset.blockId;const type=e.dataTransfer.getData('application/dreamgrid-component'),source=e.dataTransfer.getData('application/dreamgrid-layer');if(type)insert(type,target,true);else if(source&&target)move(source,target,true);else if(source)window.DreamGridEditing.moveToRoot(source);});
        doc.addEventListener('keydown',keyHandler);doc.addEventListener('toggle',fitCanvas,true);
        if(resizeObserver)resizeObserver.disconnect();resizeObserver=new ResizeObserver(()=>{fitCanvas();highlight();});const main=doc.querySelector('.wb-page')||doc.querySelector('.cp-wrap');if(main)resizeObserver.observe(main);
        fitCanvas();highlight();
    }
    function schedulePreview(){clearTimeout(previewTimer);previewTimer=setTimeout(renderCanvas,180);}
    async function request(action,extra={},uploads=false,signal){
        const body=new FormData();body.set('csrf',config.csrf);body.set('action',action);body.set('revision',revision);body.set('page',JSON.stringify(page));for(const[k,v]of Object.entries(extra))body.set(k,v);if(uploads)for(const[k,f]of activeFiles())body.set(k,f);
        const response=await fetch('/Other/admin-page-designer.php',{method:'POST',body,credentials:'same-origin',signal});let data;try{data=await response.json();}catch(e){throw new Error('The server response could not be read. Check your login and request size. Your edits remain here.');}if(!response.ok||!data.ok)throw new Error(data.error||'The request failed.');return data;
    }
    async function renderCanvas(){
        const token=++sequence;if(controller)controller.abort();controller=new AbortController();
        try{const data=await request('preview',{},false,controller.signal);if(token!==sequence)return;frame.onload=mountCanvas;frame.srcdoc=data.html;}catch(e){if(e.name!=='AbortError'&&token===sequence)show(e.message,true);}
    }
    function activeFiles(){const keys=new Set(['builder_background_image']);traverse(page.builder.blocks,n=>{keys.add('block_image_'+n.id);if(n.type==='legacy'){keys.add('background_image');page.cards.forEach(c=>keys.add('image_'+c.id));}});return[...files].filter(([k])=>keys.has(k));}
    function menuSync(menuPages){if(window.parent!==window)window.parent.postMessage({type:'cc-page-designer-menu-sync',pages:menuPages},location.origin);}
    function adopt(data){
        window.DreamGridEditing?.saved(page.id);selectedIds.clear();page=data.page;revision=data.revision;publishing=data.publishing;pages=data.pages;
        if(!page.builder)page.builder={version:1,theme:themeDefault(),blocks:[presets.node('legacy',{}, {},[],'Existing page')]};
        files.clear();resetUrls();undoStack.length=0;redoStack.length=0;dirty=false;selected=null;
        history.replaceState(null,'','/Other/admin-page-designer.php?id='+page.id);menuSync(data.menuPages);refresh();
    }
    function lockUI(value){busy=value;inspector.inert=value;$('library').inert=value;updateTop();}
    async function save(action='save',checked=false){
        if(busy)return false;
        if(!page.title.trim()){show('Give your page a title before saving.',true);inspectorMode='page';renderInspector();return false;}
        if(!inspector.reportValidity())return false;
        const uploads=activeFiles(),bytes=uploads.reduce((n,[k,f])=>n+f.size,0)+new TextEncoder().encode(JSON.stringify(page)).length+65536;
        if((config.postLimit>0&&bytes>config.postLimit)||uploads.length>config.maxUploads){show('These uploads exceed the server request limit. Save fewer or smaller images at a time.',true);return false;}
        if(action==='publish'&&!checked&&window.DreamGridPublishCheck)return window.DreamGridPublishCheck.run(true);
        lockUI(true);
        try{
            const data=await request(action,{},true);adopt(data);show(action==='publish'?(publishing.hasDraft?'Page published. A saved draft also remains available.':'Page published. Visitors now see this version.'):'Draft saved. Visitors still see the last published version.');return true;
        }catch(e){show(e.message,true);return false;}finally{lockUI(false);}
    }
    async function takeOffline(){
        if(busy||!publishing.published||!confirm('Take this page offline? Saved drafts and revisions will remain available.'))return;
        if(dirty && !await save())return;
        lockUI(true);try{adopt(await request('offline',{id:page.id}));show('Page taken offline. Your draft and history are preserved.');renderInspector();}catch(e){show(e.message,true);}finally{lockUI(false);}
    }
    async function openHistory(){
        if(busy||!page.id)return;selectedRevision=null;historyRequest++;$('revision-list').replaceChildren();$('revision-canvas').srcdoc='';$('revision-caption').textContent='Select a version to preview it.';$('restore-revision').disabled=true;$('history-notice').textContent='Loading saved versions…';$('history-dialog').showModal();
        try{
            const data=await request('history',{id:page.id});$('history-notice').textContent=data.items.length?'Restoring creates a private draft. Publish it separately when ready.':'Your current page will be preserved in history when you first save a draft or publish.';
            for(const item of data.items){const button=create('button',{type:'button',class:'revision-item'});button.append(create('strong',{},item.title),create('span',{},new Date(item.saved*1000).toLocaleString()),create('small',{},item.kind.toUpperCase()+(item.legacy?' · ORIGINAL PAGE FORMAT':'')));button.addEventListener('click',async()=>{
                const token=++historyRequest;selectedRevision=null;$('restore-revision').disabled=true;$('revision-caption').textContent='Loading revision…';
                try{const result=await request('revision',{id:page.id,token:item.token});if(token!==historyRequest)return;selectedRevision=item.token;document.querySelectorAll('.revision-item').forEach(b=>b.classList.toggle('active',b===button));$('revision-canvas').srcdoc=result.html;$('revision-caption').textContent=new Date(item.saved*1000).toLocaleString()+' · '+item.kind;$('restore-revision').disabled=false;}catch(e){if(token===historyRequest)$('history-notice').textContent=e.message;}
            });$('revision-list').append(button);}
        }catch(e){$('history-notice').textContent=e.message;}
    }
    async function restoreRevision(){
        if(busy||!selectedRevision)return;
        if(dirty&&!confirm('Replace your unsaved edits with this revision? Save your draft first if you want to retain them.'))return;
        lockUI(true);$('restore-revision').disabled=true;
        try{adopt(await request('restore',{id:page.id,token:selectedRevision}));$('history-dialog').close();show('Revision restored as a private draft. The live page is unchanged.');}
        catch(e){$('history-notice').textContent=e.message;$('restore-revision').disabled=false;}finally{lockUI(false);}
    }
    function openTransfer(){if(busy)return;$('transfer-notice').textContent='';$('export-page').disabled=!page.id;$('package-limit').textContent='This server accepts packages up to '+(config.packageLimit/1048576).toFixed(1)+' MB (32 MB format limit).';$('transfer-dialog').showModal();}
    async function exportPage(){
        if(busy||!page.id)return;
        if(dirty||activeFiles().length){if(!confirm('Save your edits as a private draft before exporting?'))return;if(!await save())return;}
        lockUI(true);$('export-page').disabled=true;$('transfer-notice').textContent='Packaging saved content and images…';
        try{
            const data=await request('export',{id:page.id}),blob=new Blob([JSON.stringify(data.package,null,2)],{type:'application/json'}),url=URL.createObjectURL(blob),link=create('a',{href:url,download:(page.slug||'page')+'.dreamgrid-page.json'});document.body.append(link);link.click();link.remove();setTimeout(()=>URL.revokeObjectURL(url),10000);$('transfer-notice').textContent='Package downloaded. Import it on another installation as a new draft.';
        }catch(e){$('transfer-notice').textContent=e.message;}finally{lockUI(false);$('export-page').disabled=false;}
    }
    async function importPage(){
        if(busy)return;const file=$('import-package').files[0];if(!file){$('transfer-notice').textContent='Choose a DreamGrid page package first.';return;}
        if(file.size>config.packageLimit){$('transfer-notice').textContent='This package exceeds this server’s upload limit.';return;}
        if(dirty&&!confirm('Open the imported page and discard your unsaved edits? Save your current draft first if you want to retain them.'))return;
        lockUI(true);$('import-page').disabled=true;$('transfer-notice').textContent='Checking and importing the package…';
        try{
            const body=new FormData();body.set('csrf',config.csrf);body.set('action','import');body.set('package',file);
            const response=await fetch(location.pathname,{method:'POST',body,credentials:'same-origin'}),data=await response.json();if(!data.ok)throw new Error(data.error||'Import failed.');
            adopt(data);$('transfer-dialog').close();setPanel('layers');show('Imported as a new private draft. Review it, then publish when ready.');
        }catch(e){$('transfer-notice').textContent=e.message;}finally{lockUI(false);$('import-page').disabled=false;}
    }
    async function deletePage(){if(busy||!page.id||!confirm('Delete this saved page? It will be removed from the website and Control Center.'))return;busy=true;updateTop();try{const data=await request('delete',{id:page.id});dirty=false;menuSync(data.menuPages);location.href='/Other/admin-page-designer.php';}catch(e){busy=false;updateTop();show(e.message,true);}}
    function openTemplates(){if(busy)return;const dialog=$('template-dialog');if(!dialog.open)dialog.showModal();}
    function applyRecipe(key){if(!canReplace())return;const recipe=presets.recipes[key];if(!recipe)return;if(page.builder.blocks.length&&dirty&&!confirm('Replace the current canvas with this layout? Undo will restore it.'))return;
        mutate(()=>{page.builder.blocks=recipe.blocks();inheritDefaults(page.builder.blocks);selected=null;inspectorMode='element';if(!page.title)page.title=config.gridName?(config.gridName+' · '+recipe.label):recipe.label;},'',true);$('template-dialog').close();renderLibrary();show('Layout added. Click any element to make it yours.');
    }
    function buildTemplates(){for(const[key,recipe]of Object.entries(presets.recipes)){const card=create('button',{type:'button',class:'template-card '+key,'data-template':key});const mini=create('div',{class:'template-mini'});if(key==='blank')mini.textContent='＋';else{mini.append(create('div',{class:'mini-nav'}));const hero=create('div',{class:'mini-hero'}),lines=create('div',{class:'mini-lines'});lines.append(create('b'),create('b'),create('i'),create('em'));hero.append(lines,create('div',{class:'mini-art'},'◈'));mini.append(hero);const cards=create('div',{class:'mini-cards'});cards.append(create('i'),create('i'),create('i'));mini.append(cards);}const content=create('div',{class:'template-copy'});content.append(create('small',{},recipe.category),create('strong',{},recipe.label),create('p',{},recipe.description));card.append(mini,content);card.addEventListener('click',()=>applyRecipe(key));$('template-list').append(card);}}
    function keyHandler(event){if(document.querySelector('dialog[open]'))return;if(event.key==='F1'&&window.DreamGridHelp){event.preventDefault();window.DreamGridHelp.open();return;}const editing=event.target.closest('input,textarea,select,[contenteditable]');if((event.ctrlKey||event.metaKey)&&event.key.toLowerCase()==='s'){event.preventDefault();event.target.blur?.();save();return;}if(editing)return;if(window.DreamGridEditing?.key(event))return;if((event.ctrlKey||event.metaKey)&&event.key.toLowerCase()==='z'){event.preventDefault();event.shiftKey?redo():undo();}else if((event.ctrlKey||event.metaKey)&&event.key.toLowerCase()==='d'){event.preventDefault();duplicate();}else if(event.key==='Delete'||event.key==='Backspace'){if(selected){event.preventDefault();remove();}}else if(event.key==='Escape'){select(null);$('inspector').parentElement.classList.remove('mobile-open');$('library').parentElement.classList.remove('mobile-open');}}
    document.addEventListener('keydown',keyHandler);window.addEventListener('beforeunload',e=>{if(dirty){e.preventDefault();e.returnValue='';}});
    window.addEventListener('resize',fitCanvas);
    document.querySelectorAll('[data-panel]').forEach(b=>b.addEventListener('click',()=>setPanel(b.dataset.panel)));
    document.querySelectorAll('[data-inspector-tab]').forEach(b=>b.addEventListener('click',()=>{inspectorTab=b.dataset.inspectorTab;renderInspector();}));
    document.querySelectorAll('[data-device]').forEach(b=>b.addEventListener('click',()=>{device=Number(b.dataset.device);document.querySelectorAll('[data-device]').forEach(n=>n.classList.toggle('active',n===b));frame.style.width=device+'px';fitCanvas();highlight();$('canvas-scroll').scrollTop=0;renderInspector();}));
    $('canvas-zoom').addEventListener('change',e=>{zoom=e.target.value;fitCanvas();highlight();});
    $('page-switch').addEventListener('click',()=>setPanel('pages'));$('undo').addEventListener('click',undo);$('redo').addEventListener('click',redo);$('save-page').addEventListener('click',()=>save());
    $('publish-page').addEventListener('click',()=>save('publish'));
    $('revision-history').addEventListener('click',openHistory);$('close-history').addEventListener('click',()=>{$('history-dialog').close();historyRequest++;});$('restore-revision').addEventListener('click',restoreRevision);
    $('transfer-pages').addEventListener('click',openTransfer);$('close-transfer').addEventListener('click',()=>$('transfer-dialog').close());$('export-page').addEventListener('click',exportPage);$('import-page').addEventListener('click',importPage);
    $('page-settings').addEventListener('click',()=>{inspectorMode='page';renderInspector();$('inspector').parentElement.classList.add('mobile-open');});$('theme-settings').addEventListener('click',()=>{inspectorMode='theme';renderInspector();$('inspector').parentElement.classList.add('mobile-open');});
    $('deselect').addEventListener('click',()=>{selected=null;selectedIds.clear();inspectorMode='element';renderInspector();highlight();updateTop();$('inspector').parentElement.classList.remove('mobile-open');});
    $('templates').addEventListener('click',openTemplates);$('close-templates').addEventListener('click',()=>$('template-dialog').close());$('append-section').addEventListener('click',()=>{selected=null;selectedIds.clear();insert('section',null);setPanel('add');});
    $('preview-toggle').addEventListener('click',()=>{previewMode=!previewMode;document.body.classList.toggle('preview-mode',previewMode);$('preview-toggle').textContent=previewMode?'Back to editing':'Preview';fitCanvas();schedulePreview();});
    $('open-page').addEventListener('click',()=>{if(page.id)window.open('/Other/custom-page.php?id='+encodeURIComponent(page.id)+'&preview=1','_blank','noopener');});
    inspector.addEventListener('submit',e=>{e.preventDefault();save();});
    function canInsert(id){if(id&&window.DreamGridEditing?.isLocked(id)){show('Unlock the destination before adding content.',true);return false;}return true;}
    function canReplace(){if(window.DreamGridEditingModel.hasLocks(page.builder.blocks)){show('Unlock protected elements before replacing the page.',true);return false;}return true;}
    window.DreamGridBuilder={config,presets,copy,create,request,mutate,refresh,select,show,group,field,save,lockUI,activeFiles,adopt,canInsert,canReplace,snapshot,restore,get dirty(){return dirty;},get revision(){return revision;},get selectedId(){return selected;},get selection(){return [...selectedIds].filter(id=>find(id));},setSelection(ids){selectedIds=new Set(ids);selected=ids.at(-1)||null;renderInspector();updateTop();highlight();renderLibrary();},get multiMode(){return multiMode;},setMultiMode(value){multiMode=value;},get pages(){return pages;},get page(){return page;},get selected(){return selectedNode();},get busy(){return busy;},files,resetUrls,traverse,total,get device(){return device;},schedulePreview,renderInspector,insert,find};
    buildTemplates();renderLibrary();renderInspector();updateTop();menuSync(config.menuPages);renderCanvas();if(!page.id&&!page.builder.blocks.length)openTemplates();
})();
