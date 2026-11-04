<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu pertanyaan / kebutuhan support dari klien yang perlu dijawab tim. */
class CompanyClarification extends Model
{
    use \App\Models\Concerns\HasUuidRouteKey;

    /** Belum dijawab · sudah dijawab tapi menunggu tanggapan klien · selesai. */
    public const STATUSES = ['open', 'waiting', 'clarified'];

    protected $fillable = ['company_id', 'question', 'answer', 'status', 'created_by'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isDone(): bool
    {
        return $this->status === 'clarified';
    }
}
