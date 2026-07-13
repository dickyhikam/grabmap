<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouteLink extends Model
{
    protected $fillable = [
        'code',
        'from_lat', 'from_lng',
        'to_lat', 'to_lng',
        'mode',
        'from_label', 'to_label',
        'opens',
    ];
}
