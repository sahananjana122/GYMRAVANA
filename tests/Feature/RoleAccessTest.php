<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_member_is_redirected_to_member_dashboard(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)->get('/dashboard')
            ->assertRedirect(route('member.dashboard', absolute: false));
    }

    public function test_member_cannot_access_admin_dashboard(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_unverified_member_can_access_dashboard(): void
    {
        $member = User::factory()->unverified()->create();
        $member->assignRole('member');

        $this->actingAs($member)->get('/member/dashboard')->assertOk();
    }

    public function test_admin_can_change_another_users_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($admin)
            ->patch(route('admin.users.role', $member), ['role' => 'trainer'])
            ->assertSessionHasNoErrors();

        $this->assertTrue($member->fresh()->hasRole('trainer'));
    }

    public function test_admin_management_pages_are_rendered(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.therapy.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.therapy-appointments.index'))->assertOk();
        $this->actingAs($admin)
            ->get(route('admin.trainers.index'))
            ->assertOk()
            ->assertSee('Trainer applications');
    }

    public function test_trainer_dashboard_is_rendered(): void
    {
        $trainer = User::role('trainer')->whereHas('trainerProfile')->firstOrFail();

        $this->actingAs($trainer)
            ->get(route('trainer.dashboard'))
            ->assertOk()
            ->assertSee('Trainer space');
    }
}
