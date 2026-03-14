<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory;

    // Le decimos a Laravel qué columnas se pueden llenar de forma segura
    protected $fillable = [
        'user_id', 
        'title', 
        'income', 
        'fixed_expenses_total', 
        'details'
    ];
}
