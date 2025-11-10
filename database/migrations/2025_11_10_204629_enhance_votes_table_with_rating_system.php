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
        Schema::table('votes', function (Blueprint $table) {
            // Tipo di voto: like, rating
            $table->enum('vote_type', ['like', 'rating'])->default('like')->after('entry_id');

            // Rating da 1 a 5 stelle (nullable per i like semplici)
            $table->tinyInteger('rating')->nullable()->after('vote_type')->comment('Rating 1-5 stelle per vote_type=rating');

            // IP address per tracking (anti-fraud)
            $table->string('ip_address', 45)->nullable()->after('rating');

            // User agent per analytics
            $table->text('user_agent')->nullable()->after('ip_address');

            // Indice composto per prevenire voti duplicati
            $table->unique(['user_id', 'entry_id', 'vote_type'], 'unique_user_entry_vote_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->dropUnique('unique_user_entry_vote_type');
            $table->dropColumn(['vote_type', 'rating', 'ip_address', 'user_agent']);
        });
    }
};
