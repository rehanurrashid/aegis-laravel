import './bootstrap'

import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createPinia } from 'pinia'
import { ZiggyVue } from 'ziggy-js'
import { TooltipPlugin } from '@/plugins/tooltip'

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
    },

    progress: {
        color: '#a0813e', // var(--gold-dark)
        showSpinner: false,
    },
})
