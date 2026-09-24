<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
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

    public function test_redirect_route_sends_the_user_to_google(): void
    {
        $response = $this->get(route('auth.google.redirect'));

        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location'));
    }

    public function test_callback_creates_a_new_user_when_no_account_exists(): void
    {
        Socialite::shouldReceive('driver->user')
            ->once()
            ->andReturn($this->fakeGoogleUser('google-123', 'Nová Uživatelka', 'nova@example.com'));

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'nova@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('google-123', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_callback_links_google_id_to_an_existing_account_with_matching_email(): void
    {
        $existing = User::factory()->create(['email' => 'stara@example.com', 'google_id' => null]);

        Socialite::shouldReceive('driver->user')
            ->once()
            ->andReturn($this->fakeGoogleUser('google-456', 'Stará Uživatelka', 'stara@example.com'));

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($existing);
        $this->assertSame('google-456', $existing->refresh()->google_id);
    }

    public function test_callback_logs_in_directly_when_google_id_already_linked(): void
    {
        $existing = User::factory()->create(['google_id' => 'google-789']);

        Socialite::shouldReceive('driver->user')
            ->once()
            ->andReturn($this->fakeGoogleUser('google-789', $existing->name, $existing->email));

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($existing);
        $this->assertSame(1, User::where('google_id', 'google-789')->count());
    }

    public function test_authenticated_user_cannot_access_google_auth_routes(): void
    {
        $user = User::factory()->create();

        $redirectResponse = $this->actingAs($user)->get(route('auth.google.redirect'));
        $callbackResponse = $this->actingAs($user)->get(route('auth.google.callback'));

        $redirectResponse->assertRedirect(route('dashboard'));
        $callbackResponse->assertRedirect(route('dashboard'));
    }
}
