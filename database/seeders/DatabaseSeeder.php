<?php

namespace Database\Seeders;

use App\Models\GroupProgram;
use App\Models\MemberProfile;
use App\Models\MembershipTier;
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
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['member', 'trainer', 'admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $tiers = collect([
            ['name' => 'Foundation', 'slug' => 'foundation', 'price' => 4500, 'billing_period' => 'month', 'features' => ['Gym and mind service access', 'Progress tracking', 'Member community'], 'is_featured' => false],
            ['name' => 'Momentum', 'slug' => 'momentum', 'price' => 7500, 'billing_period' => 'month', 'features' => ['Everything in Foundation', 'Meal-plan library', 'Trainer booking access', 'Priority therapy follow-up'], 'is_featured' => true],
            ['name' => 'Transform', 'slug' => 'transform', 'price' => 12000, 'billing_period' => 'month', 'features' => ['Everything in Momentum', 'Monthly trainer consultation', 'Advanced programmes', 'Premium member support'], 'is_featured' => false],
        ])->map(fn (array $tier) => MembershipTier::updateOrCreate(['slug' => $tier['slug']], $tier + ['is_active' => true]));

        $demoTrainers = [
            ['name' => 'Kavindi Perera', 'email' => 'kavindi.trainer@example.test', 'slug' => 'kavindi-perera', 'specialty' => 'Strength & mobility', 'gender' => 'female', 'bio' => 'Kavindi helps beginners build confident movement patterns through progressive strength and mobility sessions.', 'certifications' => 'Certified Personal Trainer; Functional Movement Fundamentals', 'experience_years' => 6, 'availability' => 'Weekdays 6:00-10:00 and 16:00-20:00'],
            ['name' => 'Dilan Fernando', 'email' => 'dilan.trainer@example.test', 'slug' => 'dilan-fernando', 'specialty' => 'Conditioning & fat loss', 'gender' => 'male', 'bio' => 'Dilan combines accessible conditioning with sustainable habit coaching for members at every fitness level.', 'certifications' => 'Level 3 Fitness Instructor; Nutrition Coaching Fundamentals', 'experience_years' => 8, 'availability' => 'Monday, Wednesday, Friday and Saturday mornings'],
            ['name' => 'Anjali Silva', 'email' => 'anjali.trainer@example.test', 'slug' => 'anjali-silva', 'specialty' => 'Yoga & breathwork', 'gender' => 'female', 'bio' => 'Anjali teaches calm, practical yoga and breathing sessions focused on mobility, recovery and everyday resilience.', 'certifications' => 'RYT 200 Yoga Teacher; Breathwork Facilitator', 'experience_years' => 7, 'availability' => 'Tuesday and Thursday evenings; Sunday mornings'],
        ];

        foreach ($demoTrainers as $trainerData) {
            $trainer = User::firstOrCreate(
                ['email' => $trainerData['email']],
                ['name' => $trainerData['name'], 'password' => Hash::make(Str::random(48)), 'email_verified_at' => now()],
            );
            $trainer->update(['name' => $trainerData['name'], 'email_verified_at' => $trainer->email_verified_at ?? now()]);
            $trainer->syncRoles(['trainer']);
            TrainerProfile::updateOrCreate(
                ['user_id' => $trainer->id],
                collect($trainerData)->except(['name', 'email'])->all() + ['status' => 'approved'],
            );
        }

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
            ['name' => 'Stress Relief', 'slug' => 'stress-relief', 'description' => 'A gentle consultation request focused on relaxation and recovery routines.'],
            ['name' => 'Head Therapy', 'slug' => 'head-therapy', 'description' => 'A request for non-emergency yoga and relaxation guidance related to head and neck tension.'],
            ['name' => 'Belly Fat', 'slug' => 'belly-fat', 'description' => 'A lifestyle-focused request combining movement, consistency and general wellness education.'],
            ['name' => 'Full Body Therapy', 'slug' => 'full-body-therapy', 'description' => 'A whole-body mobility and restorative yoga consultation request.'],
            ['name' => 'Meditation Yoga Therapy', 'slug' => 'meditation-yoga-therapy', 'description' => 'Guided breathing and meditation practices supporting calm, focus and emotional balance.'],
            ['name' => 'Back & Spine Therapy', 'slug' => 'back-spine-therapy', 'description' => 'Gentle mobility consultation for non-emergency back stiffness and movement confidence.'],
        ] as $category) {
            TherapyCategory::updateOrCreate(['slug' => $category['slug']], $category + ['is_active' => true]);
        }

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
            MemberProfile::firstOrCreate(
                ['user_id' => $member->id],
                ['membership_tier_id' => $tiers->firstWhere('is_featured', true)?->id, 'status' => 'active'],
            );
        }

        $this->seedProgramAndConsultationContent();
        $this->seedLegacyWellnessContent();
        $this->seedOptionalAdmin();
    }

    private function seedProgramAndConsultationContent(): void
    {
        $trainers = TrainerProfile::approved()->orderBy('id')->get();

        foreach ([
            ['name' => 'Yoga Flow', 'slug' => 'yoga-flow', 'description' => 'A guided group flow combining mobility, balance and controlled breathing.', 'schedule_info' => 'Tuesday and Thursday at 18:00', 'level' => 'Beginner friendly', 'duration_minutes' => 60, 'capacity' => 18],
            ['name' => 'Zumba Energy', 'slug' => 'zumba-energy', 'description' => 'An energetic dance-fitness class with simple, repeatable movement patterns.', 'schedule_info' => 'Monday and Wednesday at 18:30', 'level' => 'All levels', 'duration_minutes' => 50, 'capacity' => 24],
            ['name' => 'Guided Meditation', 'slug' => 'guided-meditation', 'description' => 'A quiet guided class for attention, relaxation and a consistent mindfulness routine.', 'schedule_info' => 'Saturday at 08:00', 'level' => 'All levels', 'duration_minutes' => 30, 'capacity' => 20],
            ['name' => 'Aerobics Basics', 'slug' => 'aerobics-basics', 'description' => 'Accessible rhythmic cardio designed to improve stamina and coordination.', 'schedule_info' => 'Tuesday and Friday at 17:30', 'level' => 'Beginner', 'duration_minutes' => 45, 'capacity' => 22],
            ['name' => 'HIIT Circuit', 'slug' => 'hiit-circuit', 'description' => 'Short work intervals and structured recovery for experienced group participants.', 'schedule_info' => 'Monday and Friday at 19:00', 'level' => 'Intermediate', 'duration_minutes' => 40, 'capacity' => 16],
            ['name' => 'Pilates Foundation', 'slug' => 'pilates-foundation', 'description' => 'Controlled mat exercises focused on posture, stability and movement quality.', 'schedule_info' => 'Wednesday and Sunday at 09:00', 'level' => 'Beginner friendly', 'duration_minutes' => 50, 'capacity' => 16],
        ] as $index => $program) {
            GroupProgram::updateOrCreate(
                ['slug' => $program['slug']],
                $program + [
                    'trainer_profile_id' => $trainers->isNotEmpty() ? $trainers[$index % $trainers->count()]->id : null,
                    'is_active' => true,
                ],
            );
        }

        $categories = TherapyCategory::all()->keyBy('slug');
        $treatments = collect([
            ['name' => 'Traditional Nadi Consultation', 'slug' => 'traditional-nadi-consultation', 'treatment_type' => 'nadi', 'category' => null, 'description' => 'A traditional pulse-based wellness consultation used to guide an appropriate non-emergency care pathway.'],
            ['name' => 'Restorative Full Body Yoga', 'slug' => 'restorative-full-body-yoga', 'treatment_type' => 'yoga_therapy', 'category' => 'full-body-therapy', 'description' => 'A gentle whole-body mobility and relaxation programme tailored after consultation.'],
            ['name' => 'Back & Spine Mobility Therapy', 'slug' => 'back-spine-mobility-therapy', 'treatment_type' => 'yoga_therapy', 'category' => 'back-spine-therapy', 'description' => 'Controlled mobility and posture-focused yoga guidance for non-emergency discomfort.'],
            ['name' => 'Stress Relief Yoga Therapy', 'slug' => 'stress-relief-yoga-therapy', 'treatment_type' => 'yoga_therapy', 'category' => 'stress-relief', 'description' => 'Breathing, restorative movement and relaxation guidance for everyday stress.'],
            ['name' => 'Meditation Yoga Consultation', 'slug' => 'meditation-yoga-consultation', 'treatment_type' => 'yoga_therapy', 'category' => 'meditation-yoga-therapy', 'description' => 'A guided meditation and breathing pathway based on the client consultation.'],
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

        foreach ([
            ['name' => 'Body Pain', 'slug' => 'body-pain', 'description' => 'General non-emergency aches or stiffness.', 'treatments' => ['traditional-nadi-consultation', 'restorative-full-body-yoga']],
            ['name' => 'Muscle Spasm or Strain', 'slug' => 'muscle-spasm-strain', 'description' => 'Non-acute muscular tightness or strain requiring professional screening.', 'treatments' => ['traditional-nadi-consultation', 'restorative-full-body-yoga']],
            ['name' => 'Joint Pain', 'slug' => 'joint-pain', 'description' => 'Non-emergency joint discomfort or reduced movement confidence.', 'treatments' => ['traditional-nadi-consultation', 'back-spine-mobility-therapy']],
            ['name' => 'Bone-Related Concern', 'slug' => 'bone-related-concern', 'description' => 'A concern that should be assessed before any exercise recommendation.', 'treatments' => ['traditional-nadi-consultation']],
            ['name' => 'General Fatigue', 'slug' => 'general-fatigue', 'description' => 'Persistent non-emergency tiredness affecting daily routines.', 'treatments' => ['traditional-nadi-consultation', 'restorative-full-body-yoga']],
            ['name' => 'Stress or Tension', 'slug' => 'stress-tension', 'description' => 'Everyday stress, tension or difficulty settling.', 'treatments' => ['stress-relief-yoga-therapy', 'meditation-yoga-consultation']],
            ['name' => 'Back Stiffness', 'slug' => 'back-stiffness', 'description' => 'Non-emergency back stiffness requiring careful movement selection.', 'treatments' => ['back-spine-mobility-therapy', 'traditional-nadi-consultation']],
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

        foreach ([
            ['name' => 'Dr. Nirmala Jayasinghe', 'slug' => 'nirmala-jayasinghe', 'gender' => 'female', 'specialization' => 'Traditional Nadi wellness consultation', 'bio' => 'Provides traditional pulse-based wellness consultations and appropriate referral guidance.', 'qualifications' => 'Traditional wellness practitioner; Yoga therapy foundations', 'experience_years' => 12, 'treatments' => ['traditional-nadi-consultation']],
            ['name' => 'Dr. Harsha Wijeratne', 'slug' => 'harsha-wijeratne', 'gender' => 'male', 'specialization' => 'Mobility and restorative yoga therapy', 'bio' => 'Supports carefully structured mobility and restorative programmes after an initial consultation.', 'qualifications' => 'Certified yoga therapist; Movement assessment training', 'experience_years' => 10, 'treatments' => ['restorative-full-body-yoga', 'back-spine-mobility-therapy']],
            ['name' => 'Ms. Amaya Senanayake', 'slug' => 'amaya-senanayake', 'gender' => 'female', 'specialization' => 'Meditation, breathwork and stress relief', 'bio' => 'Guides approachable breathing, meditation and restorative movement practices.', 'qualifications' => 'RYT 500; Meditation facilitator; Breathwork practitioner', 'experience_years' => 9, 'treatments' => ['stress-relief-yoga-therapy', 'meditation-yoga-consultation']],
        ] as $data) {
            $specialist = TherapySpecialist::updateOrCreate(
                ['slug' => $data['slug']],
                collect($data)->except('treatments')->all() + ['is_active' => true],
            );
            $specialist->treatments()->sync(
                collect($data['treatments'])->map(fn (string $slug) => $treatments[$slug]->id),
            );
        }
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
