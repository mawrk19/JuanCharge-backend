<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class KioskUser extends User
{
    protected $table = 'users';

    /**
     * Keep legacy model references working after user table unification.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('kiosk_role', function (Builder $builder) {
            $builder->where($builder->getModel()->getTable() . '.role_id', Role::KIOSK_USER);
        });
    }
}
