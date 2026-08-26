<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->date('joined_at')->nullable()->after('membership_tier_id');
            $table->string('before_photo_path')->nullable()->after('joined_at');
            $table->string('after_photo_path')->nullable()->after('before_photo_path');
        });

        DB::table('member_profiles')
            ->select(['id', 'created_at'])
            ->whereNull('joined_at')
            ->whereNotNull('created_at')
            ->orderBy('id')
            ->chunkById(100, function ($profiles): void {
                foreach ($profiles as $profile) {
                    DB::table('member_profiles')
                        ->where('id', $profile->id)
                        ->update(['joined_at' => substr((string) $profile->created_at, 0, 10)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->dropColumn(['joined_at', 'before_photo_path', 'after_photo_path']);
        });
    }
};
