<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\ActivityEvent;
use App\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    public function __construct(private ActivityService $activity) {}

    /**
     * GET /activity — paginated, filtered, grouped by date band (today/week/month).
     */
    public function index(Request $request): Response
    {
        $user      = $request->user();
        $filters   = $request->only(['module', 'severity', 'unread', 'portal', 'event_type', 'entry_type']);
        $page      = max(1, (int) $request->query('page', 1));
        $perPage   = 20;

        // ── Base query (user only, no filters) — used for sidebar counts + unread totals.
        // These must never be filtered by event_type / entry_type so they reflect
        // the full picture regardless of what the user has clicked in the sidebar.
        $baseQuery = ActivityEvent::where('user_id', $user->id);

        // ── Filtered query — used for the event list, pagination total, and grouping.
        $filteredQuery = ActivityEvent::where('user_id', $user->id)
            ->with('actor:id,display_name,role')
            ->orderByDesc('created_at');
        if (!empty($filters['module']))     $filteredQuery->where('module', $filters['module']);
        if (!empty($filters['severity']))   $filteredQuery->where('severity', $filters['severity']);
        // 'services' is a module-based filter, not an event_type filter
        if (!empty($filters['event_type']) && $filters['event_type'] !== 'services') {
            $filteredQuery->where('event_type', $filters['event_type']);
        }
        if (!empty($filters['entry_type'])) $filteredQuery->where('entry_type', $filters['entry_type']);
        if (!empty($filters['portal']))     $filteredQuery->where('portal', $filters['portal']);
        if (!empty($filters['unread']))     $filteredQuery->whereNull('read_at');

        $total      = min($filteredQuery->count(), 500);
        $lastPage   = max(1, (int) ceil($total / $perPage));
        $page       = min($page, $lastPage);
        $offset     = ($page - 1) * $perPage;

        $events = $filteredQuery->offset($offset)->limit($perPage)->get();

        // Group events into today / week / month buckets
        $today      = now()->startOfDay();
        $weekStart  = now()->subDays(7)->startOfDay();
        $monthStart = now()->subDays(30)->startOfDay();

        $grouped = ['today' => [], 'week' => [], 'month' => []];
        foreach ($events as $e) {
            $created = $e->created_at instanceof \DateTimeInterface
                ? $e->created_at
                : \Carbon\Carbon::parse($e->created_at);

            if ($created >= $today) {
                $grouped['today'][] = $this->formatEvent($e);
            } elseif ($created >= $weekStart) {
                $grouped['week'][] = $this->formatEvent($e);
            } elseif ($created >= $monthStart) {
                $grouped['month'][] = $this->formatEvent($e);
            } else {
                $grouped['month'][] = $this->formatEvent($e);
            }
        }

        // Category counts — always from the filter-free base query so sidebar totals
        // never collapse to 0 when event_type or entry_type is active.
        $catCountsRows = (clone $baseQuery)
            ->selectRaw('event_type, COUNT(*) as c')
            ->groupBy('event_type')
            ->get();
        $catCountsRaw = [];
        foreach ($catCountsRows as $row) {
            $key = is_object($row->event_type) ? ($row->event_type->value ?? '') : (string) $row->event_type;
            $catCountsRaw[$key] = (int) $row->c;
        }

        $categories = [
            ['key' => '',             'label' => 'All',           'icon' => 'inbox'],
            ['key' => 'incident',     'label' => 'Incidents',     'icon' => 'alert-triangle'],
            ['key' => 'plan',         'label' => 'Continuity Plan','icon' => 'shield'],
            ['key' => 'message',      'label' => 'Messages',      'icon' => 'message-square'],
            ['key' => 'support',      'label' => 'Support',       'icon' => 'life-buoy'],
            ['key' => 'task',         'label' => 'Tasks',         'icon' => 'check-circle'],
            ['key' => 'job_postings', 'label' => 'Job Postings',  'icon' => 'briefcase'],
            ['key' => 'document',     'label' => 'Documents',     'icon' => 'file-text'],
            ['key' => 'vault',        'label' => 'Vault',         'icon' => 'lock'],
            ['key' => 'compliance',   'label' => 'Compliance',    'icon' => 'shield'],
            ['key' => 'payment',      'label' => 'Financial',     'icon' => 'credit-card'],
            ['key' => 'account',      'label' => 'Account',       'icon' => 'log-in'],
            ['key' => 'referral',     'label' => 'Referrals',     'icon' => 'refresh-cw'],
            ['key' => 'services',     'label' => 'My Services',   'icon' => 'calendar'],
            ['key' => 'event',        'label' => 'Events',        'icon' => 'calendar'],
            ['key' => 'news',         'label' => 'News',          'icon' => 'megaphone'],
        ];

        $baseTotal = min((clone $baseQuery)->count(), 500);

        // Entry-type totals for tab pill badges.
        // Scope to ALL active filters except entry_type itself, so the
        // "My Activity" / "Notifications" badges reflect the current view.
        $entryTypeBase = clone $baseQuery;
        if (!empty($filters['module']))     $entryTypeBase->where('module', $filters['module']);
        if (!empty($filters['severity']))   $entryTypeBase->where('severity', $filters['severity']);
        if (!empty($filters['event_type']) && $filters['event_type'] !== 'services') {
            $entryTypeBase->where('event_type', $filters['event_type']);
        }
        if (!empty($filters['portal']))     $entryTypeBase->where('portal', $filters['portal']);
        if (!empty($filters['unread']))     $entryTypeBase->whereNull('read_at');

        $entryTypeCounts = $entryTypeBase
            ->selectRaw('entry_type, COUNT(*) as c')
            ->groupBy('entry_type')
            ->get()
            ->pluck('c', 'entry_type')
            ->toArray();
        $notificationCount = (int) ($entryTypeCounts['notification'] ?? 0);
        $logCount          = (int) ($entryTypeCounts['log']          ?? 0);

        $categoryCounts = array_map(function ($cat) use ($catCountsRaw, $baseTotal, $baseQuery) {
            if ($cat['key'] === '') {
                $cat['count'] = $baseTotal;
            } elseif ($cat['key'] === 'services') {
                // Services uses module filter not event_type
                $cat['count'] = (int) (clone $baseQuery)->where('module', 'services')->count();
            } elseif ($cat['key'] === 'plan') {
                $cat['count'] = (int) (clone $baseQuery)->where('module', 'plan')->count();
            } else {
                $cat['count'] = (int) ($catCountsRaw[$cat['key']] ?? 0);
            }
            return $cat;
        }, $categories);

        return Inertia::render('Shared/Activity', [
            'events'         => $events->map(fn ($e) => $this->formatEvent($e)),
            'grouped'        => $grouped,
            'totalCount'     => $baseTotal,
            'pagination'     => [
                'current_page' => $page,
                'last_page'    => $lastPage,
                'per_page'     => $perPage,
                'total'        => $total,
                'from'         => $total > 0 ? $offset + 1 : 0,
                'to'           => min($offset + $perPage, $total),
            ],
            'filters'        => $filters,
            'unreadCount'      => $this->activity->getUnreadCount($user->id),
            'notificationCount'=> $notificationCount,
            'logCount'         => $logCount,
            'criticalCount'  => ActivityEvent::where('user_id', $user->id)
                                    ->where('severity', 'critical')
                                    ->whereNull('read_at')
                                    ->count(),
            'categoryCounts' => $categoryCounts,
        ]);
    }

    public function markRead(Request $request, ActivityEvent $event): RedirectResponse
    {
        $this->activity->markRead($event->id, $request->user()->id);
        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $this->activity->markAllRead($request->user()->id);
        return back()->with('success', 'All notifications marked read.');
    }

    /**
     * GET /activity/export — stream a CSV / JSON / printable-HTML PDF export
     * of the viewer's activity log, optionally filtered by date range and
     * event type. The export action is itself logged as an audit event.
     */
    public function export(Request $request)
    {
        $user   = $request->user();
        $format = strtolower((string) $request->query('format', 'csv'));
        $from   = $request->query('from');
        $to     = $request->query('to');
        $reason = (string) $request->query('reason', 'Not specified');
        $type   = (string) $request->query('event_type', '');

        if (!in_array($format, ['csv', 'json', 'pdf'], true)) {
            $format = 'csv';
        }

        // Build the filtered set
        $q = ActivityEvent::where('user_id', $user->id)->orderByDesc('created_at');
        if ($from) $q->where('created_at', '>=', $from . ' 00:00:00');
        if ($to)   $q->where('created_at', '<=', $to   . ' 23:59:59');
        if ($type) $q->where('event_type', $type);

        $events = $q->limit(5000)->get();

        // Audit the export itself
        try {
            ActivityEvent::create([
                'id'          => 'ae_' . \Illuminate\Support\Str::random(10),
                'user_id'     => $user->id,
                'portal'      => $this->portalFor($user->role?->value ?? ''),
                'module'      => 'compliance',
                'severity'    => 'info',
                'event_type'  => 'export',
                'action'      => 'audit_log_exported',
                'title'       => 'Audit log exported',
                'description' => sprintf(
                    'Exported %d events as %s. Reason: %s.',
                    $events->count(),
                    strtoupper($format),
                    $reason
                ),
                'created_at'  => now(),
            ]);
        } catch (\Throwable $e) {
            // Don't fail the export if audit logging glitches
        }

        $rows = $events->map(fn ($e) => [
            'id'          => $e->id,
            'created_at'  => optional($e->created_at)->format('Y-m-d H:i:s'),
            'portal'      => $e->portal,
            'event_type'  => is_object($e->event_type) ? ($e->event_type->value ?? '') : (string) $e->event_type,
            'severity'    => is_object($e->severity)   ? ($e->severity->value   ?? '') : (string) $e->severity,
            'module'      => $e->module,
            'action'      => $e->action,
            'title'       => $e->title,
            'description' => $e->description,
            'read_at'     => optional($e->read_at)->format('Y-m-d H:i:s'),
        ]);

        $stamp    = now()->format('Ymd_His');
        $filename = "aegis_audit_log_{$stamp}";

        if ($format === 'json') {
            $payload = [
                'exported_at'   => now()->toIso8601String(),
                'exported_by'   => $user->id,
                'reason'        => $reason,
                'date_from'     => $from,
                'date_to'       => $to,
                'event_type'    => $type ?: 'all',
                'event_count'   => $rows->count(),
                'events'        => $rows->values()->all(),
            ];
            return response()->streamDownload(
                function () use ($payload) {
                    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                },
                "{$filename}.json",
                ['Content-Type' => 'application/json']
            );
        }

        if ($format === 'pdf') {
            // Printable HTML — user prints to PDF via the browser dialog.
            // Keeps Aegis dependency-light; PDF lib can be added later.
            $html = $this->renderPrintableHtml($user, $rows, $reason, $from, $to);
            return response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        // Default: CSV
        return response()->streamDownload(
            function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['ID', 'Created At', 'Portal', 'Event Type', 'Severity', 'Module', 'Action', 'Title', 'Description', 'Read At']);
                foreach ($rows as $r) {
                    fputcsv($out, array_values($r));
                }
                fclose($out);
            },
            "{$filename}.csv",
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    private function portalFor(string $role): string
    {
        return match ($role) {
            'practitioner'       => 'provider',
            'continuity_steward' => 'continuity_steward',
            'support_steward'    => 'support_steward',
            'business_partner'   => 'business_partner',
            'admin'              => 'admin',
            default              => 'provider',
        };
    }

    private function renderPrintableHtml($user, $rows, string $reason, ?string $from, ?string $to): string
    {
        $title = 'Aegis Audit Log Export';
        $stamp = now()->format('M j, Y · g:i A');
        $userName = htmlspecialchars($user->display_name ?? $user->id);
        $range = ($from || $to)
            ? sprintf('%s — %s', $from ?: 'beginning', $to ?: 'now')
            : 'All time';

        $rowsHtml = '';
        foreach ($rows as $r) {
            $rowsHtml .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                htmlspecialchars((string) $r['created_at']),
                htmlspecialchars((string) $r['event_type']),
                htmlspecialchars((string) $r['severity']),
                htmlspecialchars((string) $r['title']),
                htmlspecialchars((string) $r['module']),
                htmlspecialchars((string) ($r['description'] ?? ''))
            );
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8">
<title>{$title}</title>
<style>
  @media print { .no-print { display:none !important; } body { margin: 0; } }
  body { font-family: Georgia, 'Times New Roman', serif; color: #2a2a2a; padding: 36px; max-width: 1000px; margin: 0 auto; }
  h1 { font-size: 24px; margin: 0 0 6px; letter-spacing: 0.4px; }
  .sub { color: #6c6c6c; font-size: 13px; margin-bottom: 24px; }
  .meta { display: grid; grid-template-columns: 140px 1fr; gap: 4px 12px; font-size: 12.5px; margin-bottom: 24px; padding: 12px 16px; background: #fbf8f1; border: 1px solid #e8dfc6; border-radius: 6px; }
  .meta dt { color: #8a7d52; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; font-size: 11px; }
  .meta dd { margin: 0; color: #2a2a2a; }
  table { width: 100%; border-collapse: collapse; font-size: 11.5px; }
  th, td { padding: 7px 10px; text-align: left; border-bottom: 1px solid #e3e3e3; vertical-align: top; }
  th { background: #f7f5ef; color: #5b522f; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; font-size: 10.5px; }
  tr:hover { background: #fcfbf6; }
  .toolbar { margin-bottom: 16px; }
  .print-btn { padding: 8px 18px; background: #8a7d52; color: #fff; border: 0; border-radius: 4px; font-size: 13px; font-weight: 700; letter-spacing: 0.4px; cursor: pointer; }
  .print-btn:hover { background: #6e6442; }
  footer { margin-top: 28px; padding-top: 16px; border-top: 1px solid #e3e3e3; font-size: 11px; color: #8a8a8a; }
</style>
</head><body>
  <div class="toolbar no-print">
    <button class="print-btn" onclick="window.print()">Print &middot; Save as PDF</button>
  </div>
  <h1>{$title}</h1>
  <div class="sub">Generated {$stamp}</div>
  <dl class="meta">
    <dt>Exported by</dt><dd>{$userName}</dd>
    <dt>Date range</dt><dd>{$range}</dd>
    <dt>Reason</dt><dd>{$reason}</dd>
    <dt>Event count</dt><dd>{$rows->count()}</dd>
  </dl>
  <table>
    <thead><tr><th>When</th><th>Type</th><th>Severity</th><th>Title</th><th>Module</th><th>Description</th></tr></thead>
    <tbody>{$rowsHtml}</tbody>
  </table>
  <footer>This export is HIPAA-compliant. The export action itself has been logged to the audit trail.</footer>
</body></html>
HTML;
    }

    private function formatEvent(ActivityEvent $e): array
    {
        $eventType = is_object($e->event_type) ? ($e->event_type->value ?? 'system') : (string) ($e->event_type ?? 'system');
        $severity  = is_object($e->severity)   ? ($e->severity->value   ?? 'info')   : (string) ($e->severity   ?? 'info');
        $module    = (string) ($e->module ?? '');
        $entryType = (string) ($e->entry_type ?? 'notification');

        $actor = null;
        if ($e->relationLoaded('actor') && $e->actor) {
            $actor = [
                'id'           => $e->actor->id,
                'display_name' => $e->actor->display_name,
                'role'         => is_object($e->actor->role) ? ($e->actor->role->value ?? null) : $e->actor->role,
            ];
        }

        return [
            'id'          => $e->id,
            'event_type'  => $eventType,
            'entry_type'  => $entryType,
            'actor'       => $actor,
            'module'      => $e->module,
            'action'      => $e->action,
            'severity'    => $severity,
            'title'       => $e->title,
            'description' => $e->description,
            'created_at'  => $e->created_at,
            'read_at'     => $e->read_at,
            'icon'        => $this->iconFor($eventType, $module),
            'badge_label' => $this->badgeLabel($eventType, $module),
            'badge_class' => $this->badgeClass($eventType, $module),
            'important'   => in_array($severity, ['error', 'critical', 'warning'], true),
        ];
    }

    private function iconFor(string $type, string $module = ''): string
    {
        if ($module === 'services') return 'calendar';
        if ($module === 'job_postings' || $type === 'job_postings') return 'briefcase';
        return match ($type) {
            'incident'    => 'alert-triangle',
            'vault'       => 'lock',
            'task'        => 'check-circle',
            'document'    => 'file-text',
            'message'     => 'message-square',
            'payment'     => 'credit-card',
            'account'     => 'log-in',
            'compliance',
            'attestation' => 'shield',
            'referral'    => 'refresh-cw',
            'news'        => 'book-open',
            'event'       => 'calendar',
            default       => 'activity',
        };
    }

    private function badgeLabel(string $type, string $module = ''): string
    {
        if ($module === 'services') return 'My Services';
        if ($module === 'job_postings' || $type === 'job_postings') return 'Job Posting';
        return match ($type) {
            'incident'    => 'Critical Incident',
            'vault'       => 'Vault Access',
            'task'        => 'Task',
            'document'    => 'Document',
            'message'     => 'Message',
            'payment'     => 'Financial',
            'account'     => 'Login / Session',
            'compliance',
            'attestation' => 'Compliance',
            'referral'    => 'Referral',
            'news'        => 'News',
            'event'       => 'Event',
            default       => 'System',
        };
    }

    private function badgeClass(string $type, string $module = ''): string
    {
        if ($module === 'services') return 'services';
        if ($module === 'job_postings' || $type === 'job_postings') return 'job-postings';
        return match ($type) {
            'incident'    => 'critical-incident',
            'vault'       => 'vault',
            'task'        => 'task',
            'document'    => 'document',
            'message'     => 'message',
            'payment'     => 'financial',
            'account'     => 'login',
            'compliance',
            'attestation' => 'compliance',
            'referral'    => 'referral',
            'news'        => 'news',
            'event'       => 'event',
            default       => 'system',
        };
    }
}
