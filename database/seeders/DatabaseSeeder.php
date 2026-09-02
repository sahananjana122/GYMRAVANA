<?php

namespace Database\Seeders;

use App\Models\MemberProfile;
use App\Models\MembershipTier;
use App\Services\MembershipNumberService;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\TherapyCategory;
use App\Models\TherapyCondition;
use App\Models\TherapySpecialist;
use App\Models\TrainerProfile;
use App\Models\Treatment;
use App\Models\User;
use App\Models\WellnessActivity;
use App\Models\WorkoutPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['member', 'trainer', 'therapist', 'admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->call(InstructorSeeder::class);

        $tiers = collect([
            ['name' => 'Foundation', 'slug' => 'foundation', 'price' => 4500, 'billing_period' => 'month', 'duration_months' => 1, 'features' => ['Gym and mind service access', 'Progress tracking', 'Member community'], 'is_featured' => false],
            ['name' => 'Momentum', 'slug' => 'momentum', 'price' => 7500, 'billing_period' => 'month', 'duration_months' => 1, 'features' => ['Everything in Foundation', 'Meal-plan library', 'Trainer booking access', 'Priority therapy follow-up'], 'is_featured' => true],
            ['name' => 'Transform', 'slug' => 'transform', 'price' => 12000, 'billing_period' => 'month', 'duration_months' => 1, 'features' => ['Everything in Momentum', 'Monthly trainer consultation', 'Advanced programmes', 'Premium member support'], 'is_featured' => false],
        ])->map(fn (array $tier) => MembershipTier::updateOrCreate(['slug' => $tier['slug']], $tier + ['is_active' => true]));

        $categories = [
            'body' => ServiceCategory::updateOrCreate(['slug' => 'body'], ['name' => 'Body', 'description' => 'Practical programmes for strength, nutrition and sustainable physical progress.', 'display_order' => 1]),
            'mind' => ServiceCategory::updateOrCreate(['slug' => 'mind'], ['name' => 'Mind', 'description' => 'Guided practices for focus, recovery, breathing and mindful movement.', 'display_order' => 2]),
        ];

        $services = [
            ['category' => 'body', 'name' => 'Gym Workout', 'slug' => 'gym-workout', 'summary' => 'Structured strength and conditioning plans for every starting level.', 'description' => 'Choose a progressive workout path with clear levels, safe form guidance and equipment notes.', 'benefits' => ['Build functional strength', 'Improve cardiovascular fitness', 'Create a consistent training habit'], 'tags' => ['strength', 'conditioning'], 'level' => 'Beginner to advanced', 'equipment' => 'Bodyweight, resistance bands or gym equipment', 'duration_minutes' => 45],
            ['category' => 'body', 'name' => 'Meal Plan', 'slug' => 'meal-plan', 'summary' => 'Flexible food-planning guidance built around real routines.', 'description' => 'Explore balanced meal structures with dietary tags and practical calorie targets. This content is educational, not medical nutrition therapy.', 'benefits' => ['Simplify weekly planning', 'Support training recovery', 'Build balanced food habits'], 'tags' => ['high-protein', 'vegan-friendly', 'balanced'], 'level' => 'All levels', 'equipment' => 'Kitchen basics', 'duration_minutes' => 20],
            ['category' => 'mind', 'name' => 'Breathing Exercises', 'slug' => 'breathing-exercises', 'summary' => 'Short guided breathing practices for calm and focus.', 'description' => 'Learn approachable breathing patterns that fit before work, after training or during a stressful day.', 'benefits' => ['Encourage relaxation', 'Improve present-moment focus', 'Support recovery routines'], 'tags' => ['breathing', 'stress-reset'], 'level' => 'Beginner', 'equipment' => 'None', 'duration_minutes' => 10],
            ['category' => 'mind', 'name' => 'Meditation', 'slug' => 'meditation', 'summary' => 'Simple meditation sessions that make consistency achievable.', 'description' => 'Start with brief guided sessions and build a repeatable mindfulness practice without pressure or complicated techniques.', 'benefits' => ['Develop attention', 'Create a daily pause', 'Support emotional awareness'], 'tags' => ['mindfulness', 'recovery'], 'level' => 'All levels', 'equipment' => 'Quiet seated space', 'duration_minutes' => 15],
            ['category' => 'mind', 'name' => 'Yoga', 'slug' => 'yoga', 'summary' => 'Mobility, balance and mindful movement sessions.', 'description' => 'Follow accessible yoga sequences for mobility and recovery, with clear reminders to work within a comfortable range.', 'benefits' => ['Improve mobility', 'Build balance', 'Connect movement and breathing'], 'tags' => ['mobility', 'recovery', 'balance'], 'level' => 'Beginner to intermediate', 'equipment' => 'Yoga mat recommended', 'duration_minutes' => 35],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(
                ['slug' => $service['slug']],
                collect($service)->except('category')->all() + ['service_category_id' => $categories[$service['category']]->id, 'is_active' => true],
            );
        }

        foreach ([
            ['name' => 'Cupping Therapy', 'slug' => 'cupping-therapy', 'description' => 'A therapist-led cupping service focused on relaxation and post-training recovery.'],
            ['name' => 'Full Body Relaxation', 'slug' => 'full-body-relaxation', 'description' => 'A gentle full-body relaxation session designed to ease everyday tension and support recovery.'],
            ['name' => 'Deep Tissue Massage', 'slug' => 'deep-tissue-massage', 'description' => 'A focused massage service using firmer pressure for areas of persistent muscular tension.'],
            ['name' => 'Trigger Point Release', 'slug' => 'trigger-point-release', 'description' => 'Targeted therapist-led work for localized areas of muscular tightness.'],
            ['name' => 'Relaxa for Neck, Back, Shoulder & Muscle Pain', 'slug' => 'relaxa-neck-back-shoulder-muscle-pain', 'description' => 'A focused relaxation service for everyday neck, back, shoulder and muscle discomfort.'],
            ['name' => 'Foot Massage', 'slug' => 'foot-massage', 'description' => 'A focused foot massage service for relaxation after daily activity or training.'],
        ] as $category) {
            TherapyCategory::updateOrCreate(['slug' => $category['slug']], $category + ['is_active' => true]);
        }

        TherapyCategory::whereIn('slug', [
            'stress-relief', 'head-therapy', 'belly-fat', 'full-body-therapy',
            'meditation-yoga-therapy', 'back-spine-therapy',
        ])->update(['is_active' => false]);

        $productCategories = collect([
            ['name' => 'Clothing', 'slug' => 'clothing'],
            ['name' => 'Organic Skin Care & Perfume', 'slug' => 'organic-skin-care-perfume'],
            ['name' => 'Gym Equipment', 'slug' => 'gym-equipment'],
            ['name' => 'Yoga Mats', 'slug' => 'yoga-mats'],
            ['name' => 'Shoes', 'slug' => 'shoes'],
        ])->mapWithKeys(fn (array $category) => [$category['slug'] => ProductCategory::updateOrCreate(['slug' => $category['slug']], $category)]);

        foreach ([
            ['category' => 'clothing', 'name' => 'Raavana Training Tee', 'slug' => 'raavana-training-tee', 'description' => 'A breathable everyday training tee with a relaxed athletic fit.', 'price' => 3900, 'stock' => 24],
            ['category' => 'organic-skin-care-perfume', 'name' => 'Ceylon Recovery Balm', 'slug' => 'ceylon-recovery-balm', 'description' => 'A small-batch botanical balm for a calming post-training routine.', 'price' => 2800, 'stock' => 18],
            ['category' => 'gym-equipment', 'name' => 'Resistance Band Set', 'slug' => 'resistance-band-set', 'description' => 'Five resistance levels with handles and a compact storage pouch.', 'price' => 6500, 'stock' => 15],
            ['category' => 'yoga-mats', 'name' => 'Grounded Yoga Mat', 'slug' => 'grounded-yoga-mat', 'description' => 'A supportive non-slip mat for yoga, mobility and floor workouts.', 'price' => 8900, 'stock' => 12],
            ['category' => 'shoes', 'name' => 'Everyday Cross Trainer', 'slug' => 'everyday-cross-trainer', 'description' => 'A stable multipurpose shoe for strength sessions and light conditioning.', 'price' => 14500, 'stock' => 10],
        ] as $product) {
            Product::updateOrCreate(
                ['slug' => $product['slug']],
                collect($product)->except('category')->all() + ['product_category_id' => $productCategories[$product['category']]->id, 'is_active' => true],
            );
        }

        foreach (User::role('member')->get() as $member) {
            $profile = MemberProfile::firstOrCreate(
                ['user_id' => $member->id],
                ['membership_tier_id' => $tiers->firstWhere('is_featured', true)?->id, 'joined_at' => today(), 'status' => 'active'],
            );
            if ($profile->status === 'active' && blank($profile->membership_number)) {
                app(MembershipNumberService::class)->assign($profile, $profile->joined_at ?? $member->created_at);
            }
        }

        $this->call(EventSeeder::class);
        $this->call(NoticeSeeder::class);
        $this->call(FinanceSeeder::class);
        $this->call(GamificationSeeder::class);
        $this->call(GameProgressionSeeder::class);
        $this->seedProgramAndConsultationContent();
        $this->seedLegacyWellnessContent();
        $this->seedOptionalAdmin();
    }

    private function seedProgramAndConsultationContent(): void
    {
        $this->call(GroupProgramSeeder::class);

        $trainers = TrainerProfile::approved()->orderBy('id')->get();

        $categories = TherapyCategory::all()->keyBy('slug');
        $treatments = collect([
            ['name' => 'Cupping Therapy', 'slug' => 'cupping-therapy', 'treatment_type' => 'other', 'category' => 'cupping-therapy', 'description' => 'A therapist-led cupping service focused on relaxation and post-training recovery.'],
            ['name' => 'Full Body Relaxation', 'slug' => 'full-body-relaxation', 'treatment_type' => 'other', 'category' => 'full-body-relaxation', 'description' => 'A gentle full-body relaxation session designed to ease everyday tension and support recovery.'],
            ['name' => 'Deep Tissue Massage', 'slug' => 'deep-tissue-massage', 'treatment_type' => 'other', 'category' => 'deep-tissue-massage', 'description' => 'A focused massage service using firmer pressure for areas of persistent muscular tension.'],
            ['name' => 'Trigger Point Release', 'slug' => 'trigger-point-release', 'treatment_type' => 'other', 'category' => 'trigger-point-release', 'description' => 'Targeted therapist-led work for localized areas of muscular tightness.'],
            ['name' => 'Relaxa for Neck, Back, Shoulder & Muscle Pain', 'slug' => 'relaxa-neck-back-shoulder-muscle-pain', 'treatment_type' => 'other', 'category' => 'relaxa-neck-back-shoulder-muscle-pain', 'description' => 'A focused relaxation service for everyday neck, back, shoulder and muscle discomfort.'],
            ['name' => 'Foot Massage', 'slug' => 'foot-massage', 'treatment_type' => 'other', 'category' => 'foot-massage', 'description' => 'A focused foot massage service for relaxation after daily activity or training.'],
        ])->mapWithKeys(function (array $data) use ($categories) {
            $categorySlug = $data['category'];
            $treatment = Treatment::updateOrCreate(
                ['slug' => $data['slug']],
                collect($data)->except('category')->all() + [
                    'therapy_category_id' => $categorySlug ? $categories->get($categorySlug)?->id : null,
                    'is_active' => true,
                ],
            );

            return [$treatment->slug => $treatment];
        });

        Treatment::whereIn('slug', [
            'traditional-nadi-consultation', 'restorative-full-body-yoga',
            'back-spine-mobility-therapy', 'stress-relief-yoga-therapy',
            'meditation-yoga-consultation',
        ])->update(['is_active' => false]);

        foreach ([
            ['name' => 'Body Pain', 'slug' => 'body-pain', 'description' => 'General non-emergency aches or stiffness.', 'treatments' => ['deep-tissue-massage', 'full-body-relaxation', 'cupping-therapy']],
            ['name' => 'Muscle Spasm or Strain', 'slug' => 'muscle-spasm-strain', 'description' => 'Non-acute muscular tightness or strain requiring professional screening.', 'treatments' => ['trigger-point-release', 'deep-tissue-massage']],
            ['name' => 'Joint Pain', 'slug' => 'joint-pain', 'description' => 'Non-emergency joint discomfort or reduced movement confidence.', 'treatments' => ['cupping-therapy', 'full-body-relaxation']],
            ['name' => 'General Fatigue', 'slug' => 'general-fatigue', 'description' => 'Persistent non-emergency tiredness affecting daily routines.', 'treatments' => ['full-body-relaxation', 'foot-massage']],
            ['name' => 'Stress or Tension', 'slug' => 'stress-tension', 'description' => 'Everyday stress, tension or difficulty settling.', 'treatments' => ['full-body-relaxation', 'foot-massage']],
            ['name' => 'Back Stiffness', 'slug' => 'back-stiffness', 'description' => 'Non-emergency back stiffness requiring careful service selection.', 'treatments' => ['relaxa-neck-back-shoulder-muscle-pain', 'trigger-point-release', 'deep-tissue-massage']],
            ['name' => 'Foot Tension or Fatigue', 'slug' => 'foot-tension-fatigue', 'description' => 'Everyday foot tension or tiredness after activity.', 'treatments' => ['foot-massage', 'full-body-relaxation']],
        ] as $data) {
            $condition = TherapyCondition::updateOrCreate(
                ['slug' => $data['slug']],
                collect($data)->except('treatments')->all() + ['is_active' => true],
            );
            $recommendations = [];
            foreach ($data['treatments'] as $priority => $slug) {
                $recommendations[$treatments[$slug]->id] = [
                    'priority' => $priority + 1,
                    'rationale' => 'Suggested as an educational starting point; a specialist must confirm suitability.',
                ];
            }
            $condition->treatments()->sync($recommendations);
        }

        TherapyCondition::where('slug', 'bone-related-concern')->update(['is_active' => false]);

        foreach ([
            [
                'name' => 'W.H.K.T Nimesh',
                'slug' => 'whkt-nimesh',
                'gender' => null,
                'specialization' => 'Trainer and therapist',
                'bio' => 'Provides all GymRAVANA cupping, relaxation, massage and trigger point release services.',
                'qualifications' => null,
                'experience_years' => 0,
                'treatments' => $treatments->keys()->all(),
            ],
        ] as $data) {
            $specialist = TherapySpecialist::updateOrCreate(
                ['slug' => $data['slug']],
                collect($data)->except('treatments')->all() + ['is_active' => true],
            );
            $specialist->treatments()->sync(
                collect($data['treatments'])->map(fn (string $slug) => $treatments[$slug]->id),
            );
        }

        TherapySpecialist::whereIn('slug', [
            'nirmala-jayasinghe', 'harsha-wijeratne', 'amaya-senanayake',
        ])->update(['is_active' => false]);
    }

    private function seedLegacyWellnessContent(): void
    {
        foreach ([
            ['title' => 'Beginner Full Body', 'description' => 'Three rounds of bodyweight squats, wall push-ups, glute bridges and a gentle plank.', 'difficulty' => 'beginner', 'duration_minutes' => 25, 'points' => 10],
            ['title' => 'Upper Body Foundation', 'description' => 'A simple push and pull session using resistance bands or light weights.', 'difficulty' => 'beginner', 'duration_minutes' => 30, 'points' => 12],
            ['title' => 'Conditioning Circuit', 'description' => 'A moderate circuit for members familiar with basic exercise.', 'difficulty' => 'intermediate', 'duration_minutes' => 35, 'points' => 18],
        ] as $workout) {
            WorkoutPlan::updateOrCreate(['title' => $workout['title']], $workout + ['is_active' => true]);
        }

        foreach ([
            ['title' => 'Five-minute breathing reset', 'category' => 'breathing', 'description' => 'Sit comfortably and follow slow, even breathing for five minutes.', 'duration_minutes' => 5, 'points' => 5],
            ['title' => 'Mindful body scan', 'category' => 'meditation', 'description' => 'Notice sensations from head to toe without judging them.', 'duration_minutes' => 10, 'points' => 8],
            ['title' => 'Evening screen-free routine', 'category' => 'lifestyle', 'description' => 'Spend the final thirty minutes before sleep away from screens.', 'duration_minutes' => 30, 'points' => 6],
        ] as $activity) {
            WellnessActivity::updateOrCreate(['title' => $activity['title']], $activity + ['is_active' => true]);
        }
    }

    private function seedOptionalAdmin(): void
    {
        $email = config('gymravana.demo_admin_email');
        $password = config('gymravana.demo_admin_password');

        if (app()->environment('local') && $email && $password) {
            $admin = User::updateOrCreate(
                ['email' => $email],
                ['name' => 'Demo Admin', 'password' => Hash::make($password), 'email_verified_at' => now()],
            );
            $admin->syncRoles(['admin']);
        }
    }
}
