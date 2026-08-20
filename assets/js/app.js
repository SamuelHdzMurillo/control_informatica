document.addEventListener('DOMContentLoaded', () => {
    const input = document.querySelector('[data-preview-fotos]');
    const box = document.querySelector('[data-preview-box]');
    if (input && box) {
        input.addEventListener('change', () => {
            box.innerHTML = '';
            [...input.files].forEach((file) => {
                if (!file.type.startsWith('image/')) return;
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.alt = file.name;
                box.appendChild(img);
            });
        });
    }

    const search = document.getElementById('q-global');
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            search?.focus();
        }
        if (e.key === 'Escape') {
            document.documentElement.classList.remove('nav-open');
            document.querySelector('[data-user-menu]')?.classList.remove('open');
            const panel = document.querySelector('[data-user-menu] .user-menu-panel');
            if (panel) panel.hidden = true;
        }
    });

    const html = document.documentElement;
    const backdrop = document.querySelector('[data-close-nav]');
    const syncNav = () => {
        const collapsed = html.classList.contains('nav-collapsed');
        try { localStorage.setItem('sidebar', collapsed ? 'collapsed' : 'open'); } catch (err) {}
        document.querySelectorAll('[data-toggle-sidebar]').forEach((btn) => {
            btn.title = collapsed ? 'Mostrar menú' : 'Retraer menú';
        });
        if (backdrop) backdrop.hidden = !html.classList.contains('nav-open');
    };
    document.querySelectorAll('[data-toggle-sidebar]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (window.matchMedia('(max-width: 980px)').matches) {
                html.classList.toggle('nav-open');
            } else {
                html.classList.toggle('nav-collapsed');
            }
            syncNav();
        });
    });
    backdrop?.addEventListener('click', () => {
        html.classList.remove('nav-open');
        syncNav();
    });
    document.querySelectorAll('.nav a').forEach((link) => {
        link.addEventListener('click', () => {
            html.classList.remove('nav-open');
            syncNav();
        });
    });
    syncNav();

    const userMenu = document.querySelector('[data-user-menu]');
    const userBtn = document.querySelector('[data-user-menu-btn]');
    const userPanel = userMenu?.querySelector('.user-menu-panel');
    userBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        const open = !userMenu.classList.contains('open');
        userMenu.classList.toggle('open', open);
        userBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (userPanel) userPanel.hidden = !open;
    });
    document.addEventListener('click', () => {
        userMenu?.classList.remove('open');
        userBtn?.setAttribute('aria-expanded', 'false');
        if (userPanel) userPanel.hidden = true;
    });
    userPanel?.addEventListener('click', (e) => e.stopPropagation());

    const filterBtn = document.querySelector('[data-toggle-filters]');
    const filterPanel = document.querySelector('[data-filters-panel]');
    filterBtn?.addEventListener('click', () => {
        if (!filterPanel) return;
        filterPanel.hidden = !filterPanel.hidden;
        filterBtn.classList.toggle('on', !filterPanel.hidden);
    });
    if (filterBtn && filterPanel && !filterPanel.hidden) {
        filterBtn.classList.add('on');
    }

    document.querySelectorAll('[data-toggle-panel]').forEach((btn) => {
        const panel = document.querySelector(btn.getAttribute('data-toggle-panel') || '');
        if (!panel) return;
        if (!panel.hidden) btn.classList.add('on');
        btn.addEventListener('click', () => {
            panel.hidden = !panel.hidden;
            btn.classList.toggle('on', !panel.hidden);
        });
    });

    const readJson = (id) => {
        const el = document.getElementById(id);
        if (!el) return [];
        try {
            return JSON.parse(el.textContent || '[]');
        } catch (err) {
            return [];
        }
    };

    const personas = readJson('data-personas');
    const bienes = readJson('data-bienes');
    const personaSelect = document.querySelector('[data-persona-select]');
    const personaNueva = document.querySelector('[data-persona-nueva]');
    const entregadoPor = document.getElementById('entregado_por');
    const areaInput = document.getElementById('area_dependencia');
    const telInput = document.getElementById('telefono');

    const fillPersona = (p) => {
        if (!p || !personaSelect) return;
        personaSelect.value = String(p.id);
        if (areaInput) areaInput.value = p.area || '';
        if (telInput) telInput.value = p.telefono || '';
        if (personaNueva) personaNueva.hidden = true;
        if (entregadoPor) {
            entregadoPor.required = false;
            entregadoPor.value = '';
        }
    };

    const datoPersona = (id) => personas.find((x) => String(x.id) === String(id));

    const syncPersona = (fromChange = false) => {
        if (!personaSelect) return;
        const opt = personaSelect.options[personaSelect.selectedIndex];
        const valor = personaSelect.value;
        const esNueva = valor === 'nueva';
        if (personaNueva) {
            personaNueva.hidden = !esNueva;
            personaNueva.style.display = esNueva ? '' : 'none';
        }
        if (entregadoPor) entregadoPor.required = esNueva;

        if (esNueva) {
            if (fromChange) {
                if (entregadoPor) entregadoPor.value = '';
                if (areaInput) areaInput.value = '';
                if (telInput) telInput.value = '';
            }
            return;
        }

        if (entregadoPor) entregadoPor.value = '';
        if (valor === '') {
            if (areaInput) areaInput.value = '';
            if (telInput) telInput.value = '';
            return;
        }
        const p = datoPersona(valor);
        if (areaInput) {
            areaInput.value = (p && p.area) || (opt && (opt.dataset.area || opt.getAttribute('data-area'))) || '';
        }
        if (telInput) {
            telInput.value = (p && p.telefono) || (opt && (opt.dataset.telefono || opt.getAttribute('data-telefono'))) || '';
        }
    };

    personaSelect?.addEventListener('change', () => syncPersona(true));
    syncPersona(false);

    const fillBien = (b) => {
        if (!b) return;
        if (typeof window.llenarEquipoRecibir === 'function') {
            window.llenarEquipoRecibir(b);
        } else {
            const set = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.value = val || '';
            };
            set('bien_id', b.id);
            set('tipo_equipo', b.tipo);
            set('marca', b.marca);
            set('modelo', b.modelo);
            set('numero_serie', b.serie);
            set('numero_inventario', b.inventario);
        }
        const bienEstado = document.querySelector('[data-bien-estado]');
        if (bienEstado) bienEstado.textContent = 'Equipo del inventario seleccionado';
        document.querySelectorAll('[data-suggest-box-bien], [data-suggest-box-bien-inv]').forEach((el) => {
            el.hidden = true;
        });
        if (b.persona_id) {
            const p = personas.find((x) => x.id === b.persona_id);
            if (p) fillPersona(p);
        }
    };

    const renderList = (container, items, onPick, labelFn) => {
        if (!container) return;
        container.innerHTML = '';
        if (!items.length) {
            container.hidden = true;
            return;
        }
        items.slice(0, 8).forEach((item) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            const title = document.createElement('strong');
            const sub = document.createElement('small');
            const parts = labelFn(item);
            title.textContent = parts[0];
            sub.textContent = parts[1];
            btn.append(title, sub);
            btn.addEventListener('click', () => onPick(item));
            container.appendChild(btn);
        });
        container.hidden = false;
    };

    const bindBien = (selector, boxSelector, field) => {
        const el = document.querySelector(selector);
        const list = document.querySelector(boxSelector);
        if (!el || !list) return;
        el.addEventListener('input', () => {
            const q = el.value.trim().toLowerCase();
            if (q.length < 2) {
                list.hidden = true;
                return;
            }
            const hits = bienes.filter((b) => (b[field] || '').toLowerCase().includes(q)
                || (b.marca || '').toLowerCase().includes(q)
                || (b.modelo || '').toLowerCase().includes(q));
            renderList(list, hits, fillBien, (b) => [
                b.marca + ' ' + b.modelo,
                b.tipo + ' · Serie ' + (b.serie || 's/n') + ' · Inv. ' + (b.inventario || 's/n'),
            ]);
        });
    };
    bindBien('[data-suggest-bien="serie"]', '[data-suggest-box-bien]', 'serie');
    bindBien('[data-suggest-bien="inventario"]', '[data-suggest-box-bien-inv]', 'inventario');

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.suggest-wrap')) {
            document.querySelectorAll('.suggest-box').forEach((el) => { el.hidden = true; });
        }
    });
});
