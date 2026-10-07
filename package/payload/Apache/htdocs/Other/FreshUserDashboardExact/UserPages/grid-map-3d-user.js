/* Compatibility entry: the authoritative viewer is shared. */
(function(){
 window.AG_3D_MAP = Object.assign({}, window.AG_3D_MAP, {userMode:true});
 if(window.__dg3dSharedEngine) return;
 const pipeline=document.createElement('script');
 pipeline.src='/Other/assets/js/dg-3d-pipeline.js';
 pipeline.onload=function(){const engine=document.createElement('script');engine.src='/Other/assets/js/grid-map-3d.js';document.head.appendChild(engine);};
 document.head.appendChild(pipeline);
})();
