<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_the_privacy_policy(): void
    {
        $response = $this->get(route('privacy'));

        $response->assertOk();
        $response->assertSee('Zásady ochrany osobních údajů');
    }

    public function test_guest_can_view_the_terms_of_service(): void
    {
        $response = $this->get(route('terms'));

        $response->assertOk();
        $response->assertSee('Podmínky používání');
    }

    public function test_authenticated_user_can_view_both_legal_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('privacy'))->assertOk();
        $this->actingAs($user)->get(route('terms'))->assertOk();
    }
}
