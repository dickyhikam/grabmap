<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyFeature extends Model
{
    protected $fillable = ['company_id', 'feature_key', 'is_enabled', 'settings'];

    protected $casts = [
        'is_enabled' => 'boolean',
        'settings'   => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
