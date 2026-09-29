import Alpine from 'alpinejs';
import * as bootstrap from 'bootstrap';
import TomSelect from 'tom-select';
import flatpickr from 'flatpickr';
import Swal from 'sweetalert2';
import DataTable from 'datatables.net-bs5';

import 'bootstrap';
import 'admin-lte';

import 'tom-select/dist/css/tom-select.bootstrap5.css';
import 'flatpickr/dist/flatpickr.css';
import 'datatables.net-bs5/css/dataTables.bootstrap5.css';

window.Alpine = Alpine;
window.bootstrap = bootstrap;
window.Swal = Swal;

/**
 * The theme lives in a cookie so the server can render the correct theme on the
 * first paint without a flash. A later step persists it on the user profile.
 */
const cookieTheme = () =>
    document.cookie
        .split('; ')
        .find((row) => row.startsWith('theme='))
        ?.split('=')[1] ?? 'light';

Alpine.store('theme', {
    value: document.documentElement.getAttribute('data-bs-theme') || cookieTheme(),

    init() {
        this.apply(this.value);
    },

    toggle() {
        this.apply(this.value === 'dark' ? 'light' : 'dark');
    },

    apply(value) {
        this.value = value;

        document.documentElement.setAttribute('data-bs-theme', value);
        document.documentElement.classList.toggle('dark', value === 'dark');
        document.cookie = `theme=${value}; path=/; max-age=${60 * 60 * 24 * 365}; samesite=lax`;
    },
});

/**
 * Global confirm dialog: anything carrying `data-confirm` is intercepted so
 * destructive actions do not need bespoke JavaScript on every screen.
 */
document.addEventListener('click', async (event) => {
    const trigger = event.target.closest('[data-confirm]');

    if (!trigger) {
        return;
    }

    event.preventDefault();

    const result = await Swal.fire({
        title: trigger.dataset.confirmTitle || 'Are you sure?',
        text: trigger.dataset.confirm || 'This action cannot be undone.',
        icon: trigger.dataset.confirmIcon || 'warning',
        showCancelButton: true,
        confirmButtonText: trigger.dataset.confirmButton || 'Yes, continue',
        cancelButtonText: trigger.dataset.cancelButton || 'Cancel',
        customClass: {
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-secondary',
        },
    });

    if (!result.isConfirmed) {
        return;
    }

    if (trigger.tagName === 'FORM') {
        trigger.submit();

        return;
    }

    if (trigger.dataset.confirmUrl) {
        window.location.href = trigger.dataset.confirmUrl;
    }
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-tom-select]').forEach((element) => {
        new TomSelect(element, {
            create: element.dataset.tomSelect === 'true',
        });
    });

    document.querySelectorAll('[data-flatpickr]').forEach((element) => {
        flatpickr(element, {
            dateFormat: element.dataset.flatpickr || 'Y-m-d',
            allowInput: true,
        });
    });

    document.querySelectorAll('[data-dtable]').forEach((element) => {
        new DataTable(element, JSON.parse(element.dataset.dtableOptions || '{}'));
    });
});

Alpine.start();
