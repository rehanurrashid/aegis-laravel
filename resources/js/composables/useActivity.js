/*
 * useActivity() — helpers for the activity feed.
 *
 * Mirrors the PHP helpers `aegis_icon_for_event_type()` and
 * `aegis_icon_for_severity()` from icons.php, plus a timeAgo() formatter.
 */
export function useActivity() {
    // Maps event_type → icon key (port of aegis_icon_for_event_type)
    const EVENT_TYPE_ICONS = {
        message:     'message',
        task:        'check-circle',
        document:    'file-text',
        incident:    'alert-triangle',
        vault:       'lock',
        compliance:  'clock',
        attestation: 'signature',
        payment:     'dollar',
        account:     'user',
        system:      'info',
    }

    // Maps severity → icon key (port of aegis_icon_for_severity)
    const SEVERITY_ICONS = {
        critical: 'alert-triangle',
        warning:  'alert-circle',
        info:     'info',
        error:    'x-circle',
    }

    function iconForEventType(eventType) {
        return EVENT_TYPE_ICONS[eventType] ?? 'dot'
    }

    function iconForSeverity(severity) {
        return SEVERITY_ICONS[severity] ?? 'info'
    }

    function severityClass(severity) {
        return ({
            info:     'activity-item--info',
            warning:  'activity-item--warning',
            error:    'activity-item--error',
            critical: 'activity-item--critical',
        }[severity]) ?? ''
    }

    /*
     * timeAgo(datetime) → "just now" | "3m ago" | "2h ago" | "yesterday" |
     *                     "Mar 14" | "Mar 14, 2024"
     * Accepts ISO 8601 strings or Date instances.
     */
    function timeAgo(input) {
        if (!input) return ''
        const then = input instanceof Date ? input : new Date(input)
        if (Number.isNaN(then.getTime())) return ''

        const now    = new Date()
        const diffMs = now.getTime() - then.getTime()
        const sec    = Math.floor(diffMs / 1000)

        if (sec < 45) return 'just now'
        if (sec < 90) return '1m ago'
        const min = Math.floor(sec / 60)
        if (min < 60) return `${min}m ago`
        const hr = Math.floor(min / 60)
        if (hr < 24) return `${hr}h ago`
        if (hr < 48) return 'yesterday'
        const day = Math.floor(hr / 24)
        if (day < 7) return `${day}d ago`

        const months = ['Jan','Feb','Mar','Apr','May','Jun',
                        'Jul','Aug','Sep','Oct','Nov','Dec']
        const sameYear = now.getFullYear() === then.getFullYear()
        const label = `${months[then.getMonth()]} ${then.getDate()}`
        return sameYear ? label : `${label}, ${then.getFullYear()}`
    }

    return {
        iconForEventType,
        iconForSeverity,
        severityClass,
        timeAgo,
    }
}
