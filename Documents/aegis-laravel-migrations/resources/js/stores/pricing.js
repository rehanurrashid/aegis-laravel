import { defineStore } from 'pinia'

/*
 * usePricingStore — mirrors the PHP pricing_data.php helper.
 *
 * Amounts are integer cents to match Aegis schema convention (money never
 * lives in floats). UI formats with formatCents() helper.
 */
export const usePricingStore = defineStore('pricing', () => {
    const tiers = {
        access: {
            key: 'access',
            name: 'Continuity Access',
            tagline: 'Essential coverage for solo practitioners',
            monthly: 2900,   // $29.00
            annual:  2300,   // $23.00 / month billed annually
            maxCs: 1,
            maxSs: 2,
            servicesMode: false,
            includes: [
                '1 Continuity Steward',
                'Up to 2 Support Stewards',
                'Vault (sealed credentials)',
                'Plan attestation',
                'Activity feed',
            ],
        },
        practice: {
            key: 'practice',
            name: 'Continuity Practice',
            tagline: 'For practices, group offices, and discoverability',
            monthly: 4900,   // $49.00
            annual:  3900,   // $39.00 / month billed annually
            maxCs: 2,
            maxSs: 4,
            servicesMode: true,
            includes: [
                '2 Continuity Stewards',
                'Up to 4 Support Stewards',
                'Everything in Access',
                'Services discoverability',
                'Job postings & proposals',
                'Network & referrals',
            ],
        },
    }

    function formatCents(cents) {
        const dollars = (cents / 100).toFixed(2)
        return `$${dollars}`
    }

    function getTier(key) {
        return tiers[key] ?? null
    }

    return { tiers, getTier, formatCents }
})
