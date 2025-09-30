// feedback.js - Sistema de toasts y indicadores de carga reutilizable
(function(){
    const rootId = 'app-toast-container';
    function ensureContainer(){
        let c = document.getElementById(rootId);
        if(!c){
            c = document.createElement('div');
            c.id = rootId;
            c.setAttribute('aria-live','polite');
            c.style.cssText='position:fixed;top:1.25rem;right:1.25rem;display:flex;flex-direction:column;gap:.75rem;z-index:9999;max-width:320px;';
            document.body.appendChild(c);
        }
        return c;
    }
    const ICONS={success:'fa-check-circle',error:'fa-circle-exclamation',info:'fa-circle-info',warning:'fa-triangle-exclamation'};
    window.showToast = function(type, message, opts={}){
        const container = ensureContainer();
        const toast = document.createElement('div');
        const t = type && ICONS[type] ? type : 'info';
        const autoClose = opts.autoClose===false? false : true;
        const duration = typeof opts.duration==='number'? opts.duration : 4200;
        toast.className = 'app-toast app-toast-'+t;
        toast.role='status';
        toast.style.cssText='background:var(--bg-primary,#fff);color:var(--text-primary,#222);border:1px solid var(--border-color,#e2e8f0);padding:.85rem 1rem .85rem .85rem;display:flex;align-items:flex-start;gap:.75rem;border-radius:12px;box-shadow:var(--shadow-lg,0 8px 24px rgba(0,0,0,.15));font-size:.9rem;line-height:1.4;position:relative;overflow:hidden;';
        toast.innerHTML = '\n            <div style="flex-shrink:0;font-size:1.2rem;line-height:1;color:var(--primary-500,#10b981);">\n                <i class="fas '+ICONS[t]+'"></i>\n            </div>\n            <div style="flex:1 1 auto;">'+message+'</div>\n            <button type="button" aria-label="Cerrar" style="background:none;border:none;color:inherit;cursor:pointer;font-size:1rem;line-height:1;padding:.25rem .5rem;">&times;</button>\n        ';
        const closeBtn = toast.querySelector('button');
        closeBtn.addEventListener('click', ()=> removeToast(toast));
        container.appendChild(toast);
        requestAnimationFrame(()=>{ toast.style.transition='transform .4s var(--transition-easing,cubic-bezier(0.4,0,0.2,1)), opacity .4s'; toast.style.transform='translateY(0)'; toast.style.opacity='1'; });
        if(autoClose){ setTimeout(()=> removeToast(toast), duration); }
        function removeToast(el){ el.style.opacity='0'; el.style.transform='translateY(-10px)'; setTimeout(()=>{ el.remove(); }, 380); }
        return toast;
    };

    // Indicadores de carga global simples
    let globalLoadingCount = 0; let loaderEl=null;
    function ensureLoader(){
        if(loaderEl) return loaderEl;
        loaderEl = document.createElement('div');
        loaderEl.id='global-loading-overlay';
        loaderEl.style.cssText='position:fixed;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;z-index:9998;opacity:0;transition:opacity .3s;';
        loaderEl.innerHTML='<div style="background:var(--bg-primary,#fff);padding:1.25rem 1.5rem;border-radius:14px;display:flex;flex-direction:column;align-items:center;gap:.75rem;min-width:180px;box-shadow:var(--shadow-xl,0 18px 40px rgba(0,0,0,.2));"><div class="spinner" style="width:42px;height:42px;border-radius:50%;border:4px solid var(--border-color,#e2e8f0);border-top-color:var(--primary-500,#10b981);animation:spin 1s linear infinite;"></div><div style="font-weight:600;font-size:.9rem;">Cargando...</div></div>';
        document.body.appendChild(loaderEl);
        const style = document.createElement('style');
        style.textContent='@keyframes spin{to{transform:rotate(360deg)}}';
        document.head.appendChild(style);
        return loaderEl;
    }
    window.showGlobalLoading = function(){
        globalLoadingCount++; const el=ensureLoader(); el.style.display='flex'; requestAnimationFrame(()=>{ el.style.opacity='1'; });
    };
    window.hideGlobalLoading = function(){
        globalLoadingCount = Math.max(0, globalLoadingCount-1);
        if(globalLoadingCount===0 && loaderEl){ loaderEl.style.opacity='0'; setTimeout(()=>{ if(globalLoadingCount===0) loaderEl.style.display='none'; }, 320); }
    };
})();
