/* Shared AJAX transport for the public portal and staff workspace. */
(function () {
    let pending = 0;
    const activeForms = new WeakMap();

    const style = document.createElement('style');
    style.textContent = `
        #ajax-progress { position:fixed; inset:0; z-index:9999; display:grid; place-items:center; padding:24px; background:transparent; opacity:0; visibility:hidden; pointer-events:none; transition:opacity .18s ease,visibility 0s linear .18s; }
        #ajax-progress.is-visible { opacity:1; visibility:visible; pointer-events:auto; transition:opacity .18s ease; }
        #ajax-progress .ajax-loading-card { display:flex; align-items:center; gap:12px; width:min(220px,100%); min-height:54px; padding:14px 17px; border:1px solid rgb(255 255 255 / .82); border-radius:16px; background:rgb(255 255 255 / .96); color:#334155; box-shadow:0 18px 42px rgb(15 23 42 / .18); font:500 14px/1.2 system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; transform:scale(.96); transition:transform .18s ease; }
        #ajax-progress.is-visible .ajax-loading-card { transform:scale(1); }
        #ajax-progress .ajax-spinner { width:21px; height:21px; flex:0 0 21px; border:3px solid #dbe3ed; border-top-color:#90a4bc; border-radius:50%; animation:ajax-spin .75s linear infinite; }
        form[aria-busy="true"] { cursor:progress; }
        @keyframes ajax-spin { to { transform:rotate(360deg); } }
        body.ajax-staff-loading #content { position:relative; min-height:calc(100dvh - 80px); }
        body.ajax-staff-loading #content > #ajax-progress { position:absolute; inset:0; min-height:100%; }
        body.ajax-staff-loading #content > section.grid.place-items-center { visibility:hidden; }
        @media (min-width:1024px) {
            body.ajax-staff-loading #app > div > aside { pointer-events:none; }
        }
    `;
    document.head.append(style);

    function setLoading(isLoading) {
        document.documentElement.classList.toggle('ajax-loading', isLoading);
        const isStaffWorkspace = Boolean(document.querySelector('#app > div > aside'));
        document.body.classList.toggle('ajax-staff-loading', isLoading && isStaffWorkspace);
        const staffContent = isStaffWorkspace ? document.getElementById('content') : null;
        let indicator = document.getElementById('ajax-progress');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'ajax-progress';
            indicator.setAttribute('role', 'status');
            indicator.setAttribute('aria-live', 'polite');
            indicator.innerHTML = '<div class="ajax-loading-card"><span class="ajax-spinner" aria-hidden="true"></span><span>Loading...</span></div>';
        }
        (staffContent || document.body).append(indicator);
        indicator.classList.toggle('is-visible', isLoading);
    }

    function setFormBusy(form, busy) {
        if (!form) return;
        const controls = [...form.querySelectorAll('button, input[type="submit"]')];
        if (busy) {
            activeForms.set(form, controls.map(control => [control, control.disabled]));
            form.setAttribute('aria-busy', 'true');
            controls.forEach(control => control.disabled = true);
            return;
        }
        (activeForms.get(form) || []).forEach(([control, wasDisabled]) => {
            if (control.isConnected) control.disabled = wasDisabled;
        });
        activeForms.delete(form);
        form.removeAttribute('aria-busy');
    }

    function messageFrom(body, fallback) {
        if (body && typeof body === 'object') {
            if (body.error) return body.error;
            if (body.message) return body.message;
            if (body.errors) return Object.values(body.errors).flat().join(' ');
        }
        return fallback;
    }

    async function request(url, options = {}) {
        const form = document.activeElement && document.activeElement.closest('form');
        const headers = {
            Accept: 'application/json',
            ...(options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
            ...(options.headers || {}),
        };
        pending += 1;
        setLoading(true);
        setFormBusy(form, true);

        try {
            const response = await fetch(url, { credentials: 'same-origin', ...options, headers });
            const body = await response.json().catch(() => null);
            if (!response.ok) {
                const error = new Error(messageFrom(body, `Request failed (${response.status})`));
                error.status = response.status;
                error.errors = body && body.errors;
                document.dispatchEvent(new CustomEvent('ajax:error', { detail: error }));
                throw error;
            }
            return body ?? {};
        } catch (error) {
            if (error instanceof TypeError) {
                const networkError = new Error('Unable to reach the server. Check your connection and try again.');
                document.dispatchEvent(new CustomEvent('ajax:error', { detail: networkError }));
                throw networkError;
            }
            throw error;
        } finally {
            setFormBusy(form, false);
            pending -= 1;
            if (!pending) setLoading(false);
        }
    }

    window.ajax = { request };
})();
