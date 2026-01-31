<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    /**
     * Redirect to provider authentication page.
     */
    public function redirect(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle provider callback.
     */
    public function callback(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('filament.admin.auth.login')
                ->with('error', 'Unable to authenticate with '.ucfirst($provider));
        }

        // Find or create social account
        $socialAccount = SocialAccount::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($socialAccount) {
            // Update existing social account token
            $socialAccount->update([
                'provider_token' => $socialUser->token,
                'provider_refresh_token' => $socialUser->refreshToken,
                'avatar' => $socialUser->getAvatar(),
            ]);

            $user = $socialAccount->user;
        } else {
            // Check if user exists by email
            $user = User::where('email', $socialUser->getEmail())->first();

            if (! $user) {
                // Create new user
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'password' => bcrypt(Str::random(32)), // Random password
                    'email_verified_at' => now(), // Auto-verify social login
                ]);
            }

            // Create social account
            SocialAccount::create([
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'provider_token' => $socialUser->token,
                'provider_refresh_token' => $socialUser->refreshToken,
                'avatar' => $socialUser->getAvatar(),
            ]);
        }

        // Log in the user
        Auth::login($user, true);

        return redirect()->intended(route('filament.admin.pages.dashboard'));
    }

    /**
     * Unlink social account.
     */
    public function unlink(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        $user = Auth::user();

        $socialAccount = $user->socialAccounts()
            ->where('provider', $provider)
            ->first();

        if ($socialAccount) {
            $socialAccount->delete();

            return redirect()->back()
                ->with('success', ucfirst($provider).' account unlinked successfully');
        }

        return redirect()->back()
            ->with('error', 'No '.ucfirst($provider).' account linked');
    }

    /**
     * Validate provider.
     */
    protected function validateProvider(string $provider): void
    {
        $allowedProviders = ['google', 'github', 'facebook', 'twitter', 'linkedin'];

        if (! in_array($provider, $allowedProviders)) {
            abort(404);
        }
    }
}
