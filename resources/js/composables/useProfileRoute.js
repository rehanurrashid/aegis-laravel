import { router } from '@inertiajs/vue3'

/*
 * useProfileRoute() — replaces viewPartyProfile(name, kind, slug) from _shared.js.
 *
 * Centralizes public profile routing so portal pages and modals never embed
 * URL templates. Always navigates via Inertia (no full page reload).
 *
 *   const { viewProfile } = useProfileRoute()
 *   viewProfile('sarah-johnson', 'provider')
 */
export function useProfileRoute() {
    function viewProfile(slug, kind) {
        if (!slug || !kind) return

        const routeName = {
            provider: 'public.provider',
            cs:       'public.cs',
            ss:       'public.ss',
            business: 'public.bp',
        }[kind]

        if (!routeName) {
            console.warn(`useProfileRoute: unknown kind "${kind}"`)
            return
        }

        // Ziggy `route()` is provided as a global by ZiggyVue plugin.
        router.visit(route(routeName, { slug }))
    }

    function profileHref(slug, kind) {
        const routeName = {
            provider: 'public.provider',
            cs:       'public.cs',
            ss:       'public.ss',
            business: 'public.bp',
        }[kind]
        if (!routeName) return '#'
        return route(routeName, { slug })
    }

    return { viewProfile, profileHref }
}
