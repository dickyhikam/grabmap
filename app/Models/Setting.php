<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'app_settings';
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['key', 'value', 'updated_by', 'updated_at'];

    public static function get(string $key, $default = null)
    {
        return static::find($key)?->value ?? $default;
    }

    public static function put(string $key, $value, ?string $by = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value, 'updated_by' => $by, 'updated_at' => now()]
        );
    }
}
