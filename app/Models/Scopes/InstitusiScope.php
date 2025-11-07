<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Global scope untuk memfilter data berdasarkan institusi
 * Ini memastikan setiap query otomatis di-filter berdasarkan institusi user yang login
 */
class InstitusiScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Hanya apply jika ada user yang login dan memiliki institusi_id
        $user = Auth::user();

        if ($user && $user->institusi_id) {
            $builder->where($model->getTable() . '.institusi_id', $user->institusi_id);
        }
    }
}
