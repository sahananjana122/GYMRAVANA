<?php

namespace Tests\Feature;

use App\Models\MembershipTier;
use App\Models\MembershipSubscription;
use App\Models\Product;
use App\Models\Service;
use App\Models\TherapyCategory;
use App\Models\TrainerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_public_platform_pages_are_rendered(): void
    {
        $trainer = TrainerProfile::approved()->firstOrFail();
        $product = Product::with('category')->firstOrFail();
        $service = Service::with('category')->firstOrFail();

        $this->get(route('home'))->assertOk();
        $this->get(route('services.index'))->assertOk();
        $this->get(route('services.show', [$service->category, $service]))->assertOk();
        $this->get(route('yoga-therapy.index'))->assertOk();
        $this->get(route('trainers.index'))->assertOk();
        $this->get(route('trainers.show', $trainer))->assertOk();
        $this->get(route('memberships.index'))->assertOk();
        $this->get(route('products.show', [$product->category, $product]))->assertOk();
    }

    public function test_guest_can_submit_a_yoga_therapy_request(): void
    {
        $category = TherapyCategory::firstOrFail();

        $this->post(route('yoga-therapy.store'), [
            'name' => 'Guest Visitor',
            'contact_email' => 'guest@example.com',
            'therapy_category_id' => $category->id,
            'notes' => 'Please contact me about a gentle introductory session.',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('therapy_requests', [
            'user_id' => null,
            'name' => 'Guest Visitor',
            'category' => $category->name,
            'status' => 'pending',
        ]);
    }

    public function test_trainer_booking_requires_login_and_member_can_submit_request(): void
    {
        $trainer = TrainerProfile::approved()->firstOrFail();

        $this->get(route('trainers.book', $trainer))->assertRedirect(route('login'));

        $member = User::factory()->create();
        $member->assignRole('member');
        $this->actingAs($member)->post(route('trainers.book.store', $trainer), [
            'program_type' => 'Personal training',
            'requested_datetime' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'notes' => 'Beginner strength assessment.',
        ])->assertRedirect(route('member.dashboard'));

        $this->assertDatabaseHas('trainer_bookings', ['trainer_profile_id' => $trainer->id, 'user_id' => $member->id, 'status' => 'pending']);
    }

    public function test_guest_cart_checkout_creates_pending_order_and_reduces_stock(): void
    {
        $product = Product::firstOrFail();
        $startingStock = $product->stock;

        $this->post(route('cart.add', $product), ['quantity' => 2])->assertSessionHasNoErrors();
        $this->post(route('checkout.store'), [
            'customer_name' => 'Guest Customer',
            'guest_email' => 'buyer@example.com',
            'phone' => '0771234567',
            'delivery_address' => '12 Test Road, Colombo',
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', ['user_id' => null, 'guest_email' => 'buyer@example.com', 'status' => 'pending']);
        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'quantity' => 2]);
        $this->assertSame($startingStock - 2, $product->fresh()->stock);
    }

    public function test_trainer_application_is_pending_and_hidden_until_approved(): void
    {
        $this->post(route('register'), [
            'name' => 'Pending Coach',
            'email' => 'pending@example.com',
            'application_type' => 'trainer',
            'specialty' => 'Mobility',
            'bio' => 'A sufficiently detailed professional introduction for review.',
            'certifications' => 'Level 3 Coach',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('trainer.dashboard'));

        $user = User::where('email', 'pending@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('trainer'));
        $this->assertSame('pending_review', $user->trainerProfile->status);
        $this->get(route('trainers.show', $user->trainerProfile))->assertNotFound();
    }

    public function test_member_registration_assigns_selected_tier_and_can_enroll_in_service(): void
    {
        $tier = MembershipTier::firstOrFail();
        $service = Service::firstOrFail();

        $response = $this->post(route('register'), [
            'name' => 'Tier Member',
            'email' => 'tier-member@example.com',
            'application_type' => 'member',
            'membership_tier_id' => $tier->id,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'tier-member@example.com')->firstOrFail();
        $subscription = MembershipSubscription::where('user_id', $user->id)->firstOrFail();
        $response->assertRedirect(route('member.membership.checkout', $subscription));
        $this->assertSame($tier->id, $user->memberProfile->membership_tier_id);
        $this->post(route('member.services.enroll', $service))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('member_service', ['user_id' => $user->id, 'service_id' => $service->id]);
    }
}
