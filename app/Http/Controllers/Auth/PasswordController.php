<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => \App\Support\PasswordPolicy::rules(),
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'remember_token' => \Illuminate\Support\Str::random(60),
        ]);

        $request->user()->tokens()->delete();
        \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $request->user()->id)->where('id', '!=', $request->session()->getId())->delete();

        return back();
    }
}
