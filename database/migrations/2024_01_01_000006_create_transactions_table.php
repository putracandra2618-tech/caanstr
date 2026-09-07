<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('midtrans_order_id');
            $table->string('payment_type')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('gross_amount', 12, 2);
            $table->string('va_number')->nullable();
            $table->string('fraud_status')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('midtrans_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
