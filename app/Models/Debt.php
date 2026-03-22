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
        'overdraft_percentage'
    ];
}