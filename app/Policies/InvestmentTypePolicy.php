<?php

namespace App\Policies;

use App\Models\InvestmentType;
use App\Models\User;

class InvestmentTypePolicy
{
    // The gram-unit type ("Emas Tunai") can never be deleted: it's the only
    // slot ever allowed to hold gram data, and store() never creates a second
    // one, so losing it would permanently remove gold tracking from the UI
    // with no way back. Every rupiah-unit type — including the other 3 seeded
    // defaults — stays fully deletable.
    public function delete(User $user, InvestmentType $type): bool
    {
        return $type->user_id === $user->id && $type->unit !== 'gram';
    }
}
