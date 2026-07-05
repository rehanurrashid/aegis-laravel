<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
use App\Events\News\EventRsvpReceived;
use App\Events\News\EventSubmitted;
use App\Events\News\NewsCommented;
use App\Events\News\NewsPostPublished;
use App\Models\NewsComment;
use App\Models\NewsEvent;
use App\Models\NewsPollVote;
use App\Models\NewsPost;
use App\Models\NewsReaction;
use App\Models\NewsTrendingTopic;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * News feed, events, comments, reactions, poll votes.
 * Per UC-PRV-240..248.
 */
class NewsService
{
    public function __construct(private ActivityService $activity) {}

    // ── Feed ────────────────────────────────────────────────────────────────

    /**
     * Full feed data for the News page index.
     * Returns all props the Vue component needs.
     */
    public function feedData(User $user, ?string $filterType = null, ?string $tag = null): array
    {
        $role = (string) ($user->role?->value ?? $user->role ?? 'practitioner');
        $userId = $user->id;

        // Base posts query
        $query = NewsPost::where('published', 1)
            ->where(function ($q) use ($role) {
                $q->where('role_visibility', 'all')
                  ->orWhere('role_visibility', $role);
            })
            ->with(['author', 'comments.author', 'reactions', 'pollVotes'])
            ->orderByDesc('pinned')
            ->orderByDesc('published_at');

        if ($filterType && $filterType !== 'all') {
            $query->where('post_type', $filterType);
        }

        $posts = $query->limit(30)->get();

        // Filter by tag client-side is fine for ≤30 posts, but also support server-side
        if ($tag) {
            $posts = $posts->filter(function ($p) use ($tag) {
                return in_array($tag, $p->tags ?? [], true);
            })->values();
        }

        // Shape posts for frontend
        $shapedPosts = $posts->map(fn($p) => $this->shapePost($p, $userId));

        // Comments keyed by post_id
        $commentsByPost = [];
        foreach ($posts as $p) {
            $commentsByPost[$p->id] = $p->comments->map(fn($c) => $this->shapeComment($c, $userId))->values()->toArray();
        }

        // Upcoming events (3 for sidebar)
        $upcoming = NewsEvent::published()
            ->approved()
            ->where('starts_at', '>=', now())
            ->where(function ($q) use ($role) {
                $q->where('role_visibility', 'all')
                  ->orWhere('role_visibility', $role);
            })
            ->orderBy('starts_at')
            ->limit(3)
            ->get()
            ->map(fn($e) => [
                'id'          => $e->id,
                'title'       => $e->title,
                'starts_at'   => $e->starts_at?->toIso8601String(),
                'ends_at'     => $e->ends_at?->toIso8601String(),
                'location'    => $e->location,
                'ceu_credits' => (float) $e->ceu_credits,
                'is_free'     => (bool) $e->is_free,
                'is_attending'=> $e->isAttending($userId),
            ])->values();

        // Trending topics
        $trending = NewsTrendingTopic::orderByDesc('score')
            ->limit(5)
            ->get()
            ->map(fn($t) => [
                'tag'        => ltrim($t->topic, '#'),
                'post_count' => $t->score,
            ])->values();

        // Stat counters
        $countToday   = NewsPost::where('published', 1)
            ->whereDate('published_at', today())
            ->count();
        $countAuthors = NewsPost::where('published', 1)
            ->distinct('author_id')
            ->count('author_id');
        $countUpcoming = NewsEvent::published()->approved()
            ->where('starts_at', '>=', now())
            ->count();

        // Count by type (for filter badges)
        $allPublished = NewsPost::where('published', 1)
            ->where(function ($q) use ($role) {
                $q->where('role_visibility', 'all')
                  ->orWhere('role_visibility', $role);
            })->get(['post_type']);

        $countByType = [
            'all'      => $allPublished->count(),
            'platform' => $allPublished->where('post_type', 'platform')->count(),
            'provider' => $allPublished->whereIn('post_type', ['provider', 'post', 'question', 'resource', 'milestone'])->count(),
            'event'    => $allPublished->whereIn('post_type', ['event', 'announcement'])->count(),
            'resource' => $allPublished->where('post_type', 'resource')->count(),
        ];

        return [
            'posts'          => $shapedPosts->values()->toArray(),
            'upcoming'       => $upcoming->toArray(),
            'trending'       => $trending->toArray(),
            'commentsByPost' => $commentsByPost,
            'countToday'     => $countToday,
            'countAuthors'   => $countAuthors,
            'countUpcoming'  => $countUpcoming,
            'countByType'    => $countByType,
        ];
    }

    private function shapePost(NewsPost $p, string $userId): array
    {
        $author   = $p->author;
        $isSelf   = $p->author_id === $userId;
        $isLiked  = $p->reactions->where('user_id', $userId)->where('reaction', 'like')->isNotEmpty();
        $isSaved  = $p->reactions->where('user_id', $userId)->where('reaction', 'save')->isNotEmpty();
        $myVote   = $p->pollVotes->where('user_id', $userId)->first();

        // Poll options with vote counts
        $pollOptions = null;
        if ($p->poll_options) {
            $votesByKey = $p->pollVotes->groupBy('option_key');
            $pollOptions = collect($p->poll_options)->map(fn($opt, $i) => [
                'label'      => is_array($opt) ? ($opt['label'] ?? $opt) : $opt,
                'key'        => is_array($opt) ? ($opt['key'] ?? (string)$i) : (string)$i,
                'votes'      => $votesByKey->get(is_array($opt) ? ($opt['key'] ?? (string)$i) : (string)$i, collect())->count(),
            ])->values()->toArray();
        }

        return [
            'id'               => $p->id,
            'title'            => $p->title,
            'body'             => $p->body ?? '',
            'post_type'        => $p->post_type,
            'is_pinned'        => (bool) $p->pinned,
            'tags'             => $p->tags ?? [],
            'links'            => $p->links ?? [],
            'poll_question'    => $p->poll_question,
            'poll_options'     => $pollOptions,
            'poll_closes_at'   => $p->poll_closes_at?->toIso8601String(),
            'my_poll_vote'     => $myVote?->option_key,
            'created_at'       => $p->created_at?->toIso8601String() ?? $p->published_at?->toIso8601String(),
            'author_id'        => $p->author_id,
            'author_name'      => $author?->display_name ?? 'Unknown',
            'author_initials'  => $author?->avatar_initials ?? '?',
            'author_slug'      => $author?->slug ?? '',
            'author_role'      => $author?->role?->value ?? 'practitioner',
            'author_avatar_mod'=> 'gold',
            'is_self_post'     => $isSelf,
            'is_liked'         => $isLiked,
            'is_saved'         => $isSaved,
            'like_count'       => $p->reactions->where('reaction', 'like')->count(),
            'comment_count'    => $p->comments->count(),
        ];
    }

    private function shapeComment(NewsComment $c, string $userId): array
    {
        $author = $c->author;
        return [
            'id'              => $c->id,
            'body'            => $c->body,
            'created_at'      => $c->created_at?->toIso8601String(),
            'author_name'     => $author?->display_name ?? 'Unknown',
            'author_initials' => $author?->avatar_initials ?? '?',
            'author_slug'     => $author?->slug ?? '',
            'author_role'     => $author?->role?->value ?? 'practitioner',
            'author_avatar_mod' => 'gold',
            'is_self'         => $c->author_id === $userId,
            'like_count'      => 0,
        ];
    }

    // ── Write ────────────────────────────────────────────────────────────────

    public function publishPost(User $author, array $data): NewsPost
    {
        // Parse comma-separated tags
        $tags = null;
        if (!empty($data['tags'])) {
            $tags = array_values(array_filter(
                array_map('trim', explode(',', (string)$data['tags']))
            ));
        }

        // Map audience → role_visibility
        $audienceMap = [
            'all'               => 'all',
            'providers'         => 'practitioner',
            'stewards'          => 'continuity_steward',
            'business_partners' => 'business_partner',
        ];
        $roleVisibility = $audienceMap[$data['audience'] ?? 'all'] ?? 'all';

        $post = NewsPost::create([
            'id'              => 'np_' . Str::lower(Str::random(12)),
            'author_id'       => $author->id,
            'title'           => $data['title'] ?? null,
            'body'            => $data['body'] ?? null,
            'post_type'       => $data['post_type'] ?? 'provider',
            'role_visibility' => $roleVisibility,
            'audience'        => $data['audience'] ?? 'all',
            'tags'            => $tags,
            'links'           => $data['links'] ?? null,
            'poll_question'   => $data['poll_question'] ?? null,
            'poll_options'    => $data['poll_options'] ?? null,
            'poll_closes_at'  => $data['poll_closes_at'] ?? null,
            'published'       => 1,
            'pinned'          => (int) ($data['pinned'] ?? 0),
            'published_at'    => now(),
            'created_at'      => now(),
        ]);

        $this->activity->log(
            $author->id, 'provider', 'event', ActivitySeverity::Info,
            'news_post_published', 'Post published',
            "You published a post to the Aegis news feed.",
            NewsPost::class, $post->id, null, 'log', $author->id,
        );

        event(new NewsPostPublished($post));
        return $post;
    }

    public function updatePost(NewsPost $post, array $data): NewsPost
    {
        $post->update([
            'title' => $data['title'] ?? $post->title,
            'body'  => $data['body'],
        ]);
        return $post->fresh();
    }

    public function deletePost(NewsPost $post): bool
    {
        return (bool) $post->delete();
    }

    public function comment(User $author, NewsPost $post, string $body): NewsComment
    {
        $comment = NewsComment::create([
            'id'         => 'nc_' . Str::lower(Str::random(12)),
            'post_id'    => $post->id,
            'author_id'  => $author->id,
            'body'       => $body,
            'created_at' => now(),
        ]);

        $this->activity->log(
            $author->id, 'provider', 'event', ActivitySeverity::Info,
            'news_comment_posted', 'Comment posted',
            "You commented on \"{$post->title}\".",
            NewsComment::class, $comment->id, $post->author_id, 'log', $author->id,
        );

        if ($post->author_id && $post->author_id !== $author->id) {
            $this->activity->log(
                $post->author_id, 'provider', 'event', ActivitySeverity::Info,
                'news_commented', "{$author->display_name} commented on your post",
                Str::limit($body, 120),
                NewsComment::class, $comment->id, $author->id, 'notification', $author->id,
            );
        }

        event(new NewsCommented($comment));
        return $comment;
    }

    public function react(User $user, NewsPost $post, string $reactionType): NewsReaction
    {
        // Check if already reacted with same type → toggle off
        $existing = NewsReaction::where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->where('reaction', $reactionType)
            ->first();

        if ($existing) {
            $existing->delete();
            return $existing;
        }

        return NewsReaction::create([
            'id'         => 'nr_' . Str::lower(Str::random(12)),
            'post_id'    => $post->id,
            'user_id'    => $user->id,
            'reaction'   => $reactionType,   // column is 'reaction' not 'reaction_type'
            'created_at' => now(),
        ]);
    }

    public function unreact(User $user, NewsPost $post): bool
    {
        return (bool) NewsReaction::where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->delete();
    }

    public function votePoll(User $user, NewsPost $post, string $optionKey): NewsPollVote
    {
        return NewsPollVote::updateOrCreate(
            ['post_id' => $post->id, 'user_id' => $user->id],
            [
                'id'         => 'nv_' . Str::lower(Str::random(12)),
                'option_key' => $optionKey,
                'created_at' => now(),
            ]
        );
    }

    public function rsvpEvent(User $attendee, NewsEvent $event, string $status = 'going'): NewsEvent
    {
        $rsvps = $event->rsvps_json ?? [];
        $rsvps[$attendee->id] = ['status' => $status, 'at' => now()->toIso8601String()];
        $event->update(['rsvps_json' => $rsvps]);

        $this->activity->log(
            $attendee->id, 'provider', 'event', ActivitySeverity::Info,
            'event_registered', 'Registered for event',
            "You registered for \"{$event->title}\".",
            NewsEvent::class, $event->id, null, 'log', $attendee->id,
        );

        event(new EventRsvpReceived($event, $attendee));
        return $event->fresh();
    }

    public function cancelRsvp(User $attendee, NewsEvent $event): NewsEvent
    {
        $rsvps = $event->rsvps_json ?? [];
        unset($rsvps[$attendee->id]);
        $event->update(['rsvps_json' => $rsvps]);

        $this->activity->log(
            $attendee->id, 'provider', 'event', ActivitySeverity::Info,
            'event_registration_cancelled', 'Event registration cancelled',
            "You cancelled your registration for \"{$event->title}\".",
            NewsEvent::class, $event->id, null, 'log', $attendee->id,
        );

        return $event->fresh();
    }

    public function submitEvent(User $submitter, array $data): NewsEvent
    {
        $event = new NewsEvent();
        $event->id              = 'ne_' . Str::lower(Str::random(12));
        $event->title           = $data['title'];
        $event->description     = $data['description'] ?? null;
        $event->location        = $data['location'] ?? null;
        $event->category        = strtolower($data['type'] ?? 'training');
        $event->starts_at       = $data['date'] ?? null;
        $event->organizer       = $data['organizer'] ?? null;
        $event->rsvp_url        = $data['external_url'] ?? null;
        $event->is_free         = ($data['price_cents'] ?? 0) === 0;
        $event->price_cents     = (int) ($data['price_cents'] ?? 0);
        $event->ceu_credits     = (float) ($data['ceu'] ?? 0);
        $event->role_visibility = 'all';
        $event->published       = 0;
        $event->status          = 'pending';
        $event->save();

        $this->activity->log(
            $submitter->id, 'provider', 'event', ActivitySeverity::Info,
            'event_submitted', 'Community event submitted for review',
            "You submitted \"{$event->title}\" for review.",
            NewsEvent::class, $event->id, null, 'log', $submitter->id,
        );

        event(new EventSubmitted($event, $submitter));
        return $event;
    }

    /** Legacy simple feed (kept for backwards compat). */
    public function feed(string $userRole, int $limit = 20): Collection
    {
        return NewsPost::where('published', 1)
            ->where(function ($q) use ($userRole) {
                $q->where('role_visibility', 'all')
                  ->orWhere('role_visibility', $userRole);
            })
            ->orderByDesc('pinned')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }
}
