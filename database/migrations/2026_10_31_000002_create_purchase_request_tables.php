<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('number', 20)->unique();
            $table->foreignUlid('department_id')->constrained();
            $table->foreignUlid('requester_id')->constrained('users');
            $table->string('purpose');
            $table->date('needed_by')->nullable();
            $table->string('status', 20)->index();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignUlid('decided_by')->nullable()->constrained('users');
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_request_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('item_id')->constrained();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('approved_quantity')->nullable();
            $table->unsignedBigInteger('estimated_unit_cost')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_lines');
        Schema::dropIfExists('purchase_requests');
    }
};
