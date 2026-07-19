import { defineStore } from 'pinia'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

/**
 * usePricingStore — single source of truth for pricing data.
 *
 * Data flows: config/aegis.php → controller prop → Inertia shared props → here.
 * No hardcoded prices live in this file. To change a price, edit config/aegis.php only.
 *
 * formatCents() is the platform-wide money formatter. Import this store anywhere
 * you need to display a dollar amount from cents.
 *
 * getTier(key) — convenience accessor for 'access' | 'practice' plan data.
 * Returns shape: { name, tagline, monthly, annual, maxCs, maxSs, includes, ... }
 * with monthly/annual already converted to cents for use with formatCents().
 */
export const usePricingStore = defineStore('pricing', () => {
    const page = usePage()

    /** Raw pricing object from config/aegis.php via Inertia prop */
    const raw = computed(() => page.props?.pricing ?? {})

    /** Format integer cents → "$12.34" */
    function formatCents(cents) {
        if (cents == null || cents === '') return '—'
        const n = Number(cents)
        if (isNaN(n)) return '—'
        return '$' + (n / 100).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })
    }

    /**
     * getTier(key) — returns normalized tier object for 'access' | 'practice'.
     * Shape mirrors the old hardcoded tiers object so existing callers work unchanged.
     */
    function getTier(key) {
        const p = raw.value?.practitioner?.[key]
        if (!p) return null
        return {
            key,
            name:     p.name,
            tagline:  p.tagline,
            monthly:  p.monthly_cents,         // cents — use formatCents()
            annual:   p.annual_cents,           // cents per month — use formatCents()
            annualTotal: p.annual_total_cents,  // cents per year
            saveLabel: p.save_label,
            maxCs:    key === 'access' ? 1 : 2,
            maxSs:    key === 'access' ? 2 : 4,
            includes: p.features ?? [],
        }
    }

    return { raw, formatCents, getTier }
})
