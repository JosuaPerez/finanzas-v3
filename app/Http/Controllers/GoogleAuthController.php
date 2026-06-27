<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to Google's OAuth consent screen.
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the callback from Google and authenticate/create the user.
     *
     * Priority:
     *  1. Find by google_id        → log in directly.
     *  2. Find by email            → attach google_id, then log in.
     *  3. Neither exists           → create a new user, then log in.
     */
    public function callback()
    {
        $googleUser = Socialite::driver('google')->user();

        // 1. Already linked account.
        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            Auth::login($user);
            return redirect()->route('dashboard');
        }

        // 2. Email already registered — link google_id to existing account.
        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            $user->update(['google_id' => $googleUser->getId()]);
            Auth::login($user);
            return redirect()->route('dashboard');
        }

        // 3. Brand new user — create account without a traditional password.
        $user = User::create([
            'name'      => $googleUser->getName(),
            'email'     => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'password'  => null,
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
