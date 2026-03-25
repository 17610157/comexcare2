<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('distributions', function (User $user) {
    return $user !== null;
});

Broadcast::channel('distribution.{distributionId}', function (User $user, int $distributionId) {
    return true;
});
