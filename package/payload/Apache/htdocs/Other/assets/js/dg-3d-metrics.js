/* Read-only coverage; body accounting is opt-in with ?measure=1. */
(function(){
'use strict';
if(window.dg3dMetrics)return;
const measure=new URLSearchParams(location.search).get('measure')==='1';
const state={region:'',start:0,requests:0,bytes:0,wireBytes:0,pending:0,failed:0,types:{},cache:{},milestones:{},coverage:{}};
let panel;
function draw(){
 if(!panel){const host=document.querySelector('.ag3d-v1-note');if(!host)return;
 const details=document.createElement('details'),summary=document.createElement('summary');summary.textContent='Loading diagnostics';
 panel=document.createElement('pre');panel.id='dg3d-measurements';panel.style.cssText='white-space:pre-wrap;font:11px monospace;color:inherit';
 details.append(summary,panel);host.append(details);details.open=measure;}
 panel.textContent=JSON.stringify({...state,measurement:measure?'decoded response bodies; wire bytes from Resource Timing':'headers only',elapsedSeconds:state.start?Math.round((performance.now()-state.start)/1000):0},null,2);
}
window.dg3dMetrics={
 start(region){Object.assign(state,{region,start:performance.now(),requests:0,bytes:0,wireBytes:0,pending:0,failed:0,types:{},cache:{},milestones:{},coverage:{}});draw();},
 mark(name,detail){if(state.start&&!state.milestones[name])state.milestones[name]={seconds:+((performance.now()-state.start)/1000).toFixed(3),detail};},
 coverage(value){state.coverage=value;},
 cache(kind){state.cache[kind]=(state.cache[kind]||0)+1;}
};
const pattern=/grid-map-3d-(material-properties|objects|mesh|shape|texture|material|terrain-settings|terrain|data)(?:-user)?\.php/;
const original=window.fetch.bind(window);
window.fetch=async function(input,options){
 const url=String(input instanceof Request?input.url:input),match=url.match(pattern);
 if(!match||!state.start)return original(input,options);
 const generation=state.start,kind=match[1]==='texture'&&/[?&]sculpt=/.test(url)?'sculpt':match[1];
 const type=state.types[kind]||(state.types[kind]={requests:0,bytes:0,milliseconds:0,hits:0,misses:0,failed:0});
 const started=performance.now();state.requests++;state.pending++;type.requests++;
 try{
 const response=await original(input,options);
 if(generation===state.start){
 const cache=response.headers.get('X-Map3D-Cache')||response.headers.get('X-Map3D-Preview')||response.headers.get('X-Australia-3D-Cache')||'';
 if(cache.includes('HIT'))type.hits++;else if(cache.includes('MISS'))type.misses++;
 if(!response.ok){state.failed++;type.failed++;}}
 const accounting=measure?response.clone().arrayBuffer().then(b=>b.byteLength):Promise.resolve(Number(response.headers.get('Content-Length'))||0);
 accounting.then(bytes=>{if(generation===state.start){state.bytes+=bytes;type.bytes+=bytes;type.milliseconds+=Math.round(performance.now()-started);}})
 .catch(()=>{}).finally(()=>{if(generation===state.start)state.pending--;});
 return response;
 }catch(error){if(generation===state.start){state.failed++;type.failed++;state.pending--;}throw error;}
};
if(typeof PerformanceObserver!=='undefined')new PerformanceObserver(list=>{
 for(const entry of list.getEntries())if(state.start&&entry.startTime>=state.start&&pattern.test(entry.name))state.wireBytes+=entry.transferSize||0;
}).observe({type:'resource',buffered:true});
document.addEventListener('click',event=>{const button=event.target.closest('.result-button');if(button)window.dg3dMetrics.start(button.textContent.trim());},true);
const info=console.info.bind(console);
console.info=function(...args){info(...args);const text=args.join(' ');if(text.includes('REAL OPENSIM TERRAIN V1'))window.dg3dMetrics.mark('terrain',text);};
document.addEventListener('DOMContentLoaded',draw,{once:true});
setInterval(()=>{if(!document.hidden)draw();},1000);
})();
