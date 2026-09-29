<button
    type="button"
    class="nav-link btn btn-link border-0 shadow-none"
    x-data
    @click="$store.theme.toggle()"
    :aria-label="$store.theme.value === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
    :title="$store.theme.value === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
>
    <i class="bi" :class="$store.theme.value === 'dark' ? 'bi-sun' : 'bi-moon-stars'"></i>
</button>
