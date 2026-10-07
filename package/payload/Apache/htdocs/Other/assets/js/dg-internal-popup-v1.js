(function(){
    'use strict';
    // One controller per document. Child workspaces use the same implementation.
    const existing = window.__dgInternalPopupControllerV1;
    if (existing) { existing.install(); return; }
    let initialized = false;
    Object.defineProperty(window, '__dgInternalPopupV1', {
        configurable:true,
        get:function(){
            return initialized && window.dgOpenInternalPopup === openPopup &&
                window.dgCloseInternalPopup === closePublic;
        }
    });
    const nativeOpen = window.open.bind(window);
    const wiredDocuments = new WeakSet();
    let backdrop, dialog, title, frame, closeButton;
    let opened = false, dirty = false, previousFocus = null, previousOverflow = '';
    let activeUrl = '', settings = {}, generation = 0;

    function resolve(raw, base){
        try { return new URL(String(raw || ''), base || window.location.href); }
        catch (_) { return null; }
    }
    function internal(url){
        return url && /^(http:|https:)$/.test(url.protocol) && url.origin === window.location.origin;
    }
    function titleFor(url){
        const file = url.pathname.split('/').pop().toLowerCase();
        const names = {
            'admin-region-edit.php':'Edit Region', 'admin-region-map.php':'Region Map',
            'admin-region-statistics.php':'Region Statistics', 'admin-region-log.php':'Region Log',
            'admin-create-region.php':'Create Region', 'admin-region-global-map.php':'Global Map'
        };
        return (names[file] || 'Region Manager') + (url.searchParams.get('region') ? ' — ' + url.searchParams.get('region') : '');
    }
    function refreshHost(){
        if (typeof window.refreshMap === 'function') return window.refreshMap();
        window.location.reload();
    }
    function closePopup(forceRefresh){
        if (!opened) return;
        const refresh = forceRefresh === true || dirty;
        const onClose = settings.onClose;
        const closedUrl = activeUrl;
        opened = false;
        dirty = false;
        activeUrl = '';
        settings = {};
        dialog.classList.remove('open');
        backdrop.classList.remove('open');
        dialog.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('dg-internal-popup-open-v1');
        document.body.style.overflow = previousOverflow;
        frame.src = 'about:blank';
        if (previousFocus && previousFocus.isConnected) previousFocus.focus();
        previousFocus = null;
        if (refresh && window.parent !== window) {
            window.parent.postMessage({type:'dg-region-changed'}, window.location.origin);
        }
        Promise.resolve().then(function(){
            if (typeof onClose === 'function') return onClose({changed:refresh, url:closedUrl});
            if (refresh) return refreshHost();
        }).catch(function(error){ console.error('Popup refresh failed:', error); });
        document.dispatchEvent(new CustomEvent('dg:popup-closed', {detail:{changed:refresh,url:closedUrl}}));
    }
    function escape(event){
        if (!opened || event.defaultPrevented || event.key !== 'Escape') return;
        const doc = event.currentTarget;
        // An active confirmation gets Escape first; do not dismiss its workspace.
        if (doc.querySelector('.au-modal-overlay:not([hidden]), #dgRegionActionBackdropV1.open, dialog[open]')) return;
        event.preventDefault();
        event.stopPropagation();
        closePopup(false);
    }
    function routeBlankLink(event, base, opener){
        if (event.defaultPrevented || event.button > 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const anchor = event.target && event.target.closest ? event.target.closest('a[target="_blank"]') : null;
        if (!anchor || anchor.hasAttribute('download')) return;
        const url = resolve(anchor.href, base);
        if (!internal(url)) return;
        event.preventDefault();
        opener(url.href, (anchor.textContent || '').trim() || undefined);
    }
    function wireChild(){
        if (!opened) return;
        try {
            const child = frame.contentWindow, doc = child.document;
            if (!doc || child.location.href === 'about:blank') return;
            if (settings.returnPath && child.location.pathname === settings.returnPath) {
                closePopup(false);
                return;
            }
            if (doc.querySelector('[data-dg-region-changed]')) dirty = true;
            const childGeneration = generation;
            const isCurrent = function(){ return opened && generation === childGeneration && frame.contentWindow.document === doc; };
            child.close = function(){ if (isCurrent()) closePopup(false); };
            child.open = function(raw, target, features){
                if (!isCurrent()) return null;
                const url = resolve(raw, child.location.href);
                if (!internal(url)) return nativeOpen(url ? url.href : raw, target, features);
                const opener = typeof child.dgOpenInternalPopup === 'function' ? child.dgOpenInternalPopup : openPopup;
                return opener(url.href);
            };
            if (!wiredDocuments.has(doc)) {
                doc.addEventListener('keydown', function(event){ if (isCurrent()) escape(event); });
                doc.addEventListener('click', function(event){
                    if (!isCurrent()) return;
                    routeBlankLink(event, child.location.href, function(url, label){
                        const opener = typeof child.dgOpenInternalPopup === 'function' ? child.dgOpenInternalPopup : openPopup;
                        return opener(url, label);
                    });
                });
                wiredDocuments.add(doc);
            }
            if (typeof settings.onLoad === 'function') {
                Promise.resolve().then(function(){
                    if (isCurrent() && typeof settings.onLoad === 'function') return settings.onLoad(child);
                }).catch(function(error){ console.error('Popup load callback failed:', error); });
            }
        } catch (_) {
            // Statistics may redirect to the region's own port (a different origin).
        }
    }
    function build(){
        if (dialog) return;
        const style = document.createElement('style');
        style.id = 'dgInternalPopupStyleV1';
        style.textContent = `
#dgInternalPopupBackdropV1{
    position:fixed;
    inset:0;
    z-index:2147483000;
    display:none;
    background:rgba(0,0,0,.76);
    backdrop-filter:blur(2px);
}

#dgInternalPopupBackdropV1.open{
    display:block;
}

#dgInternalPopupV1{
    position:fixed;
    z-index:2147483001;
    top:50%;
    left:50%;
    width:min(1420px,calc(100vw - 46px));
    height:min(94vh,1080px);
    transform:translate(-50%,-50%);
    display:none;
    grid-template-rows:48px minmax(0,1fr);
    overflow:hidden;

    border:
        1px solid #b58a24;

    border-radius:
        12px;

    background:
        #080b0d;

    box-shadow:
        0 28px 90px rgba(0,0,0,.88),
        0 0 0 1px rgba(226,182,79,.12),
        inset 0 1px 0 rgba(255,255,255,.04);
}

#dgInternalPopupV1.open{
    display:grid;
}

#dgInternalPopupHeadV1{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;

    padding:
        0 11px
        0 18px;

    border-bottom:
        1px solid #6c541c;

    background:
        linear-gradient(
            180deg,
            #20231d 0%,
            #111613 100%
        );
}

#dgInternalPopupTitleV1{
    overflow:hidden;
    white-space:nowrap;
    text-overflow:ellipsis;

    color:
        #efbd3e;

    font-size:
        14px;

    font-weight:
        900;

    letter-spacing:
        .45px;

    text-transform:
        uppercase;
}

#dgInternalPopupCloseV1{
    width:34px;
    height:30px;

    display:flex;
    align-items:center;
    justify-content:center;

    padding:0;

    border:
        1px solid #79601f;

    border-radius:
        6px;

    background:
        linear-gradient(
            180deg,
            #292c27,
            #131613
        );

    color:
        #f1c452;

    font-size:
        17px;

    font-weight:
        900;

    cursor:
        pointer;
}

#dgInternalPopupCloseV1:hover{
    border-color:#e2b64f;
    color:#fff1bd;
}

#dgInternalPopupFrameV1{
    width:100%;
    height:100%;

    display:block;

    border:0;

    background:
        #080b0d;
}

body.dg-internal-popup-open-v1{
    overflow:hidden !important;
}

@media(max-width:900px){

    #dgInternalPopupV1{
        width:calc(100vw - 18px);
        height:calc(100vh - 18px);
    }
}

#dgInternalPopupV1[data-layout=map]{width:min(1500px,calc(100vw - 28px));height:min(980px,calc(100vh - 28px));}
#dgInternalPopupV1[data-layout=create]{width:min(1180px,calc(100vw - 40px));height:min(900px,calc(100vh - 40px));}`;
        backdrop = document.createElement('div');
        backdrop.id = 'dgInternalPopupBackdropV1';
        dialog = document.createElement('div');
        dialog.id = 'dgInternalPopupV1';
        dialog.setAttribute('role','dialog');
        dialog.setAttribute('aria-modal','true');
        dialog.setAttribute('aria-hidden','true');
        dialog.setAttribute('aria-labelledby','dgInternalPopupTitleV1');
        const head = document.createElement('div');
        head.id = 'dgInternalPopupHeadV1';
        title = document.createElement('div');
        title.id = 'dgInternalPopupTitleV1';
        closeButton = document.createElement('button');
        closeButton.id = 'dgInternalPopupCloseV1';
        closeButton.type = 'button';
        closeButton.textContent = '×';
        closeButton.setAttribute('aria-label','Close popup');
        frame = document.createElement('iframe');
        frame.id = 'dgInternalPopupFrameV1';
        frame.name = frame.id;
        frame.title = 'Region Manager';
        head.append(title,closeButton);
        dialog.append(head,frame);
        document.head.appendChild(style);
        document.body.append(backdrop,dialog);
        closeButton.addEventListener('click',function(){ closePopup(false); });
        backdrop.addEventListener('click',function(){ closePopup(false); });
        frame.addEventListener('load',wireChild);
        dialog.addEventListener('keydown',function(event){
            if (event.key === 'Tab' && event.target === closeButton && event.shiftKey) {
                event.preventDefault();
                frame.focus();
            }
        });
    }
    function openPopup(raw, requestedTitle, options){
        const url = resolve(raw);
        if (!url) return null;
        if (!internal(url)) return nativeOpen(url.href,'_blank','noopener');
        const file = url.pathname.split('/').pop().toLowerCase();
        if (file === 'admin-region-edit.php' || file === 'admin-create-region.php') url.searchParams.set('embedded','1');
        build();
        if (!opened) {
            previousFocus = document.activeElement;
            previousOverflow = document.body.style.overflow;
        }
        opened = true;
        generation++;
        const ownGeneration = generation;
        activeUrl = url.href;
        settings = Object.assign({}, options || {});
        if (file === 'admin-create-region.php' && !settings.returnPath) settings.returnPath = '/Other/admin-regions.php';
        dialog.dataset.layout = settings.layout || (file === 'admin-region-global-map.php' ? 'map' : file === 'admin-create-region.php' ? 'create' : 'default');
        title.textContent = requestedTitle || titleFor(url);
        frame.title = title.textContent;
        frame.src = url.href;
        backdrop.classList.add('open');
        dialog.classList.add('open');
        dialog.setAttribute('aria-hidden','false');
        document.body.classList.add('dg-internal-popup-open-v1');
        closeButton.focus();
        return {
            focus:function(){ if (opened && generation === ownGeneration) closeButton.focus(); },
            close:function(){ if (generation === ownGeneration) closePopup(false); },
            get closed(){ return !opened || generation !== ownGeneration; },
            opener:window
        };
    }
    function closePublic(){ closePopup(true); }
    function routedOpen(raw,target,features){
        const url = resolve(raw);
        return internal(url) ? openPopup(url.href) : nativeOpen(raw,target,features);
    }
    function install(){
        if (!document.body) return;
        if (!initialized) {
            try { build(); }
            catch (error) {
                for (const id of ['dgInternalPopupStyleV1','dgInternalPopupBackdropV1','dgInternalPopupV1']) {
                    const element = document.getElementById(id);
                    if (element) element.remove();
                }
                backdrop = dialog = title = frame = closeButton = null;
                throw error;
            }
            document.addEventListener('keydown',escape);
            document.addEventListener('focusin',function(event){
                if (opened && !dialog.contains(event.target) &&
                    !(event.target.closest && event.target.closest('.au-modal-overlay, #dgRegionActionModalV1'))) closeButton.focus();
            });
            document.addEventListener('click',function(event){ routeBlankLink(event,window.location.href,openPopup); });
            window.addEventListener('message',function(event){
                if (!opened || event.origin !== window.location.origin || event.source !== frame.contentWindow) return;
                if (event.data && event.data.type === 'dg-region-changed') dirty = true;
            });
            initialized = true;
        }
        // Reinstall public APIs without wrapping window.open or registering listeners again.
        window.dgOpenInternalPopup = openPopup;
        window.dgCloseInternalPopup = closePublic;
        window.open = routedOpen;
    }
    window.__dgInternalPopupControllerV1 = {install};
    if (document.body) install();
    else document.addEventListener('DOMContentLoaded', install, {once:true});
})();
