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
        Schema::table('entries', function (Blueprint $table) {
            $table->enum('payment_status', ['pending', 'completed', 'failed', 'refunded'])
                  ->default('pending')
                  ->after('moderation_status');
            
            $table->timestamp('paid_at')->nullable()->after('payment_status');
            
            $table->decimal('payment_amount', 8, 2)->nullable()->after('paid_at');
            
            $table->string('payment_method', 50)->nullable()->after('payment_amount');
            
            $table->string('transaction_id', 100)->nullable()->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'paid_at', 
                'payment_amount',
                'payment_method',
                'transaction_id'
            ]);
        });
    }
};
