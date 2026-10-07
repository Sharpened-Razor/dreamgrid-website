(function(){'use strict';
 const bridge=()=>window.DreamGridBuilder;
 function button(parent,label,fn,disabled=false){const B=bridge(),b=B.create('button',{type:'button',class:'small-button full'},label);b.disabled=disabled;b.addEventListener('click',()=>{if(B.busy||b.disabled)return;fn();});parent.append(b);return b;}
 function element(root,node,tab){
  const B=bridge();if(!B||node.type==='legacy')return;
  if(tab==='style'){
   const g=B.group(root,'Motion & hover');
   B.field(g,node.props,'motion','Entrance effect','select',{full:true,choices:{none:'None',fade:'Fade in','slide-up':'Slide up','slide-left':'Slide from right','slide-right':'Slide from left'}});
   B.field(g,node.props,'duration','Duration (milliseconds)','number',{min:100,max:2000,default:600,full:true});
   B.field(g,node.props,'delay','Delay (milliseconds)','number',{min:0,max:2000,default:0,full:true});
   B.field(g,node.props,'hover','Hover effect','select',{full:true,choices:{none:'None',lift:'Lift',zoom:'Image zoom',glow:'Soft glow'}});
   g.append(B.create('p',{class:'inspector-note full'},'Effects run in Preview and on the published page. Editing keeps every element visible. Reduced motion is always respected; phone motion is controlled in Page settings. Image zoom affects images inside this element.'));
  }
  if(tab!=='content')return;
  if(node.type==='navigation'){const g=B.group(root,'Sticky navigation');B.field(g,node.props,'sticky','Keep navigation at the top while scrolling','checkbox');g.append(B.create('p',{class:'inspector-note full'},'For a page-wide sticky menu, place Navigation at the top level or use the global header. Nested menus stay within their containing section.'));}
  if(!['tabs','accordion'].includes(node.type))return;
  const g=B.group(root,node.type==='tabs'?'Tab panels':'Accordion panels');
  B.field(g,node.props,'defaultPanel','Initially selected panel','number',{min:1,max:Math.max(1,node.children.length),default:1,full:true});
  if(node.type==='accordion'){B.field(g,node.props,'singleOpen','Open one panel at a time','checkbox');B.field(g,node.props,'startClosed','Start with all panels closed','checkbox');}
  g.append(B.create('p',{class:'inspector-note full'},'Each panel is native content. Its Layer name becomes the tab label or accordion heading. Select a panel in Layers to add any content. Editing shows every panel; Preview runs the interaction.'));
  node.children.forEach((child,index)=>{
   const locked=!!window.DreamGridEditingModel?.lockOwner(B.page.builder.blocks,child.id);
   const input=B.field(g,child,'name','Panel '+(index+1)+' label','text',{full:true,maxlength:80});input.disabled=locked;
   button(g,'Edit panel '+(index+1),()=>B.select(child.id,true));
   button(g,'Move panel '+(index+1)+' earlier',()=>{if(window.DreamGridEditing?.isProtected(child.id))return;B.mutate(()=>{window.DreamGridEditingModel.shift(B.page.builder.blocks,[child.id],-1);},'',true);B.refresh();},index===0||locked);
  });
  button(g,'Add panel',()=>{
   if(!B.canInsert(node.id))return;
   const child=B.presets.defaults.section();child.name='Panel '+(node.children.length+1);
   try{const copy=B.copy(B.page.builder.blocks);window.DreamGridEditingModel.append(copy,[child],node.id);}catch(e){B.show(e.message,true);return;}
   if(node.children.length>=24){B.show('Tabs and accordions support up to 24 panels.',true);return;}
   B.mutate(()=>node.children.push(child),'',true);B.refresh();B.select(child.id);
  },node.children.length>=24);
 }
 function page(root){
  const B=bridge();if(!B)return;
  const settings=B.page.builder.interactions||{disableMobile:true,backToTop:false},adopt=()=>B.page.builder.interactions=settings;
  const g=B.group(root,'Page interactions');
  B.field(g,settings,'disableMobile','Disable animations on phones','checkbox',{onChange:adopt});
  B.field(g,settings,'backToTop','Show a back-to-top button','checkbox',{onChange:adopt});
  g.append(B.create('p',{class:'inspector-note full'},'The top button appears after scrolling on the published page. Reduced-motion preferences also disable smooth scrolling.'));
  const s=B.page.seo||{title:'',description:'',shareTitle:'',shareDescription:'',image:'',imageAlt:'',noindex:false};
  const seo=B.group(root,'Search & sharing');let title,description;
  const update=()=>{B.page.seo=s;if(title)title.textContent=s.shareTitle||s.title||B.page.title;if(description)description.textContent=s.shareDescription||s.description||'Add a description for search results and shared links.';};
  const field=(key,label,type='text',extra={})=>B.field(seo,s,key,label,type,{full:true,onChange:update,...extra});
  field('title','Search title (blank uses page title)','text',{maxlength:120});
  field('description','Search description','textarea',{maxlength:500});
  field('shareTitle','Sharing title (optional)','text',{maxlength:120});
  field('shareDescription','Sharing description (optional)','textarea',{maxlength:500});
  const images=new Map();const add=(file,label)=>{if(/^[a-f0-9]{24}\.(png|jpg|gif|webp)$/.test(file||''))images.set(file,label);};
  add(B.page.backgroundImage,'Existing page background');B.page.cards.forEach((c,i)=>add(c.image,c.title||'Card '+(i+1)));add(B.page.builder.theme.backgroundImage,'Page background');B.traverse(B.page.builder.blocks,n=>add(n.props.image,n.name));add(s.image,'Current sharing image');
  field('image','Sharing image from this page','select',{choices:Object.fromEntries([['','No sharing image'],...[...images].map(([file,label])=>[file,label])])});
  field('imageAlt','Sharing image description','text',{maxlength:300});
  field('noindex','Ask search engines not to index this page','checkbox');
  const card=B.create('div',{class:'sharing-preview full',style:'padding:16px;border:1px solid var(--edge);border-radius:10px;background:var(--input)'});
  title=B.create('strong',{},s.shareTitle||s.title||B.page.title);description=B.create('p',{class:'inspector-note'},s.shareDescription||s.description||'Add a description for search results and shared links.');card.append(B.create('small',{},'SHARING TEXT PREVIEW'),B.create('br'),title,description);seo.append(card);
  seo.append(B.create('p',{class:'inspector-note full'},'Add an image to the page and Save draft before selecting it here. Sharing addresses follow the current installation automatically. Private pages and previews stay out of indexing; noindex is a search preference, not access control. Social sites choose their final preview and may cache it.'));
 }
 window.DreamGridInteractionEditor={element,page};
})();
