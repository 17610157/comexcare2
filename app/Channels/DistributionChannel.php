<?php

namespace App\Channels;

use App\Models\User;

class DistributionChannel
{
    public function join(User $user, int $distributionId): bool
    {
        return true;
    }
}
