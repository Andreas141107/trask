<script>
document.addEventListener('DOMContentLoaded', () => {
    const debounce = (fn, ms) => {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), ms);
        };
    };

    async function fetchInto(form, url) {
        const targetSel = form.getAttribute('data-target');
        if (! targetSel) {
            return;
        }
        const target = document.querySelector(targetSel);
        if (! target) {
            return;
        }

        target.style.opacity = '0.55';
        target.style.pointerEvents = 'none';

        try {
            const res = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
            });
            if (! res.ok) {
                throw new Error('Filter request failed with status ' + res.status);
            }

            const html = await res.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const fresh = doc.querySelector(targetSel);
            if (fresh) {
                target.innerHTML = fresh.innerHTML;
                if (window.lucide) {
                    window.lucide.createIcons();
                }
            }
            window.history.replaceState({}, '', url);

            const params = new URL(url, window.location.origin).searchParams;
            document.querySelectorAll('a[data-export-base]').forEach((a) => {
                const base = a.getAttribute('data-export-base');
                const qp = new URLSearchParams(params.toString());
                if (a.getAttribute('data-export') === 'excel') {
                    qp.set('format', 'excel');
                } else {
                    qp.delete('format');
                }
                a.href = base + (qp.toString() ? '?' + qp.toString() : '');
            });
        } catch (e) {
            console.error('Filter could not be updated without reloading:', e);
            return;
        } finally {
            target.style.opacity = '';
            target.style.pointerEvents = '';
        }
    }

    function urlFromForm(form, extra = {}) {
        const params = new URLSearchParams(new FormData(form));
        for (const [k, v] of [...params.entries()]) {
            if (v === '') {
                params.delete(k);
            }
        }
        for (const [k, v] of Object.entries(extra)) {
            if (v === null || v === '') {
                params.delete(k);
            } else {
                params.set(k, v);
            }
        }
        return form.action.split('?')[0] + (params.toString() ? '?' + params.toString() : '');
    }

    document.querySelectorAll('form[data-live-filter]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            fetchInto(form, urlFromForm(form));
        });

        form.addEventListener('live-filter-change', () => {
            fetchInto(form, urlFromForm(form));
        });

        const search = form.querySelector('input[type=text][name=search]');
        if (search) {
            search.addEventListener('input', debounce(() => {
                fetchInto(form, urlFromForm(form, { page: null }));
            }, 400));
        }
    });

    document.addEventListener('click', (e) => {
        const link = e.target.closest('[data-live-paginate] a[href]');
        if (! link) {
            return;
        }
        const wrap = link.closest('[data-live-paginate]');
        if (! wrap || ! wrap.id) {
            return;
        }
        const form = document.querySelector('form[data-target="#' + wrap.id + '"]');
        if (! form) {
            return;
        }
        e.preventDefault();
        const page = new URL(link.href, window.location.origin).searchParams.get('page') || '1';
        fetchInto(form, urlFromForm(form, { page }));
    });
});
</script>
