<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->longText('notes')->nullable();
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
            $table->integer('paid');
            $table->integer('change');
            $table->decimal('total', 10, 2);
            $table->enum('status', ['pending', 'paid', 'failed', 'expired']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
