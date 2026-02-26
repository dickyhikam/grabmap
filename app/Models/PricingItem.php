<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingItem extends Model
{
    protected $fillable = [
        'pricing_category_id',
        'api_name',
        'tier',
        'tier_group',
        'tier_min',
        'tier_max',
        'als_price',
        'google_price',
        'google_free_threshold',
        'als_only',
        'is_recommended',
        'notes',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'als_price' => 'decimal:4',
            'google_price' => 'decimal:4',
            'als_only' => 'boolean',
            'is_recommended' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PricingCategory::class, 'pricing_category_id');
    }
}
