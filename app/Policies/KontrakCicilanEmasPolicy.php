<?php

namespace App\Policies;

use App\Models\KontrakCicilanEmas;
use App\Models\User;

class KontrakCicilanEmasPolicy
{
    public function update(User $user, KontrakCicilanEmas $kontrak): bool
    {
        return $kontrak->user_id === $user->id;
    }

    public function delete(User $user, KontrakCicilanEmas $kontrak): bool
    {
        return $kontrak->user_id === $user->id;
    }
}
