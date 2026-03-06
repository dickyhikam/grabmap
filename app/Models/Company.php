<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = ['name', 'slug', 'logo_path', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function features(): HasMany
    {
        return $this->hasMany(CompanyFeature::class);
    }

    public function isFeatureEnabled(string $key): bool
    {
        $feature = $this->features->firstWhere('feature_key', $key);
        return $feature ? (bool) $feature->is_enabled : false;
    }

    public function getFeaturesMap(): array
    {
        $keys = ['search', 'route', 'reverse_geocode', 'route_matrix'];
        $map = [];
        foreach ($keys as $key) {
            $feature = $this->features->firstWhere('feature_key', $key);
            $map[$key] = $feature ? (bool) $feature->is_enabled : false;
            $map[$key . '_settings'] = $feature?->settings ?? [];
        }
        return $map;
    }
}
