<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\NewsComment;
use App\Models\NewsPost;
use App\Models\User;

class NewsPolicy
{
    /** Any authenticated user (any role) can read news. */
    public function view(User $user, NewsPost $post): bool
    {
        return true;
    }

    /** Admins can post org-wide news. Practitioners post to their own followers. */
    public function create(User $user): bool
    {
        $role = $this->roleEnum($user);
        return $role === UserRole::Admin || $role === UserRole::Practitioner;
    }

    public function update(User $user, NewsPost $post): bool
    {
        return $user->id === $post->author_id || $this->roleEnum($user) === UserRole::Admin;
    }

    public function delete(User $user, NewsPost $post): bool
    {
        return $this->update($user, $post);
    }

    public function comment(User $user, NewsPost $post): bool
    {
        return $this->view($user, $post);
    }

    public function deleteComment(User $user, NewsComment $comment): bool
    {
        return $user->id === $comment->author_id || $this->roleEnum($user) === UserRole::Admin;
    }

    private function roleEnum(User $user): ?UserRole
    {
        return $user->role instanceof UserRole
            ? $user->role
            : UserRole::tryFrom((string) $user->role);
    }
}
