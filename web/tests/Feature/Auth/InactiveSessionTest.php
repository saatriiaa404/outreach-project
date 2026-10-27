<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InactiveSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_deactivated_during_a_session_is_logged_out()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $user->update(['status' => UserStatus::Inactive]);

        $this->actingAs($user->fresh())
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
