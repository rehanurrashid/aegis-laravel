<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\Admin\HelpArticlePublished;
use App\Models\AdminAuditLog;
use App\Models\HelpArticle;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Admin authoring of help-center articles. Articles support category,
 * role visibility, sort order, and a published flag.
 */
class AdminHelpArticleService
{
    public function listAll(?string $roleVisibility = null): Collection
    {
        $q = HelpArticle::query();
        if ($roleVisibility !== null) {
            $q->where(function ($w) use ($roleVisibility) {
                $w->where('role_visibility', 'all')
                  ->orWhere('role_visibility', $roleVisibility);
            });
        }
        return $q->orderBy('category')->orderBy('sort_order')->get();
    }

    public function find(string $id): ?HelpArticle
    {
        return HelpArticle::find($id);
    }

    public function create(User $admin, array $data): HelpArticle
    {
        $article = HelpArticle::create([
            'id'              => 'ha_' . Str::lower(Str::random(12)),
            'category'        => $data['category'] ?? null,
            'title'           => $data['title'],
            'body'            => $data['body'],
            'role_visibility' => $data['role_visibility'] ?? 'all',
            'sort_order'      => (int) ($data['sort_order'] ?? 0),
            'published'       => (int) ($data['published'] ?? 0),
            'created_at'      => now(),
        ]);

        $this->audit($admin, 'create_help_article', $article->id, [
            'title'    => $article->title,
            'category' => $article->category,
        ]);

        if ($article->published) {
            event(new HelpArticlePublished($article, $admin));
        }

        return $article;
    }

    public function update(User $admin, HelpArticle $article, array $data): HelpArticle
    {
        $allowed = ['category', 'title', 'body', 'role_visibility', 'sort_order'];
        $article->update(array_intersect_key($data, array_flip($allowed)));

        $this->audit($admin, 'update_help_article', $article->id, array_intersect_key($data, array_flip($allowed)));
        return $article->fresh();
    }

    public function publish(User $admin, HelpArticle $article): HelpArticle
    {
        $article->update(['published' => 1]);
        $this->audit($admin, 'publish_help_article', $article->id, []);
        event(new HelpArticlePublished($article, $admin));
        return $article->fresh();
    }

    public function unpublish(User $admin, HelpArticle $article): HelpArticle
    {
        $article->update(['published' => 0]);
        $this->audit($admin, 'unpublish_help_article', $article->id, []);
        return $article->fresh();
    }

    public function delete(User $admin, HelpArticle $article): bool
    {
        $this->audit($admin, 'delete_help_article', $article->id, ['title' => $article->title]);
        return (bool) $article->delete();
    }

    public function reorder(User $admin, array $orderedIds): void
    {
        foreach ($orderedIds as $idx => $id) {
            HelpArticle::where('id', $id)->update(['sort_order' => $idx]);
        }
        $this->audit($admin, 'reorder_help_articles', 'bulk', ['count' => count($orderedIds)]);
    }

    private function audit(User $admin, string $action, string $targetId, array $meta): void
    {
        AdminAuditLog::create([
            'id'          => 'aal_' . Str::lower(Str::random(12)),
            'admin_id'    => $admin->id,
            'action'      => $action,
            'target_type' => 'help_article',
            'target_id'   => $targetId,
            'meta_json'   => json_encode($meta),
            'created_at'  => now(),
        ]);
    }
}
