<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_opens_the_login_page()
    {
        $this->get(route('home'))->assertRedirect('/login');
        $this->get('/login')->assertOk();
    }

    public function test_a_signed_in_user_goes_on_to_the_dashboard()
    {
        $this->actingAs(User::factory()->create())
            ->followingRedirects()
            ->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Dashboard'));
    }
}
