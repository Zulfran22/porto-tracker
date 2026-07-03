<?php

namespace App\Policies;

use App\Models\CustomCategory;
use App\Models\User;

class CustomCategoryPolicy
{
    public function delete(User $user, CustomCategory $category): bool
    {
        return $category->user_id === $user->id;
    }
}
