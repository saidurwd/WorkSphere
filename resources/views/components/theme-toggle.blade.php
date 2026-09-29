<a
    href="#"
    role="button"
    x-data
    @click.prevent="$store.theme.toggle()"
    :aria-label="$store.theme.value === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
    :title="$store.theme.value === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
    {{ $attributes->merge(['class' => 'nav-link']) }}
>
    <i class="bi" :class="$store.theme.value === 'dark' ? 'bi-sun' : 'bi-moon-stars'"></i>
</a>
