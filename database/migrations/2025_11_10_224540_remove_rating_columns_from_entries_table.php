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
            // Rimuovi colonne relative ai rating
            $table->dropColumn([
                'ratings_count',
                'average_rating',
                'total_rating_points'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            // Ripristina colonne se necessario (rollback)
            $table->integer('ratings_count')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('total_rating_points')->default(0);
        });
    }
};
