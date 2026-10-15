<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\User;
use App\Notifications\AccountEmail;

class AccountEmailObserver
{
    public function updated(User $user): void
    {
        if ($user->wasChanged('password')) {
            $user->notify(new AccountEmail(
                'Password akun telah diubah',
                'Password akun Ganesha Nusa kamu berhasil diperbarui.',
                'Buka akun',
                route('login'),
            ));
        }
    }
}
