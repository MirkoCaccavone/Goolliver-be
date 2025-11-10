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
            // Contatori per performance (evita COUNT query pesanti)
            $table->unsignedInteger('likes_count')->default(0)->after('views_count');
            $table->unsignedInteger('ratings_count')->default(0)->after('likes_count');

            // Statistiche rating
            $table->decimal('average_rating', 3, 2)->default(0)->after('ratings_count')->comment('Media rating 0.00-5.00');
            $table->unsignedInteger('total_rating_points')->default(0)->after('average_rating')->comment('Somma di tutti i rating');

            // Punteggio complessivo (algoritmo personalizzato)
            $table->decimal('vote_score', 8, 2)->default(0)->after('total_rating_points')->comment('Punteggio finale per ranking');

            // Indice per performance nelle query di ranking
            $table->index(['vote_score', 'created_at'], 'idx_ranking');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->dropIndex('idx_ranking');
            $table->dropColumn([
                'likes_count',
                'ratings_count',
                'average_rating',
                'total_rating_points',
                'vote_score'
            ]);
        });
    }
};
