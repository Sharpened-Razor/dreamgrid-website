(function(root){'use strict';
 function boot(doc,win,options={}){
  const editing=!!options.editing,disposers=[],listen=(el,event,fn)=>{el.addEventListener(event,fn);disposers.push(()=>el.removeEventListener(event,fn));};
  const reduced=win.matchMedia?.('(prefers-reduced-motion: reduce)'),mobile=win.matchMedia?.('(max-width:600px)');
  const motionOff=()=>editing||!!reduced?.matches||(doc.body.dataset.motionMobile==='off'&&(options.width?(typeof options.width==='function'?options.width():options.width)<=600:mobile?.matches));
  doc.querySelectorAll('[data-interactive]').forEach(group=>{
   const children=Array.from(group.children);
   if(group.dataset.interactive==='tabs'){
    const list=children.find(el=>el.classList.contains('wb-tablist')),panels=children.filter(el=>el.classList.contains('wb-tabpanel'));
    const buttons=list?Array.from(list.children):[];if(!buttons.length)return;
    if(editing){buttons.forEach((b,i)=>listen(b,'click',e=>{e.stopPropagation();options.select?.(panels[i]?.querySelector('[data-block-id]')?.dataset.blockId);}));return;}
    group.dataset.enhanced='1';list.setAttribute('role','tablist');
    const activate=(index,focus=false)=>{buttons.forEach((b,i)=>{b.setAttribute('aria-selected',String(index===i));b.tabIndex=index===i?0:-1;panels[i].hidden=index!==i;});if(focus)buttons[index].focus();options.resize?.();};
    buttons.forEach((b,i)=>{b.setAttribute('role','tab');panels[i].setAttribute('role','tabpanel');panels[i].setAttribute('aria-labelledby',b.id);panels[i].tabIndex=0;
     listen(b,'click',()=>activate(i));listen(b,'keydown',e=>{let next;if(e.key==='ArrowRight')next=(i+1)%buttons.length;else if(e.key==='ArrowLeft')next=(i+buttons.length-1)%buttons.length;else if(e.key==='Home')next=0;else if(e.key==='End')next=buttons.length-1;else return;e.preventDefault();activate(next,true);});});
    activate(Math.min(buttons.length-1,Math.max(0,Number(group.dataset.defaultPanel||1)-1)));
   }else{
    const details=children.filter(el=>el.classList.contains('wb-accordion-item'));
    details.forEach(item=>{if(editing){item.open=true;const summary=Array.from(item.children).find(el=>el.tagName==='SUMMARY');if(summary)listen(summary,'click',e=>e.preventDefault());}
     else listen(item,'toggle',()=>{if(item.open&&group.dataset.singleOpen==='1')details.forEach(other=>{if(other!==item)other.open=false;});options.resize?.();});});
   }
  });
  // Unenhanced content always remains visible. Never hide while waiting for an observer.
  const nodes=Array.from(doc.querySelectorAll('[data-motion]'));let observer;
  const sync=()=>{observer?.disconnect();nodes.forEach(el=>el.classList.remove('wb-animate'));doc.body.classList.toggle('wb-motion-off',motionOff());
   if(motionOff()||!win.IntersectionObserver)return;
   observer=new win.IntersectionObserver(entries=>entries.forEach(entry=>{if(!entry.isIntersecting)return;const el=entry.target;el.style.setProperty('--wb-duration',(Number(el.dataset.duration)||600)+'ms');el.style.setProperty('--wb-delay',(Number(el.dataset.delay)||0)+'ms');el.classList.add('wb-animate');observer.unobserve(el);}),{threshold:0.08});nodes.forEach(el=>observer.observe(el));};
  sync();[reduced,mobile].forEach(query=>{if(!query)return;if(query.addEventListener){query.addEventListener('change',sync);disposers.push(()=>query.removeEventListener('change',sync));}else if(query.addListener){query.addListener(sync);disposers.push(()=>query.removeListener(sync));}});
  const top=doc.querySelector('[data-back-to-top]');if(top&&!editing){const update=()=>top.hidden=(win.scrollY||doc.documentElement.scrollTop||0)<300;listen(win,'scroll',update);update();listen(top,'click',()=>{win.scrollTo({top:0,behavior:motionOff()?'auto':'smooth'});const main=doc.querySelector('main');if(main){main.setAttribute('tabindex','-1');main.focus({preventScroll:true});}});}
  else if(top)top.hidden=true;
  // Reveal tab/accordion ancestors of an anchor before scrolling, including nested groups.
  const reveal=target=>{let node=target;while(node){if(node.classList?.contains('wb-tabpanel')){const g=node.parentElement;if(!editing)g.querySelector('[data-tab-index="'+node.dataset.panelIndex+'"]')?.click();}if(node.tagName==='DETAILS')node.open=true;node=node.parentElement;}};
  listen(doc,'click',e=>{const link=e.target.closest?.('a[href^="#"]');if(!link)return;const value=link.getAttribute('href');let target;try{target=doc.getElementById(decodeURIComponent(value.slice(1)));}catch{return;}if(target){reveal(target);if(!editing){e.preventDefault();target.scrollIntoView({behavior:motionOff()?'auto':'smooth'});}}});
  if(win.location?.hash){let target;try{target=doc.getElementById(decodeURIComponent(win.location.hash.slice(1)));}catch{}if(target){reveal(target);target.scrollIntoView({behavior:'auto'});}}
  return ()=>{observer?.disconnect();disposers.forEach(fn=>fn());};
 }
 root.DreamGridInteractions={boot};
 if(root.document&&!root.document.getElementById('designer-config')){const run=()=>boot(root.document,root);if(root.document.readyState==='loading')root.document.addEventListener('DOMContentLoaded',run,{once:true});else run();}
})(typeof window==='undefined'?globalThis:window);
