<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiUsageLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['company_id', 'endpoint_type', 'response_status'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    const COST_PER_1000 = [
        'search_suggestions'     => 4.00,
        'search_text'            => 4.00,
        'get_place'              => 4.00,
        'reverse_geocode'        => 4.00,
        'calculate_route'        => 5.00,
        'calculate_route_matrix' => 5.00,
    ];

    const ENDPOINT_LABELS = [
        'search_suggestions'     => 'Search Suggestions',
        'search_text'            => 'Search Text',
        'get_place'              => 'Get Place',
        'reverse_geocode'        => 'Reverse Geocode',
        'calculate_route'        => 'Calculate Route',
        'calculate_route_matrix' => 'Route Matrix',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function estimateCost(string $endpointType, int $count): float
    {
        $rate = self::COST_PER_1000[$endpointType] ?? 0;
        return ($count / 1000) * $rate;
    }
}
