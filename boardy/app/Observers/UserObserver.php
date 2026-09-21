<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Redis;


class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        //
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void {
        if ($user->wasChanged('name')) {
            Redis::publish('user.renamed', json_encode([
                'id' => $user->id,
                'new_name' => $user->name,
            ]));
        }
    }


    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
