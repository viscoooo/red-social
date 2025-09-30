// main.js - Archivo JS consolidado para ALASKA
// Incluye: tema, lazy loading, formularios, likes, follows, menús, filtros, comentarios y utilidades.

(function(){
  document.addEventListener('DOMContentLoaded', () => {
    initThemeToggle();
    initLazyLoading();
    initFormHandling();
    initLikeFunctionality();
    initFollowFunctionality();
    initPostMenu();
    initProfileFilters();
    initCommentFunctionality();
  });

  // ================= Tema =================
  function initThemeToggle(){
    const btn = document.getElementById('theme-toggle');
    const stored = localStorage.getItem('theme');
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const theme = stored || (prefersDark ? 'dark' : 'light');
    document.documentElement.setAttribute('data-theme', theme);
    updateThemeIcon(theme);
    if(btn){
      btn.addEventListener('click', ()=>{
        const current = document.documentElement.getAttribute('data-theme')==='dark'?'dark':'light';
        const next = current==='dark'?'light':'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        updateThemeIcon(next);
        if(window.showToast) showToast(next==='dark'?'Modo oscuro activo':'Modo claro activo','info');
      });
    }
  }
  function updateThemeIcon(theme){
    const icon = document.querySelector('#theme-toggle i');
    if(!icon) return; icon.classList.toggle('fa-moon', theme!=='dark'); icon.classList.toggle('fa-sun', theme==='dark');
  }

  // ================= Lazy Loading =================
  function initLazyLoading(){
    const imgs = Array.from(document.querySelectorAll('img[data-src], img.lazy-image'));
    if(!imgs.length) return;
    const io = 'IntersectionObserver' in window ? new IntersectionObserver((entries,obs)=>{
      entries.forEach(en=>{ if(en.isIntersecting){ const img=en.target; const src=img.dataset.src||img.src; loadRealImage(img, src); obs.unobserve(img);} });
    },{rootMargin:'120px 0px'}) : null;
    imgs.forEach(img=>{
      if(io){ io.observe(img); } else { const src=img.dataset.src||img.src; loadRealImage(img,src); }
    });
  }
  function loadRealImage(img, src){
    const real=new Image();
    real.onload=()=>{ img.src=src; img.classList.add('loaded'); img.classList.remove('skeleton'); };
    real.onerror=()=>{ img.src='https://placehold.co/600x400/e2e8f0/475569?text=Imagen'; img.classList.remove('skeleton'); };
    real.src=src;
  }

  // ================= Formularios & Fetch Wrapper =================
  function initFormHandling(){
    document.querySelectorAll('form').forEach(f=>{
      f.addEventListener('submit', ()=>{
        const btn = f.querySelector('button[type="submit"]'); if(btn) toggleLoadingBtn(btn,true);
      });
    });
    // Wrap fetch solo una vez
    if(!window._fetchWrapped){
      window._fetchWrapped = true;
      const original = window.fetch;
      window.fetch = function(...args){
        return original.apply(this,args).finally(()=>{
          const loadingBtn = document.querySelector('button[data-loading="true"]');
          if(loadingBtn) toggleLoadingBtn(loadingBtn,false);
        });
      };
    }
  }
  function toggleLoadingBtn(btn, loading){
    if(loading){
      if(btn.dataset.loading==='true') return;
      btn.dataset.loading='true';
      btn.dataset.originalText = btn.innerHTML;
      btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>';
      btn.disabled=true;
    } else {
      btn.innerHTML = btn.dataset.originalText || btn.textContent || 'Enviar';
      btn.disabled=false; btn.dataset.loading='false';
    }
  }

  // ================= Toast (fallback si feedback.js no se cargó) =================
  window.showToast = window.showToast || function(message,type='info',duration=3500){
    let c=document.getElementById('app-toast-container');
    if(!c){ c=document.createElement('div'); c.id='app-toast-container'; c.style.cssText='position:fixed;top:1rem;right:1rem;display:flex;flex-direction:column;gap:.6rem;z-index:9999;'; document.body.appendChild(c);}    
    const t=document.createElement('div');
    t.style.cssText='background:var(--bg-primary);color:var(--text-primary);border:1px solid var(--border-color);padding:.75rem 1rem;border-radius:12px;display:flex;align-items:center;gap:.55rem;box-shadow:var(--shadow-md);font-size:.85rem;';
    t.innerHTML='<i class="fas '+(type==='success'?'fa-check-circle': type==='error'?'fa-circle-exclamation':'fa-circle-info')+'"></i><span>'+message+'</span>';
    c.appendChild(t); setTimeout(()=>{ t.style.opacity='0'; t.style.transition='opacity .35s'; setTimeout(()=>t.remove(),350); }, duration);
  };

  // ================= Likes =================
  function initLikeFunctionality(){
    delegate(document.body,'click','.like-btn',function(e){
      e.preventDefault(); const pubId=this.dataset.publicacionId; if(!pubId) return;
      fetch((window.BASE_URL||'')+'like.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'publicacion_id='+pubId})
        .then(r=>r.json()).then(d=>{ if(!d.success) return; const icon=this.querySelector('i'); if(d.liked){ icon.classList.remove('far'); icon.classList.add('fas'); icon.style.color='var(--secondary-500)'; this.classList.add('liked'); } else { icon.classList.remove('fas'); icon.classList.add('far'); icon.style.color=''; this.classList.remove('liked'); }
          const c=this.querySelector('.like-count, .like-count-compact'); if(c) c.textContent=d.likes; })
        .catch(err=>console.error(err));
    });
  }

  // ================= Follow =================
  function initFollowFunctionality(){
    delegate(document.body,'click','.follow-btn',function(e){
      e.preventDefault(); const uid=this.dataset.usuarioId; if(!uid) return;
      fetch((window.BASE_URL||'')+'seguir.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'usuario_id='+uid})
        .then(r=>r.json()).then(d=>{ if(!d.success) return; if(d.following){ this.textContent='Siguiendo'; this.classList.add('following'); } else { this.textContent='Seguir'; this.classList.remove('following'); } })
        .catch(err=>console.error(err));
    });
  }

  // ================= Menú publicaciones (placeholder) =================
  function initPostMenu(){
    document.addEventListener('click', e=>{
      const openMenus=[...document.querySelectorAll('.post-menu-content.show')];
      if(!e.target.closest('.post-menu') && !e.target.closest('.menu-toggle-btn')){
        openMenus.forEach(m=>m.classList.remove('show'));
      }
    });
  }

  // ================= Filtros perfil =================
  function initProfileFilters(){
    delegate(document.body,'click','.filter-item',function(){
      const tipo=this.getAttribute('data-tipo'); if(!tipo) return;
      const url=new URL(window.location.href); url.searchParams.set('tipo',tipo); url.searchParams.set('pagina','1'); window.location.href=url.toString();
    });
  }

  // ================= Comentarios =================
  function initCommentFunctionality(){
    window.toggleCommentSection = function(id){
      const section=document.getElementById('comments-'+id); if(!section) return;
      const hidden=section.style.display==='none'||!section.style.display; section.style.display= hidden?'block':'none';
      if(hidden && !section.dataset.cargado){ loadComments(id); }
    };

    delegate(document.body,'submit','.comment-form-compact',function(e){
      e.preventDefault(); const pubId=this.dataset.publicacionId; const input=this.querySelector('.comment-input-compact'); const contenido=(input?.value||'').trim(); if(!contenido){ showToast('Comentario vacío','error'); return; }
      fetch((window.BASE_URL||'')+'comentario.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'publicacion_id='+pubId+'&contenido='+encodeURIComponent(contenido)})
        .then(r=>r.json()).then(d=>{ if(!d.success){ showToast(d.message||'Error al comentar','error'); return; } input.value=''; const section=document.getElementById('comments-'+pubId); if(section){ section.dataset.cargado=''; section.innerHTML='<div class="comments-loading" style="text-align:center;padding:.75rem;"><i class="fas fa-spinner fa-spin"></i> Actualizando...</div>'; loadComments(pubId,true);} })
        .catch(err=>{ console.error(err); showToast('Error al comentar','error'); });
    });
  }
  function loadComments(id){
    fetch((window.BASE_URL||'')+'cargar_comentarios.php?publicacion_id='+id)
      .then(r=>r.text())
      .then(html=>{ const section=document.getElementById('comments-'+id); if(!section) return; section.innerHTML=html; section.dataset.cargado='true'; })
      .catch(err=>{ console.error(err); const section=document.getElementById('comments-'+id); if(section) section.innerHTML='<div style="padding:.65rem;font-size:.75rem;color:var(--secondary-600);">Error al cargar comentarios</div>'; });
  }

  // ================= Utilidades =================
  function delegate(root, evt, selector, handler){
    root.addEventListener(evt, function(e){ const el=e.target.closest(selector); if(el) handler.call(el,e); });
  }

})();
