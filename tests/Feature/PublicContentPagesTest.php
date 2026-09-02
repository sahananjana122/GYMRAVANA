<?php

namespace Tests\Feature;

use App\Models\ContactEnquiry;
use App\Models\Event;
use App\Models\GroupProgram;
use App\Models\Product;
use App\Models\TherapySpecialist;
use App\Models\TrainerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicContentPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_new_public_content_pages_and_legacy_services_page_render(): void
    {
        $this->get(route('about.index'))
            ->assertOk()
            ->assertSee('Our vision')
            ->assertSee('comprehensive wellness accessible')
            ->assertSee('complete health ecosystem')
            ->assertSee('GYMRAVANA Management');
        $this->get(route('programs.index'))->assertOk()->assertSee('Group energy');
        $this->get(route('group-programs.index'))
            ->assertOk()
            ->assertSee('Fat Burning Yoga Classes')
            ->assertSee('Zumba Classes')
            ->assertSee('Special Yoga Meditation Class')
            ->assertDontSee('HIIT Circuit');
        $this->get(route('contact.index'))->assertOk()->assertSee('Send a message');
        $this->get(route('services.index'))->assertOk()->assertSee('Choose a path');
    }

    public function test_premium_landing_page_reuses_platform_content_and_real_routes(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('NOT ONLY')
            ->assertSee('BODY')
            ->assertSee('Body')
            ->assertSee('Mind')
            ->assertSee(route('programs.index'))
            ->assertSee(route('services.category', 'mind'))
            ->assertSee('Fat Burning Yoga Classes')
            ->assertSee('Special Yoga Meditation Class')
            ->assertSee('MASTER GYMRAVANA')
            ->assertSee('Chief Advisor of Our Team')
            ->assertSee('Chief Governing Officer')
            ->assertSee(asset('images/landing/trainers/master-gymravana-director.png'), false)
            ->assertSee('N.T.D. Mendis')
            ->assertSee('Cupping Therapy')
            ->assertSee('Foot Massage')
            ->assertSee('W.H.K.T Nimesh')
            ->assertSee(route('therapy-finder.index'))
            ->assertSee(route('trainers.index'))
            ->assertSee('/images/landing/hero.png')
            ->assertDontSee('ddxfitness.ru');
    }

    public function test_every_named_landing_photo_exists_and_keeps_its_related_content(): void
    {
        $this->assertFileExists(public_path('images/dashboard-ravana-watermark.png'));

        $photos = [
            'images/landing/hero.png' => 'NOT ONLY',
            'images/landing/fitness-intro.jpg' => 'Build capability for every part of life.',
            'images/landing/program-body.jpg' => 'Body',
            'images/landing/program-mind.jpg' => 'Mind',
            'images/landing/group-fat-burning-yoga-classes.jpg' => 'Fat Burning Yoga Classes',
            'images/landing/group-zumba-classes.jpg' => 'Zumba Classes',
            'images/landing/group-special-yoga-meditation-class.jpg' => 'Special Yoga Meditation Class',
            'images/landing/trainers/master-gymravana-director.png' => 'MASTER GYMRAVANA',
            'images/landing/trainers/ntd-mendis.jpeg' => 'N.T.D. Mendis',
            'images/landing/trainers/pathum-weerakkodi.jpeg' => 'Pathum Weerakkodi',
            'images/landing/trainers/sahan-weerakkodi.png' => 'Sahan Weerakkodi',
            'images/landing/trainers/hmtd-herath.jpeg' => 'H.M.T.D. Herath',
            'images/landing/about-gymravana.png' => 'About GymRAVANA',
            'images/landing/therapy-cupping-therapy.jpg' => 'Cupping Therapy',
            'images/landing/therapy-full-body-relaxation.png' => 'Full Body Relaxation',
            'images/landing/therapy-deep-tissue-massage.jpg' => 'Deep Tissue Massage',
            'images/landing/therapy-trigger-point-release.png' => 'Trigger Point Release',
            'images/landing/therapy-relaxa-neck-back-shoulder-muscle-pain.png' => 'Relaxa for Neck, Back, Shoulder & Muscle Pain',
            'images/landing/therapy-foot-massage.png' => 'Foot Massage',
            'images/landing/therapy-finder.png' => 'Guided therapy finder',
            'images/landing/specialists/whkt-nimesh.jpg' => 'W.H.K.T Nimesh',
            'images/landing/membership-cta.jpg' => 'Start your fitness journey.',
        ];

        $response = $this->get(route('home'))->assertOk();

        foreach ($photos as $photo => $content) {
            $this->assertFileExists(public_path($photo));
            $response->assertSee(asset($photo), false)->assertSee($content);
        }

        $response
            ->assertSee('comprehensive wellness accessible')
            ->assertSee('dynamic workouts, mindful movement, and clinical physiotherapy')
            ->assertSee('GYMRAVANA Management')
            ->assertDontSee('/images/landing/about.jpg', false);
    }

    public function test_public_landing_photo_paths_also_render_for_database_managed_content(): void
    {
        $specialistPhoto = 'images/landing/specialists/whkt-nimesh.jpg';
        TherapySpecialist::where('slug', 'whkt-nimesh')->firstOrFail()->update(['photo_path' => $specialistPhoto]);
        $this->get(route('home'))->assertOk()->assertSee(asset($specialistPhoto), false);

        $eventPhoto = 'images/landing/about.jpg';
        Event::query()->firstOrFail()->update(['image_path' => $eventPhoto]);
        $this->get(route('events.index'))->assertOk()->assertSee(asset($eventPhoto), false);

        $productPhoto = 'images/landing/membership-cta.jpg';
        $product = Product::query()->firstOrFail();
        $product->update(['image_path' => $productPhoto]);

        $this->get(route('products.index'))->assertOk()->assertSee(asset($productPhoto), false);
        $this->get(route('products.show', [$product->category, $product]))
            ->assertOk()
            ->assertSee(asset($productPhoto), false);
    }

    public function test_named_gymravana_instructors_are_approved_and_publicly_visible(): void
    {
        $expected = [
            'N.T.D. Mendis' => 'images/landing/trainers/ntd-mendis.jpeg',
            'Pathum Weerakkodi' => 'images/landing/trainers/pathum-weerakkodi.jpeg',
            'Sahan Weerakkodi' => 'images/landing/trainers/sahan-weerakkodi.png',
            'H.M.T.D. Herath' => 'images/landing/trainers/hmtd-herath.jpeg',
        ];

        $this->assertSame(4, TrainerProfile::approved()->count());

        foreach ($expected as $name => $photoPath) {
            $profile = TrainerProfile::approved()
                ->whereHas('user', fn ($query) => $query->where('name', $name))
                ->firstOrFail();

            $this->assertSame($photoPath, $profile->photo_path);
            $this->assertFileExists(public_path($photoPath));
        }

        $response = $this->get(route('trainers.index'));
        foreach ($expected as $name => $photoPath) {
            $response->assertSee($name)->assertSee(asset($photoPath), false);
        }

        $response
            ->assertSee('MASTER GYMRAVANA')
            ->assertSee('Chief Advisor of Our Team')
            ->assertSee('Founder of the GymRAVANA Concept')
            ->assertSee('Chief Governing Officer')
            ->assertSee('Director')
            ->assertSee(asset('images/landing/trainers/master-gymravana-director.png'), false)
            ->assertDontSee('Kavindi Perera')
            ->assertDontSee('Dilan Fernando')
            ->assertDontSee('Anjali Silva');

        $this->assertLessThan(
            strpos($response->getContent(), 'N.T.D. Mendis'),
            strpos($response->getContent(), 'MASTER GYMRAVANA'),
        );
    }

    public function test_only_the_three_current_group_programs_are_published_with_their_real_schedules(): void
    {
        $images = [
            'fat-burning-yoga-classes' => 'images/landing/group-fat-burning-yoga-classes.jpg',
            'zumba-classes' => 'images/landing/group-zumba-classes.jpg',
            'special-yoga-meditation-class' => 'images/landing/group-special-yoga-meditation-class.jpg',
        ];

        $this->assertSame([
            'Fat Burning Yoga Classes',
            'Zumba Classes',
            'Special Yoga Meditation Class',
        ], GroupProgram::query()->published()->inDisplayOrder()->pluck('name')->all());

        foreach ($images as $slug => $imagePath) {
            $this->assertSame($imagePath, GroupProgram::where('slug', $slug)->value('image_path'));
            $this->assertFileExists(public_path($imagePath));
        }

        $groupProgramsPage = $this->get(route('group-programs.index'))
            ->assertOk()
            ->assertSee('Monday, Wednesday &amp; Friday', false)
            ->assertSee('Tuesday &amp; Friday', false)
            ->assertSee('6:30 PM–8:00 PM')
            ->assertSee('Open to both men and women. All age groups are welcome.');

        $homePage = $this->get(route('home'))->assertOk();
        $programsPage = $this->get(route('programs.index'))->assertOk();
        foreach ($images as $imagePath) {
            $groupProgramsPage->assertSee(asset($imagePath), false);
            $homePage->assertSee(asset($imagePath), false);
            $programsPage->assertSee(asset($imagePath), false);
        }

        $this->get(route('services.category', 'mind'))
            ->assertOk()
            ->assertSee('Special Yoga Meditation Class')
            ->assertSee('Balancing the Nadi System, Stress Relief, Anapanasati Meditation &amp; Yoga Exercises.', false)
            ->assertSee('6:30 PM–8:00 PM')
            ->assertSee(asset($images['special-yoga-meditation-class']), false);
    }

    public function test_guest_can_submit_a_contact_enquiry(): void
    {
        $this->post(route('contact.store'), [
            'name' => 'Public Visitor',
            'email' => 'visitor@example.test',
            'subject' => 'Group classes',
            'message' => 'Please share more information about beginner group classes.',
        ])->assertRedirect(route('contact.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('contact_enquiries', [
            'user_id' => null,
            'email' => 'visitor@example.test',
            'status' => 'new',
        ]);
    }

    public function test_contact_form_rejects_invalid_details(): void
    {
        $this->post(route('contact.store'), [
            'name' => '',
            'email' => 'not-an-email',
            'message' => 'short',
        ])->assertSessionHasErrors(['name', 'email', 'message']);

        $this->assertSame(0, ContactEnquiry::count());
    }

    public function test_landing_contact_form_returns_to_the_contact_section(): void
    {
        $this->post(route('contact.store'), [
            'name' => 'Landing Visitor',
            'email' => 'landing@example.test',
            'subject' => 'Personal training',
            'message' => 'Please help me choose a suitable personal training starting point.',
            'source' => 'home',
        ])->assertRedirect(route('home').'#contact')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('contact_enquiries', [
            'email' => 'landing@example.test',
            'subject' => 'Personal training',
            'status' => 'new',
        ]);
    }

    public function test_guest_and_member_group_program_requests_are_stored_correctly(): void
    {
        $program = GroupProgram::where('slug', 'fat-burning-yoga-classes')->firstOrFail();

        $this->post(route('group-programs.register', $program), [
            'name' => 'Guest Participant',
            'email' => 'guest-class@example.test',
            'preferred_session' => 'Monday morning at 8:00 AM',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $member = User::factory()->create();
        $this->actingAs($member)->post(route('group-programs.register', $program), [
            'name' => $member->name,
            'email' => $member->email,
            'preferred_session' => 'Wednesday evening at 7:00 PM',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('group_program_registrations', ['group_program_id' => $program->id, 'user_id' => null, 'email' => 'guest-class@example.test']);
        $this->assertDatabaseHas('group_program_registrations', ['group_program_id' => $program->id, 'user_id' => $member->id, 'email' => $member->email]);
    }

    public function test_group_program_rejects_requests_after_capacity_is_reached(): void
    {
        $program = GroupProgram::where('slug', 'special-yoga-meditation-class')->firstOrFail();
        $program->update(['capacity' => 1]);
        $program->registrations()->create([
            'name' => 'Existing Participant',
            'email' => 'existing@example.test',
            'status' => 'confirmed',
        ]);

        $this->from(route('group-programs.index'))->post(route('group-programs.register', $program), [
            'name' => 'Late Participant',
            'email' => 'late@example.test',
        ])->assertRedirect(route('group-programs.index'))->assertSessionHasErrors('group_program');

        $this->assertDatabaseMissing('group_program_registrations', ['email' => 'late@example.test']);
    }
}
