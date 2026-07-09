import './bootstrap'
import 'flatpickr/dist/flatpickr.min.css'
import 'tom-select/dist/css/tom-select.css'

import { createApp, h } from 'vue'
import { createInertiaApp, router } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createPinia } from 'pinia'
import { useUiStore } from '@/stores/ui'
import { ZiggyVue } from 'ziggy-js'
import { TooltipPlugin } from '@/plugins/tooltip'
import { FormEnhancerPlugin } from '@/plugins/FormEnhancerPlugin'

import AegisIcon from '@/components/ui/AegisIcon.vue'
import AegisModal from '@/components/ui/AegisModal.vue'
import AegisToast from '@/components/ui/AegisToast.vue'
import AegisConfirm from '@/components/ui/AegisConfirm.vue'
import AegisBadge from '@/components/ui/AegisBadge.vue'
import AegisStatChip from '@/components/ui/AegisStatChip.vue'
import AegisHeroBanner from '@/components/ui/AegisHeroBanner.vue'
import AegisCard from '@/components/ui/AegisCard.vue'
import AegisEmptyState from '@/components/ui/AegisEmptyState.vue'
import IncidentBanner from '@/components/features/IncidentBanner.vue'

createInertiaApp({
    title: (title) => (title ? `${title}` : 'Aegis'),

    resolve: (name) => {
        // Controllers use PascalCase portal names; disk folders use kebab-lowercase.
        // Map: 'BusinessPartner/Foo' -> 'business-partner/Foo'
        //      'ContinuitySteward/Foo' -> 'continuity-steward/Foo'
        //      'SupportSteward/Foo' -> 'support-steward/Foo'
        //      'Auth/Foo' -> 'auth/Foo', 'Admin/Foo' -> 'admin/Foo', etc.
        const folderMap = {
            'BusinessPartner':   'business-partner',
            'ContinuitySteward': 'continuity-steward',
            'SupportSteward':    'support-steward',
            'Provider':          'provider',
            'Admin':             'admin',
            'Auth':              'auth',
            'Public':            'public',
            'Shared':            'shared',
        }
        const parts = name.split('/')
        if (parts.length >= 2 && folderMap[parts[0]]) {
            parts[0] = folderMap[parts[0]]
        }
        const resolved = parts.join('/')
        return resolvePageComponent(
            `./pages/${resolved}.vue`,
            import.meta.glob('./pages/**/*.vue'),
        )
    },

    setup({ el, App, props, plugin }) {
        const pinia = createPinia()

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .use(ZiggyVue)
            .use(TooltipPlugin)
            .use(FormEnhancerPlugin)
            // Globally available primitives — no per-page imports needed.
            .component('AegisIcon', AegisIcon)
            .component('AegisModal', AegisModal)
            .component('AegisToast', AegisToast)
            .component('AegisConfirm', AegisConfirm)
            .component('AegisBadge', AegisBadge)
            .component('AegisStatChip', AegisStatChip)
            .component('AegisHeroBanner', AegisHeroBanner)
            .component('AegisCard', AegisCard)
            .component('AegisEmptyState', AegisEmptyState)
            .component('IncidentBanner', IncidentBanner)
            .mount(el)

        // ── Global flash toast handler ────────────────────────────────────
        // Fires on EVERY Inertia navigation (redirect, visit, back, forward)
        // regardless of which layout or page is rendered.
        // This is the single source of truth for flash → toast conversion.
        // ── Appearance: apply theme/dark-mode on every Inertia navigation ──────────
        const ALL_THEME_CLASSES = ['theme-dark', 'theme-gold-dark', 'theme-gold-deep', 'theme-slate']
        function applyAppearance(appearance) {
            if (!appearance) return
            const body = document.body
            body.classList.remove(...ALL_THEME_CLASSES)
            if (appearance.theme === 'gold-dark') body.classList.add('theme-gold-dark')
            if (appearance.theme === 'gold-deep') body.classList.add('theme-gold-deep')
            if (appearance.theme === 'slate')     body.classList.add('theme-slate')
            if (appearance.dark_mode)             body.classList.add('theme-dark')
            // Also sync localStorage so it survives hard reloads
            try {
                localStorage.setItem('aegis_appearance', JSON.stringify({
                    theme: appearance.theme ?? 'gold',
                    darkMode: appearance.dark_mode ?? false,
                }))
            } catch (e) {}
        }

        router.on('navigate', (event) => {
            const props = event.detail?.page?.props
            // Apply appearance on every navigation (covers initial load + SPA navigation)
            applyAppearance(props?.appearance)

            const flash = props?.flash
            if (!flash) return
            try {
                const ui = useUiStore()
                if (flash.success) ui.showToast(flash.success, 'success')
                if (flash.error)   ui.showToast(flash.error,   'error',   6000)
                if (flash.info)    ui.showToast(flash.info,    'info')
                if (flash.warning) ui.showToast(flash.warning, 'warning')
            } catch (e) {
                // Pinia store not ready on very first load — layouts handle that case
            }
        })
    },

    progress: {
        color: '#a0813e', // var(--gold-dark)
        showSpinner: false,
    },
})
