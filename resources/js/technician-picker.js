/** Selector accesible con búsqueda remota para asignar tickets a técnicos activos. */
function initTechnicianPicker(root) {
    const input = root.querySelector('[data-ts-technician-input]');
    const hidden = root.querySelector('[data-ts-technician-id]');
    const list = root.querySelector('[data-ts-technician-options]');
    const status = root.querySelector('[data-ts-technician-status]');
    const clearButton = root.querySelector('[data-ts-technician-clear]');
    const form = root.closest('form');
    const endpoint = root.dataset.searchUrl;

    if (!input || !hidden || !list || !status || !endpoint || !form) return;

    let timer = null;
    let controller = null;
    let matches = [];
    let activeIndex = -1;

    const setExpanded = (expanded) => {
        input.setAttribute('aria-expanded', String(expanded));
        list.hidden = !expanded;
    };

    const closeList = () => {
        activeIndex = -1;
        input.removeAttribute('aria-activedescendant');
        setExpanded(false);
    };

    const setStatus = (message) => {
        status.textContent = message;
    };

    const selectTechnician = (technician) => {
        hidden.value = String(technician.id);
        input.value = technician.name;
        input.setCustomValidity('');
        setStatus(`Técnico seleccionado: ${technician.name}${technician.email ? ` · ${technician.email}` : ''}`);
        closeList();
    };

    const highlight = (index) => {
        const options = Array.from(list.querySelectorAll('[role="option"]'));
        if (options.length === 0) return;

        activeIndex = Math.max(0, Math.min(index, options.length - 1));
        options.forEach((option, optionIndex) => {
            const active = optionIndex === activeIndex;
            option.setAttribute('aria-selected', String(active));
            if (active) {
                input.setAttribute('aria-activedescendant', option.id);
                option.scrollIntoView({ block: 'nearest' });
            }
        });
    };

    const renderMatches = (technicians) => {
        matches = Array.isArray(technicians) ? technicians : [];
        activeIndex = -1;
        input.removeAttribute('aria-activedescendant');
        list.replaceChildren();

        if (matches.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'ts-combobox-empty';
            empty.textContent = 'No se encontraron técnicos activos con ese criterio.';
            list.append(empty);
            setStatus('Sin coincidencias. Intenta con otro nombre o correo.');
            setExpanded(true);
            return;
        }

        matches.forEach((technician, index) => {
            const option = document.createElement('button');
            option.type = 'button';
            option.id = `${list.id}-option-${index}`;
            option.className = 'ts-combobox-option';
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', 'false');

            const name = document.createElement('span');
            name.className = 'ts-combobox-option-name';
            name.textContent = technician.name;

            const email = document.createElement('small');
            email.className = 'ts-combobox-option-email';
            email.textContent = technician.email || '';

            const copy = document.createElement('span');
            copy.className = 'ts-combobox-option-copy';
            copy.append(name, email);
            option.append(copy);

            option.addEventListener('mousedown', (event) => event.preventDefault());
            option.addEventListener('click', () => selectTechnician(technician));
            list.append(option);
        });

        setStatus(`${matches.length} coincidencia(s). Selecciona un técnico de la lista.`);
        setExpanded(true);
    };

    const search = async (term) => {
        if (controller) controller.abort();
        controller = new AbortController();
        setStatus('Buscando técnicos activos…');

        try {
            const url = new URL(endpoint, window.location.origin);
            url.searchParams.set('q', term);
            const response = await fetch(url, {
                method: 'GET',
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                signal: controller.signal,
            });

            if (!response.ok) {
                throw new Error(response.status === 429
                    ? 'Se realizaron demasiadas búsquedas. Espera un momento e inténtalo de nuevo.'
                    : 'No fue posible consultar los técnicos. Vuelve a intentarlo.');
            }

            const technicians = await response.json();
            // Evita que una respuesta tardía reemplace los resultados de una búsqueda más reciente.
            if (input.value.trim() !== term) return;
            renderMatches(technicians);
        } catch (error) {
            if (error.name === 'AbortError') return;
            list.replaceChildren();
            const message = document.createElement('div');
            message.className = 'ts-combobox-empty ts-combobox-error';
            message.textContent = error.message || 'Ocurrió un error al buscar técnicos.';
            list.append(message);
            setStatus(message.textContent);
            setExpanded(true);
        }
    };

    input.addEventListener('input', () => {
        hidden.value = '';
        input.setCustomValidity('');
        if (controller) controller.abort();
        if (timer) window.clearTimeout(timer);

        const term = input.value.trim();
        if (term.length < 2) {
            list.replaceChildren();
            closeList();
            setStatus('Escribe al menos dos caracteres para buscar.');
            return;
        }

        timer = window.setTimeout(() => search(term), 250);
    });

    input.addEventListener('keydown', (event) => {
        const options = list.querySelectorAll('[role="option"]');
        if (event.key === 'ArrowDown' && !list.hidden && options.length) {
            event.preventDefault();
            highlight(activeIndex + 1);
        } else if (event.key === 'ArrowUp' && !list.hidden && options.length) {
            event.preventDefault();
            highlight(activeIndex <= 0 ? options.length - 1 : activeIndex - 1);
        } else if (event.key === 'Enter' && !list.hidden && activeIndex >= 0 && matches[activeIndex]) {
            event.preventDefault();
            selectTechnician(matches[activeIndex]);
        } else if (event.key === 'Escape' && !list.hidden) {
            event.preventDefault();
            closeList();
        }
    });

    clearButton?.addEventListener('click', () => {
        hidden.value = '';
        input.value = '';
        input.setCustomValidity('');
        list.replaceChildren();
        closeList();
        setStatus('Escribe para buscar y selecciona una coincidencia.');
        input.focus();
    });

    form.addEventListener('submit', (event) => {
        if (!hidden.value) {
            event.preventDefault();
            input.setCustomValidity('Selecciona un técnico de la lista de resultados.');
            input.reportValidity();
            input.focus();
        }
    });

    document.addEventListener('pointerdown', (event) => {
        if (!root.contains(event.target)) closeList();
    });

    if (hidden.value && input.value) {
        setStatus(`Técnico seleccionado: ${input.value}`);
    }
}

function bootTechnicianPickers() {
    document.querySelectorAll('[data-ts-technician-picker]').forEach(initTechnicianPicker);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootTechnicianPickers, { once: true });
} else {
    bootTechnicianPickers();
}
