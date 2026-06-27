import { router } from '@inertiajs/vue3'

/*
 * useDemo() — dev-only demo user switcher.
 *
 * The PHP prototype used ?as=<user_id> on every navigation to impersonate
 * demo users from seed.json. In Laravel we expose a single named route
 * (`demo.switch`) that swaps the session user and redirects to the same
 * portal home. Hidden in production via APP_ENV check (server emits
 * import.meta.env.DEV = false on prod builds).
 *
 * IDs mirror seed.json — DO NOT rename.
 */
export function useDemo() {
    const isDev = import.meta.env.DEV

    const demoUsers = [
        { id: 'p_sarah',    name: 'Sarah Johnson',   role: 'practitioner' },
        { id: 'cs_marcus',  name: 'Marcus Williams', role: 'continuity_steward' },
        { id: 'ss_linda',   name: 'Linda Foster',    role: 'support_steward' },
        { id: 'bp_acme',    name: 'Acme Health',     role: 'business_partner' },
        { id: 'admin_root', name: 'Aegis Admin',     role: 'admin' },
    ]

    function switchUser(userId) {
        if (!isDev) return
        // Falls through to the demo.switch route — server sets session user.
        router.visit(route('demo.switch', { as: userId }))
    }

    function resetDemo() {
        if (!isDev) return
        router.visit(route('demo.reset'))
    }

    return { isDev, demoUsers, switchUser, resetDemo }
}
