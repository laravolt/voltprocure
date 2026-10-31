<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('number', 20)->unique();
            $table->foreignUlid('purchase_request_id')->nullable()->constrained();
            $table->foreignUlid('supplier_id')->constrained();
            $table->foreignUlid('buyer_id')->constrained('users');
            $table->string('status', 24)->index();
            $table->date('expected_at')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->unsignedBigInteger('total')->default(0);
            $table->timestamps();
        });

        Schema::create('purchase_order_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('item_id')->constrained();
            $table->unsignedInteger('quantity_ordered');
            $table->unsignedInteger('quantity_received')->default(0);
            $table->unsignedBigInteger('unit_cost');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
    }
};
