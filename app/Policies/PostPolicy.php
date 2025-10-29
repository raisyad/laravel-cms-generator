<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Post;

class PostPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Post $model): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, Post $model): bool { return true; }
    public function delete(User $user, Post $model): bool { return true; }
    public function restore(User $user, Post $model): bool { return true; }
    public function forceDelete(User $user, Post $model): bool { return false; }
}
