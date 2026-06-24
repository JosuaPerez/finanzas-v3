<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignBoss extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'max_health',
        'current_health',
        'experience_reward',
        'order',
        'is_defeated',
    ];

    protected function casts(): array
    {
        return [
            'is_defeated' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
