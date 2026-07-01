<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;
use App\Enums\ActivitySeverity;
use App\Services\ActivityService;
use App\Services\MessagingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MessagesController extends Controller
{
    public function __construct(private MessagingService $messaging) {}

    public function index(Request $request): Response
    {
        $user       = $request->user();
        $viewerRole = $user->role?->value ?? '';
        $isBp       = $viewerRole === 'business_partner';

        $rawThreads = $this->messaging->getThreads($user->id);

        // Pre-load counterpart users for thread enrichment (avoid N+1)
        $allParticipantIds = $rawThreads->flatMap(function ($t) {
            return json_decode($t->participant_ids ?? '[]', true) ?: [];
        })->unique()->reject(fn ($id) => $id === $user->id)->values();
        $userMap = User::whereIn('id', $allParticipantIds)->get()->keyBy('id');

        // Enrich each thread with counterpart + bucket + last-message preview
        $threads = $rawThreads->map(function ($t) use ($user, $userMap, $isBp) {
            $participantIds = json_decode($t->participant_ids ?? '[]', true) ?: [];
            $counterpartId  = collect($participantIds)->first(fn ($id) => $id !== $user->id);
            $cp             = $counterpartId ? $userMap->get($counterpartId) : null;

            $lastMsg = Message::where('thread_id', $t->id)
                ->orderByDesc('sent_at')
                ->first();

            $body    = $lastMsg?->body ?? '';
            $snippet = Str::limit($body, 60);
            $isFromSelf = $lastMsg && $lastMsg->sender_id === $user->id;
            $preview = $isFromSelf && $snippet ? 'You: ' . $snippet : $snippet;

            // Per-thread unread for the viewer
            $unreadCount = Message::where('thread_id', $t->id)
                ->where('sender_id', '!=', $user->id)
                ->get()
                ->filter(function ($m) use ($user) {
                    $readBy = json_decode($m->read_by ?? '[]', true) ?: [];
                    return !in_array($user->id, $readBy, true);
                })
                ->count();

            // Bucket assignment is role-aware
            $cpRole = $cp?->role?->value ?? '';
            if (!empty($t->is_continuity_contact)) {
                $bucket = 'continuity';
            } elseif ($isBp && $cpRole === 'practitioner') {
                $bucket = 'practitioners';
            } elseif (!$isBp && $cpRole === 'business_partner') {
                $bucket = 'business';
            } else {
                $bucket = 'network';
            }

            return [
                'id'                    => $t->id,
                'title'                 => $t->title,
                'is_archived'           => !empty($t->archived_at),
                'is_continuity_contact' => (bool) ($t->is_continuity_contact ?? false),
                'is_muted'              => (bool) ($t->is_muted ?? false),
                'muted_until'           => $t->muted_until,
                'bucket'                => $bucket,
                'last_message_at'       => $t->last_message_at,
                'last_message_snippet'  => $preview ?: 'No messages yet',
                'last_message_unread'   => $unreadCount > 0,
                'unread_count'          => $unreadCount,
                'counterpart'           => $cp ? [
                    'id'             => $cp->id,
                    'display_name'   => $cp->display_name,
                    'avatar_initials'=> $cp->avatar_initials ?? Str::upper(Str::substr($cp->display_name ?? 'U', 0, 2)),
                    'avatar_url'     => $cp->avatar_url ?? null,
                    'role'           => $cpRole,
                    'role_label'     => $this->roleLabel($cpRole),
                    'organization'   => $cp->organization ?? null,
                    'email'          => $cp->email ?? null,
                    'phone'          => $cp->phone ?? null,
                    'location'       => $cp->location ?? null,
                    'slug'           => $cp->slug ?? null,
                ] : null,
            ];
        })->values();

        // Bucket counts
        $bucketDefs = $this->bucketsFor($viewerRole);
        $bucketCounts = [];
        foreach ($bucketDefs as $b) {
            $bucketCounts[$b['key']] = $b['key'] === 'all'
                ? $threads->count()
                : $threads->where('bucket', $b['key'])->count();
        }

        // Active thread resolution
        $activeId = $request->query('thread');
        $active = $activeId
            ? $rawThreads->firstWhere('id', $activeId)
            : $rawThreads->first();

        $activeThreadFormatted = $active ? $threads->firstWhere('id', $active->id) : null;

        $activeMessages = $active
            ? $this->messaging->getMessages($active)->map(fn ($m) => [
                'id'          => $m->id,
                'thread_id'   => $m->thread_id,
                'sender_id'   => $m->sender_id,
                'body'        => $m->body,
                'sent_at'     => $m->sent_at,
                'read_by'     => json_decode($m->read_by ?? '[]', true) ?: [],
                'is_sent'     => $m->sender_id === $user->id,
                'attachments' => $this->formatAttachments($m->attachments ?? [], $m->id),
            ])
            : collect();

        // Unread counts as flat object (Inertia-friendly)
        $unreadCounts = [];
        foreach ($threads as $t) {
            if (($t['unread_count'] ?? 0) > 0) {
                $unreadCounts[$t['id']] = $t['unread_count'];
            }
        }

        // Recipient candidates (excluding self)
        $recipients = User::where('id', '!=', $user->id)
            ->orderBy('display_name')
            ->limit(200)
            ->get(['id', 'display_name', 'role', 'organization', 'avatar_initials', 'avatar_path'])
            ->map(fn ($u) => [
                'id'              => $u->id,
                'display_name'    => $u->display_name,
                'role'            => $u->role?->value ?? null,
                'role_label'      => $this->roleLabel($u->role?->value ?? ''),
                'organization'    => $u->organization ?? null,
                'avatar_initials' => $u->avatar_initials ?? Str::upper(Str::substr($u->display_name ?? 'U', 0, 2)),
                'avatar_url'      => $u->avatar_url ?? null,
            ]);

        // Auto-mark active thread as read for the viewer
        if ($active) {
            $this->messaging->markRead($active, $user);
        }

        return Inertia::render('Shared/Messages', [
            'threads'        => $threads,
            'activeThread'   => $activeThreadFormatted,
            'activeMessages' => $activeMessages->values(),
            'recipients'     => $recipients,
            'unreadCounts'   => (object) $unreadCounts,
            'buckets'        => $bucketDefs,
            'bucketCounts'   => (object) $bucketCounts,
            'currentUserId'  => $user->id,
            'currentUserInitials' => $user->avatar_initials
                ?? Str::upper(Str::substr($user->display_name ?? 'U', 0, 2)),
            'currentUserAvatarUrl' => $user->avatar_url,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'participant_ids'   => 'required|array|min:1',
            'participant_ids.*' => 'exists:users,id',
            'title'             => 'nullable|string|max:200',
            'body'              => 'required|string|min:1|max:5000',
        ]);
        $participants = array_merge([$request->user()->id], $data['participant_ids']);
        $thread = $this->messaging->createThread($participants, $data['title'] ?? null);
        $this->messaging->sendMessage($thread, $request->user(), $data['body']);
        return back()->with('success', 'Message sent.');
    }

    public function reply(Request $request, MessageThread $thread): RedirectResponse
    {
        $this->authorize('send', $thread);

        $data = $request->validate([
            'body'        => 'nullable|string|max:5000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:20480', // 20 MB per file
        ]);

        // Store uploaded files — paths saved first, URLs generated after we have msg->id
        $fileMeta = [];
        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store('messages/attachments', 'public');
            $fileMeta[] = [
                'name' => $file->getClientOriginalName(),
                'size' => $this->humanFileSize($file->getSize()),
                'mime' => $file->getMimeType(),
                'path' => $path,
            ];
        }

        $msg = $this->messaging->sendMessage($thread, $request->user(), $data['body'] ?? '');

        if (!empty($fileMeta)) {
            // Now we have $msg->id — build secure download URLs per attachment index
            $attachmentMeta = array_map(function ($item, $index) use ($msg) {
                $item['url'] = route('messages.attachment.download', [$msg->id, $index]);
                return $item;
            }, $fileMeta, array_keys($fileMeta));

            $msg->update(['attachments' => $attachmentMeta]);
        }

        return back()->with('success', 'Reply sent.');
    }

    public function mute(Request $request, MessageThread $thread): RedirectResponse
    {
        $this->authorize('read', $thread);
        $hours = (int) $request->input('hours', 8);
        $until = $hours === 0 ? null : now()->addHours($hours);
        $thread->update(['is_muted' => true, 'muted_until' => $until]);

        $user  = $request->user();
        $label = $hours === 0 ? 'indefinitely' : "for {$hours}h";
        app(ActivityService::class)->log(
            $user->id,
            $user->role?->portal() ?? 'provider',
            'message',
            ActivitySeverity::Info,
            'messages_muted',
            'Conversation muted',
            "Thread {$thread->id} muted {$label}.",
            'message_thread',
            $thread->id,
            null,
            'log',
            $user->id
        );

        return back()->with('success', 'Conversation muted.');
    }

    public function unmute(Request $request, MessageThread $thread): RedirectResponse
    {
        $this->authorize('read', $thread);
        $thread->update(['is_muted' => false, 'muted_until' => null]);

        $user = $request->user();
        app(ActivityService::class)->log(
            $user->id,
            $user->role?->portal() ?? 'provider',
            'message',
            ActivitySeverity::Info,
            'messages_unmuted',
            'Conversation unmuted',
            "Notifications restored for thread {$thread->id}.",
            'message_thread',
            $thread->id,
            null,
            'log',
            $user->id
        );

        return back()->with('success', 'Notifications unmuted.');
    }

    public function export(Request $request, MessageThread $thread): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('read', $thread);
        $format   = $request->input('format', 'txt');
        $messages = $this->messaging->getMessages($thread);
        $filename = 'chat-export-' . $thread->id . '.' . ($format === 'json' ? 'json' : 'txt');

        return response()->streamDownload(function () use ($messages, $format, $thread) {
            if ($format === 'json') {
                echo json_encode($messages->map(fn ($m) => [
                    'sent_at' => $m->sent_at,
                    'sender'  => $m->sender_id,
                    'body'    => $m->body,
                ])->toArray(), JSON_PRETTY_PRINT);
            } else {
                echo "Aegis Chat Export — Thread: {$thread->title}\n";
                echo str_repeat('-', 60) . "\n\n";
                foreach ($messages as $msg) {
                    echo "[{$msg->sent_at}] {$msg->sender_id}:\n{$msg->body}\n\n";
                }
            }
        }, $filename, [
            'Content-Type' => $format === 'json' ? 'application/json' : 'text/plain',
        ]);
    }

    public function markRead(Request $request, MessageThread $thread): RedirectResponse
    {
        $this->authorize('read', $thread);
        $this->messaging->markRead($thread, $request->user());
        return back();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function formatAttachments(mixed $raw, string $msgId = ''): array
    {
        if (empty($raw)) return [];
        $items = is_array($raw) ? $raw : (json_decode($raw, true) ?: []);
        return array_values(array_map(function ($a, $index) use ($msgId) {
            // Prefer stored URL; fall back to generating from route if available
            $url = $a['url'] ?? '';
            if (!$url && $msgId) {
                try {
                    $url = route('messages.attachment.download', [$msgId, $index]);
                } catch (\Throwable) {
                    $url = '#';
                }
            }
            return [
                'name' => $a['name'] ?? 'file',
                'size' => $a['size'] ?? '',
                'mime' => $a['mime'] ?? 'application/octet-stream',
                'url'  => $url ?: '#',
            ];
        }, $items, array_keys($items)));
    }

    private function humanFileSize(int $bytes): string
    {
        if ($bytes < 1024)       return $bytes . ' B';
        if ($bytes < 1048576)    return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'practitioner'       => 'Practitioner',
            'continuity_steward' => 'Continuity Steward',
            'support_steward'    => 'Support Steward',
            'business_partner'   => 'Business Partner',
            'admin'              => 'Admin',
            default              => 'Member',
        };
    }

    /**
     * Bucket definitions per viewer role.
     */
    private function bucketsFor(string $role): array
    {
        if ($role === 'business_partner') {
            return [
                ['key' => 'all',           'label' => 'All',           'tip' => 'All conversations'],
                ['key' => 'practitioners', 'label' => 'Practitioners', 'tip' => 'Threads with practitioners you serve'],
                ['key' => 'network',       'label' => 'Network',       'tip' => 'Other professional connections'],
            ];
        }
        return [
            ['key' => 'all',         'label' => 'All',          'tip' => 'All conversations'],
            ['key' => 'continuity',  'label' => 'Continuity',   'tip' => 'Threads with your CS, SS, or about an active incident'],
            ['key' => 'business',    'label' => 'Business',     'tip' => 'Threads with Business Partners'],
            ['key' => 'network',     'label' => 'Network',      'tip' => 'Other practitioners in your network'],
        ];
    }
}
