<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'description',
    ];

    /**
     * Serialize dates to strict ISO 8601 format (YYYY-MM-DDTHH:MM:SS).
     *
     * Laravel's default serialises timestamps with a space separator
     * ("YYYY-MM-DD HH:MM:SS"), which Safari's JS engine rejects as
     * "Invalid Date". The T-separator is required by the ISO 8601 standard
     * and is the only format all browsers parse reliably.
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d\TH:i:s');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
