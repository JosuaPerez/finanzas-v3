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
        // Linking by email is safe only when Google confirms its ownership.
        abort_unless(($googleUser->getRaw()['email_verified'] ?? false) === true
            && filter_var($googleUser->getEmail(), FILTER_VALIDATE_EMAIL), 403);

        // 1. Already linked account.
        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            return $this->loginUser($user);
        }

        // 2. Email already registered — link google_id to existing account.
        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            if ($user->email_verified_at === null) {
                return redirect()->route('login')->withErrors([
                    'email' => 'Ya existe una cuenta con ese correo. Inicia sesión con su contraseña o recupera el acceso antes de vincular Google.',
                ]);
            }
            $user->update(['google_id' => $googleUser->getId()]);
            return $this->loginUser($user);
        }

        // 3. Brand new user — create account without a traditional password.
        $user = User::create([
            'name'      => $googleUser->getName(),
            'email'     => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'password'  => null,
        ]);
        $user->email_verified_at = now();
        $user->save();

        return $this->loginUser($user);
    }

    private function loginUser(User $user): \Illuminate\Http\RedirectResponse
    {
        Auth::login($user);
        session()->regenerate();
        session()->put('google_confirmed_user_id', $user->id);
        session()->put('google_confirmed_at', now()->timestamp);

        return redirect()->route('dashboard');
    }

}
