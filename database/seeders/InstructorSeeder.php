<?php

namespace Database\Seeders;

use App\Models\TrainerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class InstructorSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);

        $instructors = $this->instructors();

        TrainerProfile::query()
            ->whereNotIn('slug', array_column($instructors, 'slug'))
            ->where('status', 'approved')
            ->update(['status' => 'rejected']);

        foreach ($instructors as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make(Str::random(48)),
                    'email_verified_at' => now(),
                ],
            );

            $user->update([
                'name' => $data['name'],
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);
            $user->syncRoles(['trainer']);

            TrainerProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'slug' => $data['slug'],
                    'specialty' => $data['specialty'],
                    'gender' => null,
                    'bio' => $data['bio'],
                    'certifications' => implode("\n", $data['qualifications']),
                    'experience_years' => $data['experience_years'],
                    'photo_path' => $data['photo_path'],
                    'availability' => null,
                    'status' => 'approved',
                ],
            );
        }
    }

    private function instructors(): array
    {
        return [
            [
                'name' => 'N.T.D. Mendis',
                'email' => 'ntd.mendis@gymravana.local',
                'slug' => 'ntd-mendis',
                'specialty' => 'Strength, conditioning and yoga',
                'bio' => 'A fitness trainer and yoga teacher with 12 years of experience, including four years teaching yoga in Moscow, Russia.',
                'qualifications' => [
                    'National Diploma in Sports Strength and Conditioning (NDP/SSC/B/2018-38)',
                    'Reiki – First Degree',
                    'Bodybuilding and Physique Judge – World Fitness Federation (WFFSL/J/C/044)',
                    '12 years of experience as a fitness trainer and yoga teacher',
                    '4 years of experience as a yoga teacher in Moscow, Russia',
                ],
                'experience_years' => 12,
                'photo_path' => 'images/landing/trainers/ntd-mendis.jpeg',
            ],
            [
                'name' => 'Pathum Weerakkodi',
                'email' => 'pathum.weerakkodi@gymravana.local',
                'slug' => 'pathum-weerakkodi',
                'specialty' => 'Fitness, yoga and sports massage',
                'bio' => 'A certified fitness trainer, sports massage therapist and yoga teacher with eight years of fitness and yoga instruction experience, including four years training in Moscow, Russia.',
                'qualifications' => [
                    'Certified Fitness Trainer',
                    'Certified Sports Massage Therapist',
                    'Yoga Teacher',
                    'Model',
                    '8 years of experience as a fitness and yoga instructor',
                    '4 years of experience as a trainer in Moscow, Russia',
                ],
                'experience_years' => 8,
                'photo_path' => 'images/landing/trainers/pathum-weerakkodi.jpeg',
            ],
            [
                'name' => 'Sahan Weerakkodi',
                'email' => 'sahan.weerakkodi@gymravana.local',
                'slug' => 'sahan-weerakkodi',
                'specialty' => 'Fitness training',
                'bio' => 'An undergraduate fitness trainer with three years of practical experience as a training instructor.',
                'qualifications' => [
                    'Undergraduate Fitness Trainer',
                    '3 years of experience as a training instructor',
                ],
                'experience_years' => 3,
                'photo_path' => 'images/landing/trainers/sahan-weerakkodi.png',
            ],
            [
                'name' => 'H.M.T.D. Herath',
                'email' => 'hmtd.herath@gymravana.local',
                'slug' => 'hmtd-herath',
                'specialty' => 'Fitness training',
                'bio' => 'An undergraduate fitness trainer with three years of practical experience as a training instructor.',
                'qualifications' => [
                    'Undergraduate Fitness Trainer',
                    '3 years of experience as a training instructor',
                ],
                'experience_years' => 3,
                'photo_path' => 'images/landing/trainers/hmtd-herath.jpeg',
            ],
        ];
    }
}
