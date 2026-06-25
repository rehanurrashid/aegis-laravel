<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\HelpArticle;
use App\Models\User;

class HelpArticlePolicy
{
    /** Published articles are visible to everyone (including unauthenticated public pages). */
    public function view(?User $user, HelpArticle $article): bool
    {
        if ($article->published_at !== null) {
            return true;
        }

        return $user !== null && $this->roleEnum($user) === UserRole::Admin;
    }

    /** Only admins author or edit help articles. */
    public function manage(User $user): bool
    {
        return $this->roleEnum($user) === UserRole::Admin;
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, HelpArticle $article): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, HelpArticle $article): bool
    {
        return $this->manage($user);
    }

    public function publish(User $user, HelpArticle $article): bool
    {
        return $this->manage($user);
    }

    private function roleEnum(User $user): ?UserRole
    {
        return $user->role instanceof UserRole
            ? $user->role
            : UserRole::tryFrom((string) $user->role);
    }
}
