<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\CustomResetPassword;
use App\Models\Debt;
use App\Models\Goal;
use App\Traits\SurvivalMechanics;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SurvivalMechanics;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'current_streak',
        'longest_streak',
        'last_action_date',
        'current_xp',
        'level',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Virtual attributes appended to every toArray() / toJson() call.
     * Inertia will include these automatically in the auth.user prop.
     *
     * @var list<string>
     */
    protected $appends = ['rank_name', 'xp_progress'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'password'           => 'hashed',
            'last_action_date'   => 'date',
        ];
    }

    // ── RPG Engine ────────────────────────────────────────────────────────────

    /**
     * Award XP to the user and level them up if the threshold is crossed.
     *
     * Level formula (inverse): level = floor(sqrt(xp / 100)) + 1
     * XP thresholds: L1→0, L2→100, L3→400, L4→900, L5→1600, …
     */
    public function addXp(int $amount): void
    {
        $this->current_xp += $amount;
        $newLevel = (int) floor(sqrt($this->current_xp / 100)) + 1;
        if ($newLevel > $this->level) {
            $this->level = $newLevel;
        }
        $this->save();
    }

    /**
     * Dynamic rank title based on the user's current level.
     */
    public function getRankNameAttribute(): string
    {
        return match (true) {
            $this->level >= 30 => 'Comandante',
            $this->level >= 20 => 'Capitán',
            $this->level >= 15 => 'Teniente',
            $this->level >= 10 => 'Sargento',
            $this->level >= 5  => 'Soldado',
            default            => 'Recluta',
        };
    }

    /**
     * Progress bar data for the current level.
     *
     * Returns ['current', 'needed', 'percentage'] relative to the current level window.
     *
     * @return array{current: int, needed: int, percentage: int}
     */
    public function getXpProgressAttribute(): array
    {
        $currentLevelXp = pow($this->level - 1, 2) * 100;
        $nextLevelXp    = pow($this->level, 2) * 100;
        $xpInLevel      = $this->current_xp - $currentLevelXp;
        $xpNeeded       = $nextLevelXp - $currentLevelXp;

        return [
            'current'    => $xpInLevel,
            'needed'     => $xpNeeded,
            'percentage' => round(($xpInLevel / $xpNeeded) * 100),
        ];
    }

    // ── Notifications ─────────────────────────────────────────────────────────

    /**
     * Sobrescribimos el correo por defecto de Laravel.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomResetPassword($token));
    }

    public function goals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Goal::class);
    }

    public function debts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Debt::class);
    }
}
