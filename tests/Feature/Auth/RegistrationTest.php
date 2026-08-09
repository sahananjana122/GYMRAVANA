<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $this->seed();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->hasRole('member'));
        $response->assertRedirect(route('verification.notice', absolute: false));
    }

    public function test_public_registration_cannot_assign_a_privileged_role(): void
    {
        $this->seed();

        $this->post('/register', [
            'name' => 'Untrusted User',
            'email' => 'untrusted@example.com',
            'role' => 'trainer',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertTrue(auth()->user()->hasRole('member'));
        $this->assertFalse(auth()->user()->hasRole('trainer'));
    }
}
