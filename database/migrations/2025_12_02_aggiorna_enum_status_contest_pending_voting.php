<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contests', function (Blueprint $table) {
            // Aggiorna l'enum status aggiungendo 'pending_voting'
            $table->enum('status', ['active', 'upcoming', 'pending_voting', 'voting', 'ended'])->default('upcoming')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contests', function (Blueprint $table) {
            // Rimuovi 'pending_voting' dall'enum
            $table->enum('status', ['active', 'upcoming', 'voting', 'ended'])->default('upcoming')->change();
        });
    }
};
