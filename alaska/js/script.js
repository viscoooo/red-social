(function(){
    'use strict';

    const BASE_URL = window.BASE_URL || '';
    const FORM_HEADERS = { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' };
    const state = { page: 2, loading: false, hasMore: true };

    const toast = (type, message) => {
        if (typeof window.showToast === 'function') {
            window.showToast(type, message);
        } else if (type === 'error') {
            console.error(message);
        } else {
            console.log(message);
        }
    };

    const encode = data => new URLSearchParams(data).toString();

    const getPostElement = element => element.closest('.post, .post-card-compact');

    const updateLikeUI = (button, liked, likes) => {
        const icon = button.querySelector('i');
        if (icon) {
            icon.classList.toggle('fas', liked);
            icon.classList.toggle('far', !liked);
            icon.classList.toggle('liked', liked);
        }
        const inlineCount = button.querySelector('.like-count, .like-count-compact');
        if (inlineCount && typeof likes === 'number' && !Number.isNaN(likes)) {
            inlineCount.textContent = likes;
        }
        const post = getPostElement(button);
        if (post) {
            if (typeof likes === 'number' && !Number.isNaN(likes)) {
                const stat = post.querySelector('.like-count-text');
                if (stat) {
                    stat.textContent = `${likes} Me gusta`;
                }
                const compactStat = post.querySelector('.like-count-compact');
                if (compactStat) {
                    compactStat.textContent = likes;
                }
            }
        }
    };

    const updateFollowUI = (button, following) => {
        button.classList.toggle('following', following);
        if (button.classList.contains('follow-btn-compact')) {
            button.innerHTML = following
                ? '<i class="fas fa-user-check"></i> Siguiendo'
                : '<i class="fas fa-user-plus"></i> Seguir';
        } else {
            button.textContent = following ? 'Siguiendo' : 'Seguir';
        }
    };

    const updateSaveUI = (button, saved) => {
        const icon = button.querySelector('i');
        const label = button.querySelector('span:nth-of-type(2), .save-label');
        if (icon) {
            icon.classList.toggle('fas', saved);
            icon.classList.toggle('far', !saved);
            icon.style.color = saved ? 'var(--naranja-principal)' : '';
        }
        if (label) {
            label.textContent = saved ? 'Guardado' : 'Guardar';
        }
    };

    const updateCommentCounters = (postContainer, total) => {
        if (!postContainer) return;
        const stat = postContainer.querySelector('.comment-count-text');
        if (stat) {
            stat.textContent = `${total} Comentarios`;
        }
        const compact = postContainer.querySelector('.comment-count-compact');
        if (compact) {
            compact.textContent = total;
        }
    };

    const buildCommentNode = comentario => {
        const wrapper = document.createElement('div');
        wrapper.className = 'comment';

        const avatar = document.createElement('div');
        avatar.className = 'comment-avatar';
        if (comentario.foto_perfil) {
            const img = document.createElement('img');
            const src = comentario.foto_perfil.startsWith('http')
                ? comentario.foto_perfil
                : `${BASE_URL}uploads/${comentario.foto_perfil}`;
            img.src = src;
            img.alt = 'Foto de perfil';
            avatar.appendChild(img);
        } else {
            avatar.textContent = (comentario.nombre || 'U').charAt(0);
        }

        const content = document.createElement('div');
        content.className = 'comment-content';

        const user = document.createElement('div');
        user.className = 'comment-user';
        user.textContent = comentario.nombre || 'Usuario';

        const text = document.createElement('div');
        text.className = 'comment-text';
        text.textContent = comentario.contenido || '';

        const time = document.createElement('div');
        time.className = 'comment-time';
        time.textContent = 'hace un momento';

        content.appendChild(user);
        content.appendChild(text);
        content.appendChild(time);

        wrapper.appendChild(avatar);
        wrapper.appendChild(content);
        return wrapper;
    };

    const handleLike = async button => {
        if (button.dataset.loading === '1') return;
        const publicacionId = button.dataset.publicacionId;
        if (!publicacionId) return;

        button.dataset.loading = '1';
        try {
            const response = await fetch(`${BASE_URL}like.php`, {
                method: 'POST',
                headers: FORM_HEADERS,
                body: encode({ publicacion_id: publicacionId })
            });
            const data = await response.json();
            if (!data.success) {
                toast('error', data.message || 'No se pudo actualizar el like.');
                return;
            }
            updateLikeUI(button, !!data.liked, Number(data.likes));
        } catch (error) {
            console.error('Error al procesar like:', error);
            toast('error', 'Ocurrió un error al registrar tu like.');
        } finally {
            delete button.dataset.loading;
        }
    };

    const handleFollow = async button => {
        if (button.dataset.loading === '1') return;
        const usuarioId = button.dataset.usuarioId;
        if (!usuarioId) return;

        button.dataset.loading = '1';
        try {
            const response = await fetch(`${BASE_URL}seguir.php`, {
                method: 'POST',
                headers: FORM_HEADERS,
                body: encode({ usuario_id: usuarioId })
            });
            const data = await response.json();
            if (!data.success) {
                toast('error', data.message || 'No se pudo actualizar el seguimiento.');
                return;
            }
            updateFollowUI(button, !!data.following);
        } catch (error) {
            console.error('Error al procesar seguimiento:', error);
            toast('error', 'No pudimos actualizar el seguimiento.');
        } finally {
            delete button.dataset.loading;
        }
    };

    const handleSave = async button => {
        if (button.dataset.loading === '1') return;
        const publicacionId = button.dataset.publicacionId;
        if (!publicacionId) return;

        button.dataset.loading = '1';
        try {
            const response = await fetch(`${BASE_URL}guardar.php`, {
                method: 'POST',
                headers: FORM_HEADERS,
                body: encode({ publicacion_id: publicacionId })
            });
            const data = await response.json();
            if (!data.success) {
                toast('error', data.message || 'No se pudo actualizar el guardado.');
                return;
            }
            updateSaveUI(button, !!data.guardado);
        } catch (error) {
            console.error('Error al guardar publicación:', error);
            toast('error', 'Tuvimos un problema al guardar la publicación.');
        } finally {
            delete button.dataset.loading;
        }
    };

    const handleComment = async form => {
        const publicacionId = form.dataset.publicacionId;
        const input = form.querySelector('.comment-input');
        const contenido = (input?.value || '').trim();
        if (!publicacionId || !contenido) {
            if (input) input.focus();
            return;
        }

        input.disabled = true;
        try {
            const response = await fetch(`${BASE_URL}comentario.php`, {
                method: 'POST',
                headers: FORM_HEADERS,
                body: encode({ publicacion_id: publicacionId, contenido })
            });
            const data = await response.json();
            if (!data.success) {
                toast('error', data.message || 'No se pudo publicar tu comentario.');
                return;
            }
            input.value = '';
            const section = document.getElementById(`comments-${publicacionId}`);
            if (section) {
                const newComment = buildCommentNode(data.comentario || {});
                const formPosition = section.querySelector('.comment-form');
                if (formPosition) {
                    section.insertBefore(newComment, formPosition);
                } else {
                    section.appendChild(newComment);
                }
                updateCommentCounters(form.closest('.post, .post-card-compact'), Number(data.totalComentarios || 0));
            }
        } catch (error) {
            console.error('Error al enviar comentario:', error);
            toast('error', 'No se pudo publicar tu comentario.');
        } finally {
            input.disabled = false;
        }
    };

    const handleCompactComment = async form => {
        const publicacionId = form.dataset.publicacionId;
        const input = form.querySelector('.comment-input-compact');
        const contenido = (input?.value || '').trim();
        if (!publicacionId || !contenido) {
            if (input) input.focus();
            return;
        }

        input.disabled = true;
        try {
            const response = await fetch(`${BASE_URL}comentario.php`, {
                method: 'POST',
                headers: FORM_HEADERS,
                body: encode({ publicacion_id: publicacionId, contenido })
            });
            const data = await response.json();
            if (!data.success) {
                toast('error', data.message || 'No se pudo publicar tu comentario.');
                return;
            }
            input.value = '';
            const section = document.getElementById(`comments-${publicacionId}`);
            if (section) {
                section.dataset.cargado = '';
                section.innerHTML = '<div class="comments-loading" style="text-align:center; padding:1rem;"><i class="fas fa-spinner fa-spin"></i> Actualizando...</div>';
                fetch(`${BASE_URL}cargar_comentarios.php?publicacion_id=${publicacionId}`)
                    .then(r => r.text())
                    .then(html => {
                        section.innerHTML = html;
                        section.dataset.cargado = 'true';
                    })
                    .catch(err => console.error('Error al recargar comentarios:', err));
            }
            updateCommentCounters(form.closest('.post, .post-card-compact'), Number(data.totalComentarios || 0));
        } catch (error) {
            console.error('Error al enviar comentario compacto:', error);
            toast('error', 'No se pudo publicar tu comentario.');
        } finally {
            input.disabled = false;
        }
    };

    const loadMorePosts = () => {
        if (!state.hasMore || state.loading) return;
        const container = document.querySelector('.main-content');
        if (!container) return;

        state.loading = true;
        fetch(`${BASE_URL}cargar_publicaciones.php?page=${state.page}`)
            .then(response => response.text())
            .then(html => {
                const trimmed = html.trim();
                if (!trimmed) {
                    state.hasMore = false;
                    return;
                }
                container.insertAdjacentHTML('beforeend', trimmed);
                state.page += 1;
            })
            .catch(error => {
                console.error('Error al cargar más publicaciones:', error);
                toast('error', 'No pudimos cargar más publicaciones.');
            })
            .finally(() => {
                state.loading = false;
            });
    };

    const nearBottom = () => (window.innerHeight + window.scrollY) >= (document.body.offsetHeight - 900);

    const initInfiniteScroll = () => {
        window.addEventListener('scroll', () => {
            if (nearBottom()) {
                loadMorePosts();
            }
        });
    };

    const initViewAllComments = () => {
        document.body.addEventListener('click', event => {
            const link = event.target.closest('.ver-todos-comentarios');
            if (!link) return;
            event.preventDefault();
            if (link.dataset.loading === '1') return;

            const publicacionId = link.dataset.publicacionId;
            if (!publicacionId) return;

            const section = document.getElementById(`comments-${publicacionId}`);
            if (!section) return;

            link.dataset.loading = '1';
            fetch(`${BASE_URL}cargar_comentarios.php?publicacion_id=${publicacionId}&all=1`)
                .then(r => r.text())
                .then(html => {
                    section.querySelectorAll('.comment').forEach(node => node.remove());
                    const form = section.querySelector('.comment-form');
                    if (form) {
                        form.insertAdjacentHTML('beforebegin', html);
                    } else {
                        section.insertAdjacentHTML('afterbegin', html);
                    }
                    link.style.display = 'none';
                })
                .catch(err => console.error('Error al cargar todos los comentarios:', err))
                .finally(() => {
                    delete link.dataset.loading;
                });
        });
    };

    const initMapToggles = () => {
        document.body.addEventListener('click', event => {
            const button = event.target.closest('.map-toggle-btn');
            if (!button) return;
            event.preventDefault();
            const targetId = button.dataset.mapTarget;
            const ubicacion = button.dataset.ubicacion;
            if (!targetId || !ubicacion) return;
            const mapContainer = document.getElementById(targetId);
            if (!mapContainer) return;
            const isHidden = mapContainer.style.display === 'none' || !mapContainer.style.display;
            if (isHidden) {
                mapContainer.style.display = 'block';
                inicializarMapaParaUbicacion(mapContainer, ubicacion);
            } else {
                mapContainer.style.display = 'none';
            }
        });
    };

    const enhanceLazyImages = () => {
        const images = Array.from(document.querySelectorAll('img[data-src]:not([data-enhanced])'));
        if (!images.length) return;
        images.forEach(img => {
            img.dataset.enhanced = '1';
            img.classList.add('skeleton');
        });
        const loadImage = img => {
            const src = img.getAttribute('data-src');
            if (!src) return;
            const real = new Image();
            real.onload = () => {
                img.src = src;
                img.classList.remove('skeleton');
                img.classList.add('loaded');
            };
            real.src = src;
        };
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries, obs) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        loadImage(entry.target);
                        obs.unobserve(entry.target);
                    }
                });
            }, { rootMargin: '200px 0px' });
            images.forEach(img => observer.observe(img));
        } else {
            images.forEach(loadImage);
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        document.body.addEventListener('click', event => {
            const likeButton = event.target.closest('.like-btn');
            if (likeButton) {
                event.preventDefault();
                handleLike(likeButton);
                return;
            }
            const followButton = event.target.closest('.follow-btn, .follow-btn-compact');
            if (followButton) {
                event.preventDefault();
                handleFollow(followButton);
                return;
            }
            const saveButton = event.target.closest('.save-btn');
            if (saveButton) {
                event.preventDefault();
                handleSave(saveButton);
            }
        });

        document.body.addEventListener('submit', event => {
            const form = event.target.closest('.comment-form');
            if (form) {
                event.preventDefault();
                handleComment(form);
                return;
            }
            const compactForm = event.target.closest('.comment-form-compact');
            if (compactForm) {
                event.preventDefault();
                handleCompactComment(compactForm);
            }
        });

        initInfiniteScroll();
        initViewAllComments();
        initMapToggles();
        enhanceLazyImages();
    });

    window.toggleCommentForm = function(publicacionId) {
        const section = document.getElementById(`comments-${publicacionId}`);
        if (!section) return;
        const isHidden = section.style.display === 'none' || !section.style.display;
        section.style.display = isHidden ? 'block' : 'none';
        if (isHidden) {
            const input = section.querySelector('.comment-input, .comment-input-compact');
            if (input) {
                setTimeout(() => input.focus(), 120);
            }
        }
    };

    const geocodeCache = {};

    const geocodeDireccion = async ubicacion => {
        if (geocodeCache[ubicacion]) return geocodeCache[ubicacion];
        try {
            const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(ubicacion)}`;
            const respuesta = await fetch(url, { headers: { 'Accept-Language': 'es' } });
            const data = await respuesta.json();
            if (Array.isArray(data) && data.length) {
                const { lat, lon } = data[0];
                geocodeCache[ubicacion] = { lat: parseFloat(lat), lng: parseFloat(lon) };
                return geocodeCache[ubicacion];
            }
        } catch (error) {
            console.warn('Geocode error', error);
        }
        return null;
    };

    window.inicializarMapaParaUbicacion = async (div, ubicacion) => {
        if (!window.L) {
            div.innerHTML = '<div style="padding:1rem;">Map library no cargada.</div>';
            return;
        }
        if (div.dataset.initialized) return;
        div.dataset.initialized = '1';
        div.innerHTML = '<div style="padding:1rem;font-size:0.85rem;">Buscando ubicación...</div>';
        const coords = await geocodeDireccion(ubicacion);
        if (!coords) {
            div.innerHTML = '<div style="padding:1rem;font-size:0.85rem;">No se pudo localizar esta ubicación.</div>';
            return;
        }
        div.innerHTML = '';
        const map = L.map(div).setView([coords.lat, coords.lng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);
        L.marker([coords.lat, coords.lng]).addTo(map).bindPopup(escapeHtml(ubicacion));
        setTimeout(() => map.invalidateSize(), 300);
    };

    window.apiFetch = async (url, options = {}) => {
        try {
            if (options.showLoading && typeof window.showGlobalLoading === 'function') {
                window.showGlobalLoading();
            }
            const res = await fetch(url, options);
            if (!res.ok) throw new Error(`Error HTTP ${res.status}`);
            return res;
        } catch (err) {
            console.error(err);
            toast('error', err.message || 'Error de red');
            throw err;
        } finally {
            if (options.showLoading && typeof window.hideGlobalLoading === 'function') {
                window.hideGlobalLoading();
            }
        }
    };

    window.escapeHtml = function(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };
})();
