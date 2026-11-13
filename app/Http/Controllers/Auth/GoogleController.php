<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public const MOBILE_CODE_TTL_SECONDS = 120;

    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Začátek přihlášení z mobilní aplikace: zapamatujeme si, kam se vrátit,
     * a pošleme uživatele ke Googlu stejným způsobem jako na webu.
     */
    public function mobileRedirect(Request $request)
    {
        $return = (string) $request->query('return');

        abort_unless($this->isAllowedMobileReturn($return), 422, 'Nepovolená návratová adresa.');

        $request->session()->put('google_mobile_return', $return);

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request)
    {
        if (Auth::check() && ! $request->session()->has('google_mobile_return')) {
            return redirect()->route('dashboard');
        }

        $googleUser = Socialite::driver('google')->user();

        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                $user->forceFill(['google_id' => $googleUser->getId()])->save();
            } else {
                $user = User::create([
                    'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Uživatel Google',
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => Str::password(40),
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();
            }
        }

        $mobileReturn = $request->session()->pull('google_mobile_return');

        if ($mobileReturn && $this->isAllowedMobileReturn($mobileReturn)) {
            // Do adresy aplikace neposíláme token, jen jednorázový krátkodobý kód.
            $code = Str::random(48);
            Cache::put('google_mobile_code:'.$code, $user->id, self::MOBILE_CODE_TTL_SECONDS);

            $separator = str_contains($mobileReturn, '?') ? '&' : '?';

            return redirect()->away($mobileReturn.$separator.'code='.$code);
        }

        Auth::login($user, remember: true);

        return redirect()->route('dashboard');
    }

    private function isAllowedMobileReturn(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        $prefixes = config('giftis.mobile_return_prefixes', []);
        if (app()->environment('local', 'testing')) {
            $prefixes = array_merge($prefixes, config('giftis.mobile_return_dev_prefixes', []));
        }

        foreach ($prefixes as $prefix) {
            if (! str_starts_with($url, $prefix)) {
                continue;
            }

            // Prefix bez dalšího znaku hostitele (zabrání např. localhost:8081.evil.com).
            $rest = substr($url, strlen($prefix));
            if ($rest === '' || in_array($rest[0], ['/', '?', '#'], true) || str_ends_with($prefix, '//')) {
                return true;
            }
        }

        return false;
    }
}
