<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('membership_tiers')) {
            Schema::create('membership_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->decimal('price', 10, 2);
            $table->string('billing_period')->default('month');
            $table->json('features');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            });
        }

        if (! Schema::hasTable('member_profiles')) {
            Schema::create('member_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('membership_tier_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['pending', 'active', 'suspended'])->default('active');
            $table->timestamps();
            });
        }

        if (! Schema::hasTable('trainer_profiles')) {
            Schema::create('trainer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('specialty');
            $table->text('bio');
            $table->text('certifications')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('availability')->nullable();
            $table->enum('status', ['pending_review', 'approved', 'rejected'])->default('pending_review');
            $table->timestamps();
            });
        }

        if (! Schema::hasTable('service_categories')) {
            Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('display_order')->default(0);
            $table->timestamps();
            });
        }

        if (! Schema::hasTable('services')) {
            Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('summary');
            $table->text('description');
            $table->json('benefits')->nullable();
            $table->json('tags')->nullable();
            $table->string('level')->nullable();
            $table->string('equipment')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            });
        }

        if (! Schema::hasTable('member_service')) {
            Schema::create('member_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at')->useCurrent();
            $table->unique(['user_id', 'service_id'], 'member_service_unique');
            });
        }

        if (! Schema::hasTable('therapy_categories')) {
            Schema::create('therapy_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            });
        }

        if (! Schema::hasColumn('therapy_requests', 'therapy_category_id')) {
            Schema::table('therapy_requests', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->change();
                $table->string('subject')->nullable()->change();
                $table->text('symptoms')->nullable()->change();
                $table->foreignId('therapy_category_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
                $table->string('name')->nullable()->after('therapy_category_id');
                $table->string('contact_email')->nullable()->after('name');
                $table->string('contact_phone', 30)->nullable()->after('contact_email');
                $table->string('category')->nullable()->after('contact_phone');
                $table->dateTime('preferred_datetime')->nullable()->after('category');
                $table->text('notes')->nullable()->after('preferred_datetime');
            });
        }

        if (! Schema::hasTable('trainer_bookings')) {
            Schema::create('trainer_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('requested_datetime');
            $table->enum('status', ['pending', 'accepted', 'declined', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            });
        }

        if (! Schema::hasTable('product_categories')) {
            Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
            });
        }

        if (! Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('stock')->default(0);
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            });
        }

        if (! Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('guest_email');
            $table->string('phone', 30)->nullable();
            $table->text('delivery_address');
            $table->enum('status', ['pending', 'confirmed', 'processing', 'completed', 'cancelled'])->default('pending');
            $table->decimal('total', 10, 2);
            $table->timestamps();
            });
        }

        if (! Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('trainer_bookings');

        Schema::table('therapy_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('therapy_category_id');
            $table->dropColumn(['name', 'contact_email', 'contact_phone', 'category', 'preferred_datetime', 'notes']);
        });

        Schema::dropIfExists('therapy_categories');
        Schema::dropIfExists('member_service');
        Schema::dropIfExists('services');
        Schema::dropIfExists('service_categories');
        Schema::dropIfExists('trainer_profiles');
        Schema::dropIfExists('member_profiles');
        Schema::dropIfExists('membership_tiers');
    }
};
