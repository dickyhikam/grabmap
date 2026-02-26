<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PricingCategory extends Model
{
    protected $fillable = ['name', 'slug', 'sort_order'];

    public function items(): HasMany
    {
        return $this->hasMany(PricingItem::class)->orderBy('sort_order');
    }
}
