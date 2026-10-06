<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'hasPassword' => $request->user()->password !== null,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    public function financialPreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'preferred_currency' => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(config('finance.currencies')))],
            'number_locale' => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(config('finance.locales')))],
        ]);
        $request->user()->update([
            ...$validated,
            'financial_preferences_set_at' => now(),
        ]);
        \Illuminate\Support\Facades\Cache::forget('dashboard_data_user_' . $request->user()->id);

        return back()->with('success', 'Preferencias guardadas. Tus registros conservan su moneda original.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if ($request->user()->password !== null) {
            $request->validate(['password' => ['required', 'current_password']]);
        } elseif ($request->session()->get('google_confirmed_user_id') !== $request->user()->id
            || $request->session()->get('google_confirmed_at', 0) < now()->subMinutes(5)->timestamp) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'password' => 'Vuelve a iniciar sesión con Google y confirma la eliminación dentro de cinco minutos.',
            ]);
        }

        $user = $request->user();

        Auth::guard('web')->logout();

        \Illuminate\Support\Facades\DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $user->id)->delete();
            \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            $user->delete();
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
