<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('transaction_type', 20);
            $table->string('system_code', 80)->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['transaction_type', 'is_active']);
        });

        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('transaction_type', 20);
            $table->decimal('amount', 14, 2);
            $table->date('transaction_date');
            $table->string('description', 255);
            $table->string('programme_name', 160)->nullable();
            $table->string('supplier_payee', 160)->nullable();
            $table->string('reference_number', 120)->nullable();
            $table->text('notes')->nullable();
            $table->string('source_type', 160)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->boolean('is_automatic')->default(false);
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();

            $table->index(['transaction_type', 'transaction_date'], 'finance_type_date_index');
            $table->index(['finance_category_id', 'transaction_date'], 'finance_category_date_index');
            $table->unique(['source_type', 'source_id'], 'financial_transactions_source_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('finance_categories');
    }
};
