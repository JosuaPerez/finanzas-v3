<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Debt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'balance',
        'interest_rate',
        'minimum_payment',
        'type',
        'credit_limit',
        'cutoff_date',
        'payment_date',
        'original_amount',
        'currency',
        'overdraft_percentage',
        'fecha_inicio',
        'plazo_original_meses',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
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
}