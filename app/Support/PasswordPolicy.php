<?php

namespace App\Support;

use Closure;
use Illuminate\Validation\Rules\Password;

class PasswordPolicy
{
    public static function rules(): array
    {
        return ['bail', 'required', 'string', 'confirmed',
            function (string $attribute, mixed $value, Closure $fail) {
                // Bcrypt only distinguishes the first 72 bytes, including Unicode.
                if (strlen($value) > 72) {
                    $fail('Usa una contraseña más corta.');
                }
            },
            Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
        ];
    }
}
