<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'email', 'action', 'status',
        'ip_address', 'user_agent', 'failed_reason',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    const ACTION_LOGIN               = 'login';
    const ACTION_REGISTER            = 'register';
    const ACTION_LOGOUT              = 'logout';
    const ACTION_EMAIL_VERIFICATION  = 'email_verification';
    const ACTION_RESEND_VERIFICATION = 'resend_verification';

    const STATUS_SUCCESS = 'success';
    const STATUS_FAIL    = 'fail';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
