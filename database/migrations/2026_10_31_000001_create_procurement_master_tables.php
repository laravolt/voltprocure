<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 16)->unique();
            $table->string('name');
            $table->foreignUlid('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignUlid('department_id')->nullable()->constrained('departments')->nullOnDelete();
        });

        Schema::create('items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('sku', 32)->unique();
            $table->string('name');
            $table->string('category');
            $table->string('unit', 16);
            $table->unsignedInteger('reorder_point')->default(0);
            $table->unsignedBigInteger('standard_cost')->default(0);
            $table->integer('stock_on_hand')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 16)->unique();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('city')->nullable();
            $table->string('payment_terms')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('items');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('department_id');
        });
        Schema::dropIfExists('departments');
    }
};
