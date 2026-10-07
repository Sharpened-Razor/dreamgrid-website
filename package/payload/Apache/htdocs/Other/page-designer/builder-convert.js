// Explicit, reversible conversion. No page is migrated during installation or loading.
window.DreamGridConvertLegacy = (page, presets, pendingCards=[]) => {
    const t=page.typography, node=presets.node, imagePairs=[];
    const style=(values,prefix)=>({font:values[prefix+'Font'],fontSize:values[prefix+'Size'],bold:values[prefix+'Bold'],italic:values[prefix+'Italic'],color:values[prefix+'Color'],align:values[prefix+'Align']});
    const title=node('heading',{text:page.title,tag:'h1',fluidTitle:true},{...style(t,'title'),marginBottom:26},[],'Page title');
    const cards=page.cards.map((card,index)=>{
        let ct=card.typography;
        if(!card.customTypography){ct={};for(const [key,source]of Object.entries({title:'cardTitle',text:'cardText',button:'button'}))for(const suffix of ['Font','Size','Bold','Italic','Color','Align'])ct[key+suffix]=t[source+suffix];}
        const elements=[];
        if(card.image||pendingCards.includes(card.id)){const image=node('image',{image:card.image,alt:''},{height:card.picture.height,fit:card.picture.fit==='stretch'?'fill':card.picture.fit,imageX:card.picture.x,imageY:card.picture.y,radius:0,marginBottom:5},[],'Card '+(index+1)+' image');elements.push(image);imagePairs.push({cardId:card.id,nodeId:image.id});}
        if(card.title)elements.push(node('heading',{text:card.title,tag:'h2'},{...style(ct,'title')},[],'Card '+(index+1)+' title'));
        if(card.text)elements.push(node('text',{text:card.text},{...style(ct,'text'),lineHeight:1.6},[],'Card '+(index+1)+' text'));
        if(card.linkUrl||card.linkLabel)elements.push(node('button',{label:card.linkLabel||'OPEN LINK',url:card.linkUrl||'',newTab:card.newTab,disabled:!card.linkUrl},{...style(ct,'button'),background:'#1d1e17',borderColor:'#8e7127',borderWidth:1,radius:0,marginTop:5},[],'Card '+(index+1)+' button'));
        return node('section',{}, {background:card.backgroundColor,padding:18,paddingY:18,gap:9,gridSpan:Math.min(card.span,page.columns),borderColor:'#79642d',borderWidth:1,radius:0,shadow:'strong'},elements,card.title||'Card '+(index+1));
    });
    const grid=node('columns',{}, {columns:page.columns,tabletColumns:2,mobileColumns:1,gap:18},cards,'Converted card layout');
    const block=node('section',{image:page.backgroundImage||''},{background:page.backgroundColor,padding:18,paddingY:34,gap:0,width:'wide',backgroundFit:page.backgroundFit,backgroundWidth:page.backgroundWidth,backgroundHeight:page.backgroundHeight,backgroundLock:page.backgroundLock,imageX:page.backgroundPositionX,imageY:page.backgroundPositionY},[title,grid],'Converted page content');
    return {block,imagePairs};
};
