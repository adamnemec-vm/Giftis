<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleMobileLoginTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogleUser(string $id, string $name, string $email): SocialiteUser
    {
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn($id);
        $googleUser->shouldReceive('getName')->andReturn($name);
        $googleUser->shouldReceive('getNickname')->andReturn(null);
        $googleUser->shouldReceive('getEmail')->andReturn($email);

        return $googleUser;
    }

    public function test_mobile_redirect_sends_the_user_to_google_for_an_allowed_return_url(): void
    {
        $response = $this->get(route('auth.google.mobile', ['return' => 'giftis://google']));

        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location'));
        $response->assertSessionHas('google_mobile_return', 'giftis://google');
    }

    public function test_mobile_redirect_rejects_foreign_return_urls(): void
    {
        $this->get(route('auth.google.mobile', ['return' => 'https://evil.example/steal']))->assertStatus(422);
        $this->get(route('auth.google.mobile', ['return' => 'http://localhost:8081.evil.com/x']))->assertStatus(422);
        $this->get(route('auth.google.mobile'))->assertStatus(422);
    }

    public function test_callback_returns_a_one_time_code_to_the_app_and_the_code_exchanges_for_a_token(): void
    {
        Socialite::shouldReceive('driver->user')
            ->once()
            ->andReturn($this->fakeGoogleUser('g-mobile-1', 'Mobilní Uživatel', 'mobil@example.com'));

        $callback = $this->withSession(['google_mobile_return' => 'giftis://google'])
            ->get(route('auth.google.callback'));

        $location = $callback->headers->get('Location');
        $this->assertStringStartsWith('giftis://google?code=', $location);
        $this->assertGuest();

        $code = substr($location, strlen('giftis://google?code='));

        $this->postJson('/api/v1/auth/google/exchange', ['code' => $code])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'email']])
            ->assertJsonPath('user.email', 'mobil@example.com');

        $this->assertDatabaseHas('users', ['email' => 'mobil@example.com', 'google_id' => 'g-mobile-1']);

        // Kód je jednorázový.
        $this->postJson('/api/v1/auth/google/exchange', ['code' => $code])->assertStatus(422);
    }

    public function test_exchange_rejects_an_unknown_code(): void
    {
        $this->postJson('/api/v1/auth/google/exchange', ['code' => 'nope'])->assertStatus(422);
    }

    public function test_the_web_google_flow_still_logs_in_with_a_session(): void
    {
        Socialite::shouldReceive('driver->user')
            ->once()
            ->andReturn($this->fakeGoogleUser('g-web-1', 'Web Uživatel', 'web@example.com'));

        $this->get(route('auth.google.callback'))->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertNotNull(User::where('email', 'web@example.com')->first());
    }
}
