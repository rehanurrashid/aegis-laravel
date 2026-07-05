<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
use App\Events\News\EventRsvpReceived;
use App\Events\News\NewsCommented;
use App\Events\News\NewsPostPublished;
use App\Models\NewsComment;
use App\Models\NewsEvent;
use App\Models\NewsPollVote;
use App\Models\NewsPost;
use App\Models\NewsReaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * News feed, events, comments, reactions, poll votes.
 * Per UC-PRV-240..248. Activity logs fire only for authored posts/comments
 * (reactions and poll votes are silent).
 */
class NewsService
{
    public function __construct(private ActivityService $activity) {}

    public function publishPost(User $author, array $data): NewsPost
    {
        $post = NewsPost::create([
            'id'              => 'np_' . Str::lower(Str::random(12)),
            'author_id'       => $author->id,
            'title'           => $data['title'],
            'body'            => $data['body'] ?? null,
            'post_type'       => $data['post_type'] ?? 'post',
            'role_visibility' => $data['role_visibility'] ?? 'all',
            'published'       => 1,
            'pinned'          => (int) ($data['pinned'] ?? 0),
            'published_at'    => now(),
            'created_at'      => now(),
        ]);

        event(new NewsPostPublished($post));
        return $post;
    }

    public function updatePost(NewsPost $post, array $data): NewsPost
    {
        $allowed = ['title', 'body', 'post_type', 'role_visibility', 'pinned'];
        $post->update(array_intersect_key($data, array_flip($allowed)));
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

        event(new NewsCommented($comment));

        if ($post->author_id && $post->author_id !== $author->id) {
            $this->activity->log(
                $post->author_id, 'provider', 'message', ActivitySeverity::Info,
                'news_commented',
                "{$author->display_name} commented on your post",
                Str::limit($body, 120),
                'news_post', $post->id, $author->id
            );
        }

        return $comment;
    }

    public function react(User $user, NewsPost $post, string $reactionType): NewsReaction
    {
        return NewsReaction::updateOrCreate(
            ['post_id' => $post->id, 'user_id' => $user->id],
            [
                'id'            => 'nr_' . Str::lower(Str::random(12)),
                'reaction_type' => $reactionType,
                'created_at'    => now(),
            ]
        );
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

        event(new EventRsvpReceived($event, $attendee));
        return $event->fresh();
    }

    public function cancelRsvp(User $attendee, NewsEvent $event): NewsEvent
    {
        $rsvps = $event->rsvps_json ?? [];
        unset($rsvps[$attendee->id]);
        $event->update(['rsvps_json' => $rsvps]);
        return $event->fresh();
    }

    /**
     * Submit a community event for admin review.
     * Creates a new NewsEvent with status=pending (not visible on public feed until approved).
     */
    public function submitEvent(User $submitter, array $data): NewsEvent
    {
        $event = new NewsEvent();
        $event->id             = 'ne_' . Str::lower(Str::random(12));
        $event->title          = $data['title'];
        $event->description    = $data['description'] ?? null;
        $event->location       = $data['location'] ?? null;
        $event->category       = strtolower($data['type'] ?? 'training');
        $event->starts_at      = $data['date'] ?? null;
        $event->organizer      = $data['organizer'] ?? null;
        $event->rsvp_url       = $data['url'] ?? null;
        $event->is_free        = ($data['price_cents'] ?? 0) === 0;
        $event->price_cents    = (int) ($data['price_cents'] ?? 0);
        $event->ceu_credits    = (float) ($data['ceu'] ?? 0);
        $event->role_visibility = 'all';
        $event->published      = 0;
        $event->status         = 'pending';
        $event->save();

        return $event;
    }

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
