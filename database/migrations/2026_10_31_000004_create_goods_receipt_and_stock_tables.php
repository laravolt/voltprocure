<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('number', 20)->unique();
            $table->foreignUlid('purchase_order_id')->constrained();
            $table->foreignUlid('received_by')->constrained('users');
            $table->timestamp('received_at');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('goods_receipt_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('purchase_order_line_id')->constrained();
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('item_id')->constrained();
            $table->integer('quantity');
            $table->string('type', 16);
            $table->ulidMorphs('source');
            $table->foreignUlid('created_by')->constrained('users');
            $table->timestamp('moved_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('goods_receipt_lines');
        Schema::dropIfExists('goods_receipts');
    }
};
