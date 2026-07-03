<?php

namespace App\Policies;

use App\Models\Portofolio;
use App\Models\User;

class PortofolioPolicy
{
    public function update(User $user, Portofolio $portofolio): bool
    {
        return $portofolio->user_id === $user->id;
    }

    public function delete(User $user, Portofolio $portofolio): bool
    {
        return $portofolio->user_id === $user->id;
    }
}
