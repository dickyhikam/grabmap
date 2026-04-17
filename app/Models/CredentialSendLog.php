<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CredentialSendLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'sent_by', 'sent_to_email',
        'include_password', 'status', 'failed_reason',
    ];

    protected $casts = [
        'include_password' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
