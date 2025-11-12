<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncJobConfiguration extends Model
{
    protected $fillable = [
        'institusi_id',
        'job_class',
        'job_name',
        'description',
        'default_parameters',
        'is_active',
        'order',
        'category',
    ];

    protected function casts(): array
    {
        return [
            'default_parameters' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function institusi(): BelongsTo
    {
        return $this->belongsTo(Institusi::class);
    }
}
