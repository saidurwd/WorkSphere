/**
 * Navbar global search box.
 *
 * Three properties this script is responsible for:
 *
 * 1. **Safe rendering.** Every value from the API is inserted with `textContent`
 *    or set as an attribute. A record title is user-authored text from five
 *    modules, so `innerHTML` anywhere in this file would be stored XSS on every
 *    page. The one `href` is set through `setAttribute`, and the server only ever
 *    returns URLs it generated with `route()`.
 *
 * 2. **Permission parity.** Nothing is filtered here. The endpoint applies the
 *    same query-layer filtering as the full search page, so the dropdown cannot
 *    show more than the list would — and a term matching nothing looks the same
 *    either way, which is the intended behaviour.
 *
 * 3. **No thrash.** Requests are debounced, and a response is discarded if a
 *    newer one has since been issued, so a fast typist never sees stale results
 *    overwrite fresh ones.
 */
(function () {
    const box = document.querySelector('[data-search-box]');
    if (!box) {
        return;
    }

    const input = box.querySelector('[data-search-input]');
    const results = document.getElementById('navbar-search-results');
    const status = document.getElementById('navbar-search-status');
    const form = box.querySelector('form');

    if (!input || !results || !status) {
        return;
    }

    // Set by the Blade view. A `.js` file is a plain Vite asset, so a Blade
    // expression inside it would reach the browser literally.
    const endpoint = box.dataset.searchEndpoint;

    if (!endpoint) {
        return;
    }

    const MIN_LENGTH = 2;
    const DEBOUNCE_MS = 300;
    const MAX_GROUPS = 4;

    let timer = null;
    let controller = null;
    let sequence = 0;
    let activeIndex = -1;

    function close() {
        results.classList.add('d-none');
        input.setAttribute('aria-expanded', 'false');
        activeIndex = -1;
    }

    function announce(message) {
        status.textContent = message;
    }

    function clear() {
        results.replaceChildren();
    }

    function heading(label, count) {
        const header = document.createElement('div');
        header.className = 'px-3 py-1 small fw-semibold text-body-secondary text-uppercase bg-body-tertiary';

        const name = document.createElement('span');
        name.textContent = label;

        const total = document.createElement('span');
        total.className = 'float-end';
        total.textContent = String(count);

        header.append(name, total);
        return header;
    }

    function row(title, meta, href) {
        const item = document.createElement('a');
        item.className = 'dropdown-item d-flex flex-column py-2 text-decoration-none';
        item.setAttribute('role', 'option');

        if (href) {
            item.setAttribute('href', href);
        }

        const label = document.createElement('span');
        label.className = 'text-truncate fw-medium';
        label.textContent = title;

        item.appendChild(label);

        if (meta) {
            const detail = document.createElement('small');
            detail.className = 'text-body-secondary';
            detail.textContent = meta;
            item.appendChild(detail);
        }

        return item;
    }

    function render(payload) {
        clear();

        const total = payload.total || 0;

        if (total === 0) {
            announce('No results');

            const empty = document.createElement('div');
            empty.className = 'px-3 py-4 text-center text-body-secondary';
            empty.textContent = 'Nothing matched “' + payload.term + '”';

            results.appendChild(empty);
            results.classList.remove('d-none');
            input.setAttribute('aria-expanded', 'true');

            return;
        }

        payload.groups.slice(0, MAX_GROUPS).forEach(function (group) {
            results.appendChild(heading(group.label, group.count));

            group.results.forEach(function (hit) {
                results.appendChild(row(hit.title, hit.meta, hit.url));
            });
        });

        if (payload.seeAllUrl) {
            const footer = document.createElement('a');
            footer.className = 'dropdown-item text-center small text-decoration-none border-top';
            footer.setAttribute('href', payload.seeAllUrl);
            footer.textContent = 'See all ' + total + ' result' + (total === 1 ? '' : 's');
            results.appendChild(footer);
        }

        results.classList.remove('d-none');
        input.setAttribute('aria-expanded', 'true');

        announce(total + ' result' + (total === 1 ? '' : 's') + ' for ' + payload.term);
    }

    function search(term) {
        // A superseded response must not overwrite a newer one.
        if (controller) {
            controller.abort();
        }

        controller = new AbortController();
        const issued = ++sequence;

        fetch(endpoint + '?q=' + encodeURIComponent(term), {
            signal: controller.signal,
            headers: { Accept: 'application/json' },
        })
            .then(function (response) {
                if (issued !== sequence) {
                    return null;
                }

                return response.ok ? response.json() : null;
            })
            .then(function (payload) {
                if (payload && issued === sequence) {
                    render(payload);
                }
            })
            .catch(function () {
                // An aborted request is the normal case while typing; a genuine
                // failure just leaves the box closed rather than showing an error
                // the user cannot act on.
                if (issued === sequence) {
                    close();
                }
            });
    }

    input.addEventListener('input', function () {
        window.clearTimeout(timer);

        const term = input.value.trim();

        if (term.length < MIN_LENGTH) {
            close();
            announce('');

            return;
        }

        timer = window.setTimeout(function () {
            search(term);
        }, DEBOUNCE_MS);
    });

    input.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            close();
            input.blur();

            return;
        }

        if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') {
            return;
        }

        const items = Array.prototype.slice.call(results.querySelectorAll('[role="option"], a.dropdown-item'));

        if (items.length === 0) {
            return;
        }

        event.preventDefault();

        activeIndex += event.key === 'ArrowDown' ? 1 : -1;

        if (activeIndex < 0) {
            activeIndex = items.length - 1;
        }

        if (activeIndex >= items.length) {
            activeIndex = 0;
        }

        items.forEach(function (item, index) {
            item.classList.toggle('active', index === activeIndex);
        });

        items[activeIndex].scrollIntoView({ block: 'nearest' });
    });

    // Enter submits the form to the full search page — the dropdown is a
    // shortcut, not the destination.
    form.addEventListener('submit', function () {
        window.clearTimeout(timer);
    });

    document.addEventListener('click', function (event) {
        if (!box.contains(event.target)) {
            close();
        }
    });

    // `/` focuses the box from anywhere, unless the user is already typing.
    document.addEventListener('keydown', function (event) {
        if (event.key !== '/' || event.metaKey || event.ctrlKey || event.altKey) {
            return;
        }

        const active = document.activeElement;

        if (active && ['INPUT', 'TEXTAREA', 'SELECT'].includes(active.tagName)) {
            return;
        }

        event.preventDefault();
        input.focus();
        input.select();
    });
})();
